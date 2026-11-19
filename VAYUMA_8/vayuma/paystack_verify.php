<?php
require_once __DIR__ . '/includes/payment_gate.php';
vayuma_require_payment_processing();
session_start();
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/paystack.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$reference = trim($_GET['reference'] ?? '');
$orderId = (int)($_GET['order_id'] ?? 0);
if ($reference === '') {
    die('Missing Paystack reference.');
}

// Verify with Paystack (authoritative check)
try {
    $data = paystack_verify($reference);
} catch (Throwable $e) {
    $_SESSION['paystack_error'] = 'Paystack verification failed: ' . $e->getMessage();
    header('Location: payment.php?order_id=' . $orderId);
    exit;
}

$status = strtolower((string)($data['status'] ?? ''));
$amount = (float)($data['amount'] ?? 0) / 100; // Paystack returns amount in kobo/cents
$channel = $data['channel'] ?? '';
$paidAt = $data['paid_at'] ?? null;
$txRef = $data['reference'] ?? '';
$fees = (float)($data['fees'] ?? 0) / 100;

// Locate the payment record for this order
$stmt = $conn->prepare(
    "SELECT id, order_id, amount, status, paystack_reference FROM payments
     WHERE paystack_reference = ? AND order_id = ? LIMIT 1"
);
$stmt->bind_param('si', $txRef, $orderId);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) {
    $_SESSION['paystack_error'] = 'No matching payment record found for this order.';
    header('Location: payment.php?order_id=' . $orderId);
    exit;
}

if ($payment['status'] === 'paid') {
    header('Location: payment_success.php?order_id=' . $orderId);
    exit;
}

// Update payment row
$conn->begin_transaction();
try {
    if ($status === 'success') {
        // Amount cross-check (Paystack amount is in minor units; compare cents)
        if (abs(($amount * 100) - (float)$payment['amount'] * 100) > 1) {
            throw new RuntimeException('Paystack amount does not match order amount.');
        }
        $stmt = $conn->prepare(
            "UPDATE payments SET status='paid', transaction_reference=?, paystack_reference=?,
             paystack_fees=?, paid_at=?, callback_received_at=NOW()
             WHERE id = ? AND order_id = ?"
        );
        $stmt->bind_param('ssdssii', $txRef, $txRef, $fees, $paidAt, $payment['id'], $orderId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare(
            "UPDATE orders SET status = 'paid' WHERE id = ? AND status = 'accepted'"
        );
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare(
            "UPDATE payments SET status='failed', paystack_reference=? WHERE id=? AND order_id=? AND status='pending'"
        );
        $stmt->bind_param('sii', $txRef, $payment['id'], $orderId);
        $stmt->execute();
        $stmt->close();
    }
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    $_SESSION['paystack_error'] = 'Payment could not be finalized: ' . $e->getMessage();
    header('Location: payment.php?order_id=' . $orderId);
    exit;
}

header('Location: payment_success.php?order_id=' . $orderId);
exit;
