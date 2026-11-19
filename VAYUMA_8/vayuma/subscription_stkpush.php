<?php
require_once __DIR__ . '/includes/payment_gate.php';
vayuma_require_payment_processing();
/**
 * VAYUMA — M-Pesa STK Push initiation for subscription payments.
 * Customer enters phone number → STK push sent → PIN entered on phone → callback confirms.
 */
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/lib/subscription_lib.php';
require_once __DIR__ . '/mpesa_lib.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !vayuma_csrf_valid()) {
    $_SESSION['subscription_error'] = 'Your payment session expired. Reload the page and enter your M-Pesa number again.';
    header('Location: subscription.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$role = (string)($_SESSION['role'] ?? '');
$transactionOpen = false;

try {
    $phone = normalize_mpesa_phone(trim((string)($_POST['phone'] ?? '')));
    $membership = ensure_user_subscription($conn, $userId, $role);
    if (!$membership || ($membership['plan_type'] ?? '') !== 'paid') {
        throw new RuntimeException('M-Pesa is available only for your paid stakeholder plan.');
    }
    $provider = (string)($membership['payment_provider'] ?? '');
    $stillActive = $membership['status'] === 'active'
        && !empty($membership['expires_at'])
        && strtotime($membership['expires_at']) >= time();
    if ($stillActive && $provider !== '' && $provider !== 'mpesa') {
        throw new RuntimeException('Your current subscription is managed by another payment provider. You can pay with M-Pesa after it expires.');
    }
    if ($stillActive && ($provider === '' || $provider === 'mpesa')) {
        throw new RuntimeException('Your subscription is already active. You do not need to pay again before it expires.');
    }

    $subscriptionId = (int)$membership['id'];
    $amount = (float)$membership['monthly_price'];
    if ($amount < 1) {
        throw new RuntimeException('This plan does not have a payable amount.');
    }
    $billingInterval = (string)($membership['plan_billing_interval'] ?? $membership['billing_interval'] ?? 'monthly');

    $transactionOpen = false;
    $conn->begin_transaction();
    $transactionOpen = true;
    $stmt = $conn->prepare(
        "SELECT id, TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age_seconds
         FROM subscription_payments
         WHERE subscription_id=? AND payment_method='mpesa' AND status='pending'
         ORDER BY id DESC LIMIT 1 FOR UPDATE"
    );
    $stmt->bind_param('i', $subscriptionId);
    $stmt->execute();
    $pending = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($pending && (int)$pending['age_seconds'] < 90) {
        throw new RuntimeException('An M-Pesa prompt is already on its way. Check the phone and enter your PIN.');
    }
    if ($pending) {
        $replaced = 'Replaced by a new M-Pesa prompt.';
        $stmt = $conn->prepare("UPDATE subscription_payments SET status='failed', result_description=? WHERE subscription_id=? AND payment_method='mpesa' AND status='pending'");
        $stmt->bind_param('si', $replaced, $subscriptionId);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conn->prepare(
        "INSERT INTO subscription_payments
         (subscription_id, user_id, amount, payment_method, billing_interval, phone_number, status)
         VALUES (?, ?, ?, 'mpesa', ?, ?, 'pending')"
    );
    $stmt->bind_param('iidss', $subscriptionId, $userId, $amount, $billingInterval, $phone);
    $stmt->execute();
    $paymentId = (int)$stmt->insert_id;
    $stmt->close();
    $conn->commit();
    $transactionOpen = false;
} catch (Throwable $exception) {
    if (!empty($transactionOpen)) {
        $conn->rollback();
    }
    $_SESSION['subscription_error'] = $exception->getMessage();
    header('Location: subscription.php');
    exit;
}

try {
    [$response, $normalizedPhone, $charged] = mpesa_subscription_stk_push($subscriptionId, $amount, $phone);
    if ((string)($response['ResponseCode'] ?? '') !== '0') {
        throw new RuntimeException($response['ResponseDescription'] ?? $response['errorMessage'] ?? 'Safaricom did not accept the PayBill request.');
    }
    $merchant = (string)($response['MerchantRequestID'] ?? '');
    $checkout = (string)($response['CheckoutRequestID'] ?? '');
    if ($checkout === '') {
        throw new RuntimeException('Safaricom did not return a checkout reference.');
    }
    $chargedAmount = (float)$charged;
    $stmt = $conn->prepare(
        'UPDATE subscription_payments
         SET merchant_request_id=?, checkout_request_id=?, phone_number=?, amount=?
         WHERE id=?'
    );
    $stmt->bind_param('sssdi', $merchant, $checkout, $normalizedPhone, $chargedAmount, $paymentId);
    $stmt->execute();
    $stmt->close();
    header('Location: subscription_pending.php?payment_id=' . $paymentId);
    exit;
} catch (Throwable $exception) {
    $message = substr($exception->getMessage(), 0, 255);
    $stmt = $conn->prepare("UPDATE subscription_payments SET status='failed', result_description=? WHERE id=? AND status='pending'");
    $stmt->bind_param('si', $message, $paymentId);
    $stmt->execute();
    $stmt->close();
    $_SESSION['subscription_error'] = $exception->getMessage();
    header('Location: subscription.php');
    exit;
}
