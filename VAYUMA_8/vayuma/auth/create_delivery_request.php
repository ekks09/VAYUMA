<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: ../login.php');
    exit;
}

function vayuma_delivery_request_error(string $message, ?int $orderId = null): void
{
    $_SESSION['delivery_error'] = $message;
    header('Location: ' . ($orderId ? '../request_delivery.php?order_id=' . $orderId : '../my_orders.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../my_orders.php');
    exit;
}

$orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$orderId = ($orderId === false || $orderId === null) ? null : $orderId;
if (!vayuma_csrf_valid()) {
    vayuma_delivery_request_error('Your session expired. Refresh the delivery form and try again.', $orderId);
}
if ($orderId === null) {
    vayuma_delivery_request_error('Choose a valid order for delivery.');
}

$postString = static function (string $key): string {
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
};
$pickup = $postString('pickup_location');
$dropoff = $postString('delivery_location');
$requestedDate = $postString('requested_date');
$notes = $postString('notes');
if ($pickup === '' || $dropoff === '' || strlen($pickup) > 255 || strlen($dropoff) > 255 || strlen($notes) > 5000) {
    vayuma_delivery_request_error('Enter pickup and delivery locations (255 characters maximum) and keep notes under 5,000 characters.', $orderId);
}
if ($requestedDate !== '') {
    $date = DateTime::createFromFormat('!Y-m-d', $requestedDate);
    if (!$date || $date->format('Y-m-d') !== $requestedDate) {
        vayuma_delivery_request_error('Choose a valid requested delivery date.', $orderId);
    }
} else {
    $requestedDate = null;
}

$userId = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id FROM orders WHERE id=? AND buyer_id=? AND status IN ('accepted','paid') LIMIT 1");
$stmt->bind_param('ii', $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$order) {
    vayuma_delivery_request_error('This order is not eligible for delivery.', $orderId);
}

try {
    $stmt = $conn->prepare(
        'INSERT INTO delivery_requests
            (order_id,requester_id,pickup_location,delivery_location,requested_date,notes)
         VALUES(?,?,?,?,?,?)'
    );
    if (!$stmt) {
        throw new mysqli_sql_exception('Unable to prepare delivery request insert.');
    }
    $stmt->bind_param('iissss', $orderId, $userId, $pickup, $dropoff, $requestedDate, $notes);
    $stmt->execute();
    $stmt->close();
} catch (mysqli_sql_exception $exception) {
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    if ((int)$exception->getCode() === 1062) {
        vayuma_delivery_request_error('A delivery request already exists for this order.', $orderId);
    }
    error_log('Delivery request creation failed: ' . $exception->getMessage());
    vayuma_delivery_request_error('The delivery request could not be saved right now. Please try again.', $orderId);
}

$_SESSION['delivery_notice'] = 'Your delivery request was sent to transport providers.';
header('Location: ../request_delivery.php?order_id=' . $orderId);
exit;
