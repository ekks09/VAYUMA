<?php
session_start();
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . '/includes/payment_feedback.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/payment_navigation.php';
require_once __DIR__ . '/mpesa_lib.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    vayuma_payment_feedback('Invalid order', 'Choose an order from My Orders before continuing.', 400);
}

$sql = "
    SELECT 
        o.id,
        o.buyer_id,
        o.seller_id,
        o.quantity,
        o.unit,
        o.price_per_unit,
        o.total_amount,
        o.status,
        p.produce_name,
        p.image
    FROM orders o
    INNER JOIN produce_listings p
        ON o.produce_listing_id = p.id
    WHERE o.id = ?
      AND o.buyer_id = ?
";

try {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new mysqli_sql_exception('Unable to prepare order lookup.');
    }
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
} catch (mysqli_sql_exception $exception) {
    error_log('Payment order lookup failed: ' . $exception->getMessage());
    vayuma_payment_feedback('Payment unavailable', 'We could not load this order. Please try again from My Orders.', 500);
}

if (!$order) {
    vayuma_payment_feedback('Order not found', 'This order is unavailable or does not belong to your account.', 404);
}

if ($order['status'] !== 'accepted') {
    vayuma_payment_feedback('Payment not available', 'This order is not ready for payment. Check its current status in My Orders.', 409);
}

