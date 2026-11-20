const {test} = require('node:test');
const assert = require('node:assert/strict');
const {spawnSync} = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const php = process.env.VAYUMA_PHP || 'php';
const root = path.resolve(__dirname, '../vayuma');

test('login diagnostics are opt-in, categorical and do not change authentication', () => {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'vayuma-login-diagnostic-'));
  try {
    for (const sub of ['auth', 'config', 'includes']) fs.mkdirSync(path.join(dir, sub));
    const source = fs.readFileSync(path.join(root, 'auth/login.php'), 'utf8');
    fs.writeFileSync(path.join(dir, 'auth/login.php'), source.replace(/\berror_log\(/g, 'test_error_log('));
    fs.copyFileSync(path.join(root, 'includes/login_identity.php'), path.join(dir, 'includes/login_identity.php'));
    fs.writeFileSync(path.join(dir, 'config/db.php'), `<?php
class Result { private $count=0; function fetch_assoc() { $mode=getenv('TEST_MODE'); if ($mode==='none' || $this->count++ >= ($mode==='ambiguous' ? 2 : 1)) return null; return ['id'=>9,'full_name'=>'Example','phone'=>'0712345678','email'=>'person@example.com','password'=>password_hash('test-correct-password', PASSWORD_DEFAULT),'role'=>'']; } }
class Stmt { function bind_param($t,&...$v){} function execute(){return getenv('TEST_MODE')!=='execute_error';} function get_result(){return getenv('TEST_MODE')==='result_error' ? false : new Result();} function close(){} }
class Conn { function prepare($q){return new Stmt();} } $conn=new Conn();`);
    const code = `function test_error_log($message){$GLOBALS['test_logs'][]=$message;} $_SERVER['REQUEST_METHOD']='POST'; $_POST=['identifier'=>'private-person@example.com','password'=>getenv('TEST_PASSWORD')]; register_shutdown_function(function(){echo json_encode(['session'=>$_SESSION??[],'logs'=>$GLOBALS['test_logs']??[],'status'=>http_response_code()]);}); require ${JSON.stringify(path.join(dir, 'auth/login.php'))};`;
    const cases = [
      ['none', 'test-wrong-password', '1', false, ['VAYUMA login diagnostic: no_match']],
      ['ambiguous', 'test-correct-password', '1', false, ['VAYUMA login diagnostic: ambiguous']],
      ['one', 'test-wrong-password', '1', false, ['VAYUMA login diagnostic: password_verification_failed']],
      ['one', 'test-wrong-password', '0', false, []],
      ['one', 'test-correct-password', '1', true, []],
      ['execute_error', 'test-correct-password', '1', false, ['VAYUMA login query failed.']],
      ['result_error', 'test-correct-password', '1', false, ['VAYUMA login query failed.']],
    ];
    for (const [mode, password, flag, success, logs] of cases) {
      const r = spawnSync(php, ['-d','display_errors=0','-r',code], {encoding:'utf8', env:{...process.env, TEST_MODE:mode, TEST_PASSWORD:password, VAYUMA_AUTH_DIAGNOSTICS:flag}});
      assert.equal(r.status, 0, r.stderr);
      // Query errors print a generic service-unavailable message before shutdown.
      const start = r.stdout.indexOf('{');
      const result = JSON.parse(r.stdout.slice(start));
      assert.equal(result.session.logged_in === true, success, mode);
      assert.deepEqual(result.logs, logs, mode);
      assert.doesNotMatch(JSON.stringify(result.logs), /private-person|test-(?:correct|wrong)-password|\$2[aby]\$/);
      if (mode.endsWith('_error')) assert.equal(result.status, 503);
    }
  } finally { fs.rmSync(dir, {recursive:true, force:true}); }
});
