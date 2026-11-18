<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/paystack.php';
require_once __DIR__ . '/lib/paystack_subscription_payments.php';
require_once __DIR__ . '/lib/paystack_webhook_security.php';

header('Content-Type: application/json; charset=utf-8');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    paystack_webhook_response(405, 'Method not allowed.');
}

function paystack_webhook_response(int $status, string $message): never
{
    http_response_code($status);
    echo json_encode(['ok' => $status >= 200 && $status < 300, 'message' => $message]);
    exit;
}

$secretKey = paystack_config()['secret_key'] ?? '';
$webhookSecrets = paystack_config()['webhook_secrets'] ?? [];
if ($secretKey === '' || str_starts_with($secretKey, 'YOUR_')) {
    error_log('VAYUMA Paystack webhook: secret key is not configured.');
    paystack_webhook_response(503, 'Webhook is not configured.');
}

$raw = (string)($GLOBALS['vayuma_paystack_raw_webhook'] ?? file_get_contents('php://input'));
if (empty($GLOBALS['vayuma_paystack_signature_verified'])) {
    if (!paystack_verify_webhook_signature($raw, $secretKey, $webhookSecrets, $_SERVER)) {
        error_log('VAYUMA Paystack webhook rejected an invalid signature.');
        paystack_webhook_response(401, 'Invalid signature.');
    }
}

$event = json_decode($raw, true);
if (!is_array($event) || !is_string($event['event'] ?? null)) {
    paystack_webhook_response(400, 'Invalid event payload.');
}

$eventName = $event['event'];
$data = is_array($event['data'] ?? null) ? $event['data'] : [];
$allowed = [
    'charge.success',
    'charge.failed',
    'invoice.payment_success',
    'invoice.payment_failed',
    'invoice.update',
    'subscription.create',
    'subscription.disable',
    'subscription.not_renew',
];
if (!in_array($eventName, $allowed, true)) {
    paystack_webhook_response(200, 'Event ignored.');
}

$nestedSubscription = is_array($data['subscription'] ?? null) ? $data['subscription'] : [];
$nestedCustomer = is_array($data['customer'] ?? null) ? $data['customer'] : [];
$nestedTransaction = is_array($data['transaction'] ?? null) ? $data['transaction'] : [];
$reference = (string)($data['reference'] ?? $nestedTransaction['reference'] ?? '');
$subscriptionCode = (string)($nestedSubscription['subscription_code'] ?? $data['subscription_code'] ?? '');
$customerCode = (string)($nestedCustomer['customer_code'] ?? '');
$transactionId = (string)($nestedTransaction['id'] ?? $data['id'] ?? '');
$invoiceStatus = strtolower((string)($data['status'] ?? $nestedTransaction['status'] ?? ''));
$processingEventName = $eventName === 'invoice.update'
    ? ($invoiceStatus === 'success' ? 'invoice.payment_success' : ($invoiceStatus === 'failed' ? 'invoice.payment_failed' : 'invoice.update'))
    : $eventName;

// Paystack payloads do not always include a top-level event ID. The raw-body
// digest is stable across retries and avoids collapsing unrelated events.
$eventId = hash('sha256', $raw);
$payload = json_encode($event, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
$stmt = $conn->prepare(
    'INSERT IGNORE INTO payment_events
     (event_id, event_type, reference, paystack_subscription_code, paystack_transaction_id, payload)
     VALUES (?, ?, NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), ?)'
);
$stmt->bind_param('ssssss', $eventId, $eventName, $reference, $subscriptionCode, $transactionId, $payload);
$stmt->execute();
$isNewEvent = $stmt->affected_rows === 1;
$stmt->close();

if (!$isNewEvent) {
    $stmt = $conn->prepare('SELECT processed FROM payment_events WHERE event_id=? LIMIT 1');
    $stmt->bind_param('s', $eventId);
    $stmt->execute();
    $storedEvent = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($storedEvent && (int)$storedEvent['processed'] === 1) {
        paystack_webhook_response(200, 'Duplicate event ignored.');
    }
}

