<?php
require_once __DIR__ . '/includes/payment_gate.php';
vayuma_require_payment_processing();
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/payment_feedback.php';
require_once __DIR__ . '/lib/paystack.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}
$orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !vayuma_csrf_valid()) {
    $_SESSION['paystack_error'] = 'The payment request expired. Refresh the page and try again.';
    header('Location: ' . (is_int($orderId) ? 'paystack_payment.php?order_id=' . $orderId : 'my_orders.php'));
    exit;
}

$userId = (int)$_SESSION['user_id'];
$channel  = $_POST['channel'] ?? 'card';
$emailInput = $_POST['email'] ?? '';

if ($orderId === false || $orderId === null) {
    vayuma_payment_feedback('Invalid order', 'Choose an order from My Orders before making payment.', 400);
}
if (!is_string($channel) || !in_array($channel, ['card','bank','ussd','mobile_money','qr','bank_account','paylater'], true)) {
    vayuma_payment_feedback('Invalid payment method', 'Choose a supported Paystack payment method.', 400);
}
if (!is_string($emailInput)) {
    vayuma_payment_feedback('Invalid email address', 'Enter a valid email address for your payment receipt.', 400);
}
$email = trim($emailInput);
// Fetch order + verify ownership + status
$stmt = $conn->prepare(
    "SELECT o.id, o.buyer_id, o.seller_id, o.total_amount, o.status, pl.produce_name
     FROM orders o
     INNER JOIN produce_listings pl ON o.produce_listing_id = pl.id
     WHERE o.id = ? AND o.buyer_id = ?
     LIMIT 1"
);
$stmt->bind_param('ii', $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$order) {
    vayuma_payment_feedback('Order not found', 'This order is unavailable or does not belong to your account.', 404);
}
if ($order['status'] !== 'accepted') {
    vayuma_payment_feedback('Payment not available', 'This order is not ready for payment.', 409);
}
$amount = (float)$order['total_amount'];

// Build mobile money payload if needed
$payload = [
    'email'      => $email,
    'amount'     => $amount,
    'channel'    => $channel,
    'metadata'   => [
        'order_id'  => $orderId,
        'custom_fields' => [
            [
                'display_name' => 'Order',
                'variable_name' => 'order_id',
                'value' => (string)$orderId,
            ],
            [
                'display_name' => 'Produce',
                'variable_name' => 'produce_name',
                'value' => $order['produce_name'],
            ],
        ],
    ],
];
if ($channel === 'mobile_money') {
    $mobileMoneyPhone = $_POST['mm_phone'] ?? '';
    $mobileMoneyProvider = $_POST['mm_provider'] ?? 'MPESA';
    if (!is_string($mobileMoneyPhone) || !is_string($mobileMoneyProvider)) {
        vayuma_payment_feedback('Invalid mobile money details', 'Enter a valid phone number and provider.', 400);
    }
    $payload['mobile_money'] = [
        'phone' => trim($mobileMoneyPhone),
        'provider' => $mobileMoneyProvider,
    ];
}

// Lock/check existing payment (one payment per order)
$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        "SELECT id, status, paystack_reference FROM payments WHERE order_id = ? LIMIT 1 FOR UPDATE"
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
    if ($payment && $payment['status'] === 'pending' && !empty($payment['paystack_reference'])) {
        throw new RuntimeException('A Paystack payment is already pending for this order. Wait for it to complete or use another order.');
    }

    if ($payment) {
        $stmt = $conn->prepare(
            "UPDATE payments SET payer_id=?, seller_id=?, amount=?, payment_method='paystack',
             paystack_channel=?, status='pending', transaction_reference=NULL, paystack_reference=NULL,
             paystack_access_code=NULL, paystack_authorization_url=NULL, paystack_fees=NULL,
             paid_at=NULL WHERE id=?"
        );
        $stmt->bind_param('iissi', $userId, $order['seller_id'], $order['total_amount'], $channel, $payment['id']);
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO payments(order_id, payer_id, seller_id, amount, payment_method, paystack_channel, status)
             VALUES(?, ?, ?, ?, 'paystack', ?, 'pending')"
        );
        $stmt->bind_param('iiids', $orderId, $userId, $order['seller_id'], $order['total_amount'], $channel);
    }
    $stmt->execute();
    $paymentId = $payment['id'] ?? $stmt->insert_id;
    $stmt->close();
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('VAYUMA payment initialization unavailable.');
    $_SESSION['paystack_error'] = 'We could not complete that payment request. Check its status before trying again.';
    header('Location: paystack_payment.php?order_id=' . $orderId);
    exit;
}

// Call Paystack to initialize the transaction
try {
    $data = paystack_initialize($payload);
    $reference = (string)($data['reference'] ?? '');
    $accessCode = (string)($data['access_code'] ?? '');
    $authUrl = (string)($data['authorization_url'] ?? '');

    if ($reference === '') {
        throw new RuntimeException('Paystack did not return a transaction reference.');
    }

    // Store reference / access_code / authorization_url on the payment row
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare(
            "UPDATE payments SET paystack_reference=?, paystack_access_code=?, paystack_authorization_url=?
             WHERE id = ? AND order_id = ?"
        );
        $stmt->bind_param('sssii', $reference, $accessCode, $authUrl, $paymentId, $orderId);
        $stmt->execute();
        $stmt->close();
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }

    // Channels that complete via a Paystack-hosted page -> redirect
    $redirectChannels = ['card', 'mobile_money', 'bank_account'];
    if (in_array($channel, $redirectChannels, true) && $authUrl !== '') {
        header('Location: ' . $authUrl);
        exit;
    }

    // Inline channels -> show payment details (bank transfer, USSD, QR, pay later)
    $_SESSION['paystack_display'] = [
        'payment_id' => $paymentId,
        'order_id'   => $orderId,
        'channel'    => $channel,
        'reference'  => $reference,
        'data'       => $data,
        'amount'     => $amount,
    ];
    header('Location: paystack_display.php?order_id=' . $orderId);
    exit;

} catch (Throwable $e) {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare(
            "UPDATE payments SET status='failed', paystack_reference=NULL WHERE id=? AND order_id=? AND status='pending'"
        );
        $stmt->bind_param('ii', $paymentId, $orderId);
        $stmt->execute();
        $stmt->close();
        $conn->commit();
    } catch (Throwable $e2) {
        $conn->rollback();
    }
    error_log('VAYUMA payment initialization unavailable.');
    $_SESSION['paystack_error'] = 'We could not complete that payment request. Check its status before trying again.';
    header('Location: paystack_payment.php?order_id=' . $orderId);
    exit;
}
