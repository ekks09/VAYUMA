<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true || strtolower(trim((string)($_SESSION['role'] ?? ''))) !== 'farmer') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !vayuma_csrf_valid()) {
    $_SESSION['seller_order_notice'] = 'The order update request expired. Refresh and try again.';
    $_SESSION['seller_order_notice_type'] = 'error';
    header('Location: seller_orders.php');
    exit;
}

$orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$status = $_POST['status'] ?? '';
if ($orderId === false || $orderId === null || !is_string($status) || !in_array($status, ['accepted', 'rejected'], true)) {
    $_SESSION['seller_order_notice'] = 'Choose a valid order update.';
    $_SESSION['seller_order_notice_type'] = 'error';
    header('Location: seller_orders.php');
    exit;
}

$sellerId = (int)$_SESSION['user_id'];
$transactionStarted = false;
try {
    $conn->begin_transaction();
    $transactionStarted = true;
    $stmt = $conn->prepare(
        "SELECT o.id,o.status,o.quantity,o.produce_listing_id,
                pl.quantity AS available_quantity,pl.status AS listing_status
         FROM orders o
         INNER JOIN produce_listings pl ON o.produce_listing_id=pl.id
         WHERE o.id=? AND o.seller_id=? FOR UPDATE"
    );
    $stmt->bind_param('ii', $orderId, $sellerId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order) {
        throw new DomainException('Order not found.');
    }
    if ($order['status'] !== 'pending') {
        throw new DomainException('This order has already been processed.');
    }
    if ($status === 'accepted') {
        if ($order['listing_status'] !== 'available' || (float)$order['quantity'] > (float)$order['available_quantity']) {
            throw new DomainException('There is not enough produce available to accept this request.');
        }

        $stmt = $conn->prepare(
            "UPDATE produce_listings
             SET quantity=quantity-?,status=CASE WHEN quantity-? <= 0 THEN 'sold' ELSE status END
             WHERE id=? AND status='available' AND quantity>=?"
        );
        $stmt->bind_param('ddid', $order['quantity'], $order['quantity'], $order['produce_listing_id'], $order['quantity']);
        $stmt->execute();
        $listingUpdated = $stmt->affected_rows === 1;
        $stmt->close();
        if (!$listingUpdated) {
            throw new DomainException('There is not enough produce available to accept this request.');
        }
    }

    $stmt = $conn->prepare('UPDATE orders SET status=? WHERE id=? AND seller_id=? AND status=\'pending\'');
    $stmt->bind_param('sii', $status, $orderId, $sellerId);
    $stmt->execute();
    $orderUpdated = $stmt->affected_rows === 1;
    $stmt->close();
    if (!$orderUpdated) {
        throw new DomainException('This order has already been processed.');
    }

    $conn->commit();
    $transactionStarted = false;
    $_SESSION['seller_order_notice'] = $status === 'accepted'
        ? 'The order was accepted and inventory was updated.'
        : 'The order request was declined.';
    $_SESSION['seller_order_notice_type'] = 'success';
} catch (Throwable $exception) {
    error_log('VAYUMA seller order update unavailable.');
    if ($transactionStarted) {
        try {
            $conn->rollback();
        } catch (Throwable $rollbackException) {
            error_log('VAYUMA seller order rollback unavailable.');
        }
    }
    $_SESSION['seller_order_notice'] = $exception instanceof DomainException
        ? $exception->getMessage()
        : 'The order could not be updated right now. Please try again.';
    $_SESSION['seller_order_notice_type'] = 'error';
}

header('Location: seller_orders.php');
exit;
