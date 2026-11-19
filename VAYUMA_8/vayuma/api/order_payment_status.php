<?php
/**
 * VAYUMA — Order Payment Status Polling Endpoint
 * AJAX endpoint for checking order payment status after M-Pesa STK Push initiation.
 * Returns JSON with the current payment status.
 */
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/payment_gate.php';

header('Content-Type: application/json');
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not logged in']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$orderId = (int)($_GET['order_id'] ?? 0);

if ($orderId < 1) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid order']);
    exit;
}

// Verify the payment belongs to this user
$stmt = $conn->prepare(
    'SELECT p.id, p.status, p.amount, p.payment_method
     FROM payments p
     WHERE p.order_id=? AND p.payer_id=?
     ORDER BY p.id DESC
     LIMIT 1'
);
$stmt->bind_param('ii', $orderId, $userId);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Payment not found']);
    exit;
}

$status = (string)$payment['status'];
$paymentId = (int)$payment['id'];

// Optional: poll Safaricom for final status if still pending
$queriedAt = $_SESSION['mpesa_order_query_at'][$paymentId] ?? 0;
if (vayuma_payment_processing_enabled() && $status === 'pending' && (time() - (int)$queriedAt) >= 8) {
    $_SESSION['mpesa_order_query_at'][$paymentId] = time();
    require_once __DIR__ . '/../mpesa_lib.php';
    $status = mpesa_refresh_order_payment($conn, $paymentId) ?? $status;
}

echo json_encode([
    'ok' => true,
    'status' => $status,
    'paid' => $status === 'paid',
    'order_id' => $orderId,
    'amount' => (float)($payment['amount'] ?? 0),
]);
