<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/admin.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true || !is_vayuma_admin((int)$_SESSION['user_id'])) {
    http_response_code(403);
    exit('Administrator access is not enabled for this account.');
}

$paymentId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($paymentId === false || $paymentId === null) {
    http_response_code(404);
    exit('Payment proof not found.');
}

$stmt = $conn->prepare("SELECT proof_path FROM payments WHERE id=? AND payment_method='bank' LIMIT 1");
$stmt->bind_param('i', $paymentId);
$stmt->execute();
$proofRow = $stmt->get_result()->fetch_assoc();
$proofPath = $proofRow['proof_path'] ?? null;
$stmt->close();

$filename = is_string($proofPath) ? basename($proofPath) : '';
if (!preg_match('/^order_[0-9]+_(?:[a-f0-9]{16}|[a-f0-9]{24})\.(jpg|png|webp|pdf)$/', $filename)) {
    http_response_code(404);
    exit('Payment proof not found.');
}

$directory = realpath(__DIR__ . '/../uploads/bank_proofs');
$filePath = $directory === false ? false : realpath($directory . DIRECTORY_SEPARATOR . $filename);
if ($filePath === false || dirname($filePath) !== $directory || !is_file($filePath)) {
    http_response_code(404);
    exit('Payment proof not found.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($filePath);
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
if (!is_string($mime) || !in_array($mime, $allowedTypes, true)) {
    http_response_code(415);
    exit('Unsupported payment proof type.');
}

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="bank-payment-proof-' . (int)$paymentId . '.' . pathinfo($filename, PATHINFO_EXTENSION) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
readfile($filePath);
exit;
