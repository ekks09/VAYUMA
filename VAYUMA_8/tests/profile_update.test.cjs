const {test} = require('node:test');
const assert = require('node:assert/strict');
const {spawnSync} = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const php = process.env.VAYUMA_PHP || 'php';
const root = path.resolve(__dirname, '../vayuma');
test('profile update requires CSRF and saves validated fields for the authenticated user (mock database)', () => {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'vayuma-profile-test-'));
  try {
    fs.cpSync(root, dir, {recursive:true});
    fs.writeFileSync(path.join(dir,'config/db.php'), `<?php
class ProfileResult { private $row; function __construct($row){$this->row=$row;} function fetch_assoc(){return $this->row;} }
class ProfileStmt { private $sql; private $params=[]; public $errno=0; function __construct($sql){$this->sql=$sql;} function bind_param($types,&...$args){$this->params=&$args;} function execute(){if(strpos($this->sql,'UPDATE users SET')===0) $GLOBALS['saved']=array_values($this->params); return true;} function get_result(){return new ProfileResult(strpos($this->sql,'SELECT id, phone FROM users WHERE id=?')===0 ? ['id'=>7,'phone'=>getenv('TEST_OLD_PHONE') ?: '0700000000'] : null);} function close(){} }
class ProfileDb { function prepare($sql){return new ProfileStmt($sql);} function begin_transaction(){} function commit(){$GLOBALS['committed']=true;} function rollback(){} }
$conn=new ProfileDb();
`);
    for (const valid of [true,false]) {
      const code = `session_start(); $_SESSION=['user_id'=>7,'logged_in'=>true,'role'=>'farmer','full_name'=>'Old Name','vayuma_csrf_token'=>'known-token']; $_SERVER['REQUEST_METHOD']='POST'; $_POST=['csrf_token'=>${JSON.stringify(valid?'known-token':'wrong')},'full_name'=>'New Name','phone'=>'0712345678','email'=>'new@example.com','return_to'=>'farmer_dashboard.php']; register_shutdown_function(function(){fwrite(STDERR,"\\nRESULT=".json_encode(['saved'=>$GLOBALS['saved']??null,'committed'=>$GLOBALS['committed']??false,'session'=>$_SESSION]));}); require ${JSON.stringify(path.join(dir,'profile.php'))};`;
      const r = spawnSync(php, ['-r',code], {encoding:'utf8',env:{...process.env,VAYUMA_AVATAR_STORAGE:'',VAYUMA_AVATAR_STORAGE_PERSISTENT:'0'}});
      assert.equal(r.status,0,r.stderr);
      const marker = r.stderr.lastIndexOf('RESULT='); assert.notEqual(marker,-1,r.stderr);
      const result = JSON.parse(r.stderr.slice(marker+7));
      if (valid) {
        assert.equal(result.committed,true);
        assert.deepEqual(result.saved,['New Name','0712345678','new@example.com',7]);
        assert.equal(result.session.full_name,'New Name');
      } else {
        assert.equal(result.committed,false); assert.equal(result.saved,null);
        assert.match(r.stdout,/Your session expired/);
      }
    }
    for (const [oldPhone, allow, blocked] of [
      ['0700000000', '+254712345678', true],
      ['0711111111', '0711111111', true],
      ['0712345678', '+254712345678', false],
      ['0700000000', '0711111111', false],
    ]) {
      const code = `session_start(); $_SESSION=['user_id'=>7,'logged_in'=>true,'role'=>'farmer','vayuma_csrf_token'=>'known-token']; $_SERVER['REQUEST_METHOD']='POST'; $_POST=['csrf_token'=>'known-token','full_name'=>'New Name','phone'=>'0712345678','email'=>'new@example.com']; register_shutdown_function(function(){fwrite(STDERR,"\\nRESULT=".json_encode(['saved'=>$GLOBALS['saved']??null]));}); require ${JSON.stringify(path.join(dir,'profile.php'))};`;
      const r = spawnSync(php, ['-r',code], {encoding:'utf8',env:{...process.env,TEST_OLD_PHONE:oldPhone,VAYUMA_ONBOARDING_PHONES:allow,VAYUMA_ONBOARDING_ENABLED:'0'}});
      assert.equal(r.status,0,r.stderr);
      const result=JSON.parse(r.stderr.slice(r.stderr.lastIndexOf('RESULT=')+7));
      assert.equal(result.saved === null, blocked);
      if (blocked) assert.match(r.stdout,/Staff phone changes require support/);
    }
  } finally { fs.rmSync(dir,{recursive:true,force:true}); }
});
