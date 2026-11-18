<?php
/**
 * VAYUMA — M-Pesa PayBill Receipt
 * Displays the confirmed M-Pesa transaction details for a completed order payment.
 * Requires a valid order_id and that the payment status is 'paid'.
 */
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
$receipt = null;
$loadError = false;

if ($orderId !== false && $orderId !== null) {
    try {
        $stmt = $conn->prepare(
            "SELECT
                o.id AS order_id,
                o.total_amount,
                o.quantity,
                o.unit,
                o.status AS order_status,
                pl.produce_name,
                pl.image,
                p.id AS payment_id,
                p.payment_method,
                p.status AS payment_status,
                p.transaction_reference,
                p.mpesa_receipt_number,
                p.phone_number,
                p.amount AS payment_amount,
                p.result_code,
                p.result_description,
                p.callback_received_at,
                p.paid_at,
                p.created_at
            FROM orders o
            LEFT JOIN payments p ON p.order_id = o.id
            LEFT JOIN produce_listings pl ON pl.id = o.produce_listing_id
            WHERE o.id = ? AND o.buyer_id = ?
            ORDER BY p.id DESC
            LIMIT 1"
        );
        if (!$stmt) {
            throw new mysqli_sql_exception('Unable to prepare receipt lookup.');
        }
        $stmt->bind_param('ii', $orderId, $userId);
        $stmt->execute();
        $receipt = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
    } catch (mysqli_sql_exception $exception) {
        error_log('M-Pesa receipt lookup failed: ' . $exception->getMessage());
        $loadError = true;
    }
}

$isPaid = $receipt && (string)($receipt['payment_status'] ?? '') === 'paid';
$hasMpesaReceipt = $isPaid && !empty($receipt['mpesa_receipt_number']);

if (!$receipt) {
    http_response_code(404);
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Receipt not found | VAYUMA</title>';
    echo '<link rel="stylesheet" href="frontend/css/tokens.css"><link rel="stylesheet" href="frontend/css/components.css"><link rel="stylesheet" href="frontend/css/layout.css">';
    echo '<style>.va-receipt-notfound{max-width:640px;margin:3rem auto;padding:0 1rem}.va-receipt-notfound__card{padding:2rem;background:var(--va-surface);border:1px solid var(--va-border);border-radius:var(--va-radius-lg);box-shadow:var(--va-shadow-sm)}.va-receipt-notfound__card h1{color:var(--va-text-primary)}.va-receipt-notfound__card p{color:var(--va-text-secondary)}</style></head><body>';
    echo '<main class="va-receipt-notfound" id="va-main-content"><section class="va-receipt-notfound__card" role="alert"><h1>Receipt not found</h1><p>This payment receipt is unavailable or does not belong to your account.</p><a class="va-btn va-btn--primary" href="my_orders.php">Return to orders</a></section></main></body></html>';
    exit;
}

