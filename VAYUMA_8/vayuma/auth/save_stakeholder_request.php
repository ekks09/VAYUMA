<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) { header('Location: ../login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../stakeholder_offers.php'); exit; }
if (!vayuma_csrf_valid()) { $_SESSION['request_error'] = 'Your session token expired. Please try again.'; header('Location: ../stakeholder_offers.php'); exit; }

$offer_id = (int)($_POST['offer_id'] ?? 0);
$message = trim((string)($_POST['message'] ?? ''));
$contact_phone = trim((string)($_POST['contact_phone'] ?? ''));
$requester_id = (int)$_SESSION['user_id'];
if ($offer_id < 1 || $message === '' || mb_strlen($message) > 3000) {
    $_SESSION['request_error'] = 'Please provide a message of up to 3,000 characters.';
    header('Location: ../stakeholder_offers.php'); exit;
}
$check = $conn->prepare("SELECT provider_id FROM stakeholder_offers WHERE id = ? AND status = 'active' LIMIT 1");
$check->bind_param('i', $offer_id); $check->execute(); $offer = $check->get_result()->fetch_assoc(); $check->close();
if (!$offer || (int)$offer['provider_id'] === $requester_id) {
    $_SESSION['request_error'] = 'This offer is unavailable for a request.';
    header('Location: ../stakeholder_offers.php'); exit;
}

$existing = $conn->prepare('SELECT id, status FROM stakeholder_offer_requests WHERE offer_id = ? AND requester_id = ? LIMIT 1');
$existing->bind_param('ii', $offer_id, $requester_id); $existing->execute(); $prior = $existing->get_result()->fetch_assoc(); $existing->close();
if ($prior) {
    if (!in_array($prior['status'], ['declined', 'cancelled'], true)) {
        $_SESSION['request_error'] = 'You already have a ' . $prior['status'] . ' request for this offer. You can follow its progress under Requests.';
        header('Location: ../stakeholder_requests.php'); exit;
    }
    $update = $conn->prepare("UPDATE stakeholder_offer_requests SET message = ?, contact_phone = ?, status = 'pending' WHERE id = ? AND requester_id = ?");
    $update->bind_param('ssii', $message, $contact_phone, $prior['id'], $requester_id);
    $ok = $update->execute(); $update->close();
} else {
    $insert = $conn->prepare("INSERT INTO stakeholder_offer_requests (offer_id, requester_id, message, contact_phone) VALUES (?, ?, ?, ?)");
    $insert->bind_param('iiss', $offer_id, $requester_id, $message, $contact_phone);
    $ok = $insert->execute(); $insert->close();
}
$_SESSION[$ok ? 'request_success' : 'request_error'] = $ok ? 'Your request has been sent to the provider.' : 'Unable to send your request. Please try again.';
header('Location: ../stakeholder_requests.php'); exit;
