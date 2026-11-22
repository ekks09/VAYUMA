<?php
session_start();
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/payment_navigation.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}

$paymentId = filter_var($_GET['payment_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$userId = (int)$_SESSION['user_id'];
$payment = null;

if ($paymentId) {
    $stmt = $conn->prepare('SELECT id, status, amount FROM subscription_payments WHERE id=? AND user_id=? LIMIT 1');
    $stmt->bind_param('ii', $paymentId, $userId);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
}

if ($payment && $payment['status'] === 'paid') {
    header('Location: subscription_receipt.php?payment_id=' . (int)$payment['id']);
    exit;
}

$status = (string)($payment['status'] ?? '');
$failed = in_array($status, ['failed', 'cancelled'], true);
?>
<!doctype html>
<html lang="<?= htmlspecialchars($current_language ?? 'en', ENT_QUOTES, 'UTF-8') ?>">
<head><?php require __DIR__ . '/includes/pwa.php'; ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>M-Pesa subscription | VAYUMA</title>
  <link rel="stylesheet" href="frontend/css/tokens.css">
  <link rel="stylesheet" href="frontend/css/components.css">
  <link rel="stylesheet" href="frontend/css/components_addition.css?v=20261110-nav">
  <link rel="stylesheet" href="frontend/css/layout.css">
  <link rel="stylesheet" href="frontend/css/pages.css">
  <style>
    .va-payment-status { max-width: 680px; margin: 3rem auto; padding: 0 1rem; }
    .va-payment-status__card { padding: clamp(1.25rem, 4vw, 2.25rem); background: var(--va-surface); border: 1px solid var(--va-border); border-radius: var(--va-radius-lg); }
    .va-payment-status__actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.5rem; }
  </style>
</head>
<body>
<?php vayuma_language_selector(); ?>
<?php vayuma_payment_navigation('subscription.php', 'Back to subscription'); ?>
<main class="va-payment-status" id="va-main-content"><?php require __DIR__ . '/includes/workspace_back.php'; ?>
  <section class="va-payment-status__card" aria-live="polite">
    <?php if (!$payment): ?>
      <h1>Payment not found</h1>
      <p>This subscription payment is unavailable or does not belong to your account.</p>
    <?php elseif ($failed): ?>
      <h1>Payment not completed</h1>
      <p>The M-Pesa prompt was cancelled or could not be confirmed. You can send a new prompt from your subscription page.</p>
    <?php else: ?>
      <h1>Check your phone</h1>
      <p>Enter your M-Pesa PIN to pay<?= $payment ? ' KSh ' . number_format((float)$payment['amount'], 2) : '' ?>. VAYUMA activates the subscription only after Safaricom confirms the PayBill payment.</p>
      <p>Payment status: <strong><?= htmlspecialchars(ucfirst($status !== '' ? $status : 'pending'), ENT_QUOTES, 'UTF-8') ?></strong></p>
    <?php endif; ?>
    <div class="va-payment-status__actions">
      <a class="va-btn va-btn--primary" href="subscription.php">Back to subscription</a>
    </div>
  </section>
</main>
<script src="assets/js/vayuma-language.js" defer></script>
<?php if ($payment && !$failed): ?>
<script>
(function () {
  const paymentId = <?= (int)$payment['id'] ?>;
  let attempts = 0;
  const timer = setInterval(async function () {
    attempts += 1;
    try {
      const response = await fetch('api/subscription_status.php?payment_id=' + paymentId, { credentials: 'same-origin' });
      const data = await response.json();
      if (data.saved) {
        clearInterval(timer);
        window.location.href = 'subscription_receipt.php?payment_id=' + paymentId;
        return;
      }
      if (data.status === 'failed' || data.status === 'cancelled') {
        clearInterval(timer);
        window.location.reload();
      }
    } catch (error) {
      // Keep waiting for the phone prompt to finish.
    }
    if (attempts >= 40) {
      clearInterval(timer);
    }
  }, 3000);
})();
</script>
<?php endif; ?>
</body>
</html>