try {
    $check = $conn->prepare("
        SELECT id, status, transaction_reference
        FROM payments
        WHERE order_id = ?
        ORDER BY CASE WHEN status = 'paid' THEN 0 ELSE 1 END, id DESC
        LIMIT 1
    ");
    if (!$check) {
        throw new mysqli_sql_exception('Unable to prepare payment lookup.');
    }
    $check->bind_param("i", $order_id);
    $check->execute();
    $payment_result = $check->get_result();
    $existing_payment = $payment_result->fetch_assoc();
    $check->close();
} catch (mysqli_sql_exception $exception) {
    error_log('Existing payment lookup failed: ' . $exception->getMessage());
    vayuma_payment_feedback('Payment unavailable', 'We could not verify the current payment state. Please check My Orders before trying again.', 500);
}

if ($existing_payment && $existing_payment['status'] === 'paid') {
    header("Location: payment_success.php?order_id=" . $order_id);
    exit;
}

$image = !empty($order['image'])
    ? htmlspecialchars($order['image'])
    : '';

$total = number_format((float)$order['total_amount'], 2);
$mpesaError = (string)($_SESSION['mpesa_error'] ?? '');
unset($_SESSION['mpesa_error']);
$phonePrefill = trim((string)($_SESSION['phone'] ?? ''));
if ($phonePrefill === '') {
    try {
        $phoneStmt = $conn->prepare('SELECT phone FROM users WHERE id=? LIMIT 1');
        if ($phoneStmt) {
            $phoneStmt->bind_param('i', $user_id);
            $phoneStmt->execute();
            $phoneRow = $phoneStmt->get_result()->fetch_assoc();
            $phoneStmt->close();
            $phonePrefill = trim((string)($phoneRow['phone'] ?? ''));
        }
    } catch (Throwable $exception) {
        $phonePrefill = '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head><?php require __DIR__ . '/includes/pwa.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Payment | VAYUMA TRIX</title>

<link rel="stylesheet" href="frontend/css/tokens.css">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css?v=20261110-nav">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
<style>
.va-payment-page { max-width: 900px; margin: 45px auto; padding: 20px; }
.va-payment-card { background: #fff; border-radius: var(--va-radius-lg); padding: 30px; box-shadow: var(--va-shadow-sm); }
.va-payment-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 1rem; border-bottom: 1px solid var(--va-border); margin-bottom: 1.5rem; }
.va-payment-logo { font-size: 1.75rem; font-weight: var(--va-font-weight-bold); color: var(--va-primary); letter-spacing: 0.05em; }
.va-payment-back { color: var(--va-primary); text-decoration: none; font-weight: var(--va-font-weight-bold); }
.va-product-row { display: flex; gap: 1.5rem; align-items: center; padding: 1rem 0; border-bottom: 1px solid var(--va-border-light); }
.va-product-image { width: 110px; height: 90px; object-fit: cover; border-radius: var(--va-radius-md); background: #e9f5ec; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: var(--va-text-muted); }
.va-product-details { flex: 1; }
.va-summary { margin-top: 1.5rem; }
.va-summary-row { display: flex; justify-content: space-between; margin: 0.75rem 0; }
.va-summary-total { font-size: 1.25rem; font-weight: var(--va-font-weight-bold); color: var(--va-primary); border-top: 1px solid var(--va-border); padding-top: 1rem; }
.va-payment-methods { margin-top: 1.5rem; }
.va-method-label { font-size: var(--va-font-size-sm); font-weight: var(--va-font-weight-bold); margin-bottom: 0.75rem; }
.va-method { display: block; border: 2px solid var(--va-border-light); border-radius: var(--va-radius-md); padding: 1rem 1.25rem; margin: 0.75rem 0; cursor: pointer; background: var(--va-surface); transition: all var(--va-transition); }
.va-method:hover { border-color: var(--va-primary); background: #f7fcf8; }
.va-method input { margin-right: 0.75rem; vertical-align: middle; width: 18px; height: 18px; }
.va-method strong { vertical-align: middle; font-size: 1rem; }
.va-method span { display: block; margin-top: 0.5rem; margin-left: 29px; color: var(--va-text-muted); font-size: 0.875rem; line-height: var(--va-line-height-relaxed); }
.va-pay-note { margin-top: 0.75rem; color: var(--va-text-muted); font-size: 0.875rem; }
.va-phone-label { display: block; font-weight: var(--va-font-weight-bold); margin: 0.75rem 0 0.4rem; }
.va-phone-input { width: 100%; padding: 0.85rem 1rem; border: 1px solid var(--va-border); border-radius: var(--va-radius-md); font: inherit; }
.va-mpesa-error { background: #fde7e7; color: #a32121; padding: 0.85rem 1rem; border-radius: var(--va-radius-md); margin-bottom: 1rem; }
</style>
</head>

<body>
<?php vayuma_language_selector(); ?>
<?php require __DIR__ . '/includes/workspace_back.php'; ?>

<?php vayuma_payment_navigation('my_orders.php', 'My Orders'); ?>
<div class="va-payment-page">
<div class="va-payment-card">

<h1 class="va-section-title">Complete Payment — M-Pesa STK Push</h1>
<p class="va-section-subtitle">Review your order. Payment processing is currently unavailable.</p>

<div class="va-product-row">
<?php if ($image): ?>
<img src="<?= $image ?>" alt="Produce" class="va-product-image">
<?php else: ?>
<div class="va-product-image"><svg class="va-icon-lg" width="40" height="40"><use href="frontend/images/icons.svg#va-icon-crops"/></svg></div>
<?php endif; ?>
<div class="va-product-details">
<h2><?= htmlspecialchars($order['produce_name']) ?></h2>
<p><?= htmlspecialchars($order['quantity']) ?> <?= htmlspecialchars($order['unit']) ?></p>
<p>KSh <?= number_format((float)$order['price_per_unit'], 2) ?> per <?= htmlspecialchars($order['unit']) ?></p>
</div>
</div>

<div class="va-summary">
<div class="va-summary-row"><span>Order</span><strong>#<?= $order['id'] ?></strong></div>
<div class="va-summary-row"><span>Quantity</span><strong><?= htmlspecialchars($order['quantity']) ?> <?= htmlspecialchars($order['unit']) ?></strong></div>
<div class="va-summary-row va-summary-total"><span>Total</span><span>KSh <?= $total ?></span></div>
</div>

<?php if (vayuma_payment_processing_enabled()): ?>
<div class="va-payment-methods">
<h3 class="va-method-label">Pay with M-Pesa STK Push</h3>
<p>Enter the Safaricom number that should receive the payment prompt. VAYUMA sends an STK push to that phone. The order is marked paid only after you enter your M-Pesa PIN and Safaricom confirms the payment.</p>
</div>

<?php if ($mpesaError !== ''): ?>
<div class="va-mpesa-error" role="alert"><?= htmlspecialchars($mpesaError, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form action="mpesa_stkush.php" method="POST" id="mpesaForm">
<?= vayuma_csrf_field() ?>
<input type="hidden" name="order_id" value="<?= $order['id'] ?>">
<label class="va-phone-label" for="mpesa-phone">M-Pesa phone number</label>
<input class="va-phone-input" id="mpesa-phone" name="phone" inputmode="tel" autocomplete="tel" required placeholder="0712345678" value="<?= htmlspecialchars($phonePrefill, ENT_QUOTES, 'UTF-8') ?>">
<button type="submit" class="va-btn va-btn--primary va-btn--full" style="margin-top:1rem">Send M-Pesa prompt</button>
</form>
<p class="va-pay-note">A pop-up from M-Pesa will appear on that phone. Enter your PIN there. Do not share the PIN on this page.</p>
<?php else: ?>
<p role="status">M-Pesa payment is temporarily unavailable. No payment prompt will be sent.</p>
<?php endif; ?>

</div>
</div>

<script>
document.getElementById('mpesaForm')?.addEventListener('submit', function () {
  const button = this.querySelector('button[type="submit"]');
  button.disabled = true;
  button.textContent = 'Sending M-Pesa prompt…';
});
</script>
<script src="assets/js/vayuma-language.js" defer></script>
</body>
</html>
