const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {spawnSync} = require('node:child_process');
const php = process.env.VAYUMA_PHP || 'php';
const root = path.resolve(__dirname,'../vayuma');
test('farmer profile reads existing values and creates, updates or refuses duplicate rows (mock database)', () => {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-farmer-profile-'));
  try {
    fs.cpSync(root,dir,{recursive:true});
    // Synthetic crypto stub tests wiring, not sodium encryption.
    fs.writeFileSync(path.join(dir,'includes/farmer_identity.php'), `<?php function vayuma_farmer_id_valid(string $v): bool {return (bool)preg_match('/^[0-9]{5,20}$/D',$v);} function vayuma_farmer_id_encrypt(string $v): string {return 'test-cipher';} function vayuma_farmer_id_decrypt(?string $v): string {return '12345678';}`);
    fs.writeFileSync(path.join(dir,'config/db.php'), `<?php
class ResultFake { private $rows; function __construct($r){$this->rows=$r;} function fetch_assoc(){return array_shift($this->rows) ?: null;} }
class StatementFake {
 private $sql; private $args=[]; function __construct($sql){$this->sql=$sql;} function bind_param($types,&...$args){$this->args=&$args;}
 function execute(){if(strpos($this->sql,'UPDATE farmer_profiles SET farmer_id_ciphertext')===0) $GLOBALS['idwrite']=array_values($this->args); if(strpos($this->sql,'UPDATE farmer_profiles SET farmer_id_ciphertext')!==0 && (strpos($this->sql,'UPDATE farmer_profiles')===0 || strpos($this->sql,'INSERT INTO farmer_profiles')===0)) $GLOBALS['write']=['sql'=>$this->sql,'values'=>array_values($this->args)]; return true;}
 function get_result(){
   if(strpos($this->sql,'FROM onboarding_records')!==false)return new ResultFake(getenv('TEST_MODE')==='claimed'?[['farmer_id_ciphertext'=>'claimed-cipher']]:[]);
   if(strpos($this->sql,'FROM users')!==false)return new ResultFake([['id'=>7]]);
   if(strpos($this->sql,'FROM farmer_profiles')!==false){$mode=getenv('TEST_MODE');if($mode==='new' || $mode==='claimed')return new ResultFake([]);$row=['id'=>3,'farm_name'=>'Green <Farm>','county'=>'Nairobi','sub_county'=>'Westlands','farm_location'=>'Sample','farm_size'=>'2','farm_size_unit'=>'acres','farming_experience'=>'5 years','main_crops'=>'Maize','soil_type'=>'Loamy','irrigation'=>'Rain-fed','secondary_crop'=>'Beans'];return new ResultFake($mode==='duplicate'?[$row,$row]:[$row]);}
   return new ResultFake([]);
 } function close(){} }
class DbFake {function prepare($sql){return new StatementFake($sql);} function begin_transaction(){return true;} function commit(){$GLOBALS['commit']=true;return true;} function rollback(){$GLOBALS['rollback']=true;} }
$conn=new DbFake();
`);
    for (const mode of ['new','existing','duplicate']) {
      const code = `session_start(); $_SESSION=['user_id'=>7,'logged_in'=>true,'role'=>'farmer','vayuma_csrf_token'=>'token']; $_SERVER['REQUEST_METHOD']='POST'; $_POST=['csrf_token'=>'token','farmer_id'=>'12345678','farm_name'=>'New Farm','county'=>'Nairobi','sub_county'=>'Westlands','farm_location'=>'Sample','farm_size'=>'2','farm_size_unit'=>'acres','farming_experience'=>'5 years','main_crops'=>'Maize','soil_type'=>'Loamy','irrigation'=>'Rain-fed','secondary_crop'=>'Beans']; register_shutdown_function(function(){echo json_encode(['write'=>$GLOBALS['write']??null,'commit'=>$GLOBALS['commit']??false,'rollback'=>$GLOBALS['rollback']??false,'error'=>$_SESSION['farm_profile_error']??'']);}); require ${JSON.stringify(path.join(dir,'auth/save_farmer.php'))};`;
      const r=spawnSync(php,['-d','display_errors=0','-r',code],{encoding:'utf8',env:{...process.env,TEST_MODE:mode}});
      assert.equal(r.status,0,r.stderr); const data=JSON.parse(r.stdout);
      if(mode==='duplicate'){assert.equal(data.write,null);assert.equal(data.rollback,true);assert.match(data.error,/Multiple farm profiles/);}
      else {assert.equal(data.commit,true);assert.match(data.write.sql,mode==='new'?/^INSERT INTO/:/^UPDATE/); assert.equal(data.write.values.includes(7),true); if(mode==='existing') assert.deepEqual(data.write.values.slice(-2),[3,7]);}
    }
    const claimedCode = `session_start(); $_SESSION=['user_id'=>7,'logged_in'=>true,'role'=>'farmer','vayuma_csrf_token'=>'token']; $_SERVER['REQUEST_METHOD']='POST'; $_POST=['csrf_token'=>'token','farm_name'=>'Farm','county'=>'Nairobi','sub_county'=>'Westlands','farm_location'=>'Village','farm_size'=>'2','farm_size_unit'=>'acres','farming_experience'=>'5 years','main_crops'=>'Maize','soil_type'=>'Loamy','irrigation'=>'Rain-fed']; register_shutdown_function(function(){echo json_encode(['idwrite'=>$GLOBALS['idwrite']??null,'commit'=>$GLOBALS['commit']??false]);}); require ${JSON.stringify(path.join(dir,'auth/save_farmer.php'))};`;
    const claimed = spawnSync(php,['-d','display_errors=0','-r',claimedCode],{encoding:'utf8',env:{...process.env,TEST_MODE:'claimed'}});
    assert.equal(claimed.status,0,claimed.stderr); assert.deepEqual(JSON.parse(claimed.stdout),{idwrite:['claimed-cipher',7],commit:true});
    const code = `session_start(); $_SESSION=['user_id'=>7,'logged_in'=>true,'role'=>'farmer','full_name'=>'Demo']; $_SERVER['REQUEST_METHOD']='GET'; require ${JSON.stringify(path.join(dir,'profile_setup.php'))};`;
    const r=spawnSync(php,['-r',code],{encoding:'utf8',env:{...process.env,TEST_MODE:'existing'}});
    assert.equal(r.status,0,r.stderr); assert.match(r.stdout,/value="Green &lt;Farm&gt;"/); assert.match(r.stdout,/<option selected>Nairobi<\/option>/); assert.match(r.stdout,/<option value="acres" selected>/); assert.match(r.stdout,/<option selected>Loamy<\/option>/);
  } finally { fs.rmSync(dir,{recursive:true,force:true}); }
});
