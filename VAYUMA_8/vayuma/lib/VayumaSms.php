<?php

final class VayumaSms
{
    private mysqli $conn;
    private array $config;

    public function __construct(mysqli $conn, ?array $config = null)
    {
        $this->conn = $conn;
        $this->config = $config ?? require __DIR__ . '/../config/africas_talking.php';
    }

    public static function normalisePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '254' . substr($digits, 1);
        }
        if (str_starts_with($digits, '7') && strlen($digits) === 9) {
            $digits = '254' . $digits;
        }
        return preg_match('/^254[17]\d{8}$/', $digits) ? '+' . $digits : null;
    }

    public function send(string $phone, string $message, string $type = 'general'): array
    {
        $phone = self::normalisePhone($phone);
        $message = trim($message);
        if ($phone === null || $message === '') {
            return ['ok' => false, 'error' => 'Invalid phone number or message.'];
        }
        if (function_exists('mb_strimwidth')) {
            $message = mb_strimwidth($message, 0, 1600, '');
        } else {
            $message = substr($message, 0, 1600);
        }

        $messageId = $this->log($phone, $message, $type, 'queued');
        if (($this->config['username'] ?? '') === '' || ($this->config['api_key'] ?? '') === '') {
            $this->updateLog($messageId, 'not_configured', null, 'Africa\'s Talking credentials are not configured.');
            return ['ok' => false, 'error' => 'SMS service is not configured.', 'id' => $messageId];
        }
        if (!function_exists('curl_init')) {
            $this->updateLog($messageId, 'failed', null, 'PHP cURL extension is unavailable.');
            return ['ok' => false, 'error' => 'SMS service is unavailable.', 'id' => $messageId];
        }

        $payload = ['username' => $this->config['username'], 'to' => $phone, 'message' => $message];
        if (($this->config['from'] ?? '') !== '') {
            $payload['from'] = $this->config['from'];
        }
        if (($this->config['delivery_callback_url'] ?? '') !== '') {
            $payload['callbackUrl'] = $this->config['delivery_callback_url'];
        }

        $curl = curl_init(rtrim($this->config['base_url'], '/') . '/version1/messaging');
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_HTTPHEADER => ['apiKey: ' . $this->config['api_key'], 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        ]);
        $body = curl_exec($curl);
        $error = curl_error($curl);
        $statusCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $data = is_string($body) ? json_decode($body, true) : null;
        $recipient = $data['SMSMessageData']['Recipients'][0] ?? [];
        $providerId = $recipient['messageId'] ?? null;
        $status = ($statusCode >= 200 && $statusCode < 300 && ($recipient['statusCode'] ?? 1) == 101)
            ? 'sent' : 'failed';
        $failure = $status === 'failed'
            ? ($error ?: ($recipient['status'] ?? ('HTTP ' . $statusCode))) : null;
        $this->updateLog($messageId, $status, $providerId, $failure, $body ?: null);

        return ['ok' => $status === 'sent', 'id' => $messageId, 'provider_id' => $providerId, 'error' => $failure];
    }

    public function sendToUser(int $userId, string $message, string $type = 'general'): array
    {
        $stmt = $this->conn->prepare('SELECT phone FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $user ? $this->send($user['phone'], $message, $type) : ['ok' => false, 'error' => 'User phone number not found.'];
    }

    private function log(string $phone, string $message, string $type, string $status): int
    {
        $stmt = $this->conn->prepare('INSERT INTO sms_messages (phone_number, message_text, message_type, status) VALUES (?, ?, ?, ?)');
        if (!$stmt) {
            error_log('VAYUMA SMS log table missing. Apply database/migrations/ussd_sms_system.sql.');
            return 0;
        }
        $stmt->bind_param('ssss', $phone, $message, $type, $status);
        $stmt->execute();
        $id = (int)$stmt->insert_id;
        $stmt->close();
        return $id;
    }

    private function updateLog(int $id, string $status, ?string $providerId, ?string $error, ?string $response = null): void
    {
        if ($id < 1) return;
        $stmt = $this->conn->prepare('UPDATE sms_messages SET status=?, provider_message_id=?, provider_response=?, error_message=?, sent_at=IF(? = \'sent\', NOW(), sent_at) WHERE id=?');
        if (!$stmt) return;
        $stmt->bind_param('sssssi', $status, $providerId, $response, $error, $status, $id);
        $stmt->execute();
        $stmt->close();
    }
}
