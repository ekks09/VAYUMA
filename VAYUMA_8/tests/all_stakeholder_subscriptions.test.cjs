// Synthetic UI fixtures. Prices below are test expectations, not application configuration.
const {test} = require('node:test');
const assert = require('node:assert/strict');
const {spawnSync} = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const php = process.env.VAYUMA_PHP || 'php';
const root = path.resolve(__dirname, '../vayuma');
const cases = [
 ['farmer','Farmer',299], ['consumer','Consumer',199], ['buyer','Buyer',499], ['transport_provider','Transport Provider',399],
 ['input_supplier','Input Supplier',999], ['manufacturer','Manufacturer',999], ['warehouse_provider','Warehouse Provider',799],
 ['retailer','Retailer',499], ['agribusiness','Agribusiness / Trader',699], ['cooperative','Cooperative',999],
 ['researcher','Researcher / Research Institution',999], ['extension_officer','Agricultural Extension Officer',null],
 ['veterinary_officer','Veterinary Officer',499], ['financial_institution','Financial Institution',2999],
 ['insurance_provider','Insurance Provider',1999], ['government_officer','Government Officer',null],
 ['ngo_partner','NGO / Development Partner',1499], ['agricultural_service_provider','Agricultural Service Provider',799],
 ['strategic_partner','Strategic Partner',null]
];
function escape(s) { return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }
test('all 19 roles show the applicable monthly plan and locked payment, free or negotiated state', {timeout:90000}, () => {
 const dir = fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-plans-test-'));
 try {
  fs.cpSync(root,dir,{recursive:true});
  fs.writeFileSync(path.join(dir,'config/db.php'), '<?php $conn = new stdClass();');
  fs.writeFileSync(path.join(dir,'lib/subscription_lib.php'), `<?php
function ensure_user_subscription($conn,$id,$role) { $plan=json_decode(getenv('TEST_PLAN'),true); if ($plan['role_key'] !== $role) throw new RuntimeException('Wrong role'); return $plan; }
function subscription_is_accessible($s) { return in_array($s['status'], ['active','free_account'], true) || ($s['status']==='negotiated_plan' && !empty($s['negotiated_approved'])); }
`);
  const render = (role,name,amount) => {
   const type=amount!==null?'paid':role==='strategic_partner'?'negotiated':'free';
   const status=type==='paid'?'payment_due':type==='free'?'free_account':'negotiated_plan';
   const plan={role_key:role,stakeholder_name:name,monthly_price:amount,plan_type:type,status,plan_billing_interval:'monthly',negotiated_approved:0};
   const code=`session_start(); $_SESSION=['user_id'=>1,'logged_in'=>true,'role'=>${JSON.stringify(role)},'phone'=>'0712345678']; $_SERVER['REQUEST_METHOD']='GET'; require ${JSON.stringify(path.join(dir,'subscription.php'))};`;
   const r=spawnSync(php,['-d','display_errors=0','-r',code],{encoding:'utf8',env:{...process.env,TEST_PLAN:JSON.stringify(plan)}});
   assert.equal(r.status,0,`${role}: ${r.stderr}`);
   assert.ok(r.stdout.includes(escape(name)),`${role}: role label`);
   if(type==='paid') {
    assert.ok(r.stdout.includes(`KSh ${amount.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})}`),`${role}: price`);
    assert.match(r.stdout,/\/ monthly/); assert.match(r.stdout,/M-Pesa payment is temporarily unavailable/);
    assert.doesNotMatch(r.stdout,/>Continue to Dashboard<\/a>/);
   } else if(type==='free') {
    assert.match(r.stdout,/does not require payment/); assert.match(r.stdout,/>Continue to Dashboard<\/a>/);
   } else {
    assert.match(r.stdout,/awaiting manual VAYUMA approval/); assert.doesNotMatch(r.stdout,/>Continue to Dashboard<\/a>/);
   }
  };
  for(const [role,name,amount] of cases) render(role,name,amount);
  // A changed database value must be displayed rather than a hard-coded price.
  render('farmer','Farmer',345.5);
 } finally {fs.rmSync(dir,{recursive:true,force:true});}
});
test('real entitlement predicate handles active, due, expired, free, grace and negotiated states', () => {
 const lib=path.join(root,'lib/subscription_lib.php');
 const code=`require ${JSON.stringify(lib)}; $cases=[null,['status'=>'active'],['status'=>'payment_due'],['status'=>'expired'],['status'=>'free_account'],['status'=>'negotiated_plan','negotiated_approved'=>0],['status'=>'negotiated_plan','negotiated_approved'=>1],['status'=>'payment_due','grace_period_ends_at'=>'2999-01-01'],['status'=>'payment_due','grace_period_ends_at'=>'2000-01-01']]; echo json_encode(array_map('subscription_is_accessible',$cases));`;
 const r=spawnSync(php,['-r',code],{encoding:'utf8'});assert.equal(r.status,0,r.stderr);
 assert.deepEqual(JSON.parse(r.stdout),[false,true,false,false,true,false,true,true,false]);
});
