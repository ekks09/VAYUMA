const {test} = require('node:test');
const assert = require('node:assert/strict');
const {spawnSync} = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const php = process.env.VAYUMA_PHP || 'php';
const root = path.resolve(__dirname, '../vayuma');
test('phone variants cover Kenyan local and international formats without matching empty phones', () => {
  const file = path.join(root, 'includes/login_identity.php');
  const code = `require ${JSON.stringify(file)}; echo json_encode([vayuma_login_phone_variants('+254 712 345 678'),vayuma_login_phone_variants('0712-345-678'),vayuma_login_phone_variants('user@example.com')]);`;
  const r = spawnSync(php, ['-r', code], {encoding:'utf8'});
  assert.equal(r.status, 0, r.stderr);
  assert.deepEqual(JSON.parse(r.stdout), [['0712345678','254712345678','+254712345678'], ['0712345678','254712345678','+254712345678'], [null,null,null]]);
});
test('login authenticates valid accounts and permits unfinished onboarding, but rejects bad credentials and unknown roles', () => {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'vayuma-login-test-'));
  try {
    for (const sub of ['auth','config','includes']) fs.mkdirSync(path.join(dir,sub));
    fs.copyFileSync(path.join(root,'auth/login.php'), path.join(dir,'auth/login.php'));
    fs.copyFileSync(path.join(root,'includes/login_identity.php'), path.join(dir,'includes/login_identity.php'));
    fs.writeFileSync(path.join(dir,'config/db.php'), `<?php
class FakeResult { private $done=false; function fetch_assoc() { if($this->done)return null;$this->done=true; return ['id'=>17,'full_name'=>'Example','phone'=>'0712345678','email'=>'person@example.com','password'=>password_hash('correct-password', PASSWORD_DEFAULT),'role'=>getenv('TEST_ROLE')]; } }
class FakeStmt { function bind_param($types, &...$vars) {} function execute() { return true; } function get_result() { return new FakeResult(); } function close() {} }
class FakeConn { function prepare($query) { return new FakeStmt(); } }
$conn = new FakeConn();
`);
    for (const [role, password, expected] of [['farmer','correct-password',true], ['', 'correct-password', true], ['bogus','correct-password',false], ['farmer','wrong-password',false]]) {
      const code = `$_SERVER['REQUEST_METHOD']='POST'; $_POST=['identifier'=>'person@example.com','password'=>${JSON.stringify(password)}]; register_shutdown_function(function(){echo json_encode($_SESSION ?? []);}); require ${JSON.stringify(path.join(dir,'auth/login.php'))};`;
      const r = spawnSync(php,['-r',code],{encoding:'utf8',env:{...process.env,TEST_ROLE:role}});
      assert.equal(r.status,0,r.stderr);
      const session = JSON.parse(r.stdout);
      assert.equal(session.logged_in === true, expected, `role=${role}`);
      if (expected) { assert.equal(session.user_id,17); assert.equal(session.role,role); }
    }
  } finally { fs.rmSync(dir,{recursive:true,force:true}); }
});

test('registration stores a usable hash, signs out, and the new account can sign in', () => {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'vayuma-register-test-'));
  try {
    for (const sub of ['auth', 'config', 'includes']) fs.mkdirSync(path.join(dir, sub));
    for (const file of ['auth/register_process.php', 'auth/login.php', 'includes/login_identity.php', 'includes/identity_errors.php', 'includes/onboarding_policy.php']) fs.copyFileSync(path.join(root, file), path.join(dir, file));
    fs.writeFileSync(path.join(dir, 'includes/csrf.php'), '<?php function vayuma_csrf_valid(): bool { return true; }');
    fs.writeFileSync(path.join(dir, 'config/db.php'), `<?php
class FakeResult { public $num_rows=0; private $row; function __construct($row=null){$this->row=$row;} function fetch_assoc(){$row=$this->row;$this->row=null;return $row;} }
class FakeStmt { public $insert_id=17; public $errno=0; private $query; private $vars; function __construct($q){$this->query=$q;} function bind_param($t,&...$v){$this->vars=&$v;} function execute(){if(strpos($this->query,'INSERT INTO users')!==false){[$name,$phone,$email,$password]=$this->vars; file_put_contents(getenv('TEST_ACCOUNT_FILE'),json_encode(['id'=>17,'full_name'=>$name,'phone'=>$phone,'email'=>$email,'password'=>$password,'role'=>'']));}return true;} function get_result(){if(strpos($this->query,'SELECT password FROM users')!==false){$u=json_decode(file_get_contents(getenv('TEST_ACCOUNT_FILE')),true);return new FakeResult(['password'=>$u['password']]);} if(strpos($this->query,'SELECT id, full_name')!==false){$u=json_decode(file_get_contents(getenv('TEST_ACCOUNT_FILE')),true); $matches=strtolower(trim($this->vars[0]))===strtolower(trim($u['email'])) || $u['phone']===$this->vars[1]; return new FakeResult($matches?$u:null);} return new FakeResult();} function close(){} }
class FakeConn { function prepare($q){return new FakeStmt($q);} function begin_transaction(){return true;} function commit(){return true;} function rollback(){return true;} } $conn=new FakeConn();`);
    const env = {...process.env, TEST_ACCOUNT_FILE:path.join(dir,'account.json')};
    const register = `$_SERVER['REQUEST_METHOD']='POST'; $_POST=['full_name'=>'Sample Farmer','phone'=>'+254 (712) 345.678','email'=>' Farmer@Example.COM ','password'=>'correct-password','confirm_password'=>'correct-password','terms'=>'accepted']; register_shutdown_function(function(){echo json_encode($_SESSION ?? []);}); require ${JSON.stringify(path.join(dir,'auth/register_process.php'))};`;
    const r = spawnSync(php, ['-r', register], {encoding:'utf8', env});
    assert.equal(r.status, 0, r.stderr); assert.ok(r.stdout.startsWith('{'), r.stdout); const session=JSON.parse(r.stdout);
    assert.equal(session.registration_success, true); assert.notEqual(session.logged_in, true);
    const stored=JSON.parse(fs.readFileSync(env.TEST_ACCOUNT_FILE, 'utf8'));assert.notEqual(stored.password, 'correct-password');assert.equal(stored.phone,'0712345678');assert.equal(stored.email,'farmer@example.com');
    for (const [identifier,password,expected] of [['farmer@example.com','correct-password',true],['Farmer@Example.COM','correct-password',true],['+254 (712) 345.678','correct-password',true],['farmer@example.com','wrong-password',false]]) {
      const login = `$_SERVER['REQUEST_METHOD']='POST'; $_POST=['identifier'=>${JSON.stringify(identifier)},'password'=>${JSON.stringify(password)}]; register_shutdown_function(function(){echo json_encode($_SESSION ?? []);}); require ${JSON.stringify(path.join(dir,'auth/login.php'))};`;
      const result=spawnSync(php,['-r',login],{encoding:'utf8',env}); assert.equal(result.status,0,result.stderr); assert.equal(JSON.parse(result.stdout).logged_in === true,expected);
    }
    assert.match(fs.readFileSync(path.join(root,'auth/register_process.php'),'utf8'), /header\('Location: \.\.\/login\.php'\)/);
  } finally { fs.rmSync(dir,{recursive:true,force:true}); }
});
