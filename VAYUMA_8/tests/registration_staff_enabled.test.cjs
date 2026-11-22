const {test}=require('node:test'), assert=require('node:assert/strict');
const fs=require('node:fs'),os=require('node:os'),path=require('node:path'),{spawnSync}=require('node:child_process');
const php=process.env.VAYUMA_PHP||'php',root=path.resolve(__dirname,'../vayuma');
test('signup with staff key configured preserves phone locking and fails closed (mock DB)',()=>{
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-reg-staff-'));
 try{
  for(const sub of ['auth','config','includes'])fs.mkdirSync(path.join(dir,sub));
  for(const file of ['auth/register_process.php','includes/login_identity.php','includes/onboarding_policy.php','includes/onboarding_lock.php','includes/identity_errors.php'])fs.copyFileSync(path.join(root,file),path.join(dir,file));
  fs.writeFileSync(path.join(dir,'includes/csrf.php'),'<?php function vayuma_csrf_valid(): bool{return true;}');
  fs.writeFileSync(path.join(dir,'config/db.php'),`<?php
class R{public $num_rows;private $row;function __construct($row=null){$this->row=$row;$this->num_rows=$row?1:0;}function fetch_assoc(){$row=$this->row;$this->row=null;return $row;}}
class S{public $insert_id=13;private $q,$v;function __construct($q){$this->q=$q;}function bind_param($t,&...$v){$this->v=&$v;}function execute(){if(strpos($this->q,'INSERT INTO onboarding_phone_locks')!==false && getenv('MODE')==='lock_error')throw new RuntimeException('sensitive db error');if(strpos($this->q,'INSERT INTO users')!==false)$GLOBALS['hash']=end($this->v);return true;}function get_result(){if(strpos($this->q,'SELECT phone_key')===0)return new R(['phone_key'=>'ok']);if(strpos($this->q,'SELECT id FROM onboarding_records')===0)return new R(getenv('MODE')==='reserved'?['id'=>8]:null);if(strpos($this->q,'SELECT id FROM users WHERE phone_identity')===0)return new R(getenv('MODE')==='duplicate'?['id'=>3]:null);if(strpos($this->q,'SELECT id, password FROM users')===0)return new R(['id'=>13,'password'=>$GLOBALS['hash']]);return new R();}function close(){}}
class C{function prepare($q){return new S($q);}function begin_transaction(){return true;}function commit(){$GLOBALS['commit']=true;return true;}function rollback(){$GLOBALS['rollback']=true;return true;}}$conn=new C;`);
  const file=path.join(dir,'auth/register_process.php');fs.writeFileSync(file,fs.readFileSync(file,'utf8').replace(/\berror_log\(/g,'test_error_log('));
  const code=`function test_error_log($s){$GLOBALS['logs'][]=$s;} $_SERVER['REQUEST_METHOD']='POST';$_POST=['full_name'=>'Example Farmer','phone'=>'0712345678','email'=>'farmer@example.test','password'=>'private-test-password','confirm_password'=>'private-test-password','terms'=>'accepted'];register_shutdown_function(function(){echo json_encode(['success'=>$_SESSION['registration_success']??false,'error'=>$_SESSION['register_error']??'','commit'=>$GLOBALS['commit']??false,'rollback'=>$GLOBALS['rollback']??false,'logs'=>$GLOBALS['logs']??[]]);});require ${JSON.stringify(file)};`;
  for(const [mode,success,rollback] of [['ok',true,false],['lock_error',false,true],['reserved',false,true],['duplicate',false,false]]){
   const r=spawnSync(php,['-d','display_errors=0','-r',code],{encoding:'utf8',env:{...process.env,MODE:mode,VAYUMA_ONBOARDING_ENABLED:'0',VAYUMA_STAFF_ONBOARD_KEY:'test-secret'}});assert.equal(r.status,0,r.stderr);const result=JSON.parse(r.stdout);assert.equal(result.success,success,mode);assert.equal(result.commit,success,mode);assert.equal(result.rollback,rollback,mode);if(mode==='lock_error')assert.deepEqual(result.logs,['VAYUMA registration failed: phone_lock']);assert.doesNotMatch(JSON.stringify(result.logs),/sensitive|071234|farmer@example|private-test-password/);
  }
 }finally{fs.rmSync(dir,{recursive:true,force:true});}
});
