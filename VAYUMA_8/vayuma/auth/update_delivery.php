<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: ../login.php');
    exit;
}
if (strtolower(trim((string)($_SESSION['role'] ?? ''))) !== 'transport_provider') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !vayuma_csrf_valid()) {
    $_SESSION['delivery_action_notice'] = 'The delivery update request expired. Refresh and try again.';
    header('Location: ../transport_deliveries.php');
    exit;
}

$deliveryId = filter_var($_POST['delivery_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$status = $_POST['status'] ?? '';
if ($deliveryId === false || $deliveryId === null || !is_string($status) || !in_array($status, ['accepted', 'in_transit', 'delivered'], true)) {
    $_SESSION['delivery_action_notice'] = 'Choose a valid delivery update.';
    header('Location: ../transport_deliveries.php');
    exit;
}

$providerId = (int)$_SESSION['user_id'];
try {
    if ($status === 'accepted') {
        $stmt = $conn->prepare(
            "UPDATE delivery_requests
             SET status='accepted',transport_provider_id=?
             WHERE id=? AND status='requested' AND transport_provider_id IS NULL"
        );
        if (!$stmt) {
            throw new mysqli_sql_exception('Unable to prepare delivery acceptance.');
        }
        $stmt->bind_param('ii', $providerId, $deliveryId);
    } elseif ($status === 'in_transit') {
        $stmt = $conn->prepare(
            "UPDATE delivery_requests SET status='in_transit'
             WHERE id=? AND status='accepted' AND transport_provider_id=?"
        );
        if (!$stmt) {
            throw new mysqli_sql_exception('Unable to prepare delivery start.');
        }
        $stmt->bind_param('ii', $deliveryId, $providerId);
    } else {
        $stmt = $conn->prepare(
            "UPDATE delivery_requests SET status='delivered'
             WHERE id=? AND status='in_transit' AND transport_provider_id=?"
        );
        if (!$stmt) {
            throw new mysqli_sql_exception('Unable to prepare delivery completion.');
        }
        $stmt->bind_param('ii', $deliveryId, $providerId);
    }

    $stmt->execute();
    $updated = $stmt->affected_rows === 1;
    $stmt->close();
    $_SESSION['delivery_action_notice'] = $updated
        ? 'Delivery status updated.'
        : 'This delivery has already changed or is not assigned to your account. Refresh the list and try again.';
} catch (mysqli_sql_exception $exception) {
    error_log('Delivery status update failed: ' . $exception->getMessage());
    $_SESSION['delivery_action_notice'] = 'The delivery status could not be updated right now. Please try again.';
}

header('Location: ../transport_deliveries.php');
exit;
