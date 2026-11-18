<?php
/**
 * VAYUMA — Paystack order payment entry (DISABLED).
 * All new order payments now use M-Pesa PayBill STK Push.
 * This file is preserved for historical bookmarks but redirects to the M-Pesa flow.
 */
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}

$orderId = filter_var($_GET['order_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($orderId === false || $orderId === null) {
    header('Location: my_orders.php');
    exit;
}

// All new order payments go through M-Pesa STK Push only.
$_SESSION['mpesa_info'] = 'Paystack checkout is no longer available. Please use M-Pesa PayBill to pay for your order.';
header('Location: payment.php?order_id=' . $orderId);
exit;
