<?php
session_start();
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}

$orderId = filter_var($_GET['order_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$userId = (int)$_SESSION['user_id'];
$order = null;
$loadError = false;

if ($orderId !== false && $orderId !== null) {
    try {
        $stmt = $conn->prepare(
            "SELECT o.id, o.total_amount, p.status AS payment_status,
                    p.payment_method, p.transaction_reference, pl.produce_name
             FROM orders o
             LEFT JOIN payments p ON p.order_id = o.id
             LEFT JOIN produce_listings pl ON pl.id = o.produce_listing_id
             WHERE o.id = ? AND o.buyer_id = ?
             ORDER BY CASE WHEN p.status = 'paid' THEN 0 ELSE 1 END, p.id DESC
             LIMIT 1"
        );
        if (!$stmt) {
            throw new mysqli_sql_exception('Unable to prepare payment status lookup.');
        }
        $stmt->bind_param('ii', $orderId, $userId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
    } catch (mysqli_sql_exception $exception) {
        error_log('M-Pesa pending status lookup failed: ' . $exception->getMessage());
        $loadError = true;
    }
}

$status = (string)($order['payment_status'] ?? '');
$isPaid = $status === 'paid';
$isFailed = in_array($status, ['failed', 'cancelled'], true);
$reference = trim((string)($order['transaction_reference'] ?? ''));
?>
<!doctype html>
<html lang="<?= htmlspecialchars($current_language, ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Payment Status | VAYUMA</title>
  <link rel="stylesheet" href="frontend/css/tokens.css">
  <link rel="stylesheet" href="frontend/css/components.css">
  <link rel="stylesheet" href="frontend/css/components_addition.css">
  <link rel="stylesheet" href="frontend/css/layout.css">
  <link rel="stylesheet" href="frontend/css/pages.css">
  <style>
    .va-payment-status { max-width: 680px; margin: 3rem auto; padding: 0 1rem; }
    .va-payment-status__card { padding: clamp(1.25rem, 4vw, 2.25rem); background: var(--va-surface); border: 1px solid var(--va-border); border-radius: var(--va-radius-lg); box-shadow: var(--va-shadow-sm); }
    .va-payment-status__card h1 { margin-bottom: .75rem; color: var(--va-text-primary); text-wrap: balance; }
    .va-payment-status__card p { margin-bottom: 1rem; color: var(--va-text-secondary); }
    .va-payment-status__actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.5rem; }
  </style>
</head>
<body>
<?php vayuma_language_selector(); ?>
<main class="va-payment-status" id="va-main-content">
  <section class="va-payment-status__card" aria-live="polite">
    <?php if ($loadError): ?>
      <h1>Payment status unavailable</h1>
      <p>We could not retrieve the latest status. Your payment has not been marked successful here. Check your orders again shortly.</p>
    <?php elseif (!$order): ?>
      <h1>Order not found</h1>
      <p>This order is unavailable or does not belong to your account.</p>
    <?php elseif ($isPaid): ?>
      <h1>Payment confirmed</h1>
      <p>Your payment of KSh <?= number_format((float)$order['total_amount'], 2) ?> has been verified.</p>
      <?php if ($reference !== ''): ?><p>Transaction reference: <strong><?= htmlspecialchars($reference, ENT_QUOTES, 'UTF-8') ?></strong></p><?php endif; ?>
    <?php elseif ($isFailed): ?>
      <h1>Payment not completed</h1>
      <?php if ($status === 'cancelled'): ?>
      <p>You cancelled the M-Pesa prompt (or did not enter your PIN in time). No money was deducted. Your order is still active and you can retry payment.</p>
      <?php else: ?>
      <p>The payment was not confirmed by Safaricom. No money was deducted. You can review this order and retry using M-Pesa.</p>
      <?php endif; ?>
    <?php else: ?>
      <h1>Check your phone</h1>
      <p>We sent an M-Pesa prompt<?= $order ? ' for KSh ' . number_format((float)$order['total_amount'], 2) : '' ?>. Enter your PIN on the phone. The order changes to paid only after Safaricom confirms the transaction.</p>
      <p>Payment is still pending. You can safely check your orders for updates.</p>
    <?php endif; ?>

    <?php if ($order && !empty($order['produce_name'])): ?>
      <p><?= htmlspecialchars((string)$order['produce_name'], ENT_QUOTES, 'UTF-8') ?> · Order #<?= (int)$order['id'] ?></p>
    <?php endif; ?>
    <div class="va-payment-status__actions">
      <a class="va-btn va-btn--primary" href="my_orders.php">My Orders</a>
      <?php if ($order && $isFailed): ?>
        <a class="va-btn va-btn--outline" href="payment.php?order_id=<?= (int)$order['id'] ?>">Retry Payment</a>
      <?php elseif ($order && $isPaid): ?>
        <a class="va-btn va-btn--outline" href="mpesa_receipt.php?order_id=<?= (int)$order['id'] ?>">View M-Pesa receipt</a>
      <?php endif; ?>
    </div>
  </section>
</main>
<script src="assets/js/vayuma-language.js" defer></script>
<?php if ($order && !$isPaid && !$isFailed && !$loadError): ?>
<script>
(function () {
  const orderId = <?= (int)$order['id'] ?>;
  let attempts = 0;
  const timer = setInterval(async function () {
    attempts += 1;
    try {
      const response = await fetch('api/order_payment_status.php?order_id=' + orderId, { credentials: 'same-origin' });
      const data = await response.json();
      if (data.paid) {
        clearInterval(timer);
        window.location.href = 'payment_success.php?order_id=' + orderId;
        return;
      }
      if (data.status === 'failed' || data.status === 'cancelled') {
        clearInterval(timer);
        window.location.reload();
      }
    } catch (error) {
      // Keep waiting. Safaricom can take a moment to confirm the PIN.
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
