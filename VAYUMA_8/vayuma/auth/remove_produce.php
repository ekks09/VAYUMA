<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: ../login.php');
    exit;
}

function vayuma_redirect_to_my_produce(string $message): void
{
    $_SESSION['produce_listing_notice'] = $message;
    header('Location: ../my_produce.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../my_produce.php');
    exit;
}

if (!vayuma_csrf_valid()) {
    vayuma_redirect_to_my_produce('Your session expired. Refresh the page and try again.');
}

$listingId = filter_var($_POST['listing_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($listingId === false || $listingId === null) {
    vayuma_redirect_to_my_produce('Select a valid listing to remove.');
}

$farmerId = (int)$_SESSION['user_id'];
try {
    $stmt = $conn->prepare(
        "UPDATE produce_listings
         SET status = 'removed'
         WHERE id = ? AND farmer_id = ? AND status = 'available'"
    );
    if (!$stmt) {
        throw new mysqli_sql_exception('Unable to prepare produce listing removal.');
    }
    $stmt->bind_param('ii', $listingId, $farmerId);
    $stmt->execute();
    $removed = $stmt->affected_rows === 1;
    $stmt->close();
} catch (mysqli_sql_exception $exception) {
    error_log('Produce listing removal failed: ' . $exception->getMessage());
    vayuma_redirect_to_my_produce('The listing could not be removed right now. Please try again.');
}

vayuma_redirect_to_my_produce(
    $removed
        ? 'The listing was removed from the marketplace.'
        : 'No available listing was found to remove. Refresh the page and try again.'
);
