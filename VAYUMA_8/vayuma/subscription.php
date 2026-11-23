<?php
/**
 * VAYUMA — My Subscription (clean design).
 */
session_start();
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/lib/subscription_lib.php';
require_once __DIR__ . '/mpesa_lib.php';
require_once __DIR__ . '/includes/role_routes.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}

$requestedRole = (string)($_SESSION['role'] ?? '');
$subscription = ensure_user_subscription($conn, (int)$_SESSION['user_id'], $requestedRole);
$receipts = []; $receiptsUnavailable = false;
try {
    // Read only this member's confirmed payment history; receipt endpoint rechecks ownership.
    $history = $conn->prepare("SELECT id, amount, paid_at FROM subscription_payments WHERE user_id=? AND status='paid' ORDER BY paid_at DESC, id DESC LIMIT 10");
    if (!$history) throw new RuntimeException('Receipt history unavailable');
    $historyUserId = (int)$_SESSION['user_id'];
    $history->bind_param('i', $historyUserId);
    $history->execute();
    $historyResult = $history->get_result();
    if (!$historyResult) throw new RuntimeException('Receipt history unavailable');
    while ($receipt = $historyResult->fetch_assoc()) $receipts[] = $receipt;
    $history->close();
} catch (Throwable $e) {
    $receiptsUnavailable = true;
    error_log('VAYUMA subscription receipt history unavailable.');
}
$subscriptionError = (string)($_SESSION['subscription_error'] ?? $_SESSION['paystack_subscription_error'] ?? '');
unset($_SESSION['subscription_error'], $_SESSION['paystack_subscription_error']);

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$trialNotice = (string)($_SESSION['trial_notice'] ?? '');
unset($_SESSION['trial_notice']);
$paid = $subscription && $subscription['plan_type'] === 'paid';
$status = $subscription['status'] ?? 'payment_due';
$billingInterval = $subscription['plan_billing_interval'] ?? $subscription['billing_interval'] ?? 'monthly';
$role = (string)($_SESSION['role'] ?? '');
$profileRoute = vayuma_role_route($role, 'profile');
$profileUrl = $profileRoute !== null ? $profileRoute . '?from=subscription.php' : 'role.php';
$dashboardUrl = vayuma_role_route($role, 'dashboard');
$dashboardAccessible = subscription_is_accessible($subscription) || subscription_dashboard_day_accessible($subscription);
$freeMonth = (new DateTimeImmutable('now', new DateTimeZone('Africa/Nairobi')))->format('Y-m');
$freeDayUsed = ($subscription['dashboard_free_month'] ?? null) === $freeMonth;
$platformHomeUrl = $dashboardAccessible && $dashboardUrl !== null
    ? $dashboardUrl
    : 'welcome.php';
$phonePrefill = trim((string)($_SESSION['phone'] ?? ''));
if ($phonePrefill === '') {
    try {
        $phoneStmt = $conn->prepare('SELECT phone FROM users WHERE id=? LIMIT 1');
        if ($phoneStmt) {
            $userId = (int)$_SESSION['user_id'];
            $phoneStmt->bind_param('i', $userId);
            $phoneStmt->execute();
            $phoneRow = $phoneStmt->get_result()->fetch_assoc();
            $phoneStmt->close();
            $phonePrefill = trim((string)($phoneRow['phone'] ?? ''));
        }
    } catch (Throwable $exception) {
        $phonePrefill = '';
    }
}

// All subscription navigation is provided below the heading, for every access
// state. Avoid repeating it in the header, where it collides on small screens.
$showNav = false;
$hideWorkspaceBack = true;

require_once __DIR__ . '/includes/ui_simple.php';
?>

