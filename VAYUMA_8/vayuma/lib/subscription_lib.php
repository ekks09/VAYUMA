<?php

function subscription_plan_for_role(mysqli $conn, string $role, bool $lock = false): ?array
{
    $stmt = $conn->prepare('SELECT * FROM subscription_plans WHERE role_key=? AND is_active=1 LIMIT 1' . ($lock ? ' FOR UPDATE' : ''));
    $stmt->bind_param('s', $role);
    $stmt->execute();
    $plan = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $plan ?: null;
}

function subscription_for_user(mysqli $conn, int $userId): ?array
{
    $stmt = $conn->prepare(
        'SELECT s.*, p.role_key, p.stakeholder_name, p.monthly_price, p.plan_type,
                p.paystack_plan_code, p.billing_interval AS plan_billing_interval, p.currency
         FROM user_subscriptions s
         INNER JOIN subscription_plans p ON p.id=s.plan_id
         WHERE s.user_id=? LIMIT 1'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Expiration and its history are one atomic transition. Re-check the
    // observed deadline after taking the lock so a renewal cannot be undone.
    $deadlineField = $row && $row['status'] === 'active' ? 'expires_at'
        : ($row && $row['status'] === 'payment_due' ? 'grace_period_ends_at' : null);
    if ($deadlineField !== null && !empty($row[$deadlineField]) && strtotime($row[$deadlineField]) < time()) {
        $observedStatus = $row['status'];
        $observedDeadline = $row[$deadlineField];
        $id = (int)$row['id'];
        if (!$conn->begin_transaction()) throw new RuntimeException('Subscription expiry transaction failed');
        try {
            $lock = $conn->prepare('SELECT status, expires_at, grace_period_ends_at FROM user_subscriptions WHERE id=? FOR UPDATE');
            $lock->bind_param('i', $id);
            $lock->execute();
            $current = $lock->get_result()->fetch_assoc();
            $lock->close();
            if (!$current) {
                $conn->commit();
                return null;
            }
            if ($current['status'] === $observedStatus && ($current[$deadlineField] ?? null) === $observedDeadline
                && !empty($current[$deadlineField]) && strtotime($current[$deadlineField]) < time()) {
                $update = $conn->prepare("UPDATE user_subscriptions SET status='expired', updated_at=NOW() WHERE id=? AND status=?");
                $update->bind_param('is', $id, $observedStatus);
                if (!$update->execute() || $update->affected_rows !== 1) throw new RuntimeException('Subscription expiry update failed');
                $update->close();
                $reason = $observedStatus === 'active' ? 'Subscription period ended.' : 'Subscription grace period ended unpaid.';
                $history = $conn->prepare("INSERT INTO subscription_status_history (user_membership_id, old_status, new_status, reason, source) VALUES (?, ?, 'expired', ?, 'subscription_check')");
                $history->bind_param('iss', $id, $observedStatus, $reason);
                if (!$history->execute()) throw new RuntimeException('Subscription expiry history failed');
                $history->close();
                $current['status'] = 'expired';
            }
            if (!$conn->commit()) throw new RuntimeException('Subscription expiry commit failed');
            $row = array_replace($row, $current);
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }

    if ($row && empty($row['billing_interval'])) {
        $row['billing_interval'] = $row['plan_billing_interval'] ?: 'monthly';
    }
    return $row ?: null;
}

function ensure_user_subscription(mysqli $conn, int $userId, string $role): ?array
{
    $conn->begin_transaction();
    try {
        // Lock the plan first, in the same order as admin reconciliation.
        $plan = subscription_plan_for_role($conn, $role, true);
        if (!$plan) {
            $conn->rollback();
            return null;
        }
        $stmt = $conn->prepare(
            'SELECT s.*, p.role_key
             FROM user_subscriptions s
             INNER JOIN subscription_plans p ON p.id=s.plan_id
             WHERE s.user_id=? LIMIT 1 FOR UPDATE'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $current = $stmt->get_result()->fetch_assoc();
        $stmt->close();


        if ($current && ($current['role_key'] ?? '') === $role) {
            $conn->commit();
            return subscription_for_user($conn, $userId);
        }

        $status = $plan['plan_type'] === 'free'
            ? 'free_account'
            : ($plan['plan_type'] === 'negotiated' ? 'negotiated_plan' : 'payment_due');
        $interval = $plan['billing_interval'] ?? 'monthly';
        $approved = 0;

        if ($current) {
            $stmt = $conn->prepare(
                'UPDATE user_subscriptions
                 SET plan_id=?, status=?, payment_provider=NULL, paystack_customer_code=NULL,
                     paystack_subscription_code=NULL, expires_at=NULL, next_billing_date=NULL,
                     billing_interval=?, negotiated_approved=?, updated_at=NOW()
                 WHERE id=?'
            );
            $stmt->bind_param('issii', $plan['id'], $status, $interval, $approved, $current['id']);
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO user_subscriptions
                 (user_id, plan_id, status, billing_interval, negotiated_approved)
                 VALUES (?,?,?,?,?)'
            );
            $stmt->bind_param('iissi', $userId, $plan['id'], $status, $interval, $approved);
        }
        $stmt->execute();
        $stmt->close();
        $conn->commit();
        return subscription_for_user($conn, $userId);
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

function subscription_is_accessible(?array $subscription): bool
{
    if (!$subscription) {
        return false;
    }
    if ($subscription['status'] === 'negotiated_plan') {
        return (int)($subscription['negotiated_approved'] ?? 0) === 1;
    }
    if ($subscription['status'] === 'payment_due' && !empty($subscription['grace_period_ends_at'])) {
        return strtotime($subscription['grace_period_ends_at']) >= time();
    }
    return in_array($subscription['status'], ['active', 'free_account'], true);
}

function subscription_status_label(string $status): string
{
    return [
        'active' => 'Active',
        'payment_due' => 'Payment due',
        'expired' => 'Expired',
        'free_account' => 'Free account',
        'negotiated_plan' => 'Negotiated plan',
    ][$status] ?? 'Payment due';
}

function paystack_subscription_plan_code(mysqli $conn, string $role): ?string
{
    $stmt = $conn->prepare('SELECT paystack_plan_code FROM subscription_plans WHERE role_key=? AND is_active=1 LIMIT 1');
    $stmt->bind_param('s', $role);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row['paystack_plan_code'] ?? null;
}

function paystack_subscription_is_active(?array $subscription): bool
{
    if (!$subscription) {
        return false;
    }
    if ($subscription['status'] === 'active' && !empty($subscription['expires_at'])) {
        return strtotime($subscription['expires_at']) >= time();
    }
    return in_array($subscription['status'], ['free_account', 'negotiated_plan'], true)
        && subscription_is_accessible($subscription);
}

function paystack_next_billing_date(string $interval, ?string $from = null): ?string
{
    $months = [
        'monthly' => 1,
        'quarterly' => 3,
        'biannually' => 6,
        'annually' => 12,
    ];
    $key = strtolower($interval);
    if (!isset($months[$key])) {
        throw new InvalidArgumentException('Unsupported billing interval.');
    }

    $date = new DateTimeImmutable($from ?: 'today');
    $day = (int)$date->format('j');
    $target = $date->modify('first day of this month')->modify('+' . $months[$key] . ' months');
    $lastDay = (int)$target->format('t');
    return $target->setDate(
        (int)$target->format('Y'),
        (int)$target->format('n'),
        min($day, $lastDay)
    )->format('Y-m-d');
}
