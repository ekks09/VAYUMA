// Synthetic PHP-WASM requests with fake database/decryption; not a TiDB or real encryption test.
const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs'), os=require('node:os'), path=require('node:path'), {spawnSync}=require('node:child_process');
const php=process.env.VAYUMA_PHP||'php', root=path.resolve(__dirname,'../vayuma');
test('CSV authorization, text protection, complete rows and failure handling',()=>{
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-csv-test-'));
 try {
  fs.mkdirSync(path.join(dir,'includes'));fs.mkdirSync(path.join(dir,'config'));
  for(const f of ['farmer_export.php','includes/csrf.php'])fs.copyFileSync(path.join(root,f),path.join(dir,f));
  fs.writeFileSync(path.join(dir,'includes/farmer_identity.php'),`<?php function vayuma_farmer_id_decrypt(?string $v): string {if(getenv('TEST_BAD_ID')==='1')throw new RuntimeException('secret failure'); return $v==='cipher'?'00123456':'';}`);
  fs.writeFileSync(path.join(dir,'config/db.php'),`<?php class Result {private $rows;function __construct(){ $this->rows=[['record_type'=>'Registered account','account_id'=>1,'full_name'=>'=HYPERLINK("bad")','phone'=>'0712345678','email'=>'test@example.test','created_at'=>'2026-01-01','county'=>'Nairobi','sub_county'=>'Westlands','location'=>'+formula','id_cipher'=>'cipher','farm_name'=>'Farm','activity'=>'Maize','farm_size'=>'1','farm_size_unit'=>'acres','preferred_language'=>'en','onboarded_at'=>null,'onboarded_by'=>null,'farming_experience'=>'2 years','soil_type'=>'Loamy','irrigation'=>'Rain-fed','secondary_crop'=>'Beans','current_role'=>'farmer'],['record_type'=>'Staff record not claimed','account_id'=>null,'full_name'=>'Unclaimed Farmer','phone'=>'0700000000','email'=>null,'created_at'=>null,'county'=>'Nairobi','sub_county'=>null,'location'=>'Village','id_cipher'=>null,'farm_name'=>null,'activity'=>'Beans','farm_size'=>'2','farm_size_unit'=>'acres','preferred_language'=>'sw','onboarded_at'=>'2026-01-01','onboarded_by'=>'Staff','farming_experience'=>null,'soil_type'=>null,'irrigation'=>null,'secondary_crop'=>null,'current_role'=>null]]; }function fetch_assoc(){return array_shift($this->rows)?:null;}} class DB {function query($q){if(getenv('TEST_DB_FAIL')==='1')return false;return new Result();}} $conn=new DB();`);
  function run(mode){
   const s=mode==='locked'?"['user_id'=>1,'logged_in'=>true,'vayuma_csrf_token'=>'token']":"['user_id'=>1,'logged_in'=>true,'vayuma_csrf_token'=>'token','onboarding_unlocked_user_id'=>1,'onboarding_key_fingerprint'=>hash('sha256','test-passphrase')]";
   const code=`session_start(); $_SESSION=${s}; $_SERVER['REQUEST_METHOD']='POST'; $_POST=['csrf_token'=>${JSON.stringify(mode==='csrf'?'invalid':'token')}]; register_shutdown_function(function(){fwrite(STDERR," STATUS=".http_response_code());}); require ${JSON.stringify(path.join(dir,'farmer_export.php'))};`;
   const r=spawnSync(php,['-d','display_errors=0','-r',code],{encoding:'utf8',env:{...process.env,VAYUMA_STAFF_ONBOARD_KEY:'test-passphrase',TEST_BAD_ID:mode==='bad_id'?'1':'0',TEST_DB_FAIL:mode==='db'?'1':'0'}});assert.equal(r.status,0,r.stderr);return r;
  }
  const good=run('ok'); assert.match(good.stdout,/Record type/);assert.match(good.stdout,/'=HYPERLINK/);assert.match(good.stdout,/'\+formula/);assert.match(good.stdout,/'0712345678/);assert.match(good.stdout,/'00123456/);assert.match(good.stdout,/Unclaimed Farmer/);assert.doesNotMatch(good.stdout,/cipher/);
  for(const mode of ['locked','csrf']) {const r=run(mode);assert.match(r.stderr,/STATUS=403/);assert.doesNotMatch(r.stdout,/Record type|00123456/);}
  for(const mode of ['bad_id','db']) {const r=run(mode);assert.match(r.stderr,/STATUS=503/);assert.match(r.stdout,/No file was prepared/);assert.doesNotMatch(r.stdout,/Record type|00123456|secret failure/);}
 }finally{fs.rmSync(dir,{recursive:true,force:true});}
});