<style>
.vy-trial-hero{box-sizing:border-box;background:linear-gradient(130deg,#064e3b,#087d42);color:#fff;border-radius:20px;padding:clamp(20px,4vw,34px);margin:0 0 24px;overflow-wrap:anywhere}.vy-trial-hero h2{color:#fff;font-size:clamp(28px,5vw,44px);margin:8px 0}.vy-trial-hero p{max-width:64ch;line-height:1.5}.vy-trial-hero .vy-trial-tag{display:inline-block;background:#dcfce7;color:#166534;padding:6px 12px;border-radius:999px;font-weight:800}.vy-trial-hero .va-btn{background:#fff;color:#065f46;border:0;white-space:normal}.vy-trial-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,240px),1fr));gap:12px;margin:14px 0 24px}.vy-trial-card{box-sizing:border-box;min-width:0;background:#fff;border:1px solid #d9ebdf;border-radius:14px;padding:18px;overflow-wrap:anywhere}.vy-trial-card h3{margin:0 0 8px}.vy-trial-card ul{padding-left:20px;line-height:1.6}.vy-trial-card p{line-height:1.5}
</style>
<section class="vy-trial-hero" aria-labelledby="trial-hero-title">
  <span class="vy-trial-tag">1 MONTH FREE · 30 DAYS</span>
  <h2 id="trial-hero-title">Discover VAYUMA with a free trial</h2>
  <p>Eligible paid-category accounts can claim one 30-day trial. Explore your category dashboard and eligible climate and soil tools. VAYUMA TRIX AI is available to signed-in accounts, including without a trial.</p>
  <?php if ($subscription && $paid && empty($subscription['has_used_trial']) && !subscription_is_accessible($subscription)): ?>
    <form method="post" action="claim_trial.php"><?= vayuma_csrf_field() ?><button class="va-btn va-btn--primary" type="submit">Claim 1 month free</button></form>
  <?php elseif ($subscription && $status === 'trialing'): ?>
    <p><strong>Your 30-day trial is active.</strong></p>
  <?php elseif ($subscription && !$paid): ?>
    <p>This category uses a <?= $subscription['plan_type'] === 'free' ? 'free' : 'negotiated' ?> plan rather than a paid-plan trial.</p>
  <?php elseif ($subscription): ?>
    <p>The one-time trial is not available for this account or your access is already active.</p>
  <?php else: ?>
    <p>Plan information is currently unavailable. Please try again later.</p>
  <?php endif; ?>
  <p>After 30 days, trial access ends. There is no automatic charge; payments are not available yet, so please do not send payment. Plan pricing and access depend on your category.</p>
</section>
<div class="vy-trial-cards" aria-label="Plan details">
  <section class="vy-trial-card"><h3>Your category plan</h3>
    <?php if ($subscription && $paid): ?><p><strong>KSh <?= number_format((float)($subscription['monthly_price'] ?? 0), 2) ?></strong> / <?= h(str_replace('_', ' ', $billingInterval)) ?></p><p>Price from the configured plan; payment is currently disabled.</p>
    <?php elseif ($subscription && $subscription['plan_type'] === 'free'): ?><p><strong>Free plan</strong></p><p>No subscription payment required for this category.</p>
    <?php elseif ($subscription): ?><p><strong>Negotiated plan</strong></p><p>Access is subject to approval and agreed terms.</p>
    <?php else: ?><p>Plan details are unavailable.</p><?php endif; ?>
  </section>
  <section class="vy-trial-card"><h3>Included experience</h3><ul><li>VAYUMA TRIX AI for signed-in members</li><li>Category dashboard, subject to access</li><li>Climate and soil insights when eligible</li></ul><a href="role.php">Explore all categories →</a></section>
</div>
<div class="va-mb-6">
  <div class="va-section">
    <div>
      <h1 class="va-section--title">Your Subscription</h1>
      <p class="va-section--sub">Stakeholder: <strong><?= h($subscription['stakeholder_name'] ?? 'Unknown') ?></strong></p>
    </div>
    <span class="va-status va-status--<?= in_array($status, ['active', 'trialing'], true) ? 'active' : ($status === 'payment_due' ? 'payment-due' : ($status === 'expired' ? 'expired' : ($status === 'free_account' ? 'free' : 'negotiated'))) ?>">
      <?= h(subscription_status_label($status)) ?>
    </span>
  </div>
</div>

<nav class="va-subscription-navigation va-mb-4" aria-label="Subscription navigation">
  <?php if ($dashboardAccessible && $dashboardUrl !== null): ?>
    <a href="<?= h($dashboardUrl) ?>" class="va-btn va-btn--outline va-btn--sm">← Back to dashboard</a>
    <a href="<?= h($profileUrl) ?>" class="va-btn va-btn--outline va-btn--sm">Edit profile</a>
  <?php else: ?>
    <a href="<?= h($profileUrl) ?>" class="va-btn va-btn--outline va-btn--sm">← Back to your profile</a>
    <a href="role.php" class="va-btn va-btn--outline va-btn--sm">Choose stakeholder type</a>
  <?php endif; ?>
  <a href="onboarding.php" class="va-btn va-btn--outline va-btn--sm">Staff Access</a>
</nav>
<p class="va-text-muted va-text-sm">Authorized onboarding staff can enter Staff Access without an active subscription.</p>

<?php if ($subscriptionError !== ''): ?>
  <div class="va-toast va-toast--error va-mb-4" role="alert" aria-live="polite">
    <div class="va-toast--title">Payment issue</div>
    <div class="va-toast--msg"><?= h($subscriptionError) ?></div>
  </div>
<?php endif; ?>

<?php if ($trialNotice !== ''): ?><p role="status"><?= h($trialNotice) ?></p><?php endif; ?>

<?php if (!$subscription): ?>
  <div class="va-toast va-toast--error" role="alert">
    This stakeholder plan is not configured. Please contact VAYUMA support.
  </div>
<?php else: ?>
<div class="va-card va-card--p va-mb-6">
  <?php if ($paid && !subscription_is_accessible($subscription) && subscription_dashboard_day_accessible($subscription)): ?>
    <h2>Free dashboard access active</h2><p>Your 24-hour dashboard window ends <?= h((new DateTimeImmutable($subscription['dashboard_free_until_utc'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Africa/Nairobi'))->format('d M Y H:i')) ?> Nairobi time. Other subscription features remain restricted.</p>
    <?php if ($dashboardUrl !== null): ?><a href="<?= h($dashboardUrl) ?>" class="va-btn va-btn--primary">Open dashboard</a><?php endif; ?>
  <?php elseif ($paid && $status === 'trialing' && $dashboardAccessible): ?>
    <h2>30-day trial active</h2>
    <p>Your trial ends <?= h($subscription['trial_ends_at']) ?>. Paybill payments are not yet available; please do not send payment.</p>
    <?php if ($dashboardUrl !== null): ?><a href="<?= h($dashboardUrl) ?>" class="va-btn va-btn--primary">Continue to dashboard</a><?php endif; ?>
  <?php elseif ($paid && $status === 'active' && $dashboardAccessible && $dashboardUrl !== null): ?>
    <div class="va-text-center va-py-8">
      <div class="va-text-2xl va-font-bold va-text-success">Subscription active</div>
      <p class="va-text-muted va-text-sm va-mt-2">Your access is active. Continue to your stakeholder dashboard.</p>
      <?php if (!empty($subscription['expires_at'])): ?><p class="va-text-muted va-text-sm">Expires <?= h(date('d M Y', strtotime($subscription['expires_at']))) ?></p><?php endif; ?>
      <a href="<?= h($dashboardUrl) ?>" class="va-btn va-btn--primary va-btn--sm va-mt-4">Continue to Dashboard</a>
    </div>
  <?php elseif ($paid): ?>
    <div class="va-d-flex va-justify-between va-items-center va-mb-4">
      <div>
        <div class="va-text-2xl va-font-bold va-text-primary">KSh <?= number_format((float)($subscription['monthly_price'] ?? 0), 2) ?></div>
        <div class="va-text-muted va-text-sm">/ <?= h(str_replace('_', ' ', $billingInterval)) ?></div>
      </div>
      <div class="va-text-right">
        <?php if (!empty($subscription['billing_interval'])): ?>
          <div class="va-text-muted va-text-sm">Billing interval: <?= h(ucfirst(str_replace('_', ' ', $billingInterval))) ?></div>
        <?php endif; ?>
        <?php if (!empty($subscription['expires_at'])): ?>
          <div class="va-text-muted va-text-sm">Expires <?= h(date('d M Y', strtotime($subscription['expires_at']))) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <p class="va-text-muted va-text-sm va-mb-4">
      Your plan and price come from the VAYUMA pricing catalogue. Access starts only after payment is verified.
    </p>

    <?php if (empty($subscription['has_used_trial'])): ?>
      <form method="post" action="claim_trial.php">
        <?= vayuma_csrf_field() ?>
        <button class="va-btn va-btn--primary" type="submit">Claim 30-day trial — KSh 0 upfront</button>
      </form>
    <?php endif; ?>
    <?php if (!$freeDayUsed): ?><form method="post" action="claim_dashboard_day.php" class="va-mt-4"><?= vayuma_csrf_field() ?><button class="va-btn va-btn--outline" type="submit">Claim 24 hours of dashboard access this month</button></form><?php else: ?><p>This month’s free dashboard access has been used.</p><?php endif; ?>
    <p class="va-text-muted va-text-sm">One dashboard-only window per Nairobi calendar month; it does not unlock paid features.</p>
    <p role="status">M-Pesa Paybill payments are not available yet. Please do not send payment. We will enable renewal once verification is ready.</p>

  <?php elseif ($subscription['plan_type'] === 'free'): ?>
    <div class="va-text-center va-py-8">
      <div class="va-text-3xl va-font-bold va-text-success">Free</div>
      <?php if ($dashboardAccessible && $dashboardUrl !== null): ?>
        <div class="va-text-muted va-text-sm va-mt-2">This account does not require payment. Your access is active.</div>
        <a href="<?= h($dashboardUrl) ?>" class="va-btn va-btn--primary va-btn--sm va-mt-4">Continue to Dashboard</a>
      <?php else: ?>
        <div class="va-text-muted va-text-sm va-mt-2">This free plan is not currently active. Please contact VAYUMA support.</div>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="va-text-center va-py-8">
      <div class="va-text-3xl va-font-bold va-text-warning">Custom</div>
      <div class="va-text-muted va-text-sm va-mt-2">Your account uses a negotiated plan.</div>
      <?php if ($dashboardAccessible && $dashboardUrl !== null): ?>
        <p class="va-text-muted va-text-sm va-mt-4">Your negotiated plan has been approved.</p>
        <a href="<?= h($dashboardUrl) ?>" class="va-btn va-btn--primary va-btn--sm va-mt-4">Continue to Dashboard</a>
      <?php elseif ($status === 'negotiated_plan'): ?>
        <p class="va-text-muted va-text-sm va-mt-4">Your account is awaiting manual VAYUMA approval. No payment is due until your plan is approved.</p>
      <?php else: ?>
        <p class="va-text-muted va-text-sm va-mt-4">This negotiated plan is not currently active. Please contact VAYUMA support.</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<section class="va-card va-card--p va-mb-6" aria-labelledby="subscription-receipts-title">
  <h2 id="subscription-receipts-title">Subscription receipts</h2>
  <?php if ($receiptsUnavailable): ?>
    <p class="va-text-muted va-text-sm">Receipts are temporarily unavailable. Please try again later.</p>
  <?php elseif (!$receipts): ?>
    <p class="va-text-muted va-text-sm">No confirmed subscription payments yet.</p>
  <?php else: ?>
    <ul>
      <?php foreach ($receipts as $receipt): ?>
        <li><a href="subscription_receipt.php?payment_id=<?= (int)$receipt['id'] ?>">Receipt #<?= (int)$receipt['id'] ?></a>
          · KSh <?= number_format((float)$receipt['amount'], 2) ?>
          <?php if (!empty($receipt['paid_at'])): ?> · <?= h($receipt['paid_at']) ?><?php endif; ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<!-- Toast container for payment notifications -->
<div id="vaToastContainer" style="position:fixed;top:var(--va-space-4);right:var(--va-space-4);z-index:60;"></div>

<script>
// ── Toast notification for payment flow ──
function vaShowToast(message, type) {
    const container = document.getElementById('vaToastContainer');
    const t = document.createElement('div');
    t.className = 'va-toast va-toast--' + type;
    const esc = document.createElement('div');
    esc.textContent = message;
    t.innerHTML = '<div class="va-toast--title">' + (type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Notice') + '</div>' +
                  '<div class="va-toast--msg">' + esc.innerHTML + '</div>';
    container.appendChild(t);
    setTimeout(() => {
        t.style.opacity = '0';
        t.style.transform = 'translateX(100%)';
        setTimeout(() => t.remove(), 300);
    }, 5000);
}

// ── Button loading state ──
function vaSetButtonLoading(btn, loading) {
    if (loading) {
        btn.disabled = true;
        btn.dataset.vaOrig = btn.innerHTML;
        btn.innerHTML = '<span style="display:inline-block;width:16px;height:16px;border:2px solid rgba(255,255,255,0.3);border-radius:50%;border-top-color:#fff;animation:spin 1s linear infinite;"></span>';
    } else {
        btn.disabled = false;
        if (btn.dataset.vaOrig) {
            btn.innerHTML = btn.dataset.vaOrig;
            delete btn.dataset.vaOrig;
        }
    }
}

// ── Poll payment status ──
function vaPollSubscriptionStatus(paymentId, maxPolls = 30) {
    let polls = 0;
    const interval = setInterval(async () => {
        polls++;
        try {
            const res = await fetch('api/subscription_status.php?payment_id=' + paymentId);
            const data = await res.json();
            if (data.ok) {
                if (data.saved) {
                    clearInterval(interval);
                    vaShowToast('Subscription activated successfully!', 'success');
                    // The server-side dashboard guard still verifies the user's entitlement.
                    setTimeout(() => window.location.href = 'dashboard.php', 1500);
                } else if (data.status === 'failed') {
                    clearInterval(interval);
                    vaShowToast('Payment failed. Please try again.', 'error');
                } else if (data.status === 'error') {
                    clearInterval(interval);
                    vaShowToast(data.error || 'Payment error.', 'error');
                }
                // Still pending - keep polling
            } else {
                clearInterval(interval);
                vaShowToast('Unable to check payment status.', 'error');
            }
        } catch (e) {
            // Network error - keep polling until max
        }
        if (polls >= maxPolls) {
            clearInterval(interval);
            vaShowToast('Payment is taking longer than expected. Please check your orders.', 'error');
        }
    }, 3000);
}

// ── Wire payment buttons ──
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('form[action="subscription_stkpush.php"] button[type="submit"]').forEach(function(button) {
        button.addEventListener('click', function() {
            vaSetButtonLoading(button, true);
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/ui_simple_close.php'; ?>
