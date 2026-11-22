<?php
/**
 * VAYUMA — Subscription Status Banner (reusable component)
 * Displays the authenticated user's actual subscription state.
 * Include on any dashboard page:
 *   require_once __DIR__ . '/includes/subscription_banner.php';
 *   echo render_subscription_banner($conn, $userId);
 */
require_once __DIR__ . '/../lib/subscription_lib.php';

function render_subscription_banner(mysqli $conn, int $userId): string
{
    $ob = [];
    $ob[] = '<style>
    .va-sub-banner {
        display: flex; align-items: center; justify-content: space-between;
        gap: var(--va-space-3); padding: var(--va-space-3) var(--va-space-4);
        border-radius: var(--va-radius); margin-bottom: var(--va-space-4);
        border: 1px solid var(--va-border); background: var(--va-surface);
        box-shadow: var(--va-shadow-sm);
    }
    .va-sub-banner--active { background: #e4f6e9; border-color: #b7e4c7; }
    .va-sub-banner--payment-due { background: #fff4d6; border-color: #f3e3a8; }
    .va-sub-banner--expired { background: #fde7e7; border-color: #f3b7b7; }
    .va-sub-banner--free { background: #ecfdf5; border-color: #b7e4c7; }
    .va-sub-banner--negotiated { background: #fef3c7; border-color: #f3d9a8; }
    .va-sub-banner--info { background: #e5f1fc; border-color: #b7d4f7; }
    .va-sub-banner--content { flex: 1; }
    .va-sub-banner--title { font-size: var(--va-font-size-sm); font-weight: var(--va-font-weight-bold); color: var(--va-text-primary); margin: 0 0 var(--va-space-1) 0; }
    .va-sub-banner--sub { font-size: var(--va-font-size-xs); color: var(--va-text-secondary); margin: 0; }
    .va-sub-banner--actions { display: flex; gap: var(--va-space-2); align-items: center; }
    .va-sub-banner--badge {
        display: inline-flex; align-items: center; gap: var(--va-space-1);
        padding: 0.25rem 0.6rem; font-size: var(--va-font-size-xs); font-weight: var(--va-font-weight-bold);
        border-radius: var(--va-radius-full); text-transform: uppercase; letter-spacing: 0.05em;
        color: #fff;
    }
    </style>';

    try {
        // The guard manages plan assignment; this banner only reads current status.
        $sub = subscription_for_user($conn, $userId);
    } catch (Throwable $exception) {
        error_log('Subscription banner unavailable: ' . $exception->getMessage());
        return implode("\n", $ob) . '<div class="va-sub-banner va-sub-banner--info" role="status">Subscription status is temporarily unavailable.</div>';
    }
    if (!$sub) {
        $ob[] = '<div class="va-sub-banner va-sub-banner--info">';
        $ob[] = '  <div class="va-sub-banner--content">';
        $ob[] = '    <p class="va-sub-banner--title">No subscription plan</p>';
        $ob[] = '    <p class="va-sub-banner--sub">Contact VAYUMA support to configure your account.</p>';
        $ob[] = '  </div>';
        $ob[] = '  <div class="va-sub-banner--actions">';
        $ob[] = '    <a href="subscription.php" class="va-btn va-btn--outline va-btn--sm">Contact Support</a>';
        $ob[] = '  </div>';
        $ob[] = '</div>';
        return implode("\n", $ob);
    }

    $status = $sub['status'] ?? 'payment_due';
    $planType = $sub['plan_type'] ?? 'paid';
    $stakeholderName = $sub['stakeholder_name'] ?? 'Unknown';
    $monthlyPrice = (float)($sub['monthly_price'] ?? 0);
    $expiresAt = $sub['expires_at'] ?? null;

    $isAccessible = subscription_is_accessible($sub);

    if ($planType === 'free') {
        $badgeClass = 'va-sub-banner--free';
        $badgeText = 'Free Account';
        $title = 'Free Access';
        $subText = 'Your account does not require payment. Access is active.';
        $action = '<a href="dashboard.php" class="va-btn va-btn--primary va-btn--sm">Continue to Dashboard</a>';
    } elseif ($planType === 'negotiated') {
        $badgeClass = 'va-sub-banner--negotiated';
        $badgeText = 'Negotiated Plan';
        $title = 'Custom Plan';
        $subText = 'Your account uses a negotiated plan. Institutional invoice support can be added later.';
        $action = '<a href="dashboard.php" class="va-btn va-btn--primary va-btn--sm">Continue to Dashboard</a>';
    } elseif ($isAccessible) {
        $badgeClass = 'va-sub-banner--active';
        $badgeText = 'Active';
        $title = 'Subscription Active';
        $expText = $expiresAt ? ' expires ' . date('d M Y', strtotime($expiresAt)) : '';
        $subText = "Paid plan ({$stakeholderName}){$expText}. Access is active.";
        $action = '<a href="subscription.php" class="va-btn va-btn--outline va-btn--sm">Manage Subscription</a>';
    } else {
        $badgeClass = 'va-sub-banner--payment-due';
        $badgeText = 'Payment Due';
        $title = 'Payment Required';
        $subText = "Your {$stakeholderName} plan requires payment to activate access.";
        $action = '<a href="subscription.php" class="va-btn va-btn--primary va-btn--sm">Pay Now</a>';
    }

    $badgeStyle = '';
    if ($badgeClass === 'va-sub-banner--active') $badgeStyle = 'background:#17663a;';
    elseif ($badgeClass === 'va-sub-banner--payment-due') $badgeStyle = 'background:#b06a00;';
    elseif ($badgeClass === 'va-sub-banner--expired') $badgeStyle = 'background:#a32121;';
    elseif ($badgeClass === 'va-sub-banner--free') $badgeStyle = 'background:#059669;';
    elseif ($badgeClass === 'va-sub-banner--negotiated') $badgeStyle = 'background:#ca8a04;';

    $ob[] = '<div class="va-sub-banner ' . $badgeClass . '">';
    $ob[] = '  <div class="va-sub-banner--content">';
    $ob[] = '    <p class="va-sub-banner--title">' . htmlspecialchars($title) . '</p>';
    $ob[] = '    <p class="va-sub-banner--sub">' . htmlspecialchars($subText) . '</p>';
    $ob[] = '  </div>';
    $ob[] = '  <div class="va-sub-banner--actions">';
    $ob[] = '    <span class="va-sub-banner--badge" style="' . $badgeStyle . '">' . htmlspecialchars($badgeText) . '</span>';
    $ob[] = '    ' . $action;
    $ob[] = '  </div>';
    $ob[] = '</div>';

    return implode("\n", $ob);
}
