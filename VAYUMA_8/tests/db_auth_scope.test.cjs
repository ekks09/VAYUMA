const {test}=require('node:test');
const assert=require('node:assert/strict');
const {spawnSync}=require('node:child_process');
const fs=require('node:fs'), os=require('node:os'), path=require('node:path');
const php=process.env.VAYUMA_PHP || 'php';
const root=path.resolve(__dirname,'../vayuma');

test('database configuration does not overwrite caller password or user',()=>{
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-db-scope-'));
 try {
  for(const file of ['db_diagnostics.php','db_tls.php'])fs.copyFileSync(path.join(root,'config',file),path.join(dir,file));
  // Only substitute the network constructor: execute the actual config otherwise.
  let src=fs.readFileSync(path.join(root,'config/db.php'),'utf8');
  src=src.replace('mysqli_init()', 'test_mysqli_init()');
  fs.writeFileSync(path.join(dir,'db.php'),src);
  const code=`class FakeDB {function options($k,$v){return true;} function real_connect($h,$u,$p,$d,$port,$socket,$flags){$GLOBALS['connected']=($u==='mock-db-user' && $p==='mock-db-password' && $d==='mock-db'); return true;}function set_charset($c){return true;}} function test_mysqli_init(){return new FakeDB();} $password='submitted-password'; $user=['name'=>'caller']; require ${JSON.stringify(path.join(dir,'db.php'))}; echo json_encode(['password_intact'=>$password==='submitted-password','user_intact'=>$user===['name'=>'caller'],'connected'=>$GLOBALS['connected']??false]);`;
  const env={...process.env, APP_ENV:'test', DB_HOST:'127.0.0.1', DB_PORT:'3306', DB_NAME:'mock-db', DB_USER:'mock-db-user', DB_PASSWORD:'mock-db-password', DB_TLS:'0'};
  const r=spawnSync(php,['-d','display_errors=0','-r',code],{encoding:'utf8',env});assert.equal(r.status,0,r.stderr);assert.deepEqual(JSON.parse(r.stdout),{password_intact:true,user_intact:true,connected:true});
 }finally{fs.rmSync(dir,{recursive:true,force:true});}
});
