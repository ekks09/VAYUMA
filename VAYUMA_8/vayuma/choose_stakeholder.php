<?php
/**
 * VAYUMA — Choose stakeholder type (clean design).
 */
session_start();
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/role_routes.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}

$userRole = (string)($_SESSION['role'] ?? '');

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$plans = $conn->query(
    'SELECT role_key, stakeholder_name, monthly_price, plan_type, is_active, paystack_plan_code, billing_interval
     FROM subscription_plans
     WHERE is_active = 1
     ORDER BY stakeholder_name'
);

$error = $_SESSION['choose_stakeholder_error'] ?? '';
unset($_SESSION['choose_stakeholder_error']);

$profileUrl = vayuma_role_route($userRole, 'profile') ?? 'role.php';
$navLinks = [
    'Back to profile' => $profileUrl,
    'Subscription' => 'subscription.php',
];
require_once __DIR__ . '/includes/ui_simple.php';

?>

<?php if ($error): ?>
<div class="va-toast va-toast--error" role="alert" aria-live="polite">
  <div class="va-toast--title">Error</div>
  <div class="va-toast--msg"><?= h($error) ?></div>
</div>
<?php endif; ?>

<div class="va-mb-6">
  <div class="va-section">
    <div>
      <h1 class="va-section--title">Subscription pricing</h1>
      <p class="va-section--sub">Your stakeholder type was selected during onboarding. Pricing is managed by the VAYUMA administrator.</p>
    </div>
  </div>
</div>

<div class="va-plan-grid">
  <?php if (!$plans): ?>
    <div class="va-toast va-toast--error" role="alert">Subscription plans could not be loaded. Please refresh the page.</div>
  <?php else: ?>
  <?php while ($p = $plans->fetch_assoc()): ?>
    <div class="va-plan">
      <div class="va-plan--header">
        <div class="va-plan--name"><?= h($p['stakeholder_name']) ?></div>
        <?php if ($p['role_key'] === $userRole): ?>
          <span class="va-badge va-badge--active">Your role</span>
        <?php endif; ?>
      </div>

      <div class="va-plan--price">
        <?php if ($p['plan_type'] === 'free'): ?>Free
        <?php elseif ($p['plan_type'] === 'negotiated'): ?>Negotiated
        <?php else: ?>KSh <?= number_format((float)$p['monthly_price'], 2) ?><?php endif; ?>
      </div>
      <?php if ($p['plan_type'] === 'paid'): ?>
        <div class="va-plan--period">/ <?= h(ucfirst(str_replace('_', ' ', $p['billing_interval'] ?? 'monthly'))) ?></div>
      <?php endif; ?>

      <span class="va-plan--badge va-plan--badge--<?= $p['plan_type'] === 'paid' ? 'paid' : ($p['plan_type'] === 'free' ? 'free' : 'negotiated') ?>">
        <?= ucfirst(str_replace('_', ' ', $p['plan_type'])) ?>
      </span>

      <?php if ($p['plan_type'] !== 'negotiated'): ?>
        <div class="va-plan--desc">
          <?php if ($p['plan_type'] === 'free'): ?>
            No payment required. Access activates immediately.
          <?php else: ?>
            Pay through the available provider to activate access for this billing period.
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="va-plan--desc">
          Custom arrangement. Contact VAYUMA support for access.
        </div>
      <?php endif; ?>

      <?php if ($p['role_key'] === $userRole): ?>
        <a href="subscription.php" class="va-btn va-btn--primary va-btn--full va-mt-4">Manage your subscription</a>
      <?php else: ?>
        <span class="va-text-muted va-text-sm va-mt-4">Not your selected stakeholder type</span>
      <?php endif; ?>
    </div>
  <?php endwhile; ?>
  <?php endif; ?>
</div>

<div class="va-mt-8 va-text-center va-text-muted va-text-sm">
  Paid plans activate only after verified payment. Free accounts activate immediately.
  Negotiated plans require administrator approval.
</div>

<?php require_once __DIR__ . '/includes/ui_simple_close.php'; ?>
