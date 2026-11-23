<?php
session_start();
header('Cache-Control: private, no-store');
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/role_routes.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/subscription_lib.php';
if (($_SESSION['logged_in'] ?? false) !== true || (int)($_SESSION['user_id'] ?? 0) <= 0) { header('Location: login.php'); exit; }
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !vayuma_csrf_valid()) { http_response_code(403); exit('Request could not be verified.'); }
try {
    $uid = (int)$_SESSION['user_id']; $role = (string)($_SESSION['role'] ?? '');
    $sub = ensure_user_subscription($conn, $uid, $role);
    if (!$sub) throw new RuntimeException('Subscription unavailable');
    // The month is a Nairobi calendar month; the deadline is stored in UTC.
    $month = (new DateTimeImmutable('now', new DateTimeZone('Africa/Nairobi')))->format('Y-m');
    $stmt = $conn->prepare("UPDATE user_subscriptions s JOIN subscription_plans p ON p.id=s.plan_id
        SET s.dashboard_free_month=?,s.dashboard_free_until_utc=DATE_ADD(UTC_TIMESTAMP(), INTERVAL 24 HOUR)
        WHERE s.user_id=? AND p.role_key=? AND p.plan_type='paid' AND p.is_active=1
          AND (s.dashboard_free_month IS NULL OR s.dashboard_free_month<>?)
          AND (s.dashboard_free_until_utc IS NULL OR s.dashboard_free_until_utc<=UTC_TIMESTAMP())
          AND NOT (s.status='active' AND s.expires_at IS NOT NULL AND s.expires_at>NOW())
          AND NOT (s.status='trialing' AND s.trial_ends_at IS NOT NULL AND s.trial_ends_at>NOW())");
    if (!$stmt) throw new RuntimeException('Access unavailable');
    $stmt->bind_param('siss', $month, $uid, $role, $month);
    if (!$stmt->execute()) throw new RuntimeException('Access unavailable');
    $changed = $stmt->affected_rows; $stmt->close();
    $_SESSION['trial_notice'] = $changed === 1 ? 'Your 24-hour dashboard access has started. Other subscription features remain restricted.' : 'This month’s free dashboard access is unavailable or already used.';
} catch (Throwable $e) {
    error_log('VAYUMA free dashboard access unavailable.');
    $_SESSION['trial_notice'] = 'Dashboard access is temporarily unavailable. Please try again.';
}
header('Location: subscription.php'); exit;
