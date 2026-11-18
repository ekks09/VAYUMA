<?php
/**
 * VAYUMA — Subscription Payment Status Polling Endpoint
 * AJAX endpoint for checking payment status after M-Pesa/Paystack initiation.
 * Returns JSON with the current payment status.
 */
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../mpesa_lib.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not logged in']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$paymentId = (int)($_GET['payment_id'] ?? 0);

if ($paymentId < 1) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid payment ID']);
    exit;
}

// Verify the payment belongs to this user
$stmt = $conn->prepare(
    'SELECT sp.id, sp.status, sp.user_id, s.id AS subscription_id, s.plan_id, s.status AS sub_status,
            p.stakeholder_name, p.monthly_price, p.plan_type, p.billing_interval
     FROM subscription_payments sp
     INNER JOIN user_subscriptions s ON s.id = sp.subscription_id
     INNER JOIN subscription_plans p ON p.id = s.plan_id
     WHERE sp.id = ? AND sp.user_id = ?
     LIMIT 1'
);
$stmt->bind_param('ii', $paymentId, $userId);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Payment not found']);
    exit;
}

$status = $payment['status'] ?? 'pending';
$queriedAt = $_SESSION['mpesa_subscription_query_at'][$paymentId] ?? 0;
if ($status === 'pending' && (time() - (int)$queriedAt) >= 8) {
    $_SESSION['mpesa_subscription_query_at'][$paymentId] = time();
    $refreshed = mpesa_refresh_subscription_payment($conn, $paymentId);
    if ($refreshed !== 'missing') {
        $status = $refreshed;
    }
}

echo json_encode([
    'ok' => true,
    'status' => $status,
    'saved' => $status === 'paid',
    'subscription_id' => (int)$payment['subscription_id'],
    'plan_name' => $payment['stakeholder_name'] ?? 'Unknown',
    'amount' => (float)($payment['monthly_price'] ?? 0),
    'plan_type' => $payment['plan_type'] ?? 'paid',
    'billing_interval' => $payment['billing_interval'] ?? 'monthly',
]);
