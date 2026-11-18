<?php

require_once __DIR__ . '/subscription_lib.php';

function paystack_apply_verified_subscription_payment(
    mysqli $conn,
    string $reference,
    array $verified,
    ?string $subscriptionCode = null,
    string $source = 'webhook'
): int {
    if (($verified['status'] ?? '') !== 'success' || ($verified['reference'] ?? '') !== $reference) {
        throw new RuntimeException('Paystack did not verify this transaction as successful.');
    }

    $transactionId = (string)($verified['id'] ?? '');
    $customer = $verified['customer'] ?? [];
    $customerCode = is_array($customer) ? (string)($customer['customer_code'] ?? '') : '';
    $verifiedSubscription = $verified['subscription'] ?? [];
    if (is_array($verifiedSubscription)) {
        $subscriptionCode = $subscriptionCode ?: (string)($verifiedSubscription['subscription_code'] ?? '');
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare(
            'SELECT sp.id AS payment_id, sp.amount AS expected_amount, sp.status AS payment_status,
                    sp.billing_interval AS payment_interval,
                    s.id AS subscription_id, s.user_id, s.status AS subscription_status,
                    s.payment_provider, s.expires_at, s.billing_interval,
                    p.monthly_price, p.currency, p.plan_type
             FROM user_subscriptions s
             INNER JOIN subscription_plans p ON p.id=s.plan_id
             LEFT JOIN subscription_payments sp
               ON sp.subscription_id=s.id AND sp.payment_method=\'paystack\'
              AND sp.paystack_reference=?
             WHERE s.id=(
               SELECT sp2.subscription_id FROM subscription_payments sp2
               WHERE sp2.payment_method=\'paystack\' AND sp2.paystack_reference=?
               LIMIT 1
             )
             OR (? <> \'\' AND s.paystack_subscription_code=?)
             OR (? <> \'\' AND s.paystack_customer_code=?)
             LIMIT 1 FOR UPDATE'
        );
        $stmt->bind_param('ssssss', $reference, $reference, $subscriptionCode, $subscriptionCode, $customerCode, $customerCode);
        $stmt->execute();
        $membership = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$membership) {
            throw new RuntimeException('No local subscription matches this Paystack transaction.');
        }
        if ($membership['plan_type'] !== 'paid') {
            throw new RuntimeException('Paystack cannot activate a free or negotiated subscription.');
        }

        $expectedAmount = $membership['expected_amount'] !== null
            ? (float)$membership['expected_amount']
            : (float)$membership['monthly_price'];
        $actualMinor = (int)($verified['amount'] ?? 0);
        $expectedMinor = (int)round($expectedAmount * 100);
        if ($actualMinor !== $expectedMinor) {
            throw new RuntimeException('Verified Paystack amount does not match the stored plan price.');
        }
        $expectedCurrency = strtoupper((string)($membership['currency'] ?? 'KES'));
        if (strtoupper((string)($verified['currency'] ?? '')) !== $expectedCurrency) {
            throw new RuntimeException('Verified Paystack currency does not match the subscription plan.');
        }

        $paymentId = (int)($membership['payment_id'] ?? 0);
        $alreadyPaid = $paymentId > 0 && $membership['payment_status'] === 'paid';
        $billingInterval = (string)($membership['payment_interval'] ?: $membership['billing_interval'] ?: 'monthly');
        $amount = $actualMinor / 100;
        $channel = (string)($verified['channel'] ?? 'card');
        $paidAt = (string)($verified['paid_at'] ?? date('Y-m-d H:i:s'));
        $rawPayload = json_encode($verified, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if ($paymentId > 0) {
            $stmt = $conn->prepare(
                'UPDATE subscription_payments
                 SET status=\'paid\', amount=?, billing_interval=?, transaction_reference=?, paystack_reference=?,
                     paystack_transaction_id=NULLIF(?, \'\'), paystack_subscription_code=NULLIF(?, \'\'),
                     paystack_customer_code=NULLIF(?, \'\'), channel=?, paid_at=?,
                     callback_received_at=NOW(), raw_payload=?
                 WHERE id=? AND (status<>\'paid\' OR paystack_reference=?)'
            );
            $stmt->bind_param(
                'dsssssssssis',
                $amount,
                $billingInterval,
                $reference,
                $reference,
                $transactionId,
                $subscriptionCode,
                $customerCode,
                $channel,
                $paidAt,
                $rawPayload,
                $paymentId,
                $reference
            );
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO subscription_payments
                 (subscription_id, user_id, amount, payment_method, billing_interval, paystack_transaction_id,
                  paystack_customer_code, paystack_subscription_code, channel, status,
                  transaction_reference, paystack_reference, paid_at, callback_received_at, raw_payload)
                 VALUES (?, ?, ?, \'paystack\', ?, NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'),
                         ?, \'paid\', ?, ?, ?, NOW(), ?)'
            );
            $stmt->bind_param(
                'iidsssssssss',
                $membership['subscription_id'],
                $membership['user_id'],
                $amount,
                $billingInterval,
                $transactionId,
                $customerCode,
                $subscriptionCode,
                $channel,
                $reference,
                $reference,
                $paidAt,
                $rawPayload
            );
            $stmt->execute();
            $paymentId = (int)$stmt->insert_id;
            $stmt->close();
        }

        if ($alreadyPaid) {
            $conn->commit();
            return $paymentId;
        }

        $provider = (string)($membership['payment_provider'] ?? '');
        $membershipStatus = (string)$membership['subscription_status'];
        $periodEnded = !empty($membership['expires_at']) && strtotime($membership['expires_at']) < time();
        $providerMayActivate = $provider === '' || $provider === 'paystack'
            || $membershipStatus === 'expired' || $periodEnded;
        if ($providerMayActivate) {
            $baseDate = !empty($membership['expires_at']) && strtotime($membership['expires_at']) > strtotime($paidAt)
                ? $membership['expires_at']
                : $paidAt;
            $expiresAt = paystack_next_billing_date($billingInterval, $baseDate);
            $nextBillingDate = $expiresAt;

            $stmt = $conn->prepare(
                'UPDATE user_subscriptions
                 SET status=\'active\', payment_provider=\'paystack\', starts_at=COALESCE(starts_at, ?),
                     expires_at=?, next_billing_date=?, grace_period_ends_at=NULL,
                     auto_renew=1, billing_interval=?, paystack_subscription_code=COALESCE(NULLIF(?, \'\'), paystack_subscription_code),
                     paystack_customer_code=COALESCE(NULLIF(?, \'\'), paystack_customer_code), updated_at=NOW()
                 WHERE id=?'
            );
            $stmt->bind_param('ssssssi', $paidAt, $expiresAt, $nextBillingDate, $billingInterval, $subscriptionCode, $customerCode, $membership['subscription_id']);
            $stmt->execute();
            $stmt->close();

            if ($membershipStatus !== 'active') {
                $stmt = $conn->prepare(
                    'INSERT INTO subscription_status_history
                     (user_membership_id, old_status, new_status, reason, source)
                     VALUES (?, ?, \'active\', ?, ?)'
                );
                $reason = 'Paystack payment verified; access active until ' . $expiresAt;
                $stmt->bind_param('isss', $membership['subscription_id'], $membershipStatus, $reason, $source);
                $stmt->execute();
                $stmt->close();
            }
            $stmt = $conn->prepare('UPDATE subscription_payments SET subscription_expires_at=? WHERE id=?');
            $stmt->bind_param('si', $expiresAt, $paymentId);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        return $paymentId;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}
