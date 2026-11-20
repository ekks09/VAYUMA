<?php
require_once __DIR__ . '/role_routes.php';
/** Same authenticated profile and subscription decision for gateway, page and API. */
function vayuma_feature_access_decision($conn, array $session): string
{
    if (($session['logged_in'] ?? false) !== true || (int)($session['user_id'] ?? 0) <= 0) return 'login';
    $role = strtolower(trim((string)($session['role'] ?? '')));
    if (vayuma_role_route($role, 'dashboard') === null) return 'role';
    $tables = ['farmer'=>'farmer_profiles', 'buyer'=>'buyer_profiles', 'consumer'=>'consumer_profiles', 'transport_provider'=>'transport_profiles', 'cooperative'=>'cooperative_profiles', 'extension_officer'=>'extension_profiles', 'researcher'=>'researcher_profiles', 'financial_institution'=>'financial_profiles'];
    $uid = (int)$session['user_id'];
    if (isset($tables[$role])) {
        $stmt = $conn->prepare('SELECT user_id FROM '.$tables[$role].' WHERE user_id=? LIMIT 1');
        if (!$stmt) throw new RuntimeException('Profile unavailable');
        $stmt->bind_param('i', $uid);
    } else {
        $stmt = $conn->prepare('SELECT profile_completed FROM stakeholder_profiles WHERE user_id=? AND role=? LIMIT 1');
        if (!$stmt) throw new RuntimeException('Profile unavailable');
        $stmt->bind_param('is', $uid, $role);
    }
    if (!$stmt->execute()) throw new RuntimeException('Profile unavailable');
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$row || (!isset($tables[$role]) && (int)($row['profile_completed'] ?? 0)!==1)) return 'profile';
    if (getenv('SUBSCRIPTION_ENFORCEMENT') !== '0') {
        require_once __DIR__ . '/../lib/subscription_lib.php';
        if (!subscription_is_accessible(ensure_user_subscription($conn, $uid, $role))) return 'subscription';
    }
    return 'allow';
}
function vayuma_feature_access_destination(string $decision, array $session): string
{
    if ($decision === 'profile') return vayuma_role_route((string)($session['role'] ?? ''), 'profile') ?? 'role.php';
    return ['login'=>'login.php','role'=>'role.php','subscription'=>'subscription.php'][$decision] ?? 'login.php';
}
