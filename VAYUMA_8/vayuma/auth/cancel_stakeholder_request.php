<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) { header('Location: ../login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !vayuma_csrf_valid()) { $_SESSION['request_error'] = 'Please refresh the page and try again.'; header('Location: ../stakeholder_requests.php'); exit; }
$request_id = (int)($_POST['request_id'] ?? 0); $user_id = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("UPDATE stakeholder_offer_requests SET status = 'cancelled' WHERE id = ? AND requester_id = ? AND status = 'pending'");
$stmt->bind_param('ii', $request_id, $user_id); $ok = $stmt->execute(); $changed = $stmt->affected_rows; $stmt->close();
$_SESSION[$ok && $changed ? 'request_success' : 'request_error'] = ($ok && $changed) ? 'Your pending request has been withdrawn.' : 'Only your own pending requests can be withdrawn.';
header('Location: ../stakeholder_requests.php'); exit;
