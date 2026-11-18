<?php

function paystack_verify_webhook_signature(
    string $rawBody,
    string $secretKey,
    array $webhookSecrets,
    array $headers
): bool {
    $modernHeader = trim((string)($headers['HTTP_PAYSTACK_SIGNATURE'] ?? ''));
    if ($modernHeader !== '') {
        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $modernHeader) as $part) {
            $separator = strpos($part, '=');
            if ($separator === false) {
                continue;
            }
            $name = trim(substr($part, 0, $separator));
            $value = trim(substr($part, $separator + 1));
            if ($name === 't') {
                if ($timestamp !== null || !ctype_digit($value)) {
                    return false;
                }
                $timestamp = (int)$value;
            } elseif ($name === 'v1') {
                $colon = strpos($value, ':');
                if ($colon !== false) {
                    $signatures[] = strtolower(substr($value, $colon + 1));
                }
            }
        }

        if ($timestamp === null || abs(time() - $timestamp) > 300 || !$signatures || !$webhookSecrets) {
            return false;
        }
        foreach ($webhookSecrets as $secret) {
            if (!is_string($secret) || $secret === '') {
                continue;
            }
            $expected = hash_hmac('sha512', $timestamp . '.' . $rawBody, $secret);
            foreach ($signatures as $signature) {
                if (preg_match('/^[a-f0-9]{128}$/', $signature) && hash_equals($expected, $signature)) {
                    return true;
                }
            }
        }
        return false;
    }

    $legacySignature = strtolower(trim((string)($headers['HTTP_X_PAYSTACK_SIGNATURE'] ?? '')));
    if (!preg_match('/^[a-f0-9]{128}$/', $legacySignature) || $secretKey === '') {
        return false;
    }
    return hash_equals(hash_hmac('sha512', $rawBody, $secretKey), $legacySignature);
}
