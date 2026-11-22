// Tests actual subscription library/guard control flow with fake database driver methods.
const {test}=require('node:test');const assert=require('node:assert/strict');const fs=require('node:fs'),os=require('node:os'),path=require('node:path'),{spawnSync}=require('node:child_process');
const php=process.env.VAYUMA_PHP||'php',root=path.resolve(__dirname,'../vayuma');
test('subscription guard handles driver false returns and mysqli exceptions without granting access',()=>{
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'vy-subfail-'));try{
 fs.mkdirSync(path.join(dir,'config'));fs.mkdirSync(path.join(dir,'lib'));
 fs.copyFileSync(path.join(root,'subscription_guard.php'),path.join(dir,'subscription_guard.php'));
 // The fake driver is not an actual mysqli connection; strip the type only in this isolated test copy.
 fs.writeFileSync(path.join(dir,'lib/subscription_lib.php'),fs.readFileSync(path.join(root,'lib/subscription_lib.php'),'utf8').replace(/mysqli \$conn/g,'$conn'));
 fs.writeFileSync(path.join(dir,'config/db.php'),`<?php
class R {private $rows;function __construct($r){$this->rows=$r;}function fetch_assoc(){return array_shift($this->rows)?:null;}}
class S {private $q;function __construct($q){$this->q=$q;}function bind_param($t,&...$args){return true;}function execute(){if(getenv('MODE')==='execute_false')return false;if(getenv('MODE')==='execute_exception')throw new mysqli_sql_exception('PRIVATE_DRIVER_DETAIL');return true;}function get_result(){if(getenv('MODE')==='result_false')return false;return new R([['id'=>3,'plan_type'=>'paid','role_key'=>'farmer']]);}function close(){}}
class C {function begin_transaction(){return getenv('MODE')!=='begin_false';}function rollback(){return true;}function commit(){return true;}function prepare($q){if(getenv('MODE')==='prepare_false')return false;if(getenv('MODE')==='prepare_exception')throw new mysqli_sql_exception('PRIVATE_DRIVER_DETAIL');return new S($q);}}$conn=new C;`);
 for(const mode of ['begin_false','prepare_false','prepare_exception','execute_false','execute_exception','result_false']){
  const code=`$_SESSION=['user_id'=>7,'logged_in'=>true,'role'=>'farmer'];require ${JSON.stringify(path.join(dir,'subscription_guard.php'))};echo 'GUARD_ALLOWED';`;
  const r=spawnSync(php,['-r',code],{encoding:'utf8',env:{...process.env,SUBSCRIPTION_ENFORCEMENT:'1',MODE:mode}});assert.equal(r.status,0,r.stderr);assert.match(r.stdout,/temporarily unavailable/,mode);assert.doesNotMatch(r.stdout,/PRIVATE_DRIVER_DETAIL|GUARD_ALLOWED|Fatal error/,mode);
 }
 }finally{fs.rmSync(dir,{recursive:true,force:true});}
});
test('subscription banner renders unavailable on mysqli exception, without exposing it',()=>{
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'vy-banner-fail-'));try{
 fs.mkdirSync(path.join(dir,'includes'));fs.mkdirSync(path.join(dir,'lib'));
 fs.copyFileSync(path.join(root,'includes/subscription_banner.php'),path.join(dir,'includes/subscription_banner.php'));
 fs.writeFileSync(path.join(dir,'lib/subscription_lib.php'),"<?php function subscription_for_user($c,$id){throw new mysqli_sql_exception('PRIVATE_DRIVER_DETAIL');}");
 const code=`class FakeDb extends mysqli {function __construct(){}} require ${JSON.stringify(path.join(dir,'includes/subscription_banner.php'))}; echo render_subscription_banner(new FakeDb,7);`;
 const r=spawnSync(php,['-r',code],{encoding:'utf8'});assert.equal(r.status,0,r.stderr);assert.match(r.stdout,/Subscription status is temporarily unavailable/);assert.doesNotMatch(r.stdout,/PRIVATE_DRIVER_DETAIL|Fatal error/);
 }finally{fs.rmSync(dir,{recursive:true,force:true});}
});
