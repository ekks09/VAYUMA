<?php
/**
 * VAYUMA — Save / remove a produce listing from saved items.
 * AJAX-friendly endpoint: returns JSON.
 */
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!vayuma_csrf_valid()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$listingId = (int)($_POST['produce_listing_id'] ?? 0);
$action = ($_POST['action'] ?? 'toggle'); // 'add', 'remove', 'toggle'

if ($listingId < 1) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid listing']);
    exit;
}

// Verify listing exists and is available
$stmt = $conn->prepare('SELECT id, status, farmer_id FROM produce_listings WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $listingId);
$stmt->execute();
$listing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$listing) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Listing not found']);
    exit;
}

// Prevent saving your own listing
if ((int)$listing['farmer_id'] === $userId) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Cannot save your own listing']);
    exit;
}

// Check existing saved item
$stmt = $conn->prepare('SELECT id FROM saved_items WHERE user_id = ? AND produce_listing_id = ? LIMIT 1');
$stmt->bind_param('ii', $userId, $listingId);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

$conn->begin_transaction();
try {
    $saved = false;
    if ($action === 'remove' || ($action === 'toggle' && $existing)) {
        // Remove
        $stmt = $conn->prepare('DELETE FROM saved_items WHERE user_id = ? AND produce_listing_id = ?');
        $stmt->bind_param('ii', $userId, $listingId);
        $stmt->execute();
        $stmt->close();
        $saved = false;
    } else {
        // Add (if not already saved)
        if (!$existing) {
            $stmt = $conn->prepare('INSERT INTO saved_items (user_id, produce_listing_id) VALUES (?, ?)');
            $stmt->bind_param('ii', $userId, $listingId);
            $stmt->execute();
            $stmt->close();
        }
        $saved = true;
    }
    $conn->commit();
    echo json_encode(['ok' => true, 'saved' => $saved, 'listing_id' => $listingId]);
} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    error_log('VAYUMA saved item update unavailable.');
    echo json_encode(['ok' => false, 'error' => 'Unable to update saved items. Please try again.']);
}
