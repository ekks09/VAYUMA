const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {spawnSync} = require('node:child_process');
const php = process.env.VAYUMA_PHP || 'php';
const root = path.resolve(__dirname,'../vayuma');
test('admin pricing updates are authorized, CSRF protected and use the submitted price (mock database)', () => {
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-admin-test-'));
 try {
  fs.cpSync(root,dir,{recursive:true});
  fs.writeFileSync(path.join(dir,'config/db.php'), `<?php
class ResultFake {public $num_rows=1; private $rows; function __construct($rows=[]){$this->rows=$rows;} function fetch_all($mode){return [];} function fetch_assoc(){return array_shift($this->rows) ?: null;} }
class StmtFake {private $sql;private $params=[];function __construct($sql){$this->sql=$sql;}function bind_param($types,&...$args){$this->params=&$args;}function execute(){if(strpos($this->sql,'UPDATE subscription_plans')===0)$GLOBALS['updated']=array_values($this->params);return true;}function get_result(){return new ResultFake(strpos($this->sql,'SELECT id, plan_type FROM subscription_plans')!==false ? [['id'=>1,'plan_type'=>'paid']] : []);}function close(){} }
class DbFake {public $error='';function begin_transaction(){return true;}function commit(){return true;}function rollback(){return true;}function prepare($sql){return new StmtFake($sql);}function query($sql){return new ResultFake();} }
$conn=new DbFake();
`);
  for(const [user,token,allowed] of [[7,'known',true],[8,'known',false],[7,'wrong',false]]){
   const code=`session_start(); $_SESSION=['user_id'=>${user},'logged_in'=>true,'role'=>'farmer','vayuma_csrf_token'=>'known']; $_SERVER['REQUEST_METHOD']='POST'; $_SERVER['PHP_SELF']='admin_subscriptions.php'; $_POST=['csrf_token'=>${JSON.stringify(token)},'id'=>'1','plan_type'=>'paid','is_active'=>'1','monthly_price'=>'345.50','stakeholder_name'=>'Farmer','billing_interval'=>'monthly']; register_shutdown_function(function(){fwrite(STDERR,"\\nRESULT=".json_encode(['update'=>$GLOBALS['updated']??null,'status'=>http_response_code()]));}); require ${JSON.stringify(path.join(dir,'admin_subscriptions.php'))};`;
   const r=spawnSync(php,['-d','display_errors=0','-r',code],{encoding:'utf8',env:{...process.env,VAYUMA_ADMIN_USER_IDS:'7'}});
   assert.equal(r.status,0,r.stderr); const i=r.stderr.lastIndexOf('RESULT=');assert.ok(i>=0,r.stderr);const result=JSON.parse(r.stderr.slice(i+7));
   if(allowed){assert.ok(result.update);assert.ok(result.update.includes('345.50'));assert.ok(result.update.includes('monthly'));}
   else {assert.equal(result.update,null); if(user===8) {assert.equal(result.status,403);assert.match(r.stdout,/Access denied/);} else assert.match(r.stdout,/Your session expired/);}
  }
 } finally {fs.rmSync(dir,{recursive:true,force:true});}
});
