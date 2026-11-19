<?php
require_once __DIR__ . '/includes/payment_gate.php';
vayuma_require_payment_processing();
/**
 * VAYUMA Paystack webhook listener.
 *
 * This is the authoritative source of truth for Paystack payments.
 * Register this URL in your Paystack dashboard:
 *   https://YOUR_DOMAIN/paystack_webhook.php
 *
 * Paystack sends POST with JSON body and a x-paystack-signature header.
 * We verify the signature using the secret key, then process the event.
 *
 * Reference: https://paystack.com/docs/api/#webhooks
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/paystack_webhook_security.php';

header('Content-Type: application/json');
http_response_code(200);
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Method not allowed']);
    exit;
}

$paystackConfig = require __DIR__ . '/config/paystack.php';
$secretKey = getenv('PAYSTACK_SECRET_KEY') ?: $paystackConfig['secret_key'];
if (empty($secretKey) || str_starts_with($secretKey, 'YOUR_')) {
    error_log('VAYUMA Paystack webhook: secret key not configured.');
    http_response_code(503);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Not configured']);
    exit;
}

$raw = file_get_contents('php://input');
if (!paystack_verify_webhook_signature($raw, $secretKey, $paystackConfig['webhook_secrets'] ?? [], $_SERVER)) {
    error_log('VAYUMA Paystack webhook rejected an invalid signature.');
    http_response_code(401);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid signature']);
    exit;
}
$GLOBALS['vayuma_paystack_raw_webhook'] = $raw;
$GLOBALS['vayuma_paystack_signature_verified'] = true;

$event = json_decode($raw, true);
if (!is_array($event) || empty($event['event'])) {
    http_response_code(400);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid event']);
    exit;
}

$eventName = $event['event'];
$data = is_array($event['data'] ?? null) ? $event['data'] : [];
$metadata = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];
$subscriptionData = is_array($data['subscription'] ?? null) ? $data['subscription'] : [];
$eventReference = (string)($data['reference'] ?? '');
$isSubscriptionEvent = in_array($eventName, [
    'invoice.payment_success',
    'invoice.payment_failed',
    'invoice.update',
    'subscription.create',
    'subscription.disable',
    'subscription.not_renew',
], true) || (
    in_array($eventName, ['charge.success', 'charge.failed'], true)
    && (
        !empty($metadata['subscription_id'])
        || !empty($subscriptionData['subscription_code'])
        || !empty($data['subscription_code'])
    )
);
if (!$isSubscriptionEvent && in_array($eventName, ['charge.success', 'charge.failed'], true) && $eventReference !== '') {
    $stmt = $conn->prepare(
        'SELECT id FROM subscription_payments
         WHERE payment_method=\'paystack\' AND paystack_reference=? LIMIT 1'
    );
    $stmt->bind_param('s', $eventReference);
    $stmt->execute();
    $isSubscriptionEvent = $stmt->get_result()->num_rows === 1;
    $stmt->close();
}
if ($isSubscriptionEvent) {
    require __DIR__ . '/paystack_subscription_webhook.php';
    exit;
}

$reference = $data['reference'] ?? '';
$status = strtolower((string)($data['status'] ?? ''));
$amount = (float)($data['amount'] ?? 0) / 100; // minor units
$channel = $data['channel'] ?? '';
$paidAt = $data['paid_at'] ?? null;
$fees = (float)($data['fees'] ?? 0) / 100;

if ($reference === '') {
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Missing reference']);
    exit;
}

// Only process charge events
$allowedEvents = ['charge.success', 'charge.failed', 'transfer.success', 'transfer.failed'];
if (!in_array($eventName, $allowedEvents, true)) {
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Event ignored']);
    exit;
}

if (!empty($data['metadata']['order_id'])) {
    $orderId = (int)$data['metadata']['order_id'];
} else {
    // Try to find payment by reference
    $stmt = $conn->prepare('SELECT id, order_id FROM payments WHERE paystack_reference = ? LIMIT 1');
    $stmt->bind_param('s', $reference);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'No matching payment']);
        exit;
    }
    $orderId = $row['order_id'];
}

// Process inside a transaction with row locking
$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        "SELECT id, order_id, amount, status FROM payments
         WHERE paystack_reference = ? AND order_id = ? LIMIT 1 FOR UPDATE"
    );
    $stmt->bind_param('si', $reference, $orderId);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$payment) {
        $conn->commit();
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'No matching payment record']);
        exit;
    }

    // Idempotency: already processed
    if ($payment['status'] === 'paid') {
        $conn->commit();
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Payment already processed']);
        exit;
    }

    if ($status === 'success') {
        // Amount cross-check (cents)
        if (abs(($amount * 100) - (float)$payment['amount'] * 100) > 1) {
            throw new RuntimeException('Paystack amount does not match order amount.');
        }

        $stmt = $conn->prepare(
            "UPDATE payments SET status='paid', transaction_reference=?, paystack_reference=?,
             paystack_fees=?, paid_at=?, callback_received_at=NOW()
             WHERE id = ? AND order_id = ?"
        );
        $stmt->bind_param('ssdssii', $reference, $reference, $fees, $paidAt, $payment['id'], $orderId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare(
            "UPDATE orders SET status = 'paid' WHERE id = ? AND status = 'accepted'"
        );
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $stmt->close();
    } elseif ($status === 'failed') {
        $stmt = $conn->prepare(
            "UPDATE payments SET status='failed', paystack_reference=? WHERE id=? AND order_id=? AND status='pending'"
        );
        $stmt->bind_param('sii', $reference, $payment['id'], $orderId);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Webhook processed']);
} catch (Throwable $e) {
    $conn->rollback();
    error_log('VAYUMA Paystack webhook error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Processing failed']);
}
