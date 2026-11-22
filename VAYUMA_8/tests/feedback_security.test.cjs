const {test}=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path');
const root=path.join(__dirname,'../vayuma'),read=f=>fs.readFileSync(path.join(root,f),'utf8');
test('shared notifications are accessible and do not silently disappear',()=>{
 const script=read('frontend/js/feedback.js'),pwa=read('includes/pwa.php');
 assert.match(pwa,/feedback\.js/);assert.match(script,/'alert'/);assert.match(script,/'status'/);assert.doesNotMatch(script,/setTimeout|fetch\(/);
 assert.match(read('register.php'),/class="form-alert" role="alert"/);
});
test('representative profile, saved-list and payment failures avoid raw exception details',()=>{
 for(const f of ['auth/save_buyer.php','auth/save_consumer.php']) assert.doesNotMatch(read(f),/exit\('Could not validate county: '\s*\.\s*\$conn->error/);
 assert.doesNotMatch(read('auth/toggle_saved_item.php'),/'error'\s*=>\s*\$e->getMessage\(\)/);
 assert.doesNotMatch(read('auth/admin_update_verification.php'),/htmlspecialchars\(\$e->getMessage\(\)/);
 for(const f of ['paystack_initialize.php','paystack_verify.php','subscription_stkpush.php','mpesa_stkpush.php','mpesa_stkush.php']) assert.doesNotMatch(read(f),/\$_SESSION\[[^\]]+\]\s*=.*getMessage\(\)/);
 for(const f of ['place_order.php','update_order_status.php']) assert.match(read(f),/instanceof DomainException/);
});
test('staff report distinguishes unavailable from empty while preserving the unlock gate',()=>{
 const source=read('onboarding.php');assert.match(source,/staffCountsAvailable/);assert.match(source,/recordsAvailable && !\$rows/);assert.match(source,/Staff report is temporarily unavailable/);
 assert.doesNotMatch(source,/vayuma_onboarding_authorized/);assert.match(source,/vayuma_onboarding_unlock_attempt/);assert.match(source,/staff-passphrase/);assert.match(source,/onboarding_unlocked_user_id/);
});
