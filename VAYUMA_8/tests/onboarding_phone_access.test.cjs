const {test} = require('node:test');
const assert = require('node:assert/strict');
const {spawnSync} = require('node:child_process');
const path = require('node:path');
const fs = require('node:fs');
const php = process.env.VAYUMA_PHP || 'php';
const root = path.resolve(__dirname, '../vayuma');

test('staff phone access reads current database identity and rejects ambiguity or database errors', () => {
  const code = `require ${JSON.stringify(path.join(root,'includes/onboarding_policy.php'))};
class R {private $rows; function __construct($r){$this->rows=$r;} function fetch_assoc(){return array_shift($this->rows) ?: null;}}
class S {private $q; function __construct($q){$this->q=$q;} function bind_param($t,&...$args){} function execute(){return getenv('MODE')!=='error';} function get_result(){if(strpos($this->q,'SELECT phone FROM users')!==false)return new R(getenv('MODE')==='deleted'?[]:[['phone'=>getenv('PHONE')]]); if(getenv('MODE')==='duplicate')return new R([['id'=>7],['id'=>8]]); if(getenv('MODE')==='wrong_owner')return new R([['id'=>8]]); return new R([['id'=>7]]);} function close(){}}
class C {function prepare($q){return new S($q);}}
echo json_encode(vayuma_onboarding_authorized(new C(),7,getenv('ALLOW')));`;
  for (const [mode,phone,allow,expected] of [
    ['ok','0712345678','+254 712 345 678',true],
    ['ok','0722222222','0712345678',false],
    ['ok','0712345678','',false],
    ['ok','0712345678','0712345678xxx',false],
    ['duplicate','0712345678','0712345678',false],
    ['wrong_owner','0712345678','0712345678',false],
    ['deleted','0712345678','0712345678',false],
    ['error','0712345678','0712345678',false],
  ]) {
    const r=spawnSync(php,['-r',code],{encoding:'utf8',env:{...process.env,MODE:mode,PHONE:phone,ALLOW:allow}});
    assert.equal(r.status,0,r.stderr); assert.equal(JSON.parse(r.stdout),expected,mode);
  }
  const profile=fs.readFileSync(path.join(root,'profile.php'),'utf8');
  assert.match(profile, /vayuma_onboarding_phone_allowed\(\$phone, \$staffPhones\)/);
  assert.match(profile, /Staff phone changes require support/);
});
