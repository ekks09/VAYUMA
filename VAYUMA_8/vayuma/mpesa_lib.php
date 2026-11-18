<?php
require_once __DIR__ . '/config/db.php';

function mpesa_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/config/mpesa.php';
    }
    return $config;
}

function mpesa_base_url(): string
{
    return mpesa_config()['environment'] === 'production'
        ? 'https://api.safaricom.co.ke'
        : 'https://sandbox.safaricom.co.ke';
}

function mpesa_json_request(string $url, array $headers, ?array $payload = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $errno) {
        throw new RuntimeException('M-Pesa connection failed: ' . ($error ?: 'cURL error ' . $errno));
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        throw new RuntimeException('M-Pesa returned an invalid response. HTTP ' . $http);
    }

    if ($http < 200 || $http >= 300) {
        $message = $data['errorMessage'] ?? $data['ResponseDescription'] ?? 'M-Pesa request failed.';
        throw new RuntimeException($message . ' (HTTP ' . $http . ')');
    }

    return $data;
}

function mpesa_access_token(): string
{
    $config = mpesa_config();

    if (str_starts_with($config['consumer_key'], 'YOUR_') || str_starts_with($config['consumer_secret'], 'YOUR_')) {
        throw new RuntimeException('Daraja credentials have not been configured yet.');
    }

    $url = mpesa_base_url() . '/oauth/v1/generate?grant_type=client_credentials';
    $basic = base64_encode($config['consumer_key'] . ':' . $config['consumer_secret']);

    $response = mpesa_json_request($url, [
        'Authorization: Basic ' . $basic,
        'Accept: application/json',
    ]);

    if (empty($response['access_token'])) {
        throw new RuntimeException('Daraja did not return an access token.');
    }

    return $response['access_token'];
}

function normalize_mpesa_phone(string $phone): string
{
    $phone = preg_replace('/[^0-9+]/', '', trim($phone));

    if (str_starts_with($phone, '+254')) {
        $phone = '254' . substr($phone, 4);
    } elseif (str_starts_with($phone, '254')) {
        // already normalized
    } elseif (str_starts_with($phone, '07') || str_starts_with($phone, '01')) {
        $phone = '254' . substr($phone, 1);
    } elseif (str_starts_with($phone, '7') || str_starts_with($phone, '1')) {
        $phone = '254' . $phone;
    } else {
        throw new InvalidArgumentException('Enter a valid Kenyan M-Pesa number, e.g. 0712345678.');
    }

    if (!preg_match('/^254[17][0-9]{8}$/', $phone)) {
        throw new InvalidArgumentException('Enter a valid Kenyan M-Pesa number, e.g. 0712345678.');
    }

    return $phone;
}

function mpesa_password(string $shortcode, string $passkey, string $timestamp): string
{
    return base64_encode($shortcode . $passkey . $timestamp);
}

function mpesa_ready_config(): array
{
    $config = mpesa_config();
    $shortcode = trim((string)($config['shortcode'] ?? ''));
    $passkey = trim((string)($config['passkey'] ?? ''));
    $transactionType = (string)($config['transaction_type'] ?? '');

    if ($shortcode === '' || str_starts_with($shortcode, 'YOUR_') || $passkey === '' || str_starts_with($passkey, 'YOUR_')) {
        throw new RuntimeException('M-Pesa PayBill shortcode and passkey have not been configured yet.');
    }
    if (!in_array($transactionType, ['CustomerPayBillOnline', 'CustomerBuyGoodsOnline'], true)) {
        throw new RuntimeException('M-Pesa transaction type must be CustomerPayBillOnline for a PayBill.');
    }

    $partyB = trim((string)($config['party_b'] ?? ''));
    $config['party_b'] = $partyB !== '' ? $partyB : $shortcode;
    $config['shortcode'] = $shortcode;

    $callback = (string)($config['callback_url'] ?? '');
    if (!filter_var($callback, FILTER_VALIDATE_URL) || !str_starts_with($callback, 'https://')) {
        throw new RuntimeException('Set MPESA_CALLBACK_URL to a public HTTPS address ending in /mpesa_callback.php.');
    }

    return $config;
}

function mpesa_account_reference(string $prefix, int $id): string
{
    $clean = preg_replace('/[^A-Za-z0-9]/', '', $prefix) ?: 'VAYUMA';
    $reference = $clean . $id;
    return substr($reference, 0, 12);
}

