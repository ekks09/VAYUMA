<?php
require_once __DIR__ . '/config/db.php';
header('Content-Type: application/json');
$providerId = trim($_REQUEST['id'] ?? $_REQUEST['messageId'] ?? '');
$status = strtolower(trim($_REQUEST['status'] ?? ''));
if ($providerId !== '') {
    $newStatus = in_array($status, ['success', 'delivered'], true) ? 'delivered' : ($status !== '' ? 'failed' : 'sent');
    $stmt = $conn->prepare('UPDATE sms_messages SET status=?, delivered_at=IF(? = \'delivered\', NOW(), delivered_at), provider_response=COALESCE(provider_response, ?) WHERE provider_message_id=?');
    if ($stmt) {
        $payload = json_encode($_REQUEST, JSON_UNESCAPED_SLASHES);
        $stmt->bind_param('ssss', $newStatus, $newStatus, $payload, $providerId);
        $stmt->execute(); $stmt->close();
    }
}
echo json_encode(['status' => 'ok']);
