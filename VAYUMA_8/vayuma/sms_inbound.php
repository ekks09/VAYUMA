<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/VayumaSms.php';
$config = require __DIR__ . '/config/africas_talking.php';
if (($config['inbound_callback_token'] ?? '') !== '' && !hash_equals($config['inbound_callback_token'], (string)($_GET['token'] ?? ''))) { http_response_code(403); exit('Forbidden'); }
$from = trim($_REQUEST['from'] ?? $_REQUEST['phoneNumber'] ?? '');
$message = strtoupper(trim($_REQUEST['text'] ?? $_REQUEST['message'] ?? ''));
$reply = match (true) {
    $message === 'HELP' => 'VAYUMA: Reply CROP for crop advice, MARKET for current produce, or STOP to end market alerts.',
    $message === 'CROP' => 'VAYUMA Crop Advice: Dial your VAYUMA USSD code, select Crop Advice, then choose a crop.',
    $message === 'MARKET' => 'VAYUMA Market: Dial your VAYUMA USSD code and select Market to view available produce or subscribe to alerts.',
    $message === 'STOP' => 'VAYUMA: Your market alerts have been stopped.',
    default => 'VAYUMA: Reply HELP for available SMS commands.',
};
if ($message === 'STOP') {
    $phone = VayumaSms::normalisePhone($from);
    if ($phone) { $stmt = $conn->prepare("UPDATE sms_subscriptions SET is_active=0 WHERE phone_number=?"); if ($stmt) { $stmt->bind_param('s', $phone); $stmt->execute(); $stmt->close(); } }
}
if ($from !== '') (new VayumaSms($conn))->send($from, $reply, 'inbound_reply');
http_response_code(200); echo 'OK';
