<?php
/**
 * VAYUMA Paystack API client.
 *
 * Covers every Paystack transaction channel:
 *   card, bank, ussd, mobile_money, qr, bank_account, paylater
 *
 * Functions:
 *   paystack_initialize(array $payload) -> array   // calls /transaction/initialize
 *   paystack_verify(string $reference)   -> array   // calls /transaction/verify/:reference
 *
 * Reference: https://paystack.com/docs/api/transaction/#initialize
 *            https://paystack.com/docs/api/transaction/#verify
 */

function paystack_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config/paystack.php';
    }
    return $config;
}

function paystack_api(string $path, string $method = 'GET', ?array $payload = null): array
{
    $config = paystack_config();
    $secretKey = $config['secret_key'];
    if (empty($secretKey) || str_starts_with($secretKey, 'YOUR_')) {
        throw new RuntimeException('Paystack secret key has not been configured yet.');
    }

    $url = 'https://api.paystack.co' . $path;
    $headers = [
        'Authorization: Bearer ' . $secretKey,
        'Content-Type: application/json',
        'Accept: application/json',
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }
    }

    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $errno) {
        throw new RuntimeException('Paystack connection failed: ' . ($error ?: 'cURL error ' . $errno));
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        throw new RuntimeException('Paystack returned an invalid response. HTTP ' . $http);
    }

    if ($http < 200 || $http >= 300) {
        $message = $data['message'] ?? 'Paystack request failed.';
        throw new RuntimeException($message . ' (HTTP ' . $http . ')');
    }

    return $data;
}

function paystack_initialize(array $payload): array
{
    $config = paystack_config();

    $defaults = [
        'currency'   => $config['currency'],
        'callback_url' => $config['callback_url'],
    ];
    $payload = array_merge($defaults, $payload);

    if (empty($payload['email'])) {
        throw new InvalidArgumentException('Email is required to initialize a Paystack transaction.');
    }
    if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Enter a valid email address.');
    }
    if (empty($payload['amount']) || (float)$payload['amount'] <= 0) {
        throw new InvalidArgumentException('Amount must be greater than zero.');
    }
    $channel = $payload['channel'] ?? 'card';
    if (!in_array($channel, $config['channels'], true)) {
        throw new InvalidArgumentException('Unsupported Paystack channel: ' . htmlspecialchars($channel));
    }

    // Mobile money requires phone and provider
    if ($channel === 'mobile_money') {
        if (empty($payload['mobile_money']['phone'])) {
            throw new InvalidArgumentException('Mobile money phone number is required.');
        }
        if (empty($payload['mobile_money']['provider'])) {
            throw new InvalidArgumentException('Mobile money provider is required.');
        }
    }

    // Paystack expects amount in the smallest currency unit (kobo for NGN, cents for KES)
    $payload['amount'] = (int)round((float)$payload['amount'] * 100);

    $response = paystack_api('/transaction/initialize', 'POST', $payload);
    if (empty($response['status']) || $response['status'] !== true) {
        throw new RuntimeException($response['message'] ?? 'Paystack did not accept the payment request.');
    }

    return $response['data'] ?? $response;
}

function paystack_verify(string $reference): array
{
    if (trim($reference) === '') {
        throw new InvalidArgumentException('Paystack reference is required.');
    }
    $response = paystack_api('/transaction/verify/' . urlencode($reference), 'GET');
    if (empty($response['status']) || $response['status'] !== true) {
        throw new RuntimeException($response['message'] ?? 'Paystack verification failed.');
    }
    return $response['data'] ?? $response;
}
