<?php
require_once __DIR__ . '/role_routes.php';

function vayuma_user_initials(string $name): string {
    $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $initials = '';
    foreach (array_slice($words, 0, 2) as $word) {
        $initials .= function_exists('mb_substr') ? mb_substr($word, 0, 1, 'UTF-8') : substr($word, 0, 1);
    }
    return $initials === '' ? 'V' : (function_exists('mb_strtoupper') ? mb_strtoupper($initials, 'UTF-8') : strtoupper($initials));
}
function vayuma_menu_plan(?array $plan, ?int $now = null): array {
    $now = $now ?? time();
    if (!$plan) return ['Unavailable', 'unavailable', null];
    $status = $plan['status'] ?? '';
    $end = $plan['expires_at'] ?? null;
    if ($status === 'payment_due' && !empty($plan['grace_period_ends_at'])) {
        if (strtotime($plan['grace_period_ends_at']) >= $now) return ['Grace Period', 'grace', $plan['grace_period_ends_at']];
        return ['Expired', 'unavailable', $plan['grace_period_ends_at']];
    }
    if ($status === 'active' && (empty($end) || strtotime($end) >= $now)) return ['Active', 'active', $end];
    if ($status === 'free_account') return ['Free', 'active', $end];
    if ($status === 'negotiated_plan' && (int)($plan['negotiated_approved'] ?? 0) === 1) return ['Negotiated', 'active', $end];
    return [$status === 'expired' || ($status === 'active' && !empty($end)) ? 'Expired' : 'Payment due', 'unavailable', $end];
}
function vayuma_menu_data($conn): array {
    $user = ['full_name' => $_SESSION['full_name'] ?? 'Member', 'email' => $_SESSION['email'] ?? '', 'phone' => $_SESSION['phone'] ?? '', 'avatar_url' => null];
    $plan = null;
    if (isset($_SESSION['user_id']) && $conn instanceof mysqli) {
        try {
            // The avatar column may not have been migrated yet; never break existing dashboards.
            $stmt = $conn->prepare('SELECT full_name, email, phone, avatar_url FROM users WHERE id=? LIMIT 1');
            $id = (int)$_SESSION['user_id']; $stmt->bind_param('i', $id); $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc() ?: $user; $stmt->close();
        } catch (Throwable $e) {
            // Before the manual avatar migration, retain real contact details.
            try {
                $stmt = $conn->prepare('SELECT full_name, email, phone FROM users WHERE id=? LIMIT 1');
                $id = (int)$_SESSION['user_id']; $stmt->bind_param('i', $id); $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc() ?: $user; $stmt->close();
            } catch (Throwable $ignored) { error_log('VAYUMA user menu: user lookup unavailable.'); }
        }
        try {
            $stmt = $conn->prepare('SELECT s.status, s.expires_at, s.grace_period_ends_at, s.negotiated_approved, p.stakeholder_name FROM user_subscriptions s JOIN subscription_plans p ON p.id=s.plan_id WHERE s.user_id=? LIMIT 1');
            $id = (int)$_SESSION['user_id']; $stmt->bind_param('i', $id); $stmt->execute();
            $plan = $stmt->get_result()->fetch_assoc() ?: null; $stmt->close();
        } catch (Throwable $e) { error_log('VAYUMA user menu: subscription lookup unavailable.'); }
    }
    return [$user, $plan];
}
