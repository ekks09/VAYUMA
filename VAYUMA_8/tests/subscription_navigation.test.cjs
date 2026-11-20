const {test} = require('node:test');
const assert = require('node:assert/strict');
const {spawnSync} = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const php = process.env.VAYUMA_PHP || 'php';
const root = path.resolve(__dirname,'../vayuma');
test('active paid plans show the correct role dashboard; unpaid plans remain locked', () => {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-subscription-ui-'));
  try {
    fs.cpSync(root, dir, {recursive:true});
    fs.writeFileSync(path.join(dir,'config/db.php'), '<?php $conn = new stdClass();');
    fs.writeFileSync(path.join(dir,'lib/subscription_lib.php'), `<?php
function ensure_user_subscription($conn,$id,$role) { return ['plan_type'=>'paid','status'=>getenv('TEST_STATUS'),'stakeholder_name'=>'Sample','monthly_price'=>299,'expires_at'=>null]; }
function subscription_is_accessible($sub) { return $sub['status']==='active'; }
`);
    const scenarios = [['farmer','farmer_dashboard.php','active'],['buyer','buyer_dashboard.php','active'],['government_officer','government_officer_dashboard.php','active'],['farmer','farmer_dashboard.php','payment_due']];
    for (const [role,dashboard,status] of scenarios) {
      const code = `session_start(); $_SESSION=['user_id'=>1,'logged_in'=>true,'role'=>${JSON.stringify(role)},'phone'=>'0712345678']; $_SERVER['REQUEST_METHOD']='GET'; require ${JSON.stringify(path.join(dir,'subscription.php'))};`;
      const r = spawnSync(php,['-r',code],{encoding:'utf8',env:{...process.env,TEST_STATUS:status}});
      assert.equal(r.status,0,r.stderr);
      if (status === 'active') {
        assert.match(r.stdout, /Subscription active/);
        assert.match(r.stdout, new RegExp(`href="${dashboard}"[^>]*>Continue to Dashboard`));
        assert.doesNotMatch(r.stdout, /M-Pesa payment is temporarily unavailable/);
      } else {
        assert.match(r.stdout, /M-Pesa payment is temporarily unavailable/);
        assert.doesNotMatch(r.stdout, /Subscription active/);
        assert.doesNotMatch(r.stdout, new RegExp(`href="${dashboard}"[^>]*>Continue to Dashboard`));
      }
    }
  } finally { fs.rmSync(dir,{recursive:true,force:true}); }
});
test('payment success returns through the guarded dashboard router', () => {
  const source = fs.readFileSync(path.join(root,'subscription.php'),'utf8');
  assert.match(source, /window\.location\.href = 'dashboard\.php'/);
});
