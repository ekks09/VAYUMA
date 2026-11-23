<?php
/**
 * VAYUMA — Paystack subscription entry (DISABLED).
 * Subscription payment processing is currently disabled.
 * This file is preserved for historical bookmarks and redirects to the subscription page.
 */
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}

// Never instruct members to send money until verified payments are available.
header('Location: subscription.php');
exit;
