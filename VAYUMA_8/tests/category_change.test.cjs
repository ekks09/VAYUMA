// Tests the real category handler against a disposable in-memory PHP mock, not TiDB.
const {test} = require('node:test');
const assert = require('node:assert/strict');
const {spawnSync} = require('node:child_process');
const fs = require('node:fs'), os = require('node:os'), path = require('node:path');
const root = path.join(__dirname,'../vayuma');
const php = process.env.VAYUMA_PHP || 'php';
test('category changes require CSRF and respect active access for every role', () => {
  const dir=fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-category-'));
  try {
    fs.cpSync(root,dir,{recursive:true});
    fs.writeFileSync(path.join(dir,'config/db.php'), `<?php
class MockResult { private $sql; function __construct($sql){$this->sql=$sql;} function fetch_assoc(){if(strpos($this->sql,'SELECT role FROM users')!==false)return ['role'=>'farmer']; if(strpos($this->sql,'SELECT expires_at')!==false && getenv('ACTIVE')==='1')return ['expires_at'=>'2099-01-01']; return null;} }
class MockStmt { private $sql; function __construct($sql){$this->sql=$sql;} function bind_param(...$args){} function execute(){return true;} function get_result(){return new MockResult($this->sql);} function close(){} }
class MockDB {function prepare($sql){return new MockStmt($sql);} } $conn=new MockDB();`);
    function run(role,active,valid=true,logged=true) {
      const code=`session_start(); $_SESSION=['user_id'=>1,'logged_in'=>${logged?'true':'false'},'role'=>'farmer','vayuma_csrf_token'=>'secret']; $_POST=['role'=>${JSON.stringify(role)},'csrf_token'=>${JSON.stringify(valid?'secret':'bad')}]; $_SERVER['REQUEST_METHOD']='POST'; register_shutdown_function(function(){echo json_encode(['role'=>$_SESSION['role']??'', 'error'=>$_SESSION['role_error']??'']);}); require ${JSON.stringify(path.join(dir,'auth/save_role.php'))};`;
      const result=spawnSync(php,['-d','display_errors=0','-r',code],{cwd:path.join(dir,'auth'),encoding:'utf8',env:{...process.env,ACTIVE:active?'1':'0'}});
      assert.equal(result.status,0,result.stderr); return JSON.parse(result.stdout);
    }
    const roles=['farmer','buyer','consumer','transport_provider','input_supplier','manufacturer','warehouse_provider','retailer','agribusiness','cooperative','researcher','extension_officer','veterinary_officer','financial_institution','insurance_provider','government_officer','ngo_partner','agricultural_service_provider','strategic_partner'];
    for (const role of roles) assert.equal(run(role,false).role,role,role);
    assert.equal(run('buyer',true).role,'farmer');
    assert.match(run('buyer',true).error,/active until/);
    assert.equal(run('farmer',true).role,'farmer');
    assert.equal(run('buyer',false,false).role,'farmer');
    assert.match(run('buyer',false,false).error,/session expired/i);
    assert.equal(run('buyer',false,true,false).role,'farmer');
    assert.match(run('invalid',false).error,/valid VAYUMA role/);
  } finally { fs.rmSync(dir,{recursive:true,force:true}); }
});
