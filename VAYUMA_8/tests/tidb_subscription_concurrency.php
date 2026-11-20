<?php
/** DESTRUCTIVE TEST, NEVER PRODUCTION. Requires a dedicated EMPTY schema whose
 * name begins vayuma_test_, TLS environment variables and explicit opt-in.
 * This creates/drops tables. It is intentionally NOT run against vayuma_db.
 * Uses two connections and the application's real expiry/reconciliation code.
 */
if (PHP_SAPI !== 'cli' || getenv('VAYUMA_RUN_DESTRUCTIVE_TIDB_TESTS') !== 'YES' || !preg_match('/^vayuma_test_[a-z0-9_]+$/', (string)getenv('DB_NAME'))) {
    fwrite(STDERR, "Refusing: dedicated empty vayuma_test_* schema and explicit opt-in are required.\n"); exit(2);
}
if (!getenv('DB_HOST') || !getenv('DB_USER') || !getenv('DB_PASSWORD') || !getenv('DB_SSL_CA')) {
    fwrite(STDERR, "Required TiDB environment variables are missing.\n"); exit(2);
}
putenv('APP_ENV=production');
require __DIR__ . '/../vayuma/lib/subscription_lib.php';
require __DIR__ . '/../vayuma/lib/plan_reconciliation.php';
require __DIR__ . '/../vayuma/config/db.php';
function check_test(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
if (($argv[1] ?? '') === 'renew-worker') {
    try {
        $conn->begin_transaction();
        $conn->query("UPDATE user_subscriptions SET expires_at='2999-01-01 00:00:00' WHERE id=1");
        echo "LOCKED\n"; flush();
        sleep(5);
        $conn->commit();
        exit(0);
    } catch (Throwable $e) { try {$conn->rollback();} catch (Throwable $ignored) {} fwrite(STDERR, "Renewal worker failed.\n"); exit(1); }
}
$owned = false; $process = null;
try {
    $result = $conn->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()");
    check_test((int)$result->fetch_row()[0] === 0, 'Test schema must be completely empty');
    $owned = true;
    $conn->query("CREATE TABLE subscription_plans (id INT PRIMARY KEY, role_key VARCHAR(60), stakeholder_name VARCHAR(120), monthly_price DECIMAL(12,2), plan_type VARCHAR(20), paystack_plan_code VARCHAR(100), billing_interval VARCHAR(20), currency CHAR(3))");
    $conn->query("CREATE TABLE user_subscriptions (id INT PRIMARY KEY, user_id INT UNIQUE, plan_id INT, status VARCHAR(30), starts_at DATETIME NULL, expires_at DATETIME NULL, next_billing_date DATE NULL, grace_period_ends_at DATETIME NULL, negotiated_approved INT NOT NULL DEFAULT 0, negotiated_approved_by INT NULL, negotiated_approved_at DATETIME NULL, billing_interval VARCHAR(20), updated_at DATETIME NULL)");
    $conn->query("CREATE TABLE subscription_status_history (id INT AUTO_INCREMENT PRIMARY KEY, user_membership_id INT, old_status VARCHAR(30), new_status VARCHAR(30), reason VARCHAR(255), source VARCHAR(30))");
    $conn->query("INSERT INTO subscription_plans VALUES (1,'farmer','Farmer',299,'paid',NULL,'monthly','KES')");
    $conn->query("INSERT INTO user_subscriptions (id,user_id,plan_id,status,expires_at,billing_interval) VALUES (1,1,1,'active','2000-01-01 00:00:00','monthly')");
    $process = proc_open([PHP_BINARY, __FILE__, 'renew-worker'], [['pipe','r'],['pipe','w'],['pipe','w']], $pipes);
    check_test(is_resource($process), 'Unable to start renewal worker');
    fclose($pipes[0]);
    check_test(trim((string)fgets($pipes[1])) === 'LOCKED', 'Renewal worker did not acquire lock');
    $observed = $conn->query('SELECT expires_at FROM user_subscriptions WHERE id=1')->fetch_row()[0];
    check_test(str_starts_with($observed, '2000-01-01'), 'Renewal committed before the stale-read check');
    $subscription = subscription_for_user($conn, 1);
    check_test($subscription['status'] === 'active' && str_starts_with($subscription['expires_at'], '2999-01-01'), 'Renewal was incorrectly expired');
    fclose($pipes[1]); fclose($pipes[2]); check_test(proc_close($process) === 0, 'Renewal worker failed'); $process=null;
    check_test((int)$conn->query('SELECT COUNT(*) FROM subscription_status_history')->fetch_row()[0] === 0, 'Unexpected expiry history');
    $conn->query("UPDATE user_subscriptions SET expires_at='2000-01-01 00:00:00' WHERE id=1");
    check_test(subscription_for_user($conn,1)['status'] === 'expired', 'Expired subscription not transitioned');
    check_test((int)$conn->query('SELECT COUNT(*) FROM subscription_status_history')->fetch_row()[0] === 1, 'Expiry history missing');
    $conn->begin_transaction();
    $conn->query('SELECT id FROM subscription_plans WHERE id=1 FOR UPDATE');
    vayuma_reconcile_plan_memberships($conn,1,'paid','free','monthly');
    $conn->query("UPDATE subscription_plans SET plan_type='free' WHERE id=1");
    $conn->commit();
    check_test($conn->query('SELECT status FROM user_subscriptions WHERE id=1')->fetch_row()[0] === 'free_account', 'Free transition failed');
    $conn->begin_transaction();
    $conn->query('SELECT id FROM subscription_plans WHERE id=1 FOR UPDATE');
    vayuma_reconcile_plan_memberships($conn,1,'free','paid','monthly');
    $conn->query("UPDATE subscription_plans SET plan_type='paid' WHERE id=1");
    $conn->commit();
    check_test($conn->query('SELECT status FROM user_subscriptions WHERE id=1')->fetch_row()[0] === 'payment_due', 'Paid transition failed');
    check_test((int)$conn->query('SELECT COUNT(*) FROM subscription_status_history')->fetch_row()[0] === 3, 'Transition history missing');
    echo "PASS: TiDB renewal race, expiry and plan type transitions\n";
} catch (Throwable $e) {
    try {$conn->rollback();} catch (Throwable $ignored) {}
    fwrite(STDERR, "TiDB transaction test failed; inspect schema and server-side logs.\n"); $failed = true;
} finally {
    if (is_resource($process)) { proc_terminate($process); proc_close($process); }
    if ($owned) {
        foreach (['subscription_status_history','user_subscriptions','subscription_plans'] as $table) {
            try {$conn->query('DROP TABLE IF EXISTS ' . $table);} catch (Throwable $ignored) {fwrite(STDERR, "Test cleanup failed; inspect the dedicated schema.\n"); $failed=true;}
        }
    }
}
exit(!empty($failed) ? 1 : 0);
