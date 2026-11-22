<?php
require_once __DIR__ . '/includes/payment_gate.php';
vayuma_require_payment_processing();
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/mpesa_lib.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}

$orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$redirect = 'payment.php' . ($orderId ? '?order_id=' . $orderId : '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !vayuma_csrf_valid()) {
    $_SESSION['mpesa_error'] = 'Your payment session expired. Reload the page and enter your M-Pesa number again.';
    header('Location: ' . ($orderId ? $redirect : 'my_orders.php'));
    exit;
}
if ($orderId === false || $orderId === null) {
    header('Location: my_orders.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$rawPhone = trim((string)($_POST['phone'] ?? ''));

try {
    $phone = normalize_mpesa_phone($rawPhone);
} catch (Throwable $exception) {
    error_log('VAYUMA payment request unavailable.');
    $_SESSION['mpesa_error'] = 'We could not complete the payment request. Check its status before trying again.';
    header('Location: ' . $redirect);
    exit;
}

$stmt = $conn->prepare(
    'SELECT id, buyer_id, seller_id, total_amount, status
     FROM orders WHERE id=? AND buyer_id=? LIMIT 1'
);
$stmt->bind_param('ii', $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    $_SESSION['mpesa_error'] = 'This order is unavailable or does not belong to your account.';
    header('Location: my_orders.php');
    exit;
}
if ($order['status'] === 'paid') {
    header('Location: payment_success.php?order_id=' . $orderId);
    exit;
}
if ($order['status'] !== 'accepted') {
    $_SESSION['mpesa_error'] = 'This order is not ready for payment.';
    header('Location: ' . $redirect);
    exit;
}

$sellerId = (int)$order['seller_id'];
$amount = (float)$order['total_amount'];
$paymentId = 0;

$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        'SELECT id, status, checkout_request_id,
                TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age_seconds
         FROM payments WHERE order_id=? LIMIT 1 FOR UPDATE'
    );
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($payment && $payment['status'] === 'paid') {
        $conn->commit();
        header('Location: payment_success.php?order_id=' . $orderId);
        exit;
    }
    if (
        $payment
        && $payment['status'] === 'pending'
        && !empty($payment['checkout_request_id'])
        && (int)$payment['age_seconds'] < 90
    ) {
        throw new RuntimeException('An M-Pesa prompt is already on its way. Check the phone and enter your PIN.');
    }

    if ($payment) {
        $existingPaymentId = (int)$payment['id'];
        $stmt = $conn->prepare(
            "UPDATE payments
             SET payer_id=?, seller_id=?, amount=?, payment_method='mpesa', phone_number=?,
                 status='pending', transaction_reference=NULL, merchant_request_id=NULL,
                 checkout_request_id=NULL, mpesa_receipt_number=NULL, result_code=NULL,
                 result_description=NULL, callback_received_at=NULL, paid_at=NULL, created_at=NOW()
             WHERE id=?"
        );
        $stmt->bind_param('iidsi', $userId, $sellerId, $amount, $phone, $existingPaymentId);
        $stmt->execute();
        $paymentId = (int)$payment['id'];
        $stmt->close();
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO payments
             (order_id, payer_id, seller_id, amount, payment_method, phone_number, status)
             VALUES (?, ?, ?, ?, 'mpesa', ?, 'pending')"
        );
        $stmt->bind_param('iiids', $orderId, $userId, $sellerId, $amount, $phone);
        $stmt->execute();
        $paymentId = (int)$stmt->insert_id;
        $stmt->close();
    }
    $conn->commit();
} catch (Throwable $exception) {
    $conn->rollback();
    error_log('VAYUMA payment request unavailable.');
    $_SESSION['mpesa_error'] = 'We could not complete the payment request. Check its status before trying again.';
    header('Location: ' . $redirect);
    exit;
}

try {
    [$response, $normalizedPhone, $charged] = mpesa_stk_push($orderId, $amount, $phone);
    if ((string)($response['ResponseCode'] ?? '') !== '0') {
        throw new RuntimeException($response['ResponseDescription'] ?? $response['errorMessage'] ?? 'Safaricom did not accept the PayBill request.');
    }
    $merchant = (string)($response['MerchantRequestID'] ?? '');
    $checkout = (string)($response['CheckoutRequestID'] ?? '');
    if ($checkout === '') {
        throw new RuntimeException('Safaricom did not return a checkout reference.');
    }
    $chargedAmount = (float)$charged;
    $stmt = $conn->prepare(
        "UPDATE payments
         SET merchant_request_id=?, checkout_request_id=?, phone_number=?, amount=?, status='pending'
         WHERE id=? AND order_id=?"
    );
    $stmt->bind_param('sssdii', $merchant, $checkout, $normalizedPhone, $chargedAmount, $paymentId, $orderId);
    $stmt->execute();
    $stmt->close();
    header('Location: mpesa_pending.php?order_id=' . $orderId);
    exit;
} catch (Throwable $exception) {
    $message = 'Payment request could not be completed. Check its status before trying again.';
    $stmt = $conn->prepare(
        "UPDATE payments SET status='failed', result_description=? WHERE id=? AND order_id=? AND status='pending'"
    );
    $stmt->bind_param('sii', $message, $paymentId, $orderId);
    $stmt->execute();
    $stmt->close();
    $_SESSION['mpesa_error'] = $message;
    header('Location: ' . $redirect);
    exit;
}
