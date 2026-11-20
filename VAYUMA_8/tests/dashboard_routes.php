<?php
require __DIR__ . '/../vayuma/includes/role_routes.php';
function expect($a, $b, $context) { if ($a !== $b) { fwrite(STDERR, "FAIL $context: " . var_export($a,true) . "\n"); exit(1); } }
$roles = ['farmer','buyer','consumer','transport_provider','researcher','extension_officer','cooperative','financial_institution','input_supplier','manufacturer','warehouse_provider','retailer','agribusiness','veterinary_officer','insurance_provider','government_officer','ngo_partner','agricultural_service_provider','strategic_partner'];
foreach ($roles as $role) {
    $_SESSION = ['user_id'=>1,'logged_in'=>true,'role'=>$role];
    $dashboard = vayuma_role_route($role, 'dashboard');
    if (!$dashboard || !is_file(__DIR__ . '/../vayuma/' . $dashboard)) { fwrite(STDERR, "Missing dashboard for $role\n"); exit(1); }
    expect(vayuma_dashboard_return_route(), $dashboard, "$role dashboard");
    expect(vayuma_profile_hub_return_route('subscription.php'), 'subscription.php', "$role hub subscription");
    expect(vayuma_profile_hub_return_route($dashboard), $dashboard, "$role hub dashboard");
    foreach (['https://evil.example','//evil.example','../subscription.php',['subscription.php']] as $bad) expect(vayuma_profile_hub_return_route($bad), $dashboard, "$role hub invalid");
    expect(vayuma_profile_return_navigation($dashboard), [$dashboard,'Back to dashboard'], "$role profile dashboard");
    expect(vayuma_profile_return_navigation('subscription.php'), ['subscription.php','Back to subscription'], "$role profile subscription");
    foreach (['https://evil.example','//evil.example','buyer_dashboard.php?x=1','../subscription.php','role.php',null, ['subscription.php']] as $invalid) {
        expect(vayuma_profile_return_navigation($invalid), [$dashboard,'Back to dashboard'], "$role untrusted profile origin");
    }
    if ($role !== 'buyer') expect(vayuma_profile_return_navigation('buyer_dashboard.php'), [$dashboard,'Back to dashboard'], "$role cross-role origin");
}
$_SESSION=['user_id'=>1,'logged_in'=>true,'role'=>'unknown'];
expect(vayuma_dashboard_return_route(),'role.php','unknown role');
$_SESSION=['user_id'=>1,'role'=>'farmer'];
expect(vayuma_dashboard_return_route(),'login.php','stale session');
expect(vayuma_profile_return_navigation('subscription.php'),['login.php','Back to login'],'stale profile');
$_SESSION=[];
expect(vayuma_dashboard_return_route(),'login.php','anonymous');
echo "PASS all 19 role routes, profile whitelisting and session fallback\n";
