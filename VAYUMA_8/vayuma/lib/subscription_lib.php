<?php

function subscription_plan_for_role(mysqli $conn, string $role): ?array
{
    $stmt = $conn->prepare('SELECT * FROM subscription_plans WHERE role_key=? AND is_active=1 LIMIT 1');
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

    if ($row && $row['status'] === 'active' && !empty($row['expires_at']) && strtotime($row['expires_at']) < time()) {
        $id = (int)$row['id'];
        $stmt = $conn->prepare("UPDATE user_subscriptions SET status='expired', updated_at=NOW() WHERE id=? AND status='active'");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $changed = $stmt->affected_rows === 1;
        $stmt->close();
        if ($changed) {
            $stmt = $conn->prepare(
                'INSERT INTO subscription_status_history
                 (user_membership_id, old_status, new_status, reason, source)
                 VALUES (?, \'active\', \'expired\', \'Subscription period ended.\', \'subscription_check\')'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }
        $row['status'] = 'expired';
    }
    if ($row && $row['status'] === 'payment_due' && !empty($row['grace_period_ends_at'])
        && strtotime($row['grace_period_ends_at']) < time()) {
        $id = (int)$row['id'];
        $stmt = $conn->prepare("UPDATE user_subscriptions SET status='expired', updated_at=NOW() WHERE id=? AND status='payment_due'");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $changed = $stmt->affected_rows === 1;
        $stmt->close();
        if ($changed) {
            $stmt = $conn->prepare(
                'INSERT INTO subscription_status_history
                 (user_membership_id, old_status, new_status, reason, source)
                 VALUES (?, \'payment_due\', \'expired\', \'Subscription grace period ended unpaid.\', \'subscription_check\')'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }
        $row['status'] = 'expired';
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

        $plan = subscription_plan_for_role($conn, $role);
        if (!$plan) {
            $conn->rollback();
            return null;
        }

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
