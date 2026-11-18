<?php
/**
 * VAYUMA — My Subscription (clean design).
 */
session_start();
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
$subscriptionError = (string)($_SESSION['subscription_error'] ?? $_SESSION['paystack_subscription_error'] ?? '');
unset($_SESSION['subscription_error'], $_SESSION['paystack_subscription_error']);

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$paid = $subscription && $subscription['plan_type'] === 'paid';
$status = $subscription['status'] ?? 'payment_due';
$billingInterval = $subscription['plan_billing_interval'] ?? $subscription['billing_interval'] ?? 'monthly';
$role = (string)($_SESSION['role'] ?? '');
$profileUrl = vayuma_role_route($role, 'profile') ?? 'role.php';
$dashboardUrl = vayuma_role_route($role, 'dashboard');
$dashboardAccessible = subscription_is_accessible($subscription);
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

$navLinks = $dashboardAccessible && $dashboardUrl !== null
    ? ['Dashboard' => $dashboardUrl]
    : ['Back to profile' => $profileUrl, 'Choose stakeholder type' => 'role.php'];

require_once __DIR__ . '/includes/ui_simple.php';
?>

<div class="va-mb-6">
  <div class="va-section">
    <div>
      <h1 class="va-section--title">Your Subscription</h1>
      <p class="va-section--sub">Stakeholder: <strong><?= h($subscription['stakeholder_name'] ?? 'Unknown') ?></strong></p>
    </div>
    <span class="va-status va-status--<?= $status === 'active' ? 'active' : ($status === 'payment_due' ? 'payment-due' : ($status === 'expired' ? 'expired' : ($status === 'free_account' ? 'free' : 'negotiated'))) ?>">
      <?= ucfirst(str_replace('_', ' ', $status)) ?>
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
</nav>

<?php if ($subscriptionError !== ''): ?>
  <div class="va-toast va-toast--error va-mb-4" role="alert" aria-live="polite">
    <div class="va-toast--title">Payment issue</div>
    <div class="va-toast--msg"><?= h($subscriptionError) ?></div>
  </div>
<?php endif; ?>

<?php if (!$subscription): ?>
  <div class="va-toast va-toast--error" role="alert">
    This stakeholder plan is not configured. Please contact VAYUMA support.
  </div>
<?php else: ?>
<div class="va-card va-card--p va-mb-6">
  <?php if ($paid): ?>
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

    <div class="va-mb-4">
      <div class="va-payment-methods">
        <h3 class="va-method-label">Pay with M-Pesa STK Push</h3>
        <p>Enter the Safaricom number that should receive the payment prompt. VAYUMA sends an STK push to that phone. Your subscription is activated only after you enter your M-Pesa PIN and Safaricom confirms the payment.</p>
      </div>

      <?php if ($subscriptionError !== ''): ?>
      <div class="va-toast va-toast--error va-mb-4" role="alert" aria-live="polite">
        <div class="va-toast--title">Payment issue</div>
        <div class="va-toast--msg"><?= h($subscriptionError) ?></div>
      </div>
      <?php endif; ?>

      <form method="post" action="subscription_stkpush.php" class="va-w-full" id="mpesaSubscriptionForm">
        <?= vayuma_csrf_field() ?>
        <label class="va-form-label-field" for="mpesa-phone">M-Pesa phone number</label>
        <input class="va-input va-w-full" id="mpesa-phone" name="phone" inputmode="tel" autocomplete="tel" required placeholder="0712345678" value="<?= h($phonePrefill) ?>">
        <button type="submit" class="va-btn va-btn--primary va-btn--full va-w-full va-mt-3">
          Send M-Pesa prompt
        </button>
      </form>

      <p class="va-text-muted va-text-sm va-mt-2">
        A pop-up from M-Pesa will appear on that phone. Enter your PIN there. Access starts only after Safaricom confirms the payment.
      </p>
    </div>

  <?php elseif ($subscription['plan_type'] === 'free'): ?>
    <div class="va-text-center va-py-8">
      <div class="va-text-3xl va-font-bold va-text-success">Free</div>
      <div class="va-text-muted va-text-sm va-mt-2">This account does not require payment. Your access is active.</div>
      <a href="dashboard.php" class="va-btn va-btn--primary va-btn--sm va-mt-4">Continue to Dashboard</a>
    </div>
  <?php else: ?>
    <div class="va-text-center va-py-8">
      <div class="va-text-3xl va-font-bold va-text-warning">Custom</div>
      <div class="va-text-muted va-text-sm va-mt-2">Your account uses a negotiated plan.</div>
      <?php if ((int)($subscription['negotiated_approved'] ?? 0) === 1): ?>
        <p class="va-text-muted va-text-sm va-mt-4">Your negotiated plan has been approved.</p>
        <a href="dashboard.php" class="va-btn va-btn--primary va-btn--sm va-mt-4">Continue to Dashboard</a>
      <?php else: ?>
        <p class="va-text-muted va-text-sm va-mt-4">Your account is awaiting manual VAYUMA approval. No payment is due until your plan is approved.</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>

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
                    setTimeout(() => window.location.href = 'subscription_receipt.php?payment_id=' + paymentId, 1500);
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
