<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}

$orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($orderId === false || $orderId === null) {
    header('Location: my_orders.php');
    exit;
}

$_SESSION['mpesa_error'] = 'Bank transfer is no longer available for new orders. Pay with M-Pesa PayBill instead.';
header('Location: payment.php?order_id=' . $orderId);
exit;