$transactionOpen = false;
try {
    if ($processingEventName === 'charge.failed') {
        if ($reference === '') {
            throw new RuntimeException('Failed Paystack event has no transaction reference.');
        }
        $conn->begin_transaction();
        $transactionOpen = true;
        $stmt = $conn->prepare(
            'UPDATE subscription_payments
             SET status=\'failed\', result_description=?, callback_received_at=NOW()
             WHERE payment_method=\'paystack\' AND paystack_reference=? AND status=\'pending\''
        );
        $description = (string)($data['gateway_response'] ?? 'Paystack payment failed.');
        $stmt->bind_param('ss', $description, $reference);
        $stmt->execute();
        $stmt->close();
        $conn->commit();
        $transactionOpen = false;
    } elseif (in_array($processingEventName, ['charge.success', 'invoice.payment_success'], true)) {
        if ($reference === '') {
            throw new RuntimeException('Successful Paystack event has no transaction reference.');
        }
        $verified = paystack_verify($reference);
        paystack_apply_verified_subscription_payment(
            $conn,
            $reference,
            $verified,
            $subscriptionCode !== '' ? $subscriptionCode : null,
            'webhook'
        );
    } elseif (in_array($processingEventName, ['invoice.payment_failed', 'subscription.create', 'subscription.disable', 'subscription.not_renew'], true)) {
        $conn->begin_transaction();
        $transactionOpen = true;
        $membership = null;
        if ($subscriptionCode !== '') {
            $stmt = $conn->prepare('SELECT * FROM user_subscriptions WHERE paystack_subscription_code=? LIMIT 1 FOR UPDATE');
            $stmt->bind_param('s', $subscriptionCode);
            $stmt->execute();
            $membership = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
        if (!$membership && $customerCode !== '') {
            $stmt = $conn->prepare('SELECT * FROM user_subscriptions WHERE paystack_customer_code=? LIMIT 1 FOR UPDATE');
            $stmt->bind_param('s', $customerCode);
            $stmt->execute();
            $membership = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }

        if ($membership) {
            $membershipId = (int)$membership['id'];
            $oldStatus = (string)$membership['status'];
            $provider = (string)($membership['payment_provider'] ?? '');
            if ($processingEventName === 'subscription.create') {
                if ($subscriptionCode !== '' && ($provider === '' || $provider === 'paystack')) {
                    $stmt = $conn->prepare(
                        'UPDATE user_subscriptions SET paystack_subscription_code=?,
                         paystack_customer_code=COALESCE(NULLIF(?, \'\'), paystack_customer_code),
                         updated_at=NOW() WHERE id=?'
                    );
                    $stmt->bind_param('ssi', $subscriptionCode, $customerCode, $membershipId);
                    $stmt->execute();
                    $stmt->close();
                }
            } elseif ($provider === 'paystack' || $provider === '') {
                if ($processingEventName === 'invoice.payment_failed') {
                    $graceEnd = date('Y-m-d H:i:s', strtotime('+7 days'));
                    $stmt = $conn->prepare(
                        'UPDATE user_subscriptions SET status=\'payment_due\', grace_period_ends_at=?,
                         updated_at=NOW() WHERE id=?'
                    );
                    $stmt->bind_param('si', $graceEnd, $membershipId);
                    $stmt->execute();
                    $stmt->close();
                    $newStatus = 'payment_due';
                    $reason = 'Paystack renewal failed; grace period until ' . $graceEnd;
                } elseif ($processingEventName === 'subscription.not_renew') {
                    $stmt = $conn->prepare('UPDATE user_subscriptions SET auto_renew=0, updated_at=NOW() WHERE id=?');
                    $stmt->bind_param('i', $membershipId);
                    $stmt->execute();
                    $stmt->close();
                    $newStatus = $oldStatus;
                    $reason = 'Paystack subscription will not renew; access remains until expiry.';
                } else {
                    $stmt = $conn->prepare(
                        'UPDATE user_subscriptions SET status=\'expired\', payment_provider=NULL,
                         auto_renew=0, updated_at=NOW() WHERE id=?'
                    );
                    $stmt->bind_param('i', $membershipId);
                    $stmt->execute();
                    $stmt->close();
                    $newStatus = 'expired';
                    $reason = 'Paystack subscription disabled.';
                }

                if ($newStatus !== $oldStatus) {
                    $stmt = $conn->prepare(
                        'INSERT INTO subscription_status_history
                         (user_membership_id, old_status, new_status, reason, source)
                         VALUES (?, ?, ?, ?, \'webhook\')'
                    );
                    $stmt->bind_param('isss', $membershipId, $oldStatus, $newStatus, $reason);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
        $conn->commit();
        $transactionOpen = false;
    }

    $stmt = $conn->prepare('UPDATE payment_events SET processed=1, processed_at=NOW() WHERE event_id=?');
    $stmt->bind_param('s', $eventId);
    $stmt->execute();
    $stmt->close();
    paystack_webhook_response(200, 'Event processed.');
} catch (Throwable $e) {
    if ($transactionOpen) {
        $conn->rollback();
    }
    error_log('VAYUMA Paystack webhook processing failed: ' . $e->getMessage());
    paystack_webhook_response(500, 'Event processing failed; Paystack may retry.');
}
