<?php
session_start();
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/payment_navigation.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}

$display = $_SESSION['paystack_display'] ?? null;
if (!$display) {
    header('Location: paystack_payment.php?order_id=' . (int)($_GET['order_id'] ?? 0));
    exit;
}
unset($_SESSION['paystack_display']);

$userId = (int)$_SESSION['user_id'];
$orderId = (int)$_GET['order_id'] ?? 0;
if ($orderId <= 0 || $display['order_id'] !== $orderId) {
    die('Invalid order.');
}

$channel = $display['channel'];
$data = $display['data'] ?? [];
$auth = $data['authorization'] ?? null;

?>
<!doctype html>
<html lang="en">
<head><?php require __DIR__ . '/includes/pwa.php'; ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Paystack Payment Details | VAYUMA TRIX</title>
<link rel="stylesheet" href="frontend/css/tokens.css">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css?v=20261110-nav">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
<style>
.va-profile-page { max-width: 950px; margin: 0 auto; padding: var(--va-space-6); }
.va-profile-brand { text-align: center; margin-bottom: var(--va-space-6); }
.va-profile-brand h1 { color: var(--va-primary); font-size: var(--va-font-size-3xl); letter-spacing: 0.05em; margin: 0; }
.va-profile-brand p { color: var(--va-text-muted); font-size: var(--va-font-size-xs); letter-spacing: 0.05em; margin-top: var(--va-space-1); }
.va-profile-card { background: var(--va-surface); border-radius: var(--va-radius-lg); padding: var(--va-space-6); box-shadow: var(--va-shadow-sm); border: 1px solid var(--va-border); }
.va-profile-heading { margin-bottom: var(--va-space-5); }
.va-profile-label { color: var(--va-primary); font-size: var(--va-font-size-xs); font-weight: var(--va-font-weight-bold); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: var(--va-space-2); }
.va-profile-heading h2 { font-size: var(--va-font-size-2xl); margin-bottom: var(--va-space-2); color: var(--va-text-primary); }
.va-profile-heading p { color: var(--va-text-secondary); line-height: var(--va-line-height-relaxed); }
.va-profile-welcome { background: var(--va-primary-light); border-radius: var(--va-radius-md); padding: var(--va-space-4); margin: var(--va-space-6) 0; color: #17663a; line-height: var(--va-line-height-relaxed); }
.va-profile-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: var(--va-space-4); }
.va-profile-form-group { display: flex; flex-direction: column; }
.va-profile-full { grid-column: 1 / -1; }
.va-profile-label-field { font-size: var(--va-font-size-sm); font-weight: var(--va-font-weight-bold); margin-bottom: var(--va-space-2); color: var(--va-text-secondary); }
.va-profile-input { width: 100%; padding: 0.75rem 1rem; font-size: var(--va-font-size-base); font-family: inherit; border: 1px solid var(--va-border); border-radius: var(--va-radius); background: var(--va-surface); color: var(--va-text-primary); }
.va-profile-input:focus { outline: none; border-color: var(--va-border-focus); box-shadow: 0 0 0 3px rgba(10, 138, 73, 0.1); }
.va-profile-hint { font-size: var(--va-font-size-xs); color: var(--va-text-muted); margin-top: var(--va-space-1); }
.va-profile-button-area { margin-top: var(--va-space-6); }
.va-profile-btn { width: 100%; border: none; padding: var(--va-space-4); background: var(--va-primary); color: #fff; border-radius: var(--va-radius); font-size: var(--va-font-size-base); font-weight: var(--va-font-weight-bold); cursor: pointer; }
.va-profile-btn:hover { background: var(--va-primary-hover); }
.va-profile-back { display: inline-block; margin-top: var(--va-space-4); color: var(--va-primary); text-decoration: none; font-weight: var(--va-font-weight-bold); }
.va-profile-error { background: #fde7e7; color: #a32121; padding: var(--va-space-3); border-radius: var(--va-radius-md); margin-bottom: var(--va-space-4); }
@media (max-width: 700px) {
  .va-profile-page { padding: var(--va-space-4); }
  .va-profile-card { padding: var(--va-space-4); }
  .va-profile-form-grid { grid-template-columns: 1fr; }
  .va-profile-full { grid-column: auto; }
}
</style>
</head>
<body>
<?php vayuma_language_selector(); ?>
<?php require __DIR__ . '/includes/workspace_back.php'; ?>
<?php vayuma_payment_navigation('my_orders.php', 'My Orders'); ?>
<div class="wrap">
    <div class="va-profile-card">
        <h1><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Complete Paystack Payment</h1>
        <p>Order #<?= $orderId ?> · Paystack channel: <strong><?= strtoupper($channel) ?></strong></p>
        <div class="amount">KSh <?= number_format((float)$display['amount'], 2) ?></div>
        <div class="va-profile-label">Paystack reference</div>
        <div class="box"><div class="row"><span class="k">Reference</span><span class="v"><?= htmlspecialchars($display['reference']) ?></span></div></div>

        <?php if ($channel === 'bank' && is_array($auth)): ?>
            <div class="va-profile-label">Bank transfer details</div>
            <div class="box">
                <?php foreach (['bank','account_number','account_name','reference'] as $k): ?>
                    <?php if (!empty($auth[$k] ?? '')): ?>
                    <div class="row"><span class="k"><?= ucfirst(str_replace('_',' ',$k)) ?></span><span class="v"><?= htmlspecialchars($auth[$k]) ?></span></div>
                    <?php endif; ?>
                <?php endforeach; ?>
                <p class="note">Transfer the exact amount to the account above. Use the Paystack reference as the narration. Paystack will confirm the payment automatically.</p>
            </div>

        <?php elseif ($channel === 'ussd' && is_array($auth)): ?>
            <div class="va-profile-label">USSD code</div>
            <div class="box">
                <div class="row"><span class="k">Dial</span><span class="v" style="font-size:20px;letter-spacing:2px"><?= htmlspecialchars($auth['ussd_code'] ?? $auth['code'] ?? '') ?></span></div>
                <p class="note">Dial the USSD code on your phone and follow the prompts to authorize the payment.</p>
            </div>

        <?php elseif ($channel === 'qr' && is_array($auth)): ?>
            <div class="va-profile-label">Scan QR code</div>
            <div class="box qr text-center">
                <?php if (!empty($auth['qr_code'] ?? '')): ?>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=<?= urlencode($auth['qr_code']) ?>" alt="Paystack QR">
                <?php endif; ?>
                <p class="note">Scan the QR code with your banking/Paystack app to complete the payment.</p>
            </div>

        <?php elseif ($channel === 'paylater' && is_array($auth)): ?>
            <div class="va-profile-label">Pay Later plan</div>
            <div class="box">
                <?php foreach (['plan','name','duration','amount'] as $k): ?>
                    <?php if (!empty($auth[$k] ?? '')): ?>
                    <div class="row"><span class="k"><?= ucfirst($k) ?></span><span class="v"><?= htmlspecialchars($auth[$k]) ?></span></div>
                    <?php endif; ?>
                <?php endforeach; ?>
                <p class="note">Select an installment plan to split this payment. Paystack confirms once the plan is accepted.</p>
            </div>
        <?php endif; ?>

        <p class="note">
            Your order is marked paid only after Paystack confirms the transaction. You will be redirected back to VAYUMA automatically.
        </p>
        <a class="btn" href="paystack_verify.php?reference=<?= urlencode($display['reference']) ?>&order_id=<?= $orderId ?>">Check Payment Status</a>
    </div>
</div>
<script src="assets/js/vayuma-language.js" defer></script>
</body>
</html>