function mpesa_stk_request(int $amount, string $phone, string $accountReference, string $description): array
{
    $config = mpesa_ready_config();
    $phone = normalize_mpesa_phone($phone);
    if ($amount < 1) {
        throw new InvalidArgumentException('M-Pesa amount must be at least KSh 1.');
    }

    $timestamp = date('YmdHis');
    $token = mpesa_access_token();
    $payload = [
        'BusinessShortCode' => $config['shortcode'],
        'Password' => mpesa_password($config['shortcode'], $config['passkey'], $timestamp),
        'Timestamp' => $timestamp,
        'TransactionType' => $config['transaction_type'],
        'Amount' => $amount,
        'PartyA' => $phone,
        'PartyB' => $config['party_b'],
        'PhoneNumber' => $phone,
        'CallBackURL' => $config['callback_url'],
        'AccountReference' => substr($accountReference, 0, 12),
        'TransactionDesc' => substr($description, 0, 13),
    ];

    $response = mpesa_json_request(
        mpesa_base_url() . '/mpesa/stkpush/v1/processrequest',
        [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        $payload
    );

    return [$response, $phone, $amount];
}

function mpesa_stk_push(int $orderId, float $amount, string $phone): array
{
    $config = mpesa_config();
    $charged = (int)round($amount);
    $reference = mpesa_account_reference((string)$config['account_reference_prefix'], $orderId);
    $description = (string)($config['transaction_desc'] ?? 'VAYUMA Payment');
    return mpesa_stk_request($charged, $phone, $reference, $description);
}

function mpesa_subscription_stk_push(int $subscriptionId, float $amount, string $phone): array
{
    $charged = (int)round($amount);
    return mpesa_stk_request($charged, $phone, mpesa_account_reference('VS', $subscriptionId), 'VAYUMA Sub');
}

function mpesa_stk_query(string $checkoutRequestId): array
{
    $config = mpesa_ready_config();
    $timestamp = date('YmdHis');
    $token = mpesa_access_token();

    return mpesa_json_request(
        mpesa_base_url() . '/mpesa/stkpushquery/v1/query',
        [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        [
            'BusinessShortCode' => $config['shortcode'],
            'Password' => mpesa_password($config['shortcode'], $config['passkey'], $timestamp),
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId,
        ]
    );
}

function mpesa_query_is_final(array $query): bool
{
    $resultCode = (int)($query['ResultCode'] ?? -1);
    return in_array($resultCode, [0, 1, 1001, 1019, 1032, 1037, 2001], true);
}

function mpesa_refresh_order_payment(mysqli $conn, int $paymentId): string
{
    $stmt = $conn->prepare('SELECT id, order_id, amount, status, checkout_request_id FROM payments WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $paymentId);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$payment) {
        return 'missing';
    }
    if ($payment['status'] !== 'pending' || trim((string)$payment['checkout_request_id']) === '') {
        return (string)$payment['status'];
    }

    try {
        $query = mpesa_stk_query((string)$payment['checkout_request_id']);
    } catch (Throwable $exception) {
        error_log('M-Pesa order status query failed: ' . $exception->getMessage());
        return 'pending';
    }
    if (!mpesa_query_is_final($query)) {
        return 'pending';
    }

    $resultCode = (int)($query['ResultCode'] ?? 1);
    $resultDescription = substr((string)($query['ResultDesc'] ?? 'M-Pesa status query'), 0, 255);
    $checkoutRequestId = (string)$payment['checkout_request_id'];

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare('SELECT id, order_id, amount, status FROM payments WHERE id=? LIMIT 1 FOR UPDATE');
        $stmt->bind_param('i', $paymentId);
        $stmt->execute();
        $locked = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$locked || $locked['status'] !== 'pending') {
            $conn->commit();
            return (string)($locked['status'] ?? 'missing');
        }
        if ($resultCode === 0) {
            $receiptCopy = $checkoutRequestId;
            $stmt = $conn->prepare(
                "UPDATE payments
                 SET status='paid', transaction_reference=?, mpesa_receipt_number=COALESCE(mpesa_receipt_number, ?),
                     result_code=?, result_description=?, callback_received_at=NOW(), paid_at=NOW()
                 WHERE id=? AND status='pending'"
            );
            $stmt->bind_param('ssisi', $checkoutRequestId, $receiptCopy, $resultCode, $resultDescription, $paymentId);
            $stmt->execute();
            $stmt->close();
            $orderId = (int)$locked['order_id'];
            $stmt = $conn->prepare("UPDATE orders SET status='paid' WHERE id=? AND status='accepted'");
            $stmt->bind_param('i', $orderId);
            $stmt->execute();
            $stmt->close();
            $conn->commit();
            return 'paid';
        }
        $status = 'failed';
        $stmt = $conn->prepare(
            "UPDATE payments SET status=?, result_code=?, result_description=?, callback_received_at=NOW() WHERE id=? AND status='pending'"
        );
        $stmt->bind_param('sisi', $status, $resultCode, $resultDescription, $paymentId);
        $stmt->execute();
        $stmt->close();
        $conn->commit();
        return 'failed';
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('M-Pesa order status update failed: ' . $exception->getMessage());
        return 'pending';
    }
}

