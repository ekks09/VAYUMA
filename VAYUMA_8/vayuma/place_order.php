<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}

function vayuma_fail_order_request(string $message, ?int $listingId = null): void
{
    $_SESSION['order_error'] = $message;
    header('Location: ' . ($listingId ? 'produce_details.php?id=' . $listingId : 'market_connect.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: market_connect.php');
    exit;
}

$buyerId = (int)$_SESSION['user_id'];
$role = strtolower(trim((string)($_SESSION['role'] ?? '')));
$allowedRoles = ['buyer', 'farmer', 'consumer', 'cooperative', 'manufacturer', 'retailer', 'agribusiness', 'input_supplier'];
if (!in_array($role, $allowedRoles, true)) {
    vayuma_fail_order_request('Your account is not allowed to place produce requests.');
}

$listingId = filter_var($_POST['produce_listing_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$listingId = ($listingId === false || $listingId === null) ? null : $listingId;
if (!vayuma_csrf_valid()) {
    vayuma_fail_order_request('Your session expired. Refresh the listing and try again.', $listingId);
}

$quantityInput = $_POST['quantity'] ?? '';
if (!is_string($quantityInput) || !preg_match('/^\d+(?:\.\d{1,2})?$/', $quantityInput) || (float)$quantityInput <= 0) {
    vayuma_fail_order_request('Enter a quantity greater than zero with no more than two decimal places.', $listingId);
}
if ($listingId === null) {
    vayuma_fail_order_request('Choose a valid produce listing.');
}

$quantity = (float)$quantityInput;
$transactionStarted = false;
try {
    $conn->begin_transaction();
    $transactionStarted = true;
    $stmt = $conn->prepare(
        'SELECT id,farmer_id,quantity,unit,price_per_unit,status
         FROM produce_listings WHERE id=? LIMIT 1 FOR UPDATE'
    );
    $stmt->bind_param('i', $listingId);
    $stmt->execute();
    $listing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$listing) {
        throw new RuntimeException('Produce listing not found.');
    }
    if ((int)$listing['farmer_id'] === $buyerId) {
        throw new RuntimeException('You cannot purchase your own listing.');
    }
    if ($listing['status'] !== 'available') {
        throw new RuntimeException('This produce is no longer available.');
    }
    if ($quantity > (float)$listing['quantity']) {
        throw new RuntimeException('Requested quantity is greater than the available quantity.');
    }

    $total = $quantity * (float)$listing['price_per_unit'];
    $status = 'pending';
    $stmt = $conn->prepare(
        'INSERT INTO orders
            (produce_listing_id,buyer_id,seller_id,quantity,unit,price_per_unit,total_amount,status)
         VALUES (?,?,?,?,?,?,?,?)'
    );
    $stmt->bind_param(
        'iiidsdds',
        $listingId,
        $buyerId,
        $listing['farmer_id'],
        $quantity,
        $listing['unit'],
        $listing['price_per_unit'],
        $total,
        $status
    );
    $stmt->execute();
    $orderId = (int)$stmt->insert_id;
    $stmt->close();
    $farmerId = (int)$listing['farmer_id'];
    $conn->commit();
    $transactionStarted = false;
} catch (Throwable $exception) {
    error_log('Produce order request failed: ' . $exception->getMessage());
    if ($transactionStarted) {
        try {
            $conn->rollback();
        } catch (Throwable $rollbackException) {
            error_log('Produce order rollback failed: ' . $rollbackException->getMessage());
        }
    }
    $message = $exception instanceof RuntimeException
        ? $exception->getMessage()
        : 'Your produce request could not be submitted right now. Please try again.';
    vayuma_fail_order_request($message, $listingId);
}

if (is_file(__DIR__ . '/lib/VayumaSms.php')) {
    require_once __DIR__ . '/lib/VayumaSms.php';
    try {
        (new VayumaSms($conn))->sendToUser(
            $farmerId,
            'VAYUMA: You have a new produce order (#' . $orderId . '). Log in to review it.',
            'order_created'
        );
    } catch (Throwable $exception) {
        error_log('Order SMS notification failed: ' . $exception->getMessage());
    }
}

$_SESSION['order_created'] = true;
header('Location: my_orders.php');
exit;
