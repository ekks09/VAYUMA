const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs'), os = require('node:os'), path = require('node:path');
const {spawnSync} = require('node:child_process');
const php = process.env.VAYUMA_PHP || 'php';
const root = path.resolve(__dirname, '../vayuma');
test('onboarding access, consent, duplicate checks and staff attribution (mock database)', () => {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'vayuma-onboarding-'));
  try {
    fs.cpSync(root, dir, {recursive:true});
    // Synthetic crypto stub: verifies wiring only, not encryption security.
    fs.writeFileSync(path.join(dir,'includes/farmer_identity.php'), `<?php function vayuma_farmer_id_valid(string $v): bool {return (bool)preg_match('/^[0-9]{5,20}$/D',$v);} function vayuma_farmer_id_encrypt(string $v): string {return 'test-cipher';}`);
    fs.writeFileSync(path.join(dir,'config/db.php'), `<?php
class FakeResult {private $rows; function __construct($rows){$this->rows=$rows;} function fetch_assoc(){return array_shift($this->rows) ?: null;}}
class FakeStmt {private $sql; private $vars=[]; public $affected_rows=1; function __construct($q){$this->sql=$q;} function bind_param($types,&...$vars){$this->vars=&$vars;} function execute(){if(strpos($this->sql,'INSERT INTO onboarding_records')!==false || strpos($this->sql,'UPDATE onboarding_records SET claim_attempts=0')!==false || strpos($this->sql,'UPDATE onboarding_records SET farmer_id_ciphertext=')!==false){file_put_contents(getenv('TEST_CAPTURE'),json_encode($this->vars));}return true;} function get_result(){if(strpos($this->sql,"SELECT full_name FROM onboarding_records")!==false)return new FakeResult([['full_name'=>'Farmer Example']]); if(strpos($this->sql,'SELECT phone FROM users')!==false)return new FakeResult([['phone'=>'0711111111']]); if(strpos($this->sql,'SELECT id FROM users WHERE phone_identity=')!==false && strpos($this->sql,'LIMIT 2')!==false)return new FakeResult([['id'=>7]]); if(strpos($this->sql,'SELECT phone_key')!==false)return new FakeResult([['phone_key'=>'ok']]); if(strpos($this->sql,'SELECT id FROM users')!==false)return new FakeResult(getenv('TEST_DUP')==='account'?[['id'=>2]]:[]); if(strpos($this->sql,'SELECT r.id, u.full_name')!==false)return new FakeResult(getenv('TEST_DUP')==='record'?[['id'=>8,'staff_name'=>'Example Agent']]:[]); return new FakeResult([]);} function close(){}}
class FakeConn {function begin_transaction(){return true;} function commit(){return true;} function rollback(){return true;} function prepare($q){return new FakeStmt($q);} function query($q){if(strpos($q,'administrative_units')!==false)throw new RuntimeException('Unexpected county lookup');return new FakeResult([]);}} $conn=new FakeConn();`);
    const capture = path.join(dir,'capture.json');
    function request({allowed='0711111111',enabled='1',duplicate='',consent='yes',method='POST',county='Nairobi'}={}) {
      fs.rmSync(capture,{force:true});
      const code = `ini_set('display_errors','0'); session_start(); $_SESSION=['user_id'=>7,'logged_in'=>true,'role'=>'farmer','onboarding_unlocked_user_id'=>7,'onboarding_key_fingerprint'=>hash('sha256','custom-secret'),'vayuma_csrf_token'=>'test-token']; $_SERVER['REQUEST_METHOD']=${JSON.stringify(method)}; $_SERVER['SCRIPT_NAME']='/onboarding.php'; $_SERVER['PHP_SELF']='/onboarding.php'; $_POST=['csrf_token'=>'test-token','full_name'=>'Sample Person','phone'=>'+254712345678','county'=>${JSON.stringify(county)},'role'=>'farmer','farmer_id'=>'12345678','farmer_location'=>'Sample village','activity'=>'Maize','farm_size'=>'1.25','farm_size_unit'=>'acres','preferred_language'=>'en','consent'=>${JSON.stringify(consent)},'onboarded_by_user_id'=>'999']; require ${JSON.stringify(path.join(dir,'onboarding.php'))};`;
      const r=spawnSync(php,['-r',code],{encoding:'utf8',env:{...process.env,VAYUMA_ONBOARDING_ENABLED:enabled,VAYUMA_ONBOARDING_PHONES:allowed,VAYUMA_STAFF_ONBOARD_KEY:'custom-secret',TEST_DUP:duplicate,TEST_CAPTURE:capture}});
      assert.equal(r.status,0,r.stderr); return {output:r.stdout, saved:fs.existsSync(capture)?JSON.parse(fs.readFileSync(capture,'utf8')):null};
    }
    let r=request({allowed:'0722222222'}); assert.ok(r.saved);
    r=request({enabled:'0'}); assert.ok(r.saved);
    r=request({duplicate:'account'}); assert.match(r.output,/account already uses this phone/i); assert.equal(r.saved,null);
    r=request({duplicate:'record'}); assert.match(r.output,/already recorded by Example Agent/i); assert.equal(r.saved,null);
    r=request({consent:''}); assert.match(r.output,/person agreed/i); assert.equal(r.saved,null);
    r=request({county:'nAiRoBi'}); assert.ok(r.saved); assert.equal(r.saved[2],'Nairobi');
    r=request({county:'Marsabit'}); assert.ok(r.saved); assert.equal(r.saved[2],'Marsabit');
    r=request({county:'Marsabit',consent:'no'}); assert.equal(r.saved,null); assert.match(r.output,/<option value="Marsabit" selected/);
    r=request({county:'Not a county'}); assert.equal(r.saved,null); assert.match(r.output,/Select a valid county/);
    r=request(); assert.ok(r.saved); assert.equal(r.saved[1],'0712345678'); assert.equal(r.saved[8],7); assert.equal(r.saved[9],'test-cipher'); assert.equal(r.saved[10],'Sample village');
    fs.rmSync(capture,{force:true});
    const reset = `ini_set('display_errors','0'); session_start(); $_SESSION=['user_id'=>7,'logged_in'=>true,'onboarding_unlocked_user_id'=>7,'onboarding_key_fingerprint'=>hash('sha256','custom-secret'),'vayuma_csrf_token'=>'test-token']; $_SERVER['REQUEST_METHOD']='POST'; $_POST=['csrf_token'=>'test-token','action'=>'reset_claim','record_id'=>'8']; register_shutdown_function(function(){echo json_encode($_SESSION);}); require ${JSON.stringify(path.join(dir,'onboarding.php'))};`;
    const result=spawnSync(php,['-r',reset],{encoding:'utf8',env:{...process.env,VAYUMA_ONBOARDING_ENABLED:'1',VAYUMA_CLAIM_ENABLED:'1',VAYUMA_STAFF_ONBOARD_KEY:'custom-secret',TEST_CAPTURE:capture}});
    assert.equal(result.status,0,result.stderr); assert.equal(JSON.parse(result.stdout).onboarding_notice,'Record updated.'); assert.deepEqual(JSON.parse(fs.readFileSync(capture,'utf8')),[8]);
    const edit = reset.replace("'action'=>'reset_claim','record_id'=>'8'", "'action'=>'update_farmer_id','record_id'=>'8','farmer_id'=>'12345678'");
    const edited=spawnSync(php,['-r',edit],{encoding:'utf8',env:{...process.env,VAYUMA_STAFF_ONBOARD_KEY:'custom-secret',TEST_CAPTURE:capture}});
    assert.equal(edited.status,0,edited.stderr);assert.equal(JSON.parse(edited.stdout).onboarding_notice,'Record updated.');assert.deepEqual(JSON.parse(fs.readFileSync(capture,'utf8')),['test-cipher',8]);
    assert.doesNotMatch(edited.stdout,/12345678/);


  } finally { fs.rmSync(dir,{recursive:true,force:true}); }
});
test('staff unlock uses passphrase, is CSRF protected and rate limited across sessions (mock shared database)', () => {
  const dir=fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-unlock-'));
  try {
    fs.cpSync(root,dir,{recursive:true});
    fs.writeFileSync(path.join(dir,'config/db.php'), `<?php
class R { private $q; private $done=false; function __construct($q){$this->q=$q;} function fetch_assoc(){if(strpos($this->q,'SELECT phone FROM users')!==false){if($this->done)return null;$this->done=true;return ['phone'=>'0711111111'];} if(strpos($this->q,'SELECT id FROM users')!==false){if($this->done)return null;$this->done=true;return ['id'=>7];} return ['failures'=>(int)file_get_contents(getenv('TEST_STATE')),'expired'=>getenv('TEST_EXPIRED')==='1'?1:0];} }
class S {private $q; function __construct($q){$this->q=$q;} function bind_param($t,&...$v){} function execute(){if(strpos($this->q,'failures=failures+1')!==false)file_put_contents(getenv('TEST_STATE'),(int)file_get_contents(getenv('TEST_STATE'))+1);if(strpos($this->q,'failures=0, window_started_at=')!==false)file_put_contents(getenv('TEST_STATE'),0);return true;} function get_result(){return new R($this->q);} function close(){} }
class C {function begin_transaction(){return true;}function commit(){return true;}function rollback(){return true;}function prepare($q){return new S($q);}} $conn=new C;`);
    const state=path.join(dir,'state'); fs.writeFileSync(state,'0');
    function run({pass='wrong',csrf='token',allowed='0711111111',enabled='1',method='POST',envKey='custom-secret',expired='0',previousKey=null,loggedIn=true}={}) {
      const code=`ini_set('display_errors','0'); session_start(); $_SESSION=['user_id'=>7,'logged_in'=>${JSON.stringify(loggedIn)},'role'=>'farmer','vayuma_csrf_token'=>'token']; if (${JSON.stringify(previousKey)} !== null) { $_SESSION['onboarding_unlocked_user_id']=7; $_SESSION['onboarding_key_fingerprint']=hash('sha256',${JSON.stringify(previousKey)}); } $_SERVER['REQUEST_METHOD']=${JSON.stringify(method)}; $_POST=['csrf_token'=>${JSON.stringify(csrf)},'passphrase'=>${JSON.stringify(pass)}]; register_shutdown_function(function(){echo "\\nRESULT=".json_encode(['status'=>http_response_code(),'unlocked'=>$_SESSION['onboarding_unlocked_user_id']??null,'id'=>session_id()]);}); require ${JSON.stringify(path.join(dir,'onboarding.php'))};`;
      const env={...process.env,VAYUMA_ONBOARDING_ENABLED:enabled,VAYUMA_ONBOARDING_PHONES:allowed,TEST_STATE:state,TEST_EXPIRED:expired}; if(envKey===null) delete env.VAYUMA_STAFF_ONBOARD_KEY; else env.VAYUMA_STAFF_ONBOARD_KEY=envKey;
      const r=spawnSync(php,['-r',code],{encoding:'utf8',env});assert.equal(r.status,0,r.stderr);return {output:r.stdout, result:JSON.parse(r.stdout.split('\nRESULT=')[1])};
    }
    let r=run({method:'GET',loggedIn:false}); assert.equal(r.result.unlocked,null); assert.doesNotMatch(r.output,/staff-passphrase/);
    r=run({method:'GET',previousKey:'old-secret'}); assert.match(r.output,/staff-passphrase/); assert.equal(r.result.unlocked,null);
    r=run({method:'GET'}); assert.match(r.output,/Staff Access/);assert.match(r.output,/autocomplete="off"/);assert.equal(r.result.unlocked,null);
    r=run({pass:'custom-secret',allowed:'0722222222'}); assert.equal(r.result.unlocked,7);
    r=run({pass:'custom-secret',enabled:'0'}); assert.equal(r.result.unlocked,7);
    r=run({pass:'custom-secret',csrf:'bad'}); assert.equal(r.result.status,403);assert.equal(+fs.readFileSync(state,'utf8'),0);assert.doesNotMatch(r.output,/custom-secret/);
    for(let i=1;i<=5;i++){r=run();assert.equal(r.result.unlocked,null);assert.equal(+fs.readFileSync(state,'utf8'),i);}
    r=run({pass:'custom-secret'});assert.equal(r.result.status,429);assert.equal(r.result.unlocked,null);
    r=run({pass:'custom-secret',expired:'1'});assert.equal(r.result.unlocked,7);assert.equal(+fs.readFileSync(state,'utf8'),0);
    r=run({pass:'wrong',envKey:null});assert.equal(r.result.status,503);assert.equal(r.result.unlocked,null);
    r=run({pass:'wrong',envKey:''});assert.equal(r.result.status,503);assert.equal(r.result.unlocked,null);
    assert.match(fs.readFileSync(path.join(root,'includes/header_user_menu.php'),'utf8'),/Staff Access/);
  } finally {fs.rmSync(dir,{recursive:true,force:true});}
});
