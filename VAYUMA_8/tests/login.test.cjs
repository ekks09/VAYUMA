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
class FakeResult { function fetch_assoc() { return ['id'=>17,'full_name'=>'Example','phone'=>'0712345678','email'=>'person@example.com','password'=>password_hash('correct-password', PASSWORD_DEFAULT),'role'=>getenv('TEST_ROLE')]; } }
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
