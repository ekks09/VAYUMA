<?php
session_start();
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/payment_navigation.php';

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
            "SELECT o.id, o.total_amount, o.status AS order_status,
                    p.status AS payment_status, p.payment_method,
                    p.transaction_reference, pl.produce_name
             FROM orders o
             LEFT JOIN payments p ON p.order_id = o.id
             LEFT JOIN produce_listings pl ON pl.id = o.produce_listing_id
             WHERE o.id = ? AND o.buyer_id = ?
             ORDER BY CASE
               WHEN p.status = 'paid' THEN 0
               WHEN p.status = 'pending' THEN 1
               ELSE 2
             END, p.id DESC
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
        error_log('Payment status lookup failed: ' . $exception->getMessage());
        $loadError = true;
    }
}

$paymentStatus = (string)($order['payment_status'] ?? '');
$isPaid = $paymentStatus === 'paid';
$isPending = $paymentStatus === 'pending';
$isFailed = in_array($paymentStatus, ['failed', 'cancelled'], true);
$paymentMethods = [
    'mpesa' => 'M-Pesa',
    'bank' => 'Bank transfer',
    'paystack' => 'Paystack',
];
$paymentMethod = $paymentMethods[(string)($order['payment_method'] ?? '')] ?? '';
$paymentReference = trim((string)($order['transaction_reference'] ?? ''));
?>
<!doctype html>
<html lang="<?= htmlspecialchars($current_language, ENT_QUOTES, 'UTF-8') ?>">
<head><?php require __DIR__ . '/includes/pwa.php'; ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, interactive-widget=resizes-content">
  <title>Payment Status | VAYUMA TRIX</title>
  <link rel="stylesheet" href="frontend/css/tokens.css">
  <link rel="stylesheet" href="frontend/css/components.css">
  <link rel="stylesheet" href="frontend/css/components_addition.css?v=20261110-nav">
  <link rel="stylesheet" href="frontend/css/layout.css">
  <link rel="stylesheet" href="frontend/css/pages.css">
  <style>
    .va-payment-status { max-width: 680px; margin: 3rem auto; padding: 0 1rem; }
    .va-payment-status__card { padding: clamp(1.25rem, 4vw, 2.25rem); background: var(--va-surface); border: 1px solid var(--va-border); border-radius: var(--va-radius-lg); box-shadow: var(--va-shadow-sm); }
    .va-payment-status__card h1 { margin: 0 0 .75rem; color: var(--va-text-primary); text-wrap: balance; }
    .va-payment-status__message { margin-bottom: 1.25rem; color: var(--va-text-secondary); }
    .va-payment-status__details { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: .75rem 1rem; padding: 1rem 0; border-top: 1px solid var(--va-border); border-bottom: 1px solid var(--va-border); }
    .va-payment-status__details dt { color: var(--va-text-muted); }
    .va-payment-status__details dd { min-width: 0; margin: 0; color: var(--va-text-primary); font-weight: 600; overflow-wrap: anywhere; }
    .va-payment-status__actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.5rem; }
    .va-payment-status__actions .va-btn { flex: 1 1 180px; }
    @media (max-width: 480px) { .va-payment-status__details { grid-template-columns: 1fr; gap: .25rem; } }
  </style>
</head>
<body>
<?php vayuma_language_selector(); ?>
<?php vayuma_payment_navigation('my_orders.php', 'My Orders'); ?>
<main class="va-payment-status" id="va-main-content"><?php require __DIR__ . '/includes/workspace_back.php'; ?>
  <section class="va-payment-status__card" aria-live="polite">
    <?php if ($loadError): ?>
      <h1>Payment status unavailable</h1>
      <p class="va-payment-status__message">We could not retrieve this payment status. Your payment has not been marked successful here. Please check your orders or try again shortly.</p>
    <?php elseif (!$order): ?>
      <h1>Order not found</h1>
      <p class="va-payment-status__message">This order is unavailable or does not belong to your account.</p>
    <?php elseif ($isPaid): ?>
      <h1>Payment confirmed</h1>
      <p class="va-payment-status__message">Your payment has been verified.</p>
    <?php elseif ($isPending): ?>
      <h1>Payment pending</h1>
      <p class="va-payment-status__message">We have not received final confirmation yet. Check your orders again before retrying payment.</p>
    <?php elseif ($isFailed): ?>
      <h1>Payment not completed</h1>
      <p class="va-payment-status__message">The payment was not confirmed. You can review this order and retry using an available payment option.</p>
    <?php else: ?>
      <h1>Payment not verified</h1>
      <p class="va-payment-status__message">There is no verified payment for this order yet. Check your orders for the latest status.</p>
    <?php endif; ?>

    <?php if ($order): ?>
      <dl class="va-payment-status__details">
        <dt>Order</dt>
        <dd>#<?= (int)$order['id'] ?><?= !empty($order['produce_name']) ? ' · ' . htmlspecialchars((string)$order['produce_name'], ENT_QUOTES, 'UTF-8') : '' ?></dd>
        <dt>Amount</dt>
        <dd>KSh <?= number_format((float)$order['total_amount'], 2) ?></dd>
        <?php if ($paymentMethod !== ''): ?>
          <dt>Payment method</dt>
          <dd><?= htmlspecialchars($paymentMethod, ENT_QUOTES, 'UTF-8') ?></dd>
        <?php endif; ?>
        <?php if ($isPaid && $paymentReference !== ''): ?>
          <dt>Transaction reference</dt>
          <dd><?= htmlspecialchars($paymentReference, ENT_QUOTES, 'UTF-8') ?></dd>
        <?php endif; ?>
        <?php if ($isPaid && $paymentMethod === 'M-Pesa'): ?>
          <dt>M-Pesa receipt</dt>
          <dd><a href="mpesa_receipt.php?order_id=<?= (int)$order['id'] ?>" class="va-text-primary va-font-semibold">View receipt</a></dd>
        <?php endif; ?>
      </dl>
    <?php endif; ?>

    <div class="va-payment-status__actions">
      <a class="va-btn va-btn--primary" href="my_orders.php">My Orders</a>
      <?php if ($order && !$loadError): ?>
        <a class="va-btn va-btn--outline" href="payment.php?order_id=<?= (int)$order['id'] ?>">View Payment Options</a>
      <?php endif; ?>
    </div>
  </section>
</main>
<script src="assets/js/vayuma-language.js" defer></script>
</body>
</html>
