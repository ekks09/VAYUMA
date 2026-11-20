<?php
/**
 * VAYUMA — Admin Subscription & Pricing (clean design).
 * Admin-managed pricing for all stakeholder types.
 */
session_start();
require_once __DIR__ . '/includes/language.php';
$simpleShellOnly = true;
$showSidebarMenu = true;
$showNav = false;
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/admin.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/lib/plan_reconciliation.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}

if (!is_vayuma_admin((int)$_SESSION['user_id'])) {
    http_response_code(403);
    ?>
    <!doctype html>
    <html lang="<?= htmlspecialchars($current_language, ENT_QUOTES, 'UTF-8') ?>">
    <head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Access denied | VAYUMA</title></head>
    <body><main><h1>Access denied</h1><p>This account is not authorised to manage subscription plans.</p><a href="dashboard.php">Return to dashboard</a></main></body>
    </html>
    <?php
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userRole = (string)($_SESSION['role'] ?? '');
$userName = (string)($_SESSION['full_name'] ?? '');
$error = '';
$supportedStakeholders = [
    'farmer' => 'Farmer',
    'buyer' => 'Buyer',
    'consumer' => 'Consumer',
    'transport_provider' => 'Transport Provider',
    'input_supplier' => 'Input Supplier',
    'manufacturer' => 'Manufacturer',
    'warehouse_provider' => 'Warehouse Provider',
    'retailer' => 'Retailer',
    'agribusiness' => 'Agribusiness / Trader',
    'cooperative' => 'Cooperative',
    'researcher' => 'Researcher / Research Institution',
    'extension_officer' => 'Agricultural Extension Officer',
    'veterinary_officer' => 'Veterinary Officer',
    'financial_institution' => 'Financial Institution',
    'insurance_provider' => 'Insurance Provider',
    'government_officer' => 'Government Officer',
    'ngo_partner' => 'NGO / Development Partner',
    'agricultural_service_provider' => 'Agricultural Service Provider',
    'strategic_partner' => 'Strategic Partner',
];

if (!function_exists('h')) {
    function h($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!vayuma_csrf_valid()) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif (($_POST['action'] ?? '') === 'create_plan') {
        $roleKey = (string)($_POST['role_key'] ?? '');
        $stakeholderName = trim((string)($_POST['stakeholder_name'] ?? ''));
        $planType = (string)($_POST['plan_type'] ?? '');
        $billingInterval = (string)($_POST['billing_interval'] ?? 'monthly');
        $priceInput = trim((string)($_POST['monthly_price'] ?? ''));
        $paystackPlanCode = trim((string)($_POST['paystack_plan_code'] ?? ''));
        $active = isset($_POST['is_active']) ? 1 : 0;
        if (!isset($supportedStakeholders[$roleKey])) {
            $error = 'Select a stakeholder type supported by VAYUMA.';
        } elseif ($stakeholderName === '' || strlen($stakeholderName) > 120) {
            $error = 'Enter a stakeholder name of no more than 120 characters.';
        } elseif (!in_array($planType, ['paid', 'free', 'negotiated'], true)) {
            $error = 'Select a supported plan type.';
        } elseif (!in_array($billingInterval, ['monthly', 'quarterly', 'biannually', 'annually'], true)) {
            $error = 'Select a supported billing interval.';
        } elseif ($planType === 'paid' && !preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $priceInput)) {
            $error = 'Enter a valid plan price with no more than two decimal places.';
        } elseif (strlen($paystackPlanCode) > 100) {
            $error = 'The Paystack plan code must be 100 characters or fewer.';
        } else {
            try {
                $price = $planType === 'paid' ? $priceInput : null;
                $code = $planType === 'paid' && $paystackPlanCode !== '' ? $paystackPlanCode : null;
                $stmt = $conn->prepare(
                    'INSERT INTO subscription_plans
                     (role_key, stakeholder_name, monthly_price, plan_type, billing_interval, paystack_plan_code, is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->bind_param('ssssssi', $roleKey, $stakeholderName, $price, $planType, $billingInterval, $code, $active);
                $stmt->execute();
                $stmt->close();
                $_SESSION['subscription_plan_message'] = 'Stakeholder subscription plan added.';
                header('Location: admin_subscriptions.php');
                exit;
            } catch (mysqli_sql_exception $exception) {
                error_log('Stakeholder plan creation failed. code=' . (int)$exception->getCode());
                $error = 'That stakeholder plan already exists or could not be saved.';
            }
        }
    } elseif (($_POST['action'] ?? '') === 'negotiated_approval') {
        $subscriptionId = filter_var($_POST['subscription_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $approved = (string)($_POST['approved'] ?? '');
        if ($subscriptionId === false || !in_array($approved, ['0', '1'], true)) {
            $error = 'Select a valid negotiated membership.';
        } else {
            $approvalTransactionOpen = false;
            try {
                $conn->begin_transaction();
                $approvalTransactionOpen = true;
                $stmt = $conn->prepare(
                    'SELECT s.status FROM user_subscriptions s
                     INNER JOIN subscription_plans p ON p.id=s.plan_id
                     WHERE s.id=? AND p.plan_type=\'negotiated\' LIMIT 1 FOR UPDATE'
                );
                $stmt->bind_param('i', $subscriptionId);
                $stmt->execute();
                $membership = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!$membership) {
                    $conn->rollback();
                    $approvalTransactionOpen = false;
                    $error = 'That negotiated membership no longer exists.';
                } else {
                    $approval = (int)$approved;
                    $stmt = $conn->prepare(
                        'UPDATE user_subscriptions
                         SET negotiated_approved=?, negotiated_approved_by=?,
                             negotiated_approved_at=IF(?, NOW(), NULL), updated_at=NOW()
                         WHERE id=?'
                    );
                    $stmt->bind_param('iiii', $approval, $userId, $approval, $subscriptionId);
                    $stmt->execute();
                    $stmt->close();

                    $oldStatus = (string)$membership['status'];
                    $reason = $approval === 1 ? 'Negotiated plan approved by administrator.' : 'Negotiated plan approval revoked by administrator.';
                    $stmt = $conn->prepare(
                        'INSERT INTO subscription_status_history
                         (user_membership_id, old_status, new_status, reason, source)
                         VALUES (?, ?, ?, ?, \'admin\')'
                    );
                    $newStatus = 'negotiated_plan';
                    $stmt->bind_param('isss', $subscriptionId, $oldStatus, $newStatus, $reason);
                    $stmt->execute();
                    $stmt->close();
                    $conn->commit();
                    $approvalTransactionOpen = false;
                    $_SESSION['subscription_plan_message'] = $approval ? 'Negotiated membership approved.' : 'Negotiated membership approval revoked.';
                    header('Location: admin_subscriptions.php');
                    exit;
                }
            } catch (Throwable $exception) {
                if ($approvalTransactionOpen) {
                    $conn->rollback();
                }
                error_log('Negotiated membership approval failed. code=' . (int)$exception->getCode());
                $error = 'The negotiated membership could not be updated.';
            }
        }
    } else {
        $planId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $planType = (string)($_POST['plan_type'] ?? '');
        $activeInput = (string)($_POST['is_active'] ?? '');
        $priceInput = trim((string)($_POST['monthly_price'] ?? ''));
        $stakeholderName = trim((string)($_POST['stakeholder_name'] ?? ''));
        $billingInterval = (string)($_POST['billing_interval'] ?? 'monthly');
        $paystackPlanCode = trim((string)($_POST['paystack_plan_code'] ?? ''));

        if ($planId === false || $planId === null) {
            $error = 'Select a valid subscription plan.';
        } elseif ($stakeholderName === '' || strlen($stakeholderName) > 120) {
            $error = 'Enter a stakeholder name of no more than 120 characters.';
        } elseif (!in_array($planType, ['paid', 'free', 'negotiated'], true)) {
            $error = 'Select a supported plan type.';
        } elseif (!in_array($billingInterval, ['monthly', 'quarterly', 'biannually', 'annually'], true)) {
            $error = 'Select a supported billing interval.';
        } elseif (!in_array($activeInput, ['0', '1'], true)) {
            $error = 'Select a valid plan status.';
        } elseif ($planType === 'paid' && !preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $priceInput)) {
            $error = 'Enter a valid plan price with no more than two decimal places.';
        } elseif (strlen($paystackPlanCode) > 100) {
            $error = 'The Paystack plan code must be 100 characters or fewer.';
        } else {
            try {
                $conn->begin_transaction();
                $exists = $conn->prepare('SELECT id, plan_type FROM subscription_plans WHERE id = ? LIMIT 1 FOR UPDATE');
                if (!$exists) {
                    throw new mysqli_sql_exception('Unable to prepare plan lookup.');
                }
                $exists->bind_param('i', $planId);
                $exists->execute();
                $oldPlan = $exists->get_result()->fetch_assoc();
                $planExists = (bool)$oldPlan;
                $exists->close();

                if (!$planExists) {
                    $error = 'That subscription plan no longer exists. Reload the page and try again.';
                } elseif ($planType === 'paid') {
                    $update = $conn->prepare(
                        'UPDATE subscription_plans
                         SET stakeholder_name=?, plan_type=?, monthly_price=?, billing_interval=?,
                             paystack_plan_code=NULLIF(?, \'\'), is_active=?
                         WHERE id=?'
                    );
                    if (!$update) {
                        throw new mysqli_sql_exception('Unable to prepare plan update.');
                    }
                    $update->bind_param('sssssii', $stakeholderName, $planType, $priceInput, $billingInterval, $paystackPlanCode, $activeInput, $planId);
                    if (!$update->execute()) throw new RuntimeException('Plan update failed');
                    $update->close();
                } else {
                    $update = $conn->prepare(
                        'UPDATE subscription_plans
                         SET stakeholder_name=?, plan_type=?, monthly_price=NULL, billing_interval=?,
                             paystack_plan_code=NULL, is_active=?
                         WHERE id=?'
                    );
                    if (!$update) {
                        throw new mysqli_sql_exception('Unable to prepare plan update.');
                    }
                    $update->bind_param('sssii', $stakeholderName, $planType, $billingInterval, $activeInput, $planId);
                    if (!$update->execute()) throw new RuntimeException('Plan update failed');
                    $update->close();
                }

                if ($error === '') {
                    vayuma_reconcile_plan_memberships($conn, (int)$planId, (string)$oldPlan['plan_type'], $planType, $billingInterval, $userId);
                    if (!$conn->commit()) throw new RuntimeException('Plan transaction commit failed');
                    $_SESSION['subscription_plan_message'] = 'Subscription plan saved.';
                    header('Location: admin_subscriptions.php');
                    exit;
                }
                $conn->rollback();
            } catch (DomainException $exception) {
                $conn->rollback();
                $error = $exception->getMessage();
            } catch (Throwable $exception) {
                try { $conn->rollback(); } catch (Throwable $ignored) {}
                error_log('Subscription plan update or reconciliation failed.');
                $error = 'The subscription plan could not be saved. Please try again.';
            }
        }
    }
}

$successMessage = (string)($_SESSION['subscription_plan_message'] ?? '');
unset($_SESSION['subscription_plan_message']);
$plansResult = $conn->query(
    'SELECT id, role_key, stakeholder_name, monthly_price, plan_type, is_active,
            billing_interval, paystack_plan_code
     FROM subscription_plans ORDER BY stakeholder_name'
);
$plans = $plansResult ? $plansResult->fetch_all(MYSQLI_ASSOC) : [];
if (!$plansResult) {
    error_log('Unable to load subscription plans: ' . $conn->error);
}
$existingRoleKeys = array_column($plans, 'role_key');

$navLinks = [
    'Dashboard' => 'dashboard.php',
    'Verification' => 'admin_verification.php',
];

$activePage = basename($_SERVER['PHP_SELF']);
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
          <div>
            <strong>VAYUMA</strong>
            <small>Smart Ecosystem</small>
          </div>
        </a>
      </div>
      <nav class="va-sidebar--nav">
        <?php foreach ($navLinks as $label => $href): ?>
          <?php $isActive = ($activePage === $href); ?>
          <a href="<?= htmlspecialchars($href) ?>" class="va-nav-item<?= $isActive ? ' va-nav-item--active' : '' ?>" <?= $isActive ? 'aria-current="page"' : '' ?>>
            <span class="va-nav-icon"><?= $label === 'Dashboard' ? '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-dashboard"/></svg>' : '✓' ?></span>
            <span><?= htmlspecialchars($label) ?></span>
          </a>
        <?php endforeach; ?>
      </nav>
      <div class="va-sidebar--bottom">
        <a href="auth/logout.php" class="va-logout-item"><span>⇥</span>Logout</a>
      </div>
    </aside>
    <?php endif; ?>

    <div class="va-main--content">
      <?php if ($successMessage !== ''): ?>
        <div class="va-toast va-toast--success" role="alert" aria-live="polite">
          <div class="va-toast--title">Success</div>
          <div class="va-toast--msg"><?= h($successMessage) ?></div>
        </div>
      <?php endif; ?>

      <div class="va-section va-mb-6">
        <div>
          <h1 class="va-section--title">Subscription &amp; Pricing</h1>
          <p class="va-section--sub">Manage stakeholder prices, billing intervals, and negotiated approvals. Payment processing is currently unavailable.</p>
          <p class="va-text-muted va-text-sm">Changing plan type updates existing memberships: free grants free access; paid makes payment due with no automatic grace period; negotiated requires a new approval. Active paid memberships must finish before changing to negotiated. Existing payment records are retained.</p>
        </div>
      </div>

      <?php if ($error !== ''): ?>
        <div class="va-toast va-toast--error" role="alert" aria-live="polite">
          <div class="va-toast--title">Error</div>
          <div class="va-toast--msg"><?= h($error) ?></div>
        </div>
      <?php endif; ?>

      <?php
      $availableRoles = array_diff(array_keys($supportedStakeholders), $existingRoleKeys);
      if ($availableRoles):
      ?>
      <div class="va-card va-card--p va-mb-6">
        <h2 class="va-text-lg va-font-semibold">Add stakeholder plan</h2>
        <p class="va-text-muted va-text-sm">Only stakeholder types already supported by VAYUMA account and dashboard routing can be added here.</p>
        <form method="post" class="va-d-grid va-gap-4" style="grid-template-columns:repeat(auto-fit,minmax(190px,1fr));">
          <?= vayuma_csrf_field() ?>
          <input type="hidden" name="action" value="create_plan">
          <label>Stakeholder type
            <select name="role_key" class="va-input" required>
              <?php foreach ($availableRoles as $roleKey): ?>
                <option value="<?= h($roleKey) ?>"><?= h($supportedStakeholders[$roleKey]) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>Display name
            <input name="stakeholder_name" class="va-input" maxlength="120" required>
          </label>
          <label>Plan type
            <select name="plan_type" class="va-input">
              <option value="paid">Paid</option><option value="free">Free</option><option value="negotiated">Negotiated</option>
            </select>
          </label>
          <label>Price amount (KSh)
            <input name="monthly_price" type="number" min="0" max="9999999999.99" step="0.01" class="va-input">
          </label>
          <label>Billing interval
            <select name="billing_interval" class="va-input">
              <option value="monthly">Monthly</option><option value="quarterly">Quarterly</option>
              <option value="biannually">Biannually</option><option value="annually">Annually</option>
            </select>
          </label>
          <label>Paystack plan code (optional)
            <input name="paystack_plan_code" maxlength="100" class="va-input" placeholder="Created automatically at checkout">
            <small class="va-text-muted">Leave blank; VAYUMA creates and saves this plan when checkout begins.</small>
          </label>
          <label><input type="checkbox" name="is_active" value="1" checked> Active</label>
          <div><button type="submit" class="va-btn va-btn--primary">Add plan</button></div>
        </form>
      </div>
      <?php endif; ?>

      <div class="va-card va-card--p va-mb-6">
        <table class="va-table">
          <thead>
            <tr>
              <th>Stakeholder</th>
              <th>Price (KSh)</th>
              <th>Interval</th>
              <th>Type</th>
              <th>Paystack plan code (auto-created)</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if (!$plansResult):
            ?>
              <tr><td colspan="7"><div class="va-toast va-toast--error" role="alert">Subscription plans could not be loaded. Refresh the page to retry.</div></td></tr>
            <?php
            else:
            foreach ($plans as $p):
              $planFormId = 'subscription-plan-' . (int)$p['id'];
            ?>
            <tr>
              <td>
                <label class="sr-only" for="<?= h($planFormId) ?>-name">Stakeholder display name</label>
                <input id="<?= h($planFormId) ?>-name" form="<?= h($planFormId) ?>" name="stakeholder_name" maxlength="120" value="<?= h($p['stakeholder_name']) ?>" class="va-input va-text-sm" style="min-width:190px;" required>
                <small class="va-text-muted"><?= h($p['role_key']) ?></small>
              </td>
              <td>
                <label class="sr-only" for="<?= h($planFormId) ?>-price">Price for <?= h($p['stakeholder_name']) ?></label>
                <input id="<?= h($planFormId) ?>-price" form="<?= h($planFormId) ?>" type="number" name="monthly_price" min="0" max="9999999999.99" step="0.01" value="<?= h($p['monthly_price'] ?? '') ?>" class="va-input va-text-sm" style="width:130px;" <?= $p['plan_type'] === 'paid' ? 'required' : 'disabled' ?>>
              </td>
              <td>
                <label class="sr-only" for="<?= h($planFormId) ?>-interval">Billing interval</label>
                <select id="<?= h($planFormId) ?>-interval" form="<?= h($planFormId) ?>" name="billing_interval" class="va-input va-text-sm">
                  <?php foreach (['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'biannually' => 'Biannually', 'annually' => 'Annually'] as $intervalKey => $intervalLabel): ?>
                    <option value="<?= h($intervalKey) ?>" <?= ($p['billing_interval'] ?? 'monthly') === $intervalKey ? 'selected' : '' ?>><?= h($intervalLabel) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td>
                <label class="sr-only" for="<?= h($planFormId) ?>-type">Plan type for <?= h($p['stakeholder_name']) ?></label>
                <select id="<?= h($planFormId) ?>-type" form="<?= h($planFormId) ?>" name="plan_type" class="va-input va-text-sm" style="width:160px;">
                  <option value="paid" <?= $p['plan_type'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                  <option value="free" <?= $p['plan_type'] === 'free' ? 'selected' : '' ?>>Free</option>
                  <option value="negotiated" <?= $p['plan_type'] === 'negotiated' ? 'selected' : '' ?>>Negotiated</option>
                </select>
              </td>
              <td>
                <label class="sr-only" for="<?= h($planFormId) ?>-paystack-code">Paystack plan code</label>
                <input id="<?= h($planFormId) ?>-paystack-code" form="<?= h($planFormId) ?>" name="paystack_plan_code" maxlength="100" value="<?= h($p['paystack_plan_code'] ?? '') ?>" class="va-input va-text-sm" style="min-width:130px;" <?= $p['plan_type'] === 'paid' ? '' : 'disabled' ?>>
              </td>
              <td>
                <input form="<?= h($planFormId) ?>" type="hidden" name="is_active" value="0">
                <label class="va-badge" for="<?= h($planFormId) ?>-active" style="padding:0.2rem 0.6rem;font-size:0.75rem;">
                  <input id="<?= h($planFormId) ?>-active" form="<?= h($planFormId) ?>" type="checkbox" name="is_active" value="1" <?= $p['is_active'] ? 'checked' : '' ?> style="margin-right:0.375rem;">
                  <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
                </label>
              </td>
              <td>
                <form id="<?= h($planFormId) ?>" method="post" style="margin:0;">
                  <input type="hidden" name="id" value="<?= h($p['id']) ?>">
                  <input type="hidden" name="action" value="save_plan">
                  <?= vayuma_csrf_field() ?>
                  <button type="submit" class="va-btn va-btn--outline va-btn--sm">Save</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="va-card va-card--p va-mb-6">
        <h2 class="va-text-lg va-font-semibold">Negotiated memberships</h2>
        <p class="va-text-muted va-text-sm">Strategic and other negotiated accounts remain blocked until an administrator explicitly approves them.</p>
        <?php
        $negotiated = $conn->query(
            "SELECT s.id, s.status, s.negotiated_approved, u.full_name, u.email, p.stakeholder_name
             FROM user_subscriptions s
             INNER JOIN users u ON u.id=s.user_id
             INNER JOIN subscription_plans p ON p.id=s.plan_id
             WHERE p.plan_type='negotiated'
             ORDER BY s.updated_at DESC"
        );
        if ($negotiated === false):
          error_log('Unable to load negotiated memberships: ' . $conn->error);
        ?>
          <p class="va-toast va-toast--error" role="alert">Negotiated memberships could not be loaded.</p>
        <?php elseif ($negotiated->num_rows === 0): ?>
          <p class="va-text-muted">No negotiated memberships are awaiting review.</p>
        <?php else: ?>
          <table class="va-table">
            <thead><tr><th>User</th><th>Plan</th><th>Approval</th><th>Action</th></tr></thead>
            <tbody>
              <?php while ($member = $negotiated->fetch_assoc()): ?>
                <tr>
                  <td><?= h($member['full_name']) ?><br><small><?= h($member['email'] ?? '') ?></small></td>
                  <td><?= h($member['stakeholder_name']) ?></td>
                  <td><?= (int)$member['negotiated_approved'] === 1 ? 'Approved' : 'Awaiting approval' ?></td>
                  <td>
                    <form method="post">
                      <?= vayuma_csrf_field() ?>
                      <input type="hidden" name="action" value="negotiated_approval">
                      <input type="hidden" name="subscription_id" value="<?= (int)$member['id'] ?>">
                      <input type="hidden" name="approved" value="<?= (int)$member['negotiated_approved'] === 1 ? '0' : '1' ?>">
                      <button type="submit" class="va-btn va-btn--outline va-btn--sm"><?= (int)$member['negotiated_approved'] === 1 ? 'Revoke' : 'Approve' ?></button>
                    </form>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

    </div>
  </div>
</main>

<script>
document.querySelectorAll('select[name="plan_type"]').forEach(function (select) {
  const price = document.getElementById(select.id.replace(/-type$/, '-price'));
  const planCode = document.getElementById(select.id.replace(/-type$/, '-paystack-code'));
  if (!price) return;
  function updatePriceState() {
    const paid = select.value === 'paid';
    price.disabled = !paid;
    price.required = paid;
    if (planCode) planCode.disabled = !paid;
  }
  select.addEventListener('change', updatePriceState);
  updatePriceState();
});
</script>

<?php require_once __DIR__ . '/includes/ui_simple_close.php'; ?>
