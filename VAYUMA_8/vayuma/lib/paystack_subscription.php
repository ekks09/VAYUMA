<?php
/**
 * VAYUMA Paystack subscription helpers (card-based recurring billing).
 *
 * Paystack subscriptions are card-based. This layer:
 *   - creates/retrieves a Paystack customer for a user
 *   - creates a subscription against an admin-managed plan (paystack_plan_code)
 *   - verifies transactions server-side
 *
 * Depends on lib/paystack.php (paystack_api, paystack_initialize, paystack_verify).
 *
 * Reference: https://paystack.com/docs/api/customer
 *            https://paystack.com/docs/api/subscription
 */

require_once __DIR__ . '/paystack.php';

function paystack_subscription_config(): array
{
    $config = paystack_config();
    return [
        'secret_key'  => $config['secret_key'],
        'currency'    => $config['currency'] ?? 'KES',
    ];
}

function paystack_api_sub(string $path, string $method = 'GET', ?array $payload = null): array
{
    return paystack_api($path, $method, $payload);
}

/**
 * Create a Paystack customer for the user's email, or return existing customer_code.
 * Stores nothing; caller persists customer_code in user_subscriptions.
 */
function paystack_get_or_create_customer(string $email): array
{
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('A valid email is required to create a Paystack customer.');
    }

    $lookup = paystack_api_sub('/customer?email=' . rawurlencode($email), 'GET');
    foreach (($lookup['data'] ?? []) as $customer) {
        if (strcasecmp((string)($customer['email'] ?? ''), $email) === 0
            && !empty($customer['customer_code'])) {
            return $customer;
        }
    }

    $response = paystack_api_sub('/customer', 'POST', [
        'email' => $email,
    ]);
    if (empty($response['status']) || $response['status'] !== true) {
        throw new RuntimeException($response['message'] ?? 'Paystack could not create customer.');
    }
    return $response['data'] ?? $response;
}

/**
 * Create a recurring Paystack plan on first checkout and persist its plan code.
 */
