<?php
require_once __DIR__ . '/includes/payment_gate.php';
vayuma_require_payment_processing();

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/subscription_lib.php';

header('Content-Type: application/json');
http_response_code(200);

/*
|--------------------------------------------------------------------------
| READ SAFARICOM CALLBACK
|--------------------------------------------------------------------------
*/

$raw = file_get_contents('php://input');

$data = json_decode($raw, true);

if (!is_array($data)) {
    echo json_encode([
        'ResultCode' => 1,
        'ResultDesc' => 'Invalid JSON'
    ]);
    exit;
}

$stk = $data['Body']['stkCallback'] ?? null;

if (!is_array($stk)) {
    echo json_encode([
        'ResultCode' => 1,
        'ResultDesc' => 'Invalid STK callback'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| CALLBACK INFORMATION
|--------------------------------------------------------------------------
*/

$checkoutRequestId = trim(
    (string)($stk['CheckoutRequestID'] ?? '')
);

$resultCode = (int)(
    $stk['ResultCode'] ?? 1
);

$resultDescription = trim(
    (string)($stk['ResultDesc'] ?? '')
);

if ($checkoutRequestId === '') {
    echo json_encode([
        'ResultCode' => 1,
        'ResultDesc' => 'Missing CheckoutRequestID'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| FIND PAYMENT
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id,
        order_id,
        amount,
        status
    FROM payments
    WHERE checkout_request_id = ?
    LIMIT 1
");

if (!$stmt) {
    echo json_encode([
        'ResultCode' => 1,
        'ResultDesc' => 'Database error'
    ]);
    exit;
}

$stmt->bind_param(
    's',
    $checkoutRequestId
);

$stmt->execute();

$payment = $stmt->get_result()->fetch_assoc();

$stmt->close();

/*
|--------------------------------------------------------------------------
| PAYMENT NOT FOUND
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| ALREADY PAID ORDER
| A missing order row is not fatal: subscription STK callbacks share this URL.
|--------------------------------------------------------------------------
*/

if ($payment && $payment['status'] === 'paid') {

    echo json_encode([
        'ResultCode' => 0,
        'ResultDesc' => 'Payment already processed'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| EXTRACT CALLBACK METADATA
|--------------------------------------------------------------------------
*/

$receiptNumber = '';
$phoneNumber = '';
$amount = null;

$metadata =
    $stk['CallbackMetadata']['Item'] ?? [];

foreach ($metadata as $item) {

    $name = $item['Name'] ?? '';

    $value = $item['Value'] ?? null;

    switch ($name) {

        case 'MpesaReceiptNumber':

            $receiptNumber =
                (string)$value;

            break;

        case 'PhoneNumber':

            $phoneNumber =
                (string)$value;

            break;

        case 'Amount':

            $amount =
                (float)$value;

            break;
    }
}

/* Subscription callbacks share this Daraja URL with marketplace orders. */
$stmt = $conn->prepare('SELECT id, subscription_id, amount, billing_interval, status FROM subscription_payments WHERE checkout_request_id=? LIMIT 1');
$stmt->bind_param('s', $checkoutRequestId); $stmt->execute(); $subscriptionPayment=$stmt->get_result()->fetch_assoc(); $stmt->close();
if ($subscriptionPayment) {
    $conn->begin_transaction();
    try {
        $stmt=$conn->prepare('SELECT id, subscription_id, amount, billing_interval, status FROM subscription_payments WHERE id=? LIMIT 1 FOR UPDATE');
        $stmt->bind_param('i',$subscriptionPayment['id']); $stmt->execute(); $subscriptionPayment=$stmt->get_result()->fetch_assoc(); $stmt->close();
        if (!$subscriptionPayment) {
            $conn->commit(); echo json_encode(['ResultCode'=>0,'ResultDesc'=>'Subscription payment already handled']); exit;
        }
        // Provider separation: fetch membership payment_provider
        $stmt=$conn->prepare('SELECT payment_provider, status, expires_at, billing_interval FROM user_subscriptions WHERE id=? LIMIT 1 FOR UPDATE');
        $stmt->bind_param('i',$subscriptionPayment['subscription_id']); $stmt->execute(); $membership=$stmt->get_result()->fetch_assoc(); $stmt->close();
        $currentProvider = $membership['payment_provider'] ?? null;
        $currentStatus = $membership['status'] ?? '';
        $periodEnded = !empty($membership['expires_at']) && strtotime($membership['expires_at']) < time();
        $canUpdate = ($currentProvider === null || $currentProvider === 'mpesa' || $currentStatus === 'expired' || $periodEnded);
        if (!$canUpdate) {
            $conn->commit(); echo json_encode(['ResultCode'=>0,'ResultDesc'=>'Membership owned by another provider']); exit;
        }
        if ($subscriptionPayment['status'] !== 'paid' && $resultCode === 0) {
            if ($amount === null || abs($amount-(float)$subscriptionPayment['amount'])>0.01) throw new RuntimeException('Subscription M-Pesa amount does not match.');
            $stmt=$conn->prepare("UPDATE subscription_payments SET status='paid',transaction_reference=?,mpesa_receipt_number=?,phone_number=COALESCE(NULLIF(?,''),phone_number),result_code=?,result_description=?,callback_received_at=NOW(),paid_at=NOW() WHERE id=?");
            $stmt->bind_param('sssisi',$receiptNumber,$receiptNumber,$phoneNumber,$resultCode,$resultDescription,$subscriptionPayment['id']); $stmt->execute(); $stmt->close();
            $baseDate = !empty($membership['expires_at']) && strtotime($membership['expires_at']) > time()
                ? $membership['expires_at']
                : date('Y-m-d H:i:s');
            $expiresAt = paystack_next_billing_date((string)($subscriptionPayment['billing_interval'] ?: $membership['billing_interval'] ?: 'monthly'), $baseDate);
            $stmt=$conn->prepare("UPDATE user_subscriptions SET status='active', payment_provider='mpesa', starts_at=COALESCE(starts_at,NOW()), expires_at=?, next_billing_date=?, grace_period_ends_at=NULL, updated_at=NOW() WHERE id=?");
            $stmt->bind_param('ssi',$expiresAt,$expiresAt,$subscriptionPayment['subscription_id']); $stmt->execute(); $stmt->close();
            $stmt=$conn->prepare('UPDATE subscription_payments SET subscription_expires_at=? WHERE id=?');
            $stmt->bind_param('si',$expiresAt,$subscriptionPayment['id']); $stmt->execute(); $stmt->close();
            if ($currentStatus !== 'active') {
                $stmt=$conn->prepare("INSERT INTO subscription_status_history (user_membership_id,old_status,new_status,reason,source) VALUES (?,?,'active',?,'mpesa_callback')");
                $reason='M-Pesa payment confirmed; access active until '.$expiresAt;
                $stmt->bind_param('iss',$subscriptionPayment['subscription_id'],$currentStatus,$reason); $stmt->execute(); $stmt->close();
            }
        } elseif ($subscriptionPayment['status'] !== 'paid') {
            $status='failed'; $stmt=$conn->prepare('UPDATE subscription_payments SET status=?,result_code=?,result_description=?,callback_received_at=NOW() WHERE id=?');
            $stmt->bind_param('sisi',$status,$resultCode,$resultDescription,$subscriptionPayment['id']); $stmt->execute(); $stmt->close();
        }
        $conn->commit(); echo json_encode(['ResultCode'=>0,'ResultDesc'=>'Subscription callback processed']); exit;
    } catch(Throwable $e) { $conn->rollback(); error_log('VAYUMA subscription callback error: '.$e->getMessage()); echo json_encode(['ResultCode'=>1,'ResultDesc'=>'Subscription callback failed']); exit; }
}

if (!$payment) {
    echo json_encode([
        'ResultCode' => 0,
        'ResultDesc' => 'Callback received'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| PROCESS PAYMENT SAFELY
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();

try {

    /*
     * Lock payment row so the same callback
     * cannot be processed twice simultaneously.
     */

    $stmt = $conn->prepare("
        SELECT
            id,
            order_id,
            amount,
            status
        FROM payments
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->bind_param(
        'i',
        $payment['id']
    );

    $stmt->execute();

    $lockedPayment =
        $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$lockedPayment) {

        throw new RuntimeException(
            'Payment record not found.'
        );
    }

    /*
     * Check again after locking.
     */

    if ($lockedPayment['status'] === 'paid') {

        $conn->commit();

        echo json_encode([
            'ResultCode' => 0,
            'ResultDesc' => 'Payment already processed'
        ]);

        exit;
    }

    /*
     |--------------------------------------------------------------------------
     | SUCCESSFUL M-PESA PAYMENT
     |--------------------------------------------------------------------------
     */

    if ($resultCode === 0) {

        /*
         * Safaricom must return the amount.
         */

        if ($amount === null) {

            throw new RuntimeException(
                'M-Pesa callback did not contain an amount.'
            );
        }

        /*
         * Prevent somebody from paying
         * the wrong amount.
         */

        if (
            abs(
                $amount -
                (float)$lockedPayment['amount']
            ) > 0.01
        ) {

            throw new RuntimeException(
                'M-Pesa amount does not match order amount.'
            );
        }

        /*
         * Payment is genuinely successful.
         */

        $stmt = $conn->prepare("
            UPDATE payments
            SET
                status = 'paid',
                transaction_reference = ?,
                mpesa_receipt_number = ?,
                phone_number =
                    COALESCE(
                        NULLIF(?, ''),
                        phone_number
                    ),
                result_code = ?,
                result_description = ?,
                callback_received_at = NOW(),
                paid_at = NOW()
            WHERE id = ?
        ");

        $stmt->bind_param(
            'sssisi',
            $receiptNumber,
            $receiptNumber,
            $phoneNumber,
            $resultCode,
            $resultDescription,
            $lockedPayment['id']
        );

        $stmt->execute();

        $stmt->close();

        /*
         * Update the order only after
         * confirmed M-Pesa payment.
         */

        $stmt = $conn->prepare("
            UPDATE orders
            SET status = 'paid'
            WHERE id = ?
              AND status = 'accepted'
        ");

        $stmt->bind_param(
            'i',
            $lockedPayment['order_id']
        );

        $stmt->execute();

        $stmt->close();

    } else {

        /*--------------------------------------------------------------------------
         | FAILED / CANCELLED M-PESA PAYMENT
         |--------------------------------------------------------------------------
         */

        $status = 'failed';

        /* Map common Safaricom result codes to meaningful statuses.
         * 1032 = cancelled by user (entered wrong PIN or cancelled prompt)
         * 1019 = insufficient funds
         * 1    = general failure
         */
        if ($resultCode === 1032) {
            $status = 'cancelled';
        }

        /* Revert the order back to 'accepted' so the buyer can retry payment.
         * This keeps the order lifecycle consistent: accepted -> paid (or back to accepted on failure).
         */
        $stmt = $conn->prepare("
            UPDATE orders
            SET status = 'accepted'
            WHERE id = ?
              AND status IN ('accepted', 'pending')
        ");
        $stmt->bind_param('i', $lockedPayment['order_id']);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("
            UPDATE payments
            SET
                status = ?,
                result_code = ?,
                result_description = ?,
                callback_received_at = NOW()
            WHERE id = ?
        ");

        $stmt->bind_param(
            'sisi',
            $status,
            $resultCode,
            $resultDescription,
            $lockedPayment['id']
        );

        $stmt->execute();
        $stmt->close();
    }

    /*
     |--------------------------------------------------------------------------
     | COMMIT
     |--------------------------------------------------------------------------
     */

    $conn->commit();

    echo json_encode([
        'ResultCode' => 0,
        'ResultDesc' => 'Callback processed successfully'
    ]);

} catch (Throwable $e) {

    $conn->rollback();

    /*
     * Return HTTP 200 so Safaricom receives
     * a valid response while the error is logged.
     */

    error_log(
        'VAYUMA M-Pesa callback error: ' .
        $e->getMessage()
    );

    echo json_encode([
        'ResultCode' => 1,
        'ResultDesc' => 'Callback processing failed'
    ]);
}
