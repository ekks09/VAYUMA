// Isolated PHP-WASM mocks: simulated driver failures, not a live mysqli/TiDB test.
const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs'),os=require('node:os'),path=require('node:path'),{spawnSync}=require('node:child_process');
const php=process.env.VAYUMA_PHP||'php',root=path.resolve(__dirname,'../vayuma');
test('dashboard queries handle mysqli exceptions and false returns without leaking database detail',()=>{
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-db-fail-'));
 try {
  fs.cpSync(root,dir,{recursive:true});
  fs.writeFileSync(path.join(dir,'subscription_guard.php'),'<?php require_once __DIR__."/config/db.php";');
  fs.writeFileSync(path.join(dir,'includes/subscription_banner.php'),'<?php function render_subscription_banner($c,$u){return "";}');
  fs.writeFileSync(path.join(dir,'config/db.php'),`<?php
function targeted($sql){return strpos(strtolower($sql),strtolower(getenv('FAIL_QUERY'))) !== false;}
class FakeResult {private $rows; function __construct($rows){$this->rows=$rows;} function fetch_assoc(){return array_shift($this->rows) ?: null;} function fetch_all($m=0){return $this->rows;}}
class FakeStmt {private $sql;function __construct($q){$this->sql=$q;}function bind_param($t,&...$v){return true;}function execute(){if(targeted($this->sql)){if(getenv('FAIL_MODE')==='execute_exception')throw new mysqli_sql_exception('PRIVATE_SQL_EXAMPLE');if(getenv('FAIL_MODE')==='execute_false')return false;}return true;}function get_result(){if(strpos($this->sql,'SELECT * FROM stakeholder_profiles')!==false)return new FakeResult([['organization_name'=>'Example','county'=>'Nairobi','service_focus'=>'','profile_completed'=>1,'verification_status'=>'pending']]);if(strpos($this->sql,'COUNT(')!==false)return new FakeResult([['c'=>0,'total'=>0,'listing_count'=>0,'order_count'=>0,'sales_total'=>0]]);return new FakeResult([]);}function close(){}}
class FakeConn {function prepare($q){if(targeted($q)){if(getenv('FAIL_MODE')==='prepare_exception')throw new mysqli_sql_exception('PRIVATE_SQL_EXAMPLE');if(getenv('FAIL_MODE')==='prepare_false')return false;}return new FakeStmt($q);}function query($q){if(targeted($q)){if(getenv('FAIL_MODE')==='execute_exception')throw new mysqli_sql_exception('PRIVATE_SQL_EXAMPLE');if(getenv('FAIL_MODE')==='execute_false')return false;}return new FakeResult([]);}}$conn=new FakeConn;`);
  for(const [file,role] of [['farmer_dashboard.php','farmer'],['buyer_dashboard.php','buyer'],['consumer_dashboard.php','consumer'],['input_supplier_dashboard.php','input_supplier'],['cooperative_dashboard.php','cooperative'],['finance_dashboard.php','financial_institution'],['stakeholder_dashboard.php','strategic_partner']]){
   const p=path.join(dir,file),s=fs.readFileSync(p,'utf8').replace('session_start();',`session_start(); $_SESSION=['user_id'=>7,'logged_in'=>true,'role'=>'${role}','full_name'=>'Test Member'];`);fs.writeFileSync(p,s);
  }
  function run(file,target,mode){const result=spawnSync(php,[path.join(dir,file)],{encoding:'utf8',env:{...process.env,FAIL_QUERY:target,FAIL_MODE:mode,SCRIPT_NAME:'/'+file,PHP_SELF:'/'+file}});assert.equal(result.status,0,result.stderr);assert.doesNotMatch(result.stdout,/PRIVATE_SQL_EXAMPLE|Fatal error|Stack trace/);return result.stdout;}
  for(const mode of ['prepare_exception','prepare_false','execute_exception','execute_false']){
   assert.match(run('farmer_dashboard.php','FROM produce_categories',mode),/Listing counts temporarily unavailable/);
   assert.match(run('buyer_dashboard.php','FROM saved_items',mode),/Saved items are temporarily unavailable/);
   assert.match(run('buyer_dashboard.php','FROM orders o',mode),/Recent orders are temporarily unavailable/);
   assert.match(run('input_supplier_dashboard.php','FROM stakeholder_offers',mode),/Unavailable/);
   assert.match(run('cooperative_dashboard.php','listing_count',mode),/Listing data is unavailable/);
   assert.match(run('stakeholder_dashboard.php','SELECT organization_name',mode),/dashboard is temporarily unavailable/);
  }
  for(const mode of ['execute_exception','execute_false']){
   assert.match(run('buyer_dashboard.php','FROM produce_listings pl',mode),/Produce listings unavailable/);
   assert.match(run('consumer_dashboard.php','FROM produce_listings pl',mode),/Produce listings unavailable/);
   assert.match(run('finance_dashboard.php','SELECT COUNT(*) AS total',mode),/Partner data is unavailable/);
  }
 } finally {fs.rmSync(dir,{recursive:true,force:true});}
});
test('subscription guard fails closed on mysqli exception instead of leaking or allowing access',()=>{
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-guard-fail-'));try{
  fs.mkdirSync(path.join(dir,'config'));fs.mkdirSync(path.join(dir,'lib'));
  fs.writeFileSync(path.join(dir,'config/db.php'),'<?php $conn=new stdClass;');
  fs.writeFileSync(path.join(dir,'lib/subscription_lib.php'),"<?php function ensure_user_subscription($c,$u,$r){throw new mysqli_sql_exception('PRIVATE_SQL_EXAMPLE');} function subscription_is_accessible($s){return true;}");
  fs.copyFileSync(path.join(root,'subscription_guard.php'),path.join(dir,'subscription_guard.php'));
  const code=`$_SESSION=['user_id'=>1,'logged_in'=>true,'role'=>'farmer'];require ${JSON.stringify(path.join(dir,'subscription_guard.php'))};echo 'GUARD_PASSED';`;
  const result=spawnSync(php,['-r',code],{encoding:'utf8',env:{...process.env,SUBSCRIPTION_ENFORCEMENT:'1'}});assert.equal(result.status,0,result.stderr);assert.match(result.stdout,/temporarily unavailable/);assert.doesNotMatch(result.stdout,/PRIVATE_SQL_EXAMPLE|GUARD_PASSED|Fatal error/);
 }finally{fs.rmSync(dir,{recursive:true,force:true});}
});
