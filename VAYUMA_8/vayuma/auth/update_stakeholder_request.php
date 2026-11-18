<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) { header('Location: ../login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../stakeholder_requests.php'); exit; }
if (!vayuma_csrf_valid()) { $_SESSION['request_error'] = 'Your session token expired. Please refresh and try again.'; header('Location: ../stakeholder_requests.php'); exit; }
$request_id = (int)($_POST['request_id'] ?? 0);
$status = (string)($_POST['status'] ?? '');
$provider_id = (int)$_SESSION['user_id'];
if ($request_id < 1 || !in_array($status, ['accepted','declined','completed'], true)) { header('Location: ../stakeholder_requests.php'); exit; }
if ($status === 'completed') {
    $stmt = $conn->prepare("UPDATE stakeholder_offer_requests r INNER JOIN stakeholder_offers o ON o.id = r.offer_id SET r.status = 'completed' WHERE r.id = ? AND o.provider_id = ? AND r.status = 'accepted'");
} else {
    $stmt = $conn->prepare("UPDATE stakeholder_offer_requests r INNER JOIN stakeholder_offers o ON o.id = r.offer_id SET r.status = ? WHERE r.id = ? AND o.provider_id = ? AND r.status = 'pending'");
    $stmt->bind_param('sii', $status, $request_id, $provider_id);
}
if ($status === 'completed') { $stmt->bind_param('ii', $request_id, $provider_id); }
$ok = $stmt->execute(); $changed = $stmt->affected_rows; $stmt->close();
$_SESSION[$ok && $changed ? 'request_success' : 'request_error'] = ($ok && $changed) ? 'Request status updated.' : 'The request could not be updated. It may have already changed or may not belong to your account.';
header('Location: ../stakeholder_requests.php'); exit;
