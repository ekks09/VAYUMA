const {test} = require('node:test');
const assert = require('node:assert/strict');
const {spawnSync} = require('node:child_process');
const path = require('node:path');
const fs = require('node:fs');
const root = path.resolve(__dirname, '../vayuma');
const php = process.env.VAYUMA_PHP || 'php';
const run = code => { const p=spawnSync(php,['-r',code],{encoding:'utf8'}); assert.equal(p.status,0,p.stderr); return JSON.parse(p.stdout); };
const read = file => fs.readFileSync(path.join(root,file),'utf8');
test('farmer onboarding requires both ID and a location', () => {
 const code=`require ${JSON.stringify(path.join(root,'includes/onboarding_policy.php'))}; $a=['full_name'=>'Test Person','phone'=>'0712345678','county'=>'Nairobi','role'=>'farmer','activity'=>'Maize','farm_size'=>'2','farm_size_unit'=>'acres','preferred_language'=>'en','consent'=>'yes','farmer_id'=>'12345678','farmer_location'=>'Sample village']; $b=$a; unset($b['farmer_id']); $c=$a; unset($c['farmer_location']); echo json_encode([vayuma_onboarding_validate($a,['Nairobi'])[1],vayuma_onboarding_validate($b,['Nairobi'])[1],vayuma_onboarding_validate($c,['Nairobi'])[1]]);`;
 const [ok,id,location]=run(code); assert.equal(ok,null); assert.match(id,/ID/); assert.match(location,/location/);
});
test('dashboard free-day predicate does not grant paid subscription entitlement', () => {
 const code=`require ${JSON.stringify(path.join(root,'lib/subscription_lib.php'))}; $s=['plan_type'=>'paid','status'=>'expired','dashboard_free_until_utc'=>gmdate('Y-m-d H:i:s', time()+3600)]; echo json_encode([subscription_dashboard_day_accessible($s), subscription_is_accessible($s),subscription_dashboard_day_accessible(['plan_type'=>'paid','dashboard_free_until_utc'=>'2000-01-01 00:00:00'])]);`;
 assert.deepEqual(run(code),[true,false,false]);
});
test('export requires unlock and CSRF, and sanitizes spreadsheet formulas', () => {
 const s=read('farmer_export.php'); assert.match(s,/onboarding_unlocked_user_id/); assert.match(s,/onboarding_key_fingerprint/); assert.match(s,/vayuma_csrf_valid/); assert.match(s,/vayuma_csv_cell/); assert.match(s,/claimed_by_user_id IS NULL/); assert.match(s,/vayuma_farmer_id_decrypt/);
});
test('ID key failure is closed and fields are encrypted before persistence', () => {
 assert.match(read('includes/farmer_identity.php'),/sodium_crypto_secretbox\(/);
 assert.match(read('auth/save_farmer.php'),/vayuma_farmer_id_encrypt\(\$farmerId\)/);
 assert.match(read('onboarding.php'),/vayuma_farmer_id_encrypt\(\$data\['farmer_id'\]\)/);
});
