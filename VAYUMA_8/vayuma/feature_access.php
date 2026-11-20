<?php
session_start();
require_once __DIR__.'/includes/role_routes.php';
$destinations = ['climate'=>'climate.php','soil'=>'soil_analysis.php','ai'=>'ai_advisor.php'];
$key = is_string($_GET['feature'] ?? null) ? $_GET['feature'] : '';
if (!isset($destinations[$key])) { http_response_code(404); exit('Feature not found.'); }
$_SESSION['pending_feature'] = $key;
if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) { header('Location: login.php'); exit; }
$role = strtolower(trim((string)($_SESSION['role'] ?? '')));
if (vayuma_role_route($role, 'dashboard') === null) { header('Location: role.php'); exit; }
require_once __DIR__.'/config/db.php';
// Honour onboarding before subscription checks; table names are a server-side allowlist.
$profileTables = ['farmer'=>'farmer_profiles', 'buyer'=>'buyer_profiles', 'consumer'=>'consumer_profiles', 'transport_provider'=>'transport_profiles', 'cooperative'=>'cooperative_profiles', 'extension_officer'=>'extension_profiles', 'researcher'=>'researcher_profiles', 'financial_institution'=>'financial_profiles'];
try {
    $uid=(int)$_SESSION['user_id'];
    if (isset($profileTables[$role])) {
        $stmt=$conn->prepare('SELECT user_id FROM '.$profileTables[$role].' WHERE user_id=? LIMIT 1');
        if (!$stmt) throw new RuntimeException();
        $stmt->bind_param('i',$uid);
    } else {
        $stmt=$conn->prepare('SELECT profile_completed FROM stakeholder_profiles WHERE user_id=? AND role=? LIMIT 1');
        if (!$stmt) throw new RuntimeException();
        $stmt->bind_param('is',$uid,$role);
    }
    if (!$stmt->execute()) throw new RuntimeException();
    $row=$stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$row || (!isset($profileTables[$role]) && (int)($row['profile_completed'] ?? 0)!==1)) { header('Location: '.vayuma_role_route($role,'profile')); exit; }
} catch (Throwable $ignored) { http_response_code(503); exit('Unable to check account setup. Please try again later.'); }
require_once __DIR__.'/subscription_guard.php';
unset($_SESSION['pending_feature']);
header('Location: '.$destinations[$key]);
exit;
