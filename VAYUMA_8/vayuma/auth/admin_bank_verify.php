<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/admin.php';
require_once __DIR__ . '/../includes/csrf.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true || !is_vayuma_admin((int)$_SESSION['user_id'])) {
    http_response_code(403);
    exit('Administrator access is not enabled for this account.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !vayuma_csrf_valid()) {
    $_SESSION['bank_admin_notice'] = 'The payment review request expired. Refresh and try again.';
    header('Location: ../admin.php');
    exit;
}

$paymentId = filter_var($_POST['payment_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$status = $_POST['status'] ?? '';
if ($paymentId === false || $paymentId === null || !is_string($status) || !in_array($status, ['paid', 'failed'], true)) {
    $_SESSION['bank_admin_notice'] = 'Choose a valid bank payment action.';
    header('Location: ../admin.php');
    exit;
}

$transactionStarted = false;
try {
    $conn->begin_transaction();
    $transactionStarted = true;
    $stmt = $conn->prepare("SELECT id,order_id,status FROM payments WHERE id=? AND payment_method='bank' LIMIT 1 FOR UPDATE");
    $stmt->bind_param('i', $paymentId);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$payment || $payment['status'] !== 'pending') {
        throw new RuntimeException('This bank payment is no longer pending review.');
    }

    $adminId = (int)$_SESSION['user_id'];
    if ($status === 'paid') {
        $stmt = $conn->prepare("UPDATE orders SET status='paid' WHERE id=? AND status='accepted'");
        $stmt->bind_param('i', $payment['order_id']);
        $stmt->execute();
        $orderUpdated = $stmt->affected_rows === 1;
        $stmt->close();
        if (!$orderUpdated) {
            throw new RuntimeException('The order is no longer awaiting payment.');
        }

        $stmt = $conn->prepare("UPDATE payments SET status='paid',verified_by=?,verified_at=NOW(),paid_at=NOW() WHERE id=? AND status='pending'");
        $stmt->bind_param('ii', $adminId, $paymentId);
    } else {
        $stmt = $conn->prepare("UPDATE payments SET status='failed',verified_by=?,verified_at=NOW() WHERE id=? AND status='pending'");
        $stmt->bind_param('ii', $adminId, $paymentId);
    }

    $stmt->execute();
    $paymentUpdated = $stmt->affected_rows === 1;
    $stmt->close();
    if (!$paymentUpdated) {
        throw new RuntimeException('The bank payment could not be updated.');
    }
    $conn->commit();
    $transactionStarted = false;
    $_SESSION['bank_admin_notice'] = $status === 'paid'
        ? 'The transfer was verified and the order is marked paid.'
        : 'The transfer was marked as failed.';
} catch (Throwable $exception) {
    error_log('Bank payment review failed: ' . $exception->getMessage());
    if ($transactionStarted) {
        try {
            $conn->rollback();
        } catch (Throwable $rollbackException) {
            error_log('Bank payment review rollback failed: ' . $rollbackException->getMessage());
        }
    }
    $_SESSION['bank_admin_notice'] = $exception instanceof RuntimeException
        ? $exception->getMessage()
        : 'The bank payment could not be updated right now. Please try again.';
}

header('Location: ../admin.php');
exit;
