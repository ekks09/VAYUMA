const {test} = require('node:test');
const assert = require('node:assert/strict');
const {spawnSync} = require('node:child_process');
const path = require('node:path');
const php = process.env.VAYUMA_PHP || 'php';
const policy = path.resolve(__dirname, '../vayuma/includes/onboarding_policy.php');
function run(code) {
  const r = spawnSync(php, ['-r', `require ${JSON.stringify(policy)}; ${code}`], {encoding:'utf8'});
  assert.equal(r.status, 0, r.stderr);
  return JSON.parse(r.stdout);
}
test('onboarding privileges are a separate explicit staff allowlist', () => {
  assert.deepEqual(run("echo json_encode([vayuma_onboarding_phone_allowed('+254 712 345 678','0712345678, 0733333333'),vayuma_onboarding_phone_allowed('0712345679','0712345678'),vayuma_onboarding_phone_allowed('',''),vayuma_onboarding_phone_allowed('0712345678','0712345678abc')]);"), [true,false,false,false]);
});
test('Kenyan phone formats normalize to a shared identity; invalid phones fail', () => {
  assert.deepEqual(run("echo json_encode([vayuma_onboarding_phone('0712 345 678'),vayuma_onboarding_phone('+254-712-345-678'),vayuma_onboarding_phone('254712345678'),vayuma_onboarding_phone('abc')]);"), ['0712345678','0712345678','0712345678',null]);
});
test('requires consent and farmer fields; non-farmers do not invent farm data', () => {
  const data = run(`$base=['full_name'=>'Test Person','phone'=>'0712345678','county'=>'Nairobi','role'=>'farmer','activity'=>'Maize','farmer_id'=>'12345678','farmer_location'=>'Example village','farm_size'=>'2.5','farm_size_unit'=>'acres','preferred_language'=>'sw','consent'=>'yes']; $noConsent=$base; unset($noConsent['consent']); $badSize=$base; $badSize['farm_size']='-2'; $buyer=$base; $buyer['role']='buyer'; $buyer['activity']=''; $buyer['farm_size']=''; $buyer['farm_size_unit']=''; echo json_encode([vayuma_onboarding_validate($base,['Nairobi']),vayuma_onboarding_validate($noConsent,['Nairobi']),vayuma_onboarding_validate($badSize,['Nairobi']),vayuma_onboarding_validate($buyer,['Nairobi'])]);`);
  assert.equal(data[0][1], null); assert.equal(data[0][0].phone, '0712345678');
  assert.match(data[1][1], /agreed/); assert.match(data[2][1], /farm size/);
  assert.equal(data[3][1], null); assert.equal(data[3][0].farm_size, null);
});
