const {test}=require('node:test'), assert=require('node:assert/strict'), fs=require('node:fs'), path=require('node:path');
const root=path.join(__dirname,'../vayuma'), read=f=>fs.readFileSync(path.join(root,f),'utf8');
test('claiming is gated and normal signup points unclaimed farmers to code verification',()=>{
 const registration=read('auth/register_process.php');
 assert.match(registration,/vayuma_onboarding_lock_phone/); assert.match(registration,/onboarding_records WHERE phone_identity=\?/); assert.doesNotMatch(registration,/This phone has an onboarding record/);
 assert.match(read('register.php'),/href="claim_account\.php"/);
 const claim=read('claim_account.php'); assert.match(claim,/hash_equals/); assert.match(claim,/claim_attempts.*< 5/); assert.match(claim,/claimed_by_user_id IS NULL/); assert.match(claim,/claim_code_hash=NULL/);
});
test('claimed profile prefill is authenticated and does not overwrite an existing profile',()=>{
 const profile=read('profile_setup.php'); assert.match(profile,/\!\$farmProfile && \!\$profileDuplicate/); assert.match(profile,/claimed_by_user_id=\?/); assert.match(profile,/\$claim->bind_param\('i', \$user_id\)/); assert.match(profile,/'main_crops'=>\$claimed\['activity'\]/);
 const login=read('auth/login.php'); assert.match(login,/password_verify/); assert.ok(login.indexOf('password_verify')<login.indexOf('claimed_by_user_id')); assert.match(login,/NOT EXISTS \(SELECT 1 FROM farmer_profiles/);
});