function mpesa_refresh_subscription_payment(mysqli $conn, int $paymentId): string
{
    require_once __DIR__ . '/lib/subscription_lib.php';

    $stmt = $conn->prepare('SELECT id, subscription_id, amount, billing_interval, status, checkout_request_id FROM subscription_payments WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $paymentId);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$payment) {
        return 'missing';
    }
    if ($payment['status'] !== 'pending' || trim((string)$payment['checkout_request_id']) === '') {
        return (string)$payment['status'];
    }

    try {
        $query = mpesa_stk_query((string)$payment['checkout_request_id']);
    } catch (Throwable $exception) {
        error_log('M-Pesa subscription status query failed: ' . $exception->getMessage());
        return 'pending';
    }
    if (!mpesa_query_is_final($query)) {
        return 'pending';
    }

    $resultCode = (int)($query['ResultCode'] ?? 1);
    $resultDescription = substr((string)($query['ResultDesc'] ?? 'M-Pesa status query'), 0, 255);

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare('SELECT id, subscription_id, amount, billing_interval, status, checkout_request_id FROM subscription_payments WHERE id=? LIMIT 1 FOR UPDATE');
        $stmt->bind_param('i', $paymentId);
        $stmt->execute();
        $locked = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$locked || $locked['status'] !== 'pending') {
            $conn->commit();
            return (string)($locked['status'] ?? 'missing');
        }

        $subscriptionId = (int)$locked['subscription_id'];
        $stmt = $conn->prepare('SELECT payment_provider, status, expires_at, billing_interval FROM user_subscriptions WHERE id=? LIMIT 1 FOR UPDATE');
        $stmt->bind_param('i', $subscriptionId);
        $stmt->execute();
        $membership = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $provider = $membership['payment_provider'] ?? null;
        $currentStatus = $membership['status'] ?? '';
        $periodEnded = !empty($membership['expires_at']) && strtotime($membership['expires_at']) < time();
        $canUpdate = ($provider === null || $provider === 'mpesa' || $currentStatus === 'expired' || $periodEnded);
        if (!$canUpdate) {
            $conn->commit();
            return 'pending';
        }

        if ($resultCode === 0) {
            $receipt = (string)$locked['checkout_request_id'];
            $receiptCopy = $receipt;
            $stmt = $conn->prepare(
                "UPDATE subscription_payments
                 SET status='paid', transaction_reference=?, mpesa_receipt_number=COALESCE(mpesa_receipt_number, ?),
                     result_code=?, result_description=?, callback_received_at=NOW(), paid_at=NOW()
                 WHERE id=? AND status='pending'"
            );
            $stmt->bind_param('ssisi', $receipt, $receiptCopy, $resultCode, $resultDescription, $paymentId);
            $stmt->execute();
            $stmt->close();
            $baseDate = !empty($membership['expires_at']) && strtotime($membership['expires_at']) > time()
                ? $membership['expires_at']
                : date('Y-m-d H:i:s');
            $expiresAt = paystack_next_billing_date((string)($locked['billing_interval'] ?: $membership['billing_interval'] ?: 'monthly'), $baseDate);
            $stmt = $conn->prepare(
                "UPDATE user_subscriptions
                 SET status='active', payment_provider='mpesa', starts_at=COALESCE(starts_at, NOW()),
                     expires_at=?, next_billing_date=?, grace_period_ends_at=NULL, updated_at=NOW()
                 WHERE id=?"
            );
            $stmt->bind_param('ssi', $expiresAt, $expiresAt, $subscriptionId);
            $stmt->execute();
            $stmt->close();
            $stmt = $conn->prepare('UPDATE subscription_payments SET subscription_expires_at=? WHERE id=?');
            $stmt->bind_param('si', $expiresAt, $paymentId);
            $stmt->execute();
            $stmt->close();
            if ($currentStatus !== 'active') {
                $stmt = $conn->prepare("INSERT INTO subscription_status_history (user_membership_id, old_status, new_status, reason, source) VALUES (?, ?, 'active', ?, 'mpesa_query')");
                $reason = 'M-Pesa payment confirmed; access active until ' . $expiresAt;
                $stmt->bind_param('iss', $subscriptionId, $currentStatus, $reason);
                $stmt->execute();
                $stmt->close();
            }
            $conn->commit();
            return 'paid';
        }

        $status = 'failed';
        $stmt = $conn->prepare('UPDATE subscription_payments SET status=?, result_code=?, result_description=?, callback_received_at=NOW() WHERE id=? AND status=\'pending\'');
        $stmt->bind_param('sisi', $status, $resultCode, $resultDescription, $paymentId);
        $stmt->execute();
        $stmt->close();
        $conn->commit();
        return 'failed';
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('M-Pesa subscription status update failed: ' . $exception->getMessage());
        return 'pending';
    }
}