$paidAt = !empty($receipt['paid_at']) ? date('d M Y, h:i A', strtotime($receipt['paid_at'])) : '—';
$callbackAt = !empty($receipt['callback_received_at']) ? date('d M Y, h:i A', strtotime($receipt['callback_received_at'])) : '—';
?>
<!doctype html>
<html lang="<?= htmlspecialchars((string)($current_language ?? 'en'), ENT_QUOTES, 'UTF-8') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>M-Pesa Receipt | VAYUMA</title>
<link rel="stylesheet" href="frontend/css/tokens.css">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css?v=20261110-nav">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
<style>
.va-receipt-page { max-width: 700px; margin: 2rem auto; padding: 0 1rem; }
.va-receipt-card { background: var(--va-surface); border: 1px solid var(--va-border); border-radius: var(--va-radius-lg); box-shadow: var(--va-shadow-sm); overflow: hidden; }
.va-receipt-header { background: var(--va-primary); color: #ffffff; padding: 1.5rem; }
.va-receipt-header h1 { margin: 0; font-size: 1.25rem; }
.va-receipt-header p { margin: 0.25rem 0 0; opacity: 0.9; font-size: 0.9rem; }
.va-receipt-body { padding: 1.5rem; }
.va-receipt-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.va-receipt-field { border: 1px solid var(--va-border); border-radius: var(--va-radius-md); padding: 0.85rem 1rem; background: var(--va-bg); }
.va-receipt-field__label { display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--va-text-muted); margin-bottom: 0.35rem; }
.va-receipt-field__value { display: block; font-size: 1rem; color: var(--va-text-primary); word-break: break-all; }
.va-receipt-field__value--paid { color: var(--va-success); font-weight: var(--va-font-weight-bold); }
.va-receipt-actions { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--va-border); }
.va-receipt-actions .va-btn { flex: 1 1 160px; }
.va-receipt-success-badge { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: #e4f6e9; color: var(--va-success); border-radius: var(--va-radius-full); font-weight: var(--va-font-weight-bold); font-size: 0.875rem; margin-bottom: 1rem; }
.va-receipt-success-badge svg { width: 16px; height: 16px; }
@media (max-width: 480px) {
    .va-receipt-grid { grid-template-columns: 1fr; }
    .va-receipt-actions { flex-direction: column; }
    .va-receipt-actions .va-btn { flex: none; width: 100%; }
}
</style>
</head>
<body>
<?php vayuma_language_selector(); ?>
<?php vayuma_payment_navigation('my_orders.php', 'My Orders'); ?>
<main class="va-receipt-page" id="va-main-content">
<div class="va-receipt-card">
  <div class="va-receipt-header">
    <h1>M-Pesa Payment Receipt</h1>
    <p>VAYUMA Marketplace · Order #<?= (int)$receipt['order_id'] ?></p>
  </div>
  <div class="va-receipt-body">
    <?php if ($isPaid): ?>
    <div class="va-receipt-success-badge">
      <svg class="va-icon" width="16" height="16"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg>
      Payment confirmed by Safaricom
    </div>
    <?php else: ?>
    <div style="padding:0.5rem 1rem;background:#fff4d6;color:#b06a00;border-radius:var(--va-radius-full);font-weight:var(--va-font-weight-bold);font-size:0.875rem;margin-bottom:1rem;">
      Payment status: <?= htmlspecialchars(ucfirst((string)($receipt['payment_status'] ?? 'pending')), ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>

    <div class="va-receipt-grid">
      <div class="va-receipt-field">
        <span class="va-receipt-field__label">Order</span>
        <span class="va-receipt-field__value">#<?= (int)$receipt['order_id'] ?></span>
      </div>
      <div class="va-receipt-field">
        <span class="va-receipt-field__label">Produce</span>
        <span class="va-receipt-field__value"><?= htmlspecialchars((string)($receipt['produce_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <div class="va-receipt-field">
        <span class="va-receipt-field__label">Quantity</span>
        <span class="va-receipt-field__value"><?= htmlspecialchars((string)($receipt['quantity'] ?? '—'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)($receipt['unit'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <div class="va-receipt-field">
        <span class="va-receipt-field__label">Amount paid</span>
        <span class="va-receipt-field__value<?= $isPaid ? ' va-receipt-field__value--paid' : '' ?>">KSh <?= number_format((float)($receipt['payment_amount'] ?? $receipt['total_amount']), 2) ?></span>
      </div>
      <div class="va-receipt-field">
        <span class="va-receipt-field__label">Payment method</span>
        <span class="va-receipt-field__value">M-Pesa PayBill</span>
      </div>
      <div class="va-receipt-field">
        <span class="va-receipt-field__label">Phone number</span>
        <span class="va-receipt-field__value"><?= htmlspecialchars((string)($receipt['phone_number'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <?php if ($hasMpesaReceipt): ?>
      <div class="va-receipt-field">
        <span class="va-receipt-field__label">M-Pesa receipt number</span>
        <span class="va-receipt-field__value"><?= htmlspecialchars((string)$receipt['mpesa_receipt_number'], ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <?php endif; ?>
      <div class="va-receipt-field">
        <span class="va-receipt-field__label">Transaction reference</span>
        <span class="va-receipt-field__value"><?= htmlspecialchars((string)($receipt['transaction_reference'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <div class="va-receipt-field">
        <span class="va-receipt-field__label">Payment status</span>
        <span class="va-receipt-field__value"><?= htmlspecialchars(ucfirst((string)($receipt['payment_status'] ?? 'pending')), ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <div class="va-receipt-field">
        <span class="va-receipt-field__label">Confirmed at</span>
        <span class="va-receipt-field__value"><?= $isPaid ? $paidAt : '—' ?></span>
      </div>
      <div class="va-receipt-field">
        <span class="va-receipt-field__label">Safaricom callback</span>
        <span class="va-receipt-field__value"><?= $callbackAt ?></span>
      </div>
    </div>

    <?php if ($isPaid && !empty($receipt['result_description'])): ?>
    <div style="margin-top:1rem;padding:0.85rem 1rem;background:var(--va-bg);border:1px solid var(--va-border);border-radius:var(--va-radius-md);font-size:0.875rem;color:var(--va-text-secondary);">
      <strong style="display:block;margin-bottom:0.25rem;color:var(--va-text-primary);">Safaricom response:</strong>
      <?= htmlspecialchars((string)$receipt['result_description'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>

    <div class="va-receipt-actions">
      <a class="va-btn va-btn--primary" href="my_orders.php">Return to orders</a>
      <?php if ($isPaid): ?>
      <a class="va-btn va-btn--outline" href="produce_details.php?id=<?= (int)($receipt['produce_listing_id'] ?? 0) ?>">View produce</a>
      <?php endif; ?>
    </div>
  </div>
</div>
</main>
<script src="assets/js/vayuma-language.js" defer></script>
</body>
</html>
