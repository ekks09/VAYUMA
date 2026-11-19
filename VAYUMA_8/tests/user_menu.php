<?php
require __DIR__ . '/../vayuma/includes/user_menu_data.php';
function check($condition, $message) { if (!$condition) { fwrite(STDERR, "FAIL: $message\n"); exit(1); } }
check(vayuma_user_initials('Mary Wanjiku') === 'MW', 'initials');
check(vayuma_menu_plan(['status'=>'free_account'])[1] === 'active', 'free status');
check(vayuma_menu_plan(['status'=>'active','expires_at'=>'2000-01-01'])[0] === 'Expired', 'expired active status');
check(vayuma_menu_plan(['status'=>'payment_due','grace_period_ends_at'=>'2999-01-01'])[0] === 'Grace Period', 'grace status');
check(vayuma_menu_plan(['status'=>'payment_due','grace_period_ends_at'=>'2000-01-01'])[0] === 'Expired', 'elapsed grace status');
check(vayuma_menu_plan(['status'=>'negotiated_plan','negotiated_approved'=>0])[1] === 'unavailable', 'unapproved negotiated');
check(vayuma_menu_plan(null)[0] === 'Unavailable', 'missing plan');
$now = strtotime('2026-01-01 00:00:00 UTC');
check(vayuma_menu_plan(['status'=>'payment_due','grace_period_ends_at'=>'2026-01-02 00:00:00 UTC'], $now)[1] === 'grace', 'fixed-time grace');
check(vayuma_menu_plan(['status'=>'payment_due','grace_period_ends_at'=>'2025-12-31 00:00:00 UTC'], $now)[0] === 'Expired', 'fixed-time expired grace');
check(vayuma_menu_plan(['status'=>'active','expires_at'=>'2026-01-02 00:00:00 UTC'], $now)[0] === 'Active', 'fixed-time active');
check(vayuma_menu_plan(['status'=>'active','expires_at'=>'2025-12-31 00:00:00 UTC'], $now)[0] === 'Expired', 'fixed-time expired active');
$_SESSION = ['user_id'=>1,'logged_in'=>true,'role'=>'consumer','full_name'=>'<script>alert(1)</script>'];
ob_start(); require __DIR__ . '/../vayuma/includes/header_user_menu.php'; $html=ob_get_clean();
check(strpos($html,'<script>alert') === false, 'escape name');
check(strpos($html,'profile.php?return_to=consumer_dashboard.php') !== false, 'profile return');
check(strpos($html,'auth/logout.php') !== false, 'logout');
$_SERVER['SCRIPT_NAME'] = '/subscription.php';
ob_start(); require __DIR__ . '/../vayuma/includes/header_user_menu.php'; $subscriptionHtml=ob_get_clean();
check(strpos($subscriptionHtml,'profile.php?return_to=subscription.php') !== false, 'subscription origin');
echo "PASS menu status, initials, escaping and role return\n";
