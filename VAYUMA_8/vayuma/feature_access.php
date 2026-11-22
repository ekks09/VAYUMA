<?php
session_start();
require_once __DIR__.'/includes/feature_access_policy.php';
$destinations = ['climate'=>'climate.php','soil'=>'soil_analysis.php','ai'=>'ai_advisor.php'];
$key = is_string($_GET['feature'] ?? null) ? $_GET['feature'] : '';
if (!isset($destinations[$key])) { http_response_code(404); exit('Feature not found.'); }
$_SESSION['pending_feature'] = $key;
if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) { header('Location: login.php'); exit; }
// AI is available to every signed-in account; other features retain their access policy.
if ($key === 'ai') { unset($_SESSION['pending_feature']); header('Location: ai_advisor.php'); exit; }
if (vayuma_role_route((string)($_SESSION['role'] ?? ''), 'dashboard') === null) { header('Location: role.php'); exit; }
$vayumaDbReadinessProbe = true;
require_once __DIR__.'/config/db.php';
try {
    if (!isset($conn) || !$conn) throw new RuntimeException('Database unavailable');
    $decision = vayuma_feature_access_decision($conn, $_SESSION);
    if ($decision !== 'allow') { header('Location: '.vayuma_feature_access_destination($decision, $_SESSION)); exit; }
} catch (Throwable $ignored) { http_response_code(503); exit('Unable to check account setup. Please try again later.'); }
unset($_SESSION['pending_feature']);
header('Location: '.$destinations[$key]);
exit;
