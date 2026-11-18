<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/payment_feedback.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !vayuma_csrf_valid()) {
    vayuma_payment_feedback('Payment request expired', 'Refresh the payment page and try again.', 400);
}

$orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($orderId === false || $orderId === null) {
    vayuma_payment_feedback('Invalid order', 'Return to your orders and select a payment option.', 400);
}

$userId = (int)$_SESSION['user_id'];
try {
    $stmt = $conn->prepare('SELECT status FROM orders WHERE id=? AND buyer_id=? LIMIT 1');
    $stmt->bind_param('ii', $orderId, $userId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} catch (Throwable $exception) {
    error_log('Payment routing order lookup failed: ' . $exception->getMessage());
    vayuma_payment_feedback('Payment unavailable', 'We could not load this order. Please try again from My Orders.', 500);
}

if (!$order) {
    vayuma_payment_feedback('Order not found', 'This order is unavailable or does not belong to your account.', 404);
}
if ($order['status'] === 'paid') {
    header('Location: payment_success.php?order_id=' . $orderId);
    exit;
}
if ($order['status'] !== 'accepted') {
    vayuma_payment_feedback('Payment not available', 'This order is not ready for payment. Check its current status in My Orders.', 409);
}

/* VAYUMA now processes new order payments through M-Pesa PayBill only. */
header('Location: payment.php?order_id=' . $orderId);
exit;
