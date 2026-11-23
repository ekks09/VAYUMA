const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs'), os=require('node:os'), path=require('node:path'), {spawnSync}=require('node:child_process');
const php=process.env.VAYUMA_PHP||'php', root=path.resolve(__dirname,'../vayuma');

test('staff search is CSRF protected, phone-normalized, private and does not save a record (mock DB)',()=>{
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-staff-portal-'));
 try {
  fs.cpSync(root,dir,{recursive:true});
  fs.writeFileSync(path.join(dir,'config/db.php'),`<?php
class R {private $rows;function __construct($r){$this->rows=$r;}function fetch_assoc(){return array_shift($this->rows) ?: null;}}
class S {private $q,$v=[];function __construct($q){$this->q=$q;}function bind_param($t,&...$v){$this->v=&$v;}function execute(){if(strpos($this->q,'SELECT r.full_name')!==false)file_put_contents(getenv('TEST_CAPTURE'),json_encode($this->q));if(strpos($this->q,'INSERT INTO onboarding_records')!==false)file_put_contents(getenv('TEST_INSERT'),'unexpected');return true;}function get_result(){if(strpos($this->q,'SELECT phone FROM users')!==false)return new R([['phone'=>'0711111111']]);if(strpos($this->q,'SELECT id FROM users WHERE phone_identity')!==false)return new R([['id'=>7]]);if(strpos($this->q,'SELECT r.full_name')!==false)return new R([['id'=>4,'full_name'=>'Sample Farmer','phone_identity'=>'0712345678','county'=>'Nairobi','farmer_location'=>'Kibera','preferred_language'=>'en','farmer_id_ciphertext'=>null,'stakeholder_role'=>'farmer','activity'=>'Maize','farm_size'=>'1','farm_size_unit'=>'acres','created_at'=>'2026-01-01','claimed_by_user_id'=>null,'staff_name'=>'Example Staff']]);return new R([]);}function close(){}}
class C {function prepare($q){return new S($q);}function query($q){return new R(strpos($q,'administrative_units')!==false?[['name'=>'Nairobi']]:[]);}}$conn=new C();`);
  const capture=path.join(dir,'capture'), insert=path.join(dir,'insert');
  function run({csrf='token',phone='+254 712 345 678',flag='1',claimFlash=false,view='onboarded'}={}){
   fs.rmSync(capture,{force:true});fs.rmSync(insert,{force:true});
   const code=`ini_set('display_errors','0'); session_start(); $_SESSION=['user_id'=>7,'logged_in'=>true,'onboarding_unlocked_user_id'=>7,'onboarding_key_fingerprint'=>hash('sha256','custom-secret'),'vayuma_csrf_token'=>'token']; if (${claimFlash?'true':'false'}) {$_SESSION['onboarding_claim_code']='abcdef0123456789abcdef0123456789'; $_SESSION['onboarding_claim_name']='<Farmer>'; } $_SERVER['REQUEST_METHOD']='POST'; $_SERVER['SCRIPT_NAME']='/onboarding.php'; $_POST=['csrf_token'=>${JSON.stringify(csrf)},'action'=>'find_record','view'=>${JSON.stringify(view)},'search_phone'=>${JSON.stringify(phone)}]; register_shutdown_function(function(){fwrite(STDERR,"\\nFLASH=".json_encode(isset($_SESSION['onboarding_claim_code'])));}); require ${JSON.stringify(path.join(dir,'onboarding.php'))};`;
   const r=spawnSync(php,['-r',code],{encoding:'utf8',env:{...process.env,VAYUMA_STAFF_ONBOARD_KEY:'custom-secret',VAYUMA_ONBOARDING_ENABLED:'1',VAYUMA_ONBOARDING_PHONES:'0711111111',VAYUMA_CLAIM_ENABLED:flag,TEST_CAPTURE:capture,TEST_INSERT:insert}});assert.equal(r.status,0,r.stderr);return {html:r.stdout,search:fs.existsSync(capture)?JSON.parse(fs.readFileSync(capture,'utf8')):null,insert:fs.existsSync(insert),stderr:r.stderr};
  }
  let r=run();assert.match(r.search,/WHERE r.stakeholder_role='farmer'/);assert.equal(r.insert,false);assert.match(r.html,/Sample Farmer/);assert.match(r.html,/<form action="farmer_export.php" method="post" class="onboard-export-actions"/);assert.match(r.html,/<button type="submit" name="mode" value="download"[^>]*>[^<]*<span[^>]*>[^<]*<\/span> Download farmers CSV<\/button>/);assert.equal((r.html.match(/name="mode" value="download"/g)||[]).length,1);assert.match(r.html,/name="mode" value="preview"/);assert.match(r.html,/name="action" value="reset_claim"/);assert.match(r.html,/method="post" action="onboarding.php#records"/);assert.doesNotMatch(r.html,/search_phone=0712345678/);
  r=run({csrf:'bad'});assert.equal(r.insert,false);assert.match(r.html,/session expired/i);
  r=run({phone:'Example Staff'});assert.match(r.html,/Sample Farmer/); r=run({phone:'Maize Kibera'});assert.match(r.html,/Sample Farmer/); r=run({view:'all'});assert.doesNotMatch(r.search,/WHERE r.stakeholder_role='farmer'/); r=run({phone:'nonexistent'});assert.match(r.html,/No records found/);
  r=run({flag:'0'});assert.match(r.html,/claiming is currently disabled/i);assert.doesNotMatch(r.html,/name="action" value="reset_claim"/);
  r=run({claimFlash:true});assert.doesNotMatch(r.html,/abcdef0123456789abcdef0123456789/);assert.match(r.stderr,/FLASH=false/);

 }finally{fs.rmSync(dir,{recursive:true,force:true});}
});
