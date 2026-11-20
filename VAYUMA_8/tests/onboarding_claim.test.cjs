const {test}=require('node:test'), assert=require('node:assert/strict'), fs=require('node:fs'), os=require('node:os'), path=require('node:path'), {spawnSync}=require('node:child_process'), crypto=require('node:crypto');
const php=process.env.VAYUMA_PHP||'php', root=path.resolve(__dirname,'../vayuma');
test('claim requires a private code, persists failed attempts and consumes a successful claim once (mock DB)',()=>{
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-claim-'));
 try {
  fs.cpSync(root,dir,{recursive:true});
  fs.writeFileSync(path.join(dir,'config/db.php'),`<?php
class FakeResult {private $r; function __construct($r){$this->r=$r;} function fetch_assoc(){return $this->r;}}
class FakeStmt {private $db,$q,$vars=[]; public $insert_id=24,$affected_rows=1; function __construct($db,$q){$this->db=$db;$this->q=$q;} function bind_param($types,&...$v){$this->vars=&$v;} function execute(){if(strpos($this->q,'claim_attempts=claim_attempts+1')!==false)$this->db->state['claim_attempts']++; if(strpos($this->q,'INSERT INTO users')!==false){$this->db->state['account']=['phone'=>$this->vars[1], 'email'=>$this->vars[2], 'password'=>$this->vars[3]];} if(strpos($this->q,'SET claimed_by_user_id=')!==false){$this->db->state['claimed_by_user_id']=$this->vars[0];$this->db->state['claim_code_hash']=null;}return true;} function get_result(){if(strpos($this->q,'SELECT phone_key')!==false)return new FakeResult(['phone_key'=>'ok']); if(strpos($this->q,'FROM onboarding_records WHERE phone_identity')!==false){$s=$this->db->state;return new FakeResult(['id'=>5,'full_name'=>'Sample Farmer','claim_code_hash'=>$s['claim_code_hash'],'claim_attempts'=>$s['claim_attempts'],'claimed_by_user_id'=>$s['claimed_by_user_id'],'unexpired'=>$s['unexpired']]);} if(strpos($this->q,'SELECT id FROM users')!==false)return new FakeResult($this->db->state['existing']?['id'=>2]:null);return new FakeResult(null);}function close(){}}
class FakeConn {public $state; function __construct(){$this->state=json_decode(file_get_contents(getenv('TEST_STATE')),true);} function begin_transaction(){return true;} function commit(){file_put_contents(getenv('TEST_STATE'),json_encode($this->state));return true;}function rollback(){return true;}function prepare($q){return new FakeStmt($this,$q);}} $conn=new FakeConn();`);
  const statePath=path.join(dir,'state.json'), code='abcdef0123456789abcdef0123456789';
  const initial={claim_code_hash:crypto.createHash('sha256').update(code).digest('hex'),claim_attempts:0,claimed_by_user_id:null,unexpired:1,existing:false};
  const put=o=>fs.writeFileSync(statePath,JSON.stringify(o)), get=()=>JSON.parse(fs.readFileSync(statePath));
  const request=({token=code,csrf='token',enabled='1',password='very-strong-pass'}={})=>{
   const snippet=`ini_set('display_errors','0'); session_start(); $_SESSION=['vayuma_csrf_token'=>'token']; $_SERVER['REQUEST_METHOD']='POST'; $_SERVER['SCRIPT_NAME']='/claim_account.php'; $_POST=['csrf_token'=>${JSON.stringify(csrf)},'phone'=>'+254712345678','code'=>${JSON.stringify(token)},'email'=>'farmer@example.com','password'=>${JSON.stringify(password)},'confirm_password'=>${JSON.stringify(password)}]; require ${JSON.stringify(path.join(dir,'claim_account.php'))};`;
   const r=spawnSync(php,['-r',snippet],{encoding:'utf8',env:{...process.env,VAYUMA_ONBOARDING_ENABLED:'1',VAYUMA_CLAIM_ENABLED:enabled,TEST_STATE:statePath}}); assert.equal(r.status,0,r.stderr);return r.stdout;
  };
  put({...initial}); let out=request({enabled:'0'});assert.match(out,/not available yet/i);assert.equal(get().claimed_by_user_id,null);
  out=request({csrf:'wrong'});assert.match(out,/session expired/i);assert.equal(get().claim_attempts,0);
  for(let i=0;i<5;i++) {out=request({token:'00000000000000000000000000000000'});assert.match(out,/invalid or expired/i);}
  assert.equal(get().claim_attempts,5);out=request();assert.match(out,/invalid or expired/i);assert.equal(get().claimed_by_user_id,null);
  put({...initial,unexpired:0});out=request();assert.match(out,/invalid or expired/i);assert.equal(get().claimed_by_user_id,null);
  put({...initial,existing:true});out=request();assert.match(out,/Unable to complete the claim/i);assert.equal(get().claimed_by_user_id,null);
  put({...initial});request();let state=get();assert.equal(state.claimed_by_user_id,24);assert.equal(state.claim_code_hash,null);assert.equal(state.account.phone,'0712345678');assert.notEqual(state.account.password,'very-strong-pass');
  out=request();assert.match(out,/invalid or expired/i);assert.equal(get().claimed_by_user_id,24);
 } finally {fs.rmSync(dir,{recursive:true,force:true});}
});
test('claim links transfer phone without placing it in the URL', () => {
 const register=fs.readFileSync(path.join(root,'register.php'),'utf8'), login=fs.readFileSync(path.join(root,'login.php'),'utf8'), claim=fs.readFileSync(path.join(root,'claim_account.php'),'utf8'), js=fs.readFileSync(path.join(root,'assets/js/claim-phone.js'),'utf8');
 assert.match(register,/data-claim-source="phone" href="claim_account.php"/);assert.match(login,/data-claim-source="loginIdentifier" href="claim_account.php"/);assert.match(claim,/id="claim-phone"/);assert.match(js,/sessionStorage\.setItem/);assert.match(js,/sessionStorage\.removeItem/);assert.match(js,/if \(!phone\.value/);
 assert.match(fs.readFileSync(path.join(root,'auth/register_process.php'),'utf8'),/\$_SESSION\['register_phone'\] = \$phone/);
});
