<?php
session_start();
header('Cache-Control: private, no-store');
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/subscription_lib.php';
if (($_SESSION['logged_in'] ?? false) !== true || (int)($_SESSION['user_id'] ?? 0) <= 0) {
    header('Location: login.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !vayuma_csrf_valid()) {
    http_response_code(403); exit('Request could not be verified.');
}
try {
    $userId = (int)$_SESSION['user_id'];
    $role = (string)($_SESSION['role'] ?? '');
    $sub = ensure_user_subscription($conn, $userId, $role);
    if (!$sub) throw new RuntimeException('Plan unavailable');
    // A single conditional write serializes simultaneous claims. The flag is
    // retained when a member changes roles, so changing plans cannot reset it.
    $stmt = $conn->prepare("UPDATE user_subscriptions s JOIN subscription_plans p ON p.id=s.plan_id
        SET s.has_used_trial=1, s.trial_started_at=NOW(), s.trial_ends_at=DATE_ADD(NOW(), INTERVAL 30 DAY),
            s.status='trialing', s.updated_at=NOW()
        WHERE s.user_id=? AND p.role_key=? AND p.is_active=1 AND p.plan_type='paid'
          AND s.has_used_trial=0 AND NOT (s.status='active' AND s.expires_at IS NOT NULL AND s.expires_at>NOW())");
    if (!$stmt) throw new RuntimeException('Trial unavailable');
    $stmt->bind_param('is', $userId, $role);
    if (!$stmt->execute()) throw new RuntimeException('Trial unavailable');
    $_SESSION['trial_notice'] = $stmt->affected_rows === 1
        ? 'Your 30-day trial has started.' : 'A trial is not available for this account.';
    $stmt->close();
} catch (Throwable $e) {
    error_log('Trial claim unavailable: ' . $e->getMessage());
    $_SESSION['trial_notice'] = 'Trial activation is temporarily unavailable. Please try again later.';
}
header('Location: subscription.php'); exit;
