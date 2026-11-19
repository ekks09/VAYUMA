<?php
require_once __DIR__ . '/includes/payment_gate.php';
vayuma_require_payment_processing();
/**
 * VAYUMA — M-Pesa STK Push initiation for marketplace orders.
 * Customer enters phone number → STK push sent → PIN entered on phone → callback confirms.
 */
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/mpesa_lib.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !vayuma_csrf_valid()) {
    $_SESSION['mpesa_error'] = 'Your payment session expired. Reload the page and enter your M-Pesa number again.';
    header('Location: my_orders.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($orderId === false || $orderId === null) {
    $_SESSION['mpesa_error'] = 'Invalid order. Return to your orders and select a payment option.';
    header('Location: my_orders.php');
    exit;
}

try {
    $phone = normalize_mpesa_phone(trim((string)($_POST['phone'] ?? '')));
} catch (Throwable $exception) {
    $_SESSION['mpesa_error'] = $exception->getMessage();
    header('Location: payment.php?order_id=' . $orderId);
    exit;
}

// Verify the order belongs to this user and is ready for payment
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
    header('Location: payment.php?order_id=' . $orderId);
    exit;
}

$sellerId = (int)$order['seller_id'];
$amount = (float)$order['total_amount'];
$paymentId = 0;
$transactionOpen = false;

try {
    $conn->begin_transaction();
    $transactionOpen = true;

    // Lock any existing pending payment for this order
    $stmt = $conn->prepare(
        'SELECT id, TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age_seconds
         FROM payments WHERE order_id=? AND payment_method=\'mpesa\' AND status=\'pending\'
         ORDER BY id DESC LIMIT 1 FOR UPDATE'
    );
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $pending = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($pending && (int)$pending['age_seconds'] < 90) {
        throw new RuntimeException('An M-Pesa prompt is already on its way. Check the phone and enter your PIN.');
    }
    if ($pending) {
        // Replace stale pending payment
        $stmt = $conn->prepare(
            "UPDATE payments SET status='failed', result_description='Replaced by a new M-Pesa prompt.'
             WHERE id=? AND status='pending'"
        );
        $stmt->bind_param('i', $pending['id']);
        $stmt->execute();
        $stmt->close();
    }

    // Insert new pending payment
    $stmt = $conn->prepare(
        "INSERT INTO payments
         (order_id, payer_id, seller_id, amount, payment_method, phone_number, status)
         VALUES (?, ?, ?, ?, 'mpesa', ?, 'pending')"
    );
    $stmt->bind_param('iiids', $orderId, $userId, $sellerId, $amount, $phone);
    $stmt->execute();
    $paymentId = (int)$stmt->insert_id;
    $stmt->close();

    $conn->commit();
    $transactionOpen = false;
} catch (Throwable $exception) {
    if ($transactionOpen) {
        $conn->rollback();
    }
    $_SESSION['mpesa_error'] = $exception->getMessage();
    header('Location: payment.php?order_id=' . $orderId);
    exit;
}

// Initiate M-Pesa STK Push with Safaricom
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
        'UPDATE payments
         SET merchant_request_id=?, checkout_request_id=?, phone_number=?, amount=?
         WHERE id=?'
    );
    $stmt->bind_param('sssdi', $merchant, $checkout, $normalizedPhone, $chargedAmount, $paymentId);
    $stmt->execute();
    $stmt->close();

    header('Location: mpesa_pending.php?order_id=' . $orderId);
    exit;
} catch (Throwable $exception) {
    $message = substr($exception->getMessage(), 0, 255);
    $stmt = $conn->prepare(
        "UPDATE payments SET status='failed', result_description=?
         WHERE id=? AND order_id=? AND status='pending'"
    );
    $stmt->bind_param('sii', $message, $paymentId, $orderId);
    $stmt->execute();
    $stmt->close();
    $_SESSION['mpesa_error'] = $exception->getMessage();
    header('Location: payment.php?order_id=' . $orderId);
    exit;
}
