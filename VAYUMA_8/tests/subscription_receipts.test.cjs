const {test} = require('node:test');
const assert = require('node:assert/strict');
const {spawnSync} = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const php = process.env.VAYUMA_PHP || 'php';
const root = path.resolve(__dirname, '../vayuma');
test('confirmed subscription receipts appear only for their owner and show reference and expiry (mock database)', () => {
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-receipt-test-'));
 try {
  fs.cpSync(root,dir,{recursive:true});
  fs.writeFileSync(path.join(dir,'config/db.php'), `<?php
class FakeResult {private $rows; function __construct($rows){$this->rows=$rows;} function fetch_assoc(){return array_shift($this->rows) ?: null;} }
class FakeStmt {private $sql;private $args=[];function __construct($sql){$this->sql=$sql;}function bind_param($t,&...$a){$this->args=&$a;}function execute(){return true;}function close(){} function get_result(){
 if (strpos($this->sql,'FROM subscription_payments WHERE user_id=?')!==false && strpos($this->sql,"status='paid'")!==false) return new FakeResult($this->args[0]===7 ? [['id'=>42,'amount'=>299,'paid_at'=>'2026-01-01 12:00:00']] : []);
 if (strpos($this->sql,'FROM subscription_payments p')!==false && strpos($this->sql,'p.user_id = ?')!==false && strpos($this->sql,"p.status = 'paid'")!==false) return new FakeResult($this->args[0]===42 && $this->args[1]===7 ? [['payment_method'=>'mpesa','status'=>'paid','amount'=>299,'transaction_reference'=>'sample-ref','mpesa_receipt_number'=>'sample-receipt','paystack_transaction_id'=>null,'paid_at'=>'2026-01-01 12:00:00','subscription_expires_at'=>'2026-02-01 12:00:00','billing_interval'=>'monthly','stakeholder_name'=>'Farmer','monthly_price'=>299]] : []);
 return new FakeResult([]);
} }
class FakeDb {function prepare($sql){return new FakeStmt($sql);} }
$conn=new FakeDb();
`);
  fs.writeFileSync(path.join(dir,'lib/subscription_lib.php'), `<?php
function ensure_user_subscription($conn,$id,$role){return ['plan_type'=>'paid','status'=>'active','stakeholder_name'=>'Farmer','monthly_price'=>299,'plan_billing_interval'=>'monthly','expires_at'=>'2026-02-01'];} function subscription_is_accessible($s){return true;}
`);
  function render(file,user){const code=`session_start(); $_SESSION=['user_id'=>${user},'logged_in'=>true,'role'=>'farmer','phone'=>'0712345678']; $_SERVER['REQUEST_METHOD']='GET'; $_SERVER['PHP_SELF']=${JSON.stringify(file)}; $_GET=['payment_id'=>'42']; require ${JSON.stringify(path.join(dir,file))};`;const r=spawnSync(php,['-d','display_errors=0','-r',code],{encoding:'utf8'});assert.equal(r.status,0,r.stderr);return r.stdout;}
  const owner=render('subscription.php',7);assert.match(owner,/subscription_receipt\.php\?payment_id=42/);assert.match(owner,/KSh 299\.00/);
  const other=render('subscription.php',8);assert.doesNotMatch(other,/subscription_receipt\.php\?payment_id=42/);assert.match(other,/No confirmed subscription payments yet/);
  const receipt=render('subscription_receipt.php',7);assert.match(receipt,/sample-receipt/);assert.match(receipt,/01 Feb 2026/);assert.match(receipt,/Print Receipt/);
  const denied=render('subscription_receipt.php',8);assert.match(denied,/Receipt unavailable/);assert.doesNotMatch(denied,/sample-receipt/);
 } finally {fs.rmSync(dir,{recursive:true,force:true});}
});
