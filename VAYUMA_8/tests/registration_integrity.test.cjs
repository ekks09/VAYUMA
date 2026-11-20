const {test} = require('node:test');
const assert = require('node:assert/strict');
const {spawnSync} = require('node:child_process');
const fs = require('node:fs'), os = require('node:os'), path = require('node:path');
const php=process.env.VAYUMA_PHP || 'php';
const root=path.resolve(__dirname,'../vayuma');

test('registration commits only when the persisted hash matches and verifies (mock transaction)',()=>{
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-reg-integrity-'));
 try {
  for(const sub of ['auth','config','includes'])fs.mkdirSync(path.join(dir,sub));
  for(const file of ['auth/register_process.php','includes/login_identity.php','includes/onboarding_policy.php','includes/identity_errors.php'])fs.copyFileSync(path.join(root,file),path.join(dir,file));
  fs.writeFileSync(path.join(dir,'includes/csrf.php'),'<?php function vayuma_csrf_valid(): bool {return true;}');
  fs.writeFileSync(path.join(dir,'config/db.php'),`<?php
class R {public $num_rows=0;private $row;function __construct($r=null){$this->row=$r;}function fetch_assoc(){return $this->row;}}
class S {public $insert_id=17;private $q;private $v;function __construct($q){$this->q=$q;}function bind_param($t,&...$v){$this->v=&$v;}function execute(){if(strpos($this->q,'INSERT INTO users')!==false){$GLOBALS['hash']=end($this->v);$GLOBALS['inserted']=true;}return true;}function get_result(){if(strpos($this->q,'SELECT password FROM users')===0){if(getenv('MODE')==='query_failure')return false;if(getenv('MODE')==='missing')return new R();$hash=$GLOBALS['hash'];if(getenv('MODE')==='corrupt')$hash=substr_replace($hash,$hash[10]==='X'?'Y':'X',10,1);return new R(['password'=>$hash]);}return new R();}function close(){}}
class C {function prepare($q){return new S($q);}function begin_transaction(){$GLOBALS['begun']=true;return true;}function commit(){$GLOBALS['committed']=true;return true;}function rollback(){$GLOBALS['rolled_back']=true;return true;}}$conn=new C();`);
  const code=`function test_error_log($s){$GLOBALS['logs'][]=$s;} $_SERVER['REQUEST_METHOD']='POST'; $_POST=['full_name'=>'Example User','phone'=>'0712345678','email'=>getenv('TEST_EMAIL'),'password'=>'test-private-password','confirm_password'=>'test-private-password','terms'=>'accepted']; register_shutdown_function(function(){echo json_encode(['success'=>$_SESSION['registration_success']??false,'error'=>$_SESSION['register_error']??null,'begun'=>$GLOBALS['begun']??false,'committed'=>$GLOBALS['committed']??false,'rolled_back'=>$GLOBALS['rolled_back']??false,'logs'=>$GLOBALS['logs']??[]]);}); require ${JSON.stringify(path.join(dir,'auth/register_process.php'))};`;
  const file=path.join(dir,'auth/register_process.php');fs.writeFileSync(file,fs.readFileSync(file,'utf8').replace(/\berror_log\(/g,'test_error_log('));
  for(const [mode,email,success] of [['ok','person@example.com',true],['ok','',true],['corrupt','person@example.com',false],['missing','person@example.com',false],['query_failure','person@example.com',false]]){
   const r=spawnSync(php,['-d','display_errors=0','-r',code],{encoding:'utf8',env:{...process.env,MODE:mode,TEST_EMAIL:email,VAYUMA_ONBOARDING_ENABLED:'0'}});assert.equal(r.status,0,r.stderr);const result=JSON.parse(r.stdout);assert.equal(result.begun,true,mode);assert.equal(result.success,success,mode);assert.equal(result.committed,success,mode);assert.equal(result.rolled_back,!success,mode);assert.doesNotMatch(JSON.stringify(result.logs),/test-private-password|person@example|\$2[aby]\$/);if(mode==='corrupt')assert.deepEqual(result.logs,['VAYUMA registration password integrity check failed.']);
  }
 }finally{fs.rmSync(dir,{recursive:true,force:true});}
});
