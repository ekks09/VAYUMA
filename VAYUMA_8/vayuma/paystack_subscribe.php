<?php
/**
 * VAYUMA — Paystack subscription entry (DISABLED).
 * All new subscription payments now use M-Pesa PayBill STK Push.
 * This file is preserved for historical bookmarks but redirects to the M-Pesa flow.
 */
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}

// All new subscription payments go through M-Pesa STK Push only.
$_SESSION['mpesa_info'] = 'Paystack checkout is no longer available. Please use M-Pesa PayBill to pay for your subscription.';
header('Location: subscription.php');
exit;
