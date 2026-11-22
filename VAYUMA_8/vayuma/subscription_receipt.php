<?php
/**
 * VAYUMA — Subscription Receipt (clean design).
 * Professional printable receipt for confirmed payments.
 */
session_start();
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
require_once __DIR__ . '/includes/language.php';
$simpleShellOnly = true;
$showSidebarMenu = true;
$showNav = false;
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}

$id = (int)($_GET['payment_id'] ?? 0);
$uid = (int)$_SESSION['user_id'];

$stmt = $conn->prepare('SELECT p.payment_method, p.status, p.amount, p.transaction_reference, p.mpesa_receipt_number, p.paystack_transaction_id, p.paid_at, p.subscription_expires_at, p.billing_interval, pl.stakeholder_name, pl.monthly_price
    FROM subscription_payments p
    INNER JOIN user_subscriptions s ON s.id = p.subscription_id
    INNER JOIN subscription_plans pl ON pl.id = s.plan_id
    WHERE p.id = ? AND p.user_id = ? AND p.status = \'paid\'
    LIMIT 1');
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$p) {
    http_response_code(404);
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Receipt unavailable | VAYUMA</title><link rel="stylesheet" href="frontend/css/tokens.css"><link rel="stylesheet" href="assets/css/vayuma-ui.css?v=2"><link rel="stylesheet" href="frontend/css/user-menu.css?v=3"></head><body class="vy-denied-page">';
    require __DIR__ . '/includes/workspace_back.php';
    echo '<main class="vy-unavailable"><h1>Receipt unavailable</h1><p>This receipt is unavailable or does not belong to your account.</p><a href="subscription.php">Back to subscription</a></main></body></html>';
    exit;
}

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$navLinks = [
    'Dashboard' => 'dashboard.php',
    'Subscription' => 'subscription.php',
];
require_once __DIR__ . '/includes/ui_simple.php';

?>

<main class="va-page-main" id="va-main-content">
  <div class="va-container">
    <?php if ($currentUserId > 0): ?>
    <div class="va-sidebar-overlay" id="vaSidebarOverlay" onclick="vaCloseSidebar()"></div>
    <aside class="va-sidebar" id="vaSidebar">
      <div class="va-sidebar--logo">
        <a href="<?= htmlspecialchars($platformHomeUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="VAYUMA home">
          <img src="frontend/images/vayuma-logo.jpg" alt="VAYUMA">
          <div><strong>VAYUMA</strong><small>Smart Ecosystem</small></div>
        </a>
      </div>
      <nav class="va-sidebar--nav">
        <?php foreach ($navLinks as $label => $href): ?>
          <?php $isActive = ($activePage === $href); ?>
          <a href="<?= htmlspecialchars($href) ?>" class="va-nav-item<?= $isActive ? ' va-nav-item--active' : '' ?>" <?= $isActive ? 'aria-current="page"' : '' ?>>
            <span class="va-nav-icon"><?= $label === 'Dashboard' ? '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-dashboard"/></svg>' : '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg>' ?></span>
            <span><?= htmlspecialchars($label) ?></span>
          </a>
        <?php endforeach; ?>
      </nav>
      <div class="va-sidebar--bottom">
        <a href="auth/logout.php" class="va-logout-item"><span>⇥</span>Logout</a>
      </div>
    </aside>
    <?php endif; ?>

    <div class="va-main--content"><?php $workspaceBackMenu = false; require __DIR__ . '/includes/workspace_back.php'; ?>
      <div class="va-receipt va-mb-6">
        <div class="va-receipt--header">
          <div class="va-d-flex va-justify-between va-items-center va-mb-4">
            <div>
              <a href="<?= htmlspecialchars($platformHomeUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="VAYUMA home">
                <img src="frontend/images/vayuma-logo.jpg" alt="VAYUMA" style="width:32px;height:32px;border-radius:6px;margin-right:var(--va-space-2);">
              </a>
              <div>
                <div class="va-font-semibold va-text-primary va-text-sm">VAYUMA Smart Ecosystem</div>
                <div class="va-text-xs va-text-muted">Agricultural Marketplace</div>
              </div>
            </div>
            <div class="va-text-right">
              <div class="va-font-semibold va-text-primary">Subscription Receipt</div>
              <div class="va-text-xs va-text-muted">Receipt #<?= h($id) ?></div>
            </div>
          </div>
        </div>

        <div class="va-d-grid va-gap-4 va-mb-6" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
          <div class="va-receipt--field">
            <div class="va-receipt--field-label">Plan</div>
            <div class="va-receipt--field-value"><?= h($p['stakeholder_name'] ?? 'Subscription') ?></div>
          </div>
          <div class="va-receipt--field">
            <div class="va-receipt--field-label">Amount</div>
            <div class="va-receipt--field-value va-receipt--amount">KSh <?= number_format((float)$p['amount'], 2) ?></div>
          </div>
          <div class="va-receipt--field">
            <div class="va-receipt--field-label">Currency</div>
            <div class="va-receipt--field-value">KSh</div>
          </div>
          <div class="va-receipt--field">
            <div class="va-receipt--field-label">Billing</div>
            <div class="va-receipt--field-value"><?= ucfirst(str_replace('_', ' ', $p['billing_interval'] ?? 'monthly')) ?></div>
          </div>
          <div class="va-receipt--field">
            <div class="va-receipt--field-label">Payment status</div>
            <div class="va-receipt--field-value"><?= h(ucfirst($p['status'])) ?></div>
          </div>
        </div>

        <div class="va-divider va-mb-4"></div>

        <div class="va-receipt--field">
          <div class="va-receipt--field-label"><?= $p['payment_method'] === 'paystack' ? 'Transaction Reference' : 'M-Pesa Reference' ?></div>
          <div class="va-receipt--field-value"><?= h($p['mpesa_receipt_number'] ?: $p['transaction_reference'] ?: $p['paystack_transaction_id']) ?></div>
        </div>
        <div class="va-receipt--field">
          <div class="va-receipt--field-label">Payment Method</div>
          <div class="va-receipt--field-value"><?= ucfirst(str_replace('_', ' ', $p['payment_method'])) ?></div>
        </div>
        <div class="va-receipt--field">
          <div class="va-receipt--field-label">Payment Date</div>
          <div class="va-receipt--field-value"><?= h($p['paid_at']) ?></div>
        </div>
        <div class="va-receipt--field">
          <div class="va-receipt--field-label">Subscription Expires</div>
          <div class="va-receipt--field-value"><?= !empty($p['subscription_expires_at']) ? h(date('d M Y, H:i', strtotime($p['subscription_expires_at']))) : 'Not recorded' ?></div>
        </div>

        <div class="va-divider va-mb-6"></div>

        <div class="va-text-center">
          <p class="va-text-xs va-text-muted va-mb-4">
            This receipt is generated from your VAYUMA subscription payment records.
            Keep this transaction reference for your records.
          </p>
          <div class="va-d-flex va-gap-3 va-justify-center">
            <a href="dashboard.php" class="va-btn va-btn--outline va-btn--sm">Dashboard</a>
            <a href="subscription.php" class="va-btn va-btn--primary va-btn--sm">Subscription</a>
            <button class="va-btn va-btn--outline va-btn--sm" onclick="window.print()">Print Receipt</button>
          </div>
        </div>
      </div>

    </div>
  </div>
</main>

<?php require_once __DIR__ . '/includes/ui_simple_close.php'; ?>
