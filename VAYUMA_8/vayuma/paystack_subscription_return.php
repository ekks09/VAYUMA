<?php
require_once __DIR__ . '/includes/payment_gate.php';
vayuma_require_payment_processing();
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/paystack.php';
require_once __DIR__ . '/lib/paystack_subscription_payments.php';
require_once __DIR__ . '/includes/role_routes.php';
require_once __DIR__ . '/includes/payment_navigation.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$profileUrl = vayuma_role_route((string)($_SESSION['role'] ?? ''), 'profile') ?? 'role.php';
$reference = trim((string)($_GET['reference'] ?? ''));
$error = '';
if ($reference === '') {
    $error = 'The payment reference is missing. Check your subscription page for the latest status.';
} else {
    $stmt = $conn->prepare(
        'SELECT id, status FROM subscription_payments
         WHERE user_id=? AND payment_method=\'paystack\' AND paystack_reference=? LIMIT 1'
    );
    $stmt->bind_param('is', $userId, $reference);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$payment) {
        http_response_code(404);
        $error = 'This payment does not belong to your account.';
    } elseif ($payment['status'] !== 'paid') {
        try {
            $verified = paystack_verify($reference);
            if (($verified['status'] ?? '') === 'success') {
                paystack_apply_verified_subscription_payment($conn, $reference, $verified, null, 'callback');
            } else {
                $stmt = $conn->prepare(
                    "UPDATE subscription_payments SET status='failed', callback_received_at=NOW()
                     WHERE id=? AND status='pending'"
                );
                $stmt->bind_param('i', $payment['id']);
                $stmt->execute();
                $stmt->close();
                $error = 'Paystack did not confirm a successful payment. You can try again from your subscription page.';
            }
        } catch (Throwable $e) {
            error_log('VAYUMA Paystack return verification failed: ' . $e->getMessage());
            $error = 'We could not confirm the payment just yet. Your account will update after Paystack verification completes.';
        }
    }

    $stmt = $conn->prepare(
        'SELECT id, status FROM subscription_payments WHERE user_id=? AND paystack_reference=? LIMIT 1'
    );
    $stmt->bind_param('is', $userId, $reference);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($payment && $payment['status'] === 'paid') {
        header('Location: subscription_receipt.php?payment_id=' . (int)$payment['id']);
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head><?php require __DIR__ . '/includes/pwa.php'; ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Subscription payment | VAYUMA TRIX</title>
  <link rel="stylesheet" href="frontend/css/tokens.css">
  <link rel="stylesheet" href="frontend/css/components.css">
  <link rel="stylesheet" href="frontend/css/components_addition.css?v=20261110-nav">
  <link rel="stylesheet" href="frontend/css/layout.css">
</head>
<body>
  <?php vayuma_payment_navigation('subscription.php', 'Back to subscription'); ?>
  <main class="va-container" style="max-width:620px;padding-top:4rem"><?php require __DIR__ . '/includes/workspace_back.php'; ?>
    <section class="va-card va-card--p">
      <h1>Payment status</h1>
      <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
      <p>Do not pay again while a payment is being verified. You can check your subscription status below.</p>
      <a class="va-btn va-btn--primary" href="subscription.php">View subscription</a>
      <a class="va-btn va-btn--outline" href="<?= htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8') ?>">Back to your profile</a>
    </section>
  </main>
</body>
</html>
