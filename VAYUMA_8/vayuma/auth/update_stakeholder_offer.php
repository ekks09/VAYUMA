<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) { header('Location: ../login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !vayuma_csrf_valid()) { $_SESSION['offer_error'] = 'Please refresh the page and try again.'; header('Location: ../stakeholder_offers.php?view=manage'); exit; }
$id = (int)($_POST['offer_id'] ?? 0); $status = (string)($_POST['status'] ?? ''); $user_id = (int)$_SESSION['user_id'];
if ($id < 1 || !in_array($status, ['active','paused','closed'], true)) { header('Location: ../stakeholder_offers.php?view=manage'); exit; }
$stmt = $conn->prepare('UPDATE stakeholder_offers SET status = ? WHERE id = ? AND provider_id = ?');
$stmt->bind_param('sii', $status, $id, $user_id); $ok = $stmt->execute(); $changed = $stmt->affected_rows; $stmt->close();
$_SESSION[$ok && $changed ? 'offer_success' : 'offer_error'] = ($ok && $changed) ? 'Offer status updated.' : 'No offer was changed. Check that the offer belongs to you.';
header('Location: ../stakeholder_offers.php?view=manage'); exit;
