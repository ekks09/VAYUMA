<?php
/*
 * VAYUMA subscription access guard.
 *
 * Enforcement is enabled by default. Set SUBSCRIPTION_ENFORCEMENT=0 only for
 * local development before the subscription migrations have been applied.
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/subscription_lib.php';

if (!defined('SUBSCRIPTION_ENFORCEMENT')) {
    define('SUBSCRIPTION_ENFORCEMENT', getenv('SUBSCRIPTION_ENFORCEMENT') !== '0');
}

if (
    SUBSCRIPTION_ENFORCEMENT === true &&
    isset($_SESSION['user_id']) &&
    ($_SESSION['logged_in'] ?? false) === true
) {
    try {
        $subscription = ensure_user_subscription(
            $conn,
            (int) $_SESSION['user_id'],
            (string) ($_SESSION['role'] ?? '')
        );
    } catch (Throwable $exception) {
        error_log('Subscription access check unavailable: ' . $exception->getMessage());
        http_response_code(503);
        exit('Subscription access is temporarily unavailable. Please try again later.');
    }

    require_once __DIR__ . '/includes/role_routes.php';
    $ownDashboard = vayuma_role_route((string)($_SESSION['role'] ?? ''), 'dashboard');
    $requestedPage = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $isDashboard = $ownDashboard !== null && ($requestedPage === $ownDashboard || $requestedPage === 'dashboard.php');
    if (!$subscription || (!subscription_is_accessible($subscription) && !($isDashboard && subscription_dashboard_day_accessible($subscription)))) {
        header('Location: subscription.php');
        exit;
    }
}