function paystack_ensure_subscription_plan(mysqli $conn, array $membership): string
{
    $existingCode = trim((string)($membership['paystack_plan_code'] ?? ''));
    if ($existingCode !== '') {
        return $existingCode;
    }

    $interval = (string)($membership['plan_billing_interval'] ?? $membership['billing_interval'] ?? 'monthly');
    if (!in_array($interval, ['monthly', 'quarterly', 'biannually', 'annually'], true)) {
        throw new RuntimeException('This subscription uses a billing interval Paystack does not support.');
    }

    $amount = (float)($membership['monthly_price'] ?? 0);
    if ($amount <= 0) {
        throw new RuntimeException('Set a positive price for this stakeholder plan before enabling Paystack.');
    }

    $currency = strtoupper((string)($membership['currency'] ?? paystack_subscription_config()['currency']));
    if (!preg_match('/^[A-Z]{3}$/', $currency)) {
        throw new RuntimeException('Set a valid three-letter currency for this stakeholder plan.');
    }

    $response = paystack_api_sub('/plan', 'POST', [
        'name' => (string)($membership['stakeholder_name'] ?? $membership['role_key'] ?? 'VAYUMA Subscription')
            . ' - ' . ucfirst($interval),
        'interval' => $interval,
        'amount' => (int)round($amount * 100),
        'currency' => $currency,
    ]);
    if (($response['status'] ?? false) !== true) {
        throw new RuntimeException($response['message'] ?? 'Paystack could not create the recurring plan.');
    }

    $data = $response['data'] ?? [];
    $createdCode = trim((string)($data['plan_code'] ?? ''));
    if ($createdCode === '') {
        throw new RuntimeException('Paystack created no plan code for this recurring plan.');
    }

    $planId = (int)($membership['plan_id'] ?? 0);
    if ($planId < 1) {
        throw new RuntimeException('The local subscription plan could not be identified.');
    }

    $stmt = $conn->prepare(
        "UPDATE subscription_plans
         SET paystack_plan_code=?
         WHERE id=? AND (paystack_plan_code IS NULL OR paystack_plan_code='')"
    );
    $stmt->bind_param('si', $createdCode, $planId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare('SELECT paystack_plan_code FROM subscription_plans WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $planId);
    $stmt->execute();
    $savedPlan = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $savedCode = trim((string)($savedPlan['paystack_plan_code'] ?? ''));
    if ($savedCode === '') {
        throw new RuntimeException('The Paystack plan code could not be saved to VAYUMA.');
    }

    return $savedCode;
}

/**
 * Create a Paystack subscription for a customer on a plan.
 * Returns subscription data incl. authorization_url (first payment) and subscription_code.
 */
function paystack_create_subscription(string $customerCode, string $planCode, string $email): array
{
    if (empty($customerCode) || empty($planCode)) {
        throw new InvalidArgumentException('Customer code and plan code are required.');
    }
    $response = paystack_api_sub('/subscription', 'POST', [
        'customer' => $customerCode,
        'plan'     => $planCode,
        'email'    => $email,
    ]);
    if (empty($response['status']) || $response['status'] !== true) {
        throw new RuntimeException($response['message'] ?? 'Paystack could not create subscription.');
    }
    return $response['data'] ?? $response;
}

/**
 * Enable/disable auto-renew for a Paystack subscription.
 */
function paystack_subscription_toggle(string $subscriptionCode, bool $token): array
{
    $action = $token ? 'enable' : 'disable';
    $response = paystack_api_sub('/subscription/' . $action, 'POST', [
        'code' => $subscriptionCode,
    ]);
    if (empty($response['status']) || $response['status'] !== true) {
        throw new RuntimeException($response['message'] ?? 'Paystack could not ' . $action . ' subscription.');
    }
    return $response['data'] ?? $response;
}

/**
 * Record a webhook event for idempotency. Returns true if the event was new (not processed before).
 */
function paystack_event_seen(mysqli $conn, string $eventId): bool
{
    if ($eventId === '') {
        return false;
    }
    $stmt = $conn->prepare('SELECT id FROM payment_events WHERE event_id=? LIMIT 1');
    $stmt->bind_param('s', $eventId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row !== null;
}

function paystack_record_event(mysqli $conn, array $event): void
{
    $eventId   = $event['event_id'] ?? '';
    $eventType = $event['event_type'] ?? $event['event'] ?? '';
    $reference = $event['reference'] ?? null;
    $subCode   = $event['paystack_subscription_code'] ?? null;
    $txnId     = $event['paystack_transaction_id'] ?? null;
    $payload   = json_encode($event['payload'] ?? $event, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

    if ($eventId !== '') {
        $stmt = $conn->prepare(
            'INSERT IGNORE INTO payment_events (event_id, event_type, reference, paystack_subscription_code, paystack_transaction_id, payload)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('ssssss', $eventId, $eventType, $reference, $subCode, $txnId, $payload);
    } else {
        $stmt = $conn->prepare(
            'INSERT INTO payment_events (event_type, reference, paystack_subscription_code, paystack_transaction_id, payload)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssss', $eventType, $reference, $subCode, $txnId, $payload);
    }
    $stmt->execute();
    $stmt->close();
}

function paystack_mark_event_processed(mysqli $conn, string $eventId): void
{
    if ($eventId === '') {
        return;
    }
    $stmt = $conn->prepare('UPDATE payment_events SET processed=1, processed_at=NOW() WHERE event_id=?');
    $stmt->bind_param('s', $eventId);
    $stmt->execute();
    $stmt->close();
}

/**
 * Append a status-change audit row for a user membership.
 */
function paystack_record_status_history(mysqli $conn, int $membershipId, ?string $oldStatus, string $newStatus, string $reason, string $source = 'webhook'): void
{
    $stmt = $conn->prepare(
        'INSERT INTO subscription_status_history (user_membership_id, old_status, new_status, reason, source)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('issss', $membershipId, $oldStatus, $newStatus, $reason, $source);
    $stmt->execute();
    $stmt->close();
}

/**
 * Set a user's membership status and record history.
 */
function paystack_set_membership_status(mysqli $conn, int $membershipId, string $newStatus, string $reason, string $source = 'webhook', ?array $dates = null): void
{
    $stmt = $conn->prepare('SELECT status FROM user_subscriptions WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $membershipId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        throw new RuntimeException('Subscription membership not found.');
    }
    $oldStatus = $row['status'] ?? null;

    $sets = 'status=?';
    $params = [$newStatus];
    $types = 's';
    if ($dates !== null) {
        if (array_key_exists('expires_at', $dates)) { $sets .= ', expires_at=?'; $params[] = $dates['expires_at']; $types .= 's'; }
        if (array_key_exists('next_billing_date', $dates)) { $sets .= ', next_billing_date=?'; $params[] = $dates['next_billing_date']; $types .= 's'; }
        if (array_key_exists('grace_period_ends_at', $dates)) { $sets .= ', grace_period_ends_at=?'; $params[] = $dates['grace_period_ends_at']; $types .= 's'; }
        if (array_key_exists('auto_renew', $dates)) { $sets .= ', auto_renew=?'; $params[] = (int)$dates['auto_renew']; $types .= 'i'; }
    }
    $sets .= ', updated_at=NOW()';
    $params[] = $membershipId;
    $types .= 'i';

    $stmt = $conn->prepare('UPDATE user_subscriptions SET ' . $sets . ' WHERE id=?');
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();

    paystack_record_status_history($conn, $membershipId, $oldStatus, $newStatus, $reason, $source);
}

/**
 * Compute next billing date by adding interval to a reference date.
 */
function paystack_next_billing_date(string $interval, ?string $from = null): ?string
{
    $from = $from ?: date('Y-m-d');
    switch (strtolower($interval)) {
        case 'quarterly':  $modifier = '+3 months'; break;
        case 'biannually': $modifier = '+6 months'; break;
        case 'annually':   $modifier = '+1 year'; break;
        case 'monthly':
        default:           $modifier = '+1 month'; break;
    }
    $dt = new DateTime($from);
    $dt->modify($modifier);
    return $dt->format('Y-m-d');
}
