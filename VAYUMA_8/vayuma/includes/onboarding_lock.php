<?php
/** Serialize phone writes across registration, profile edits and staff intake.
 * Must be called inside a transaction. Never log phone numbers or lock keys.
 */
function vayuma_onboarding_lock_phone($conn, string $phone): void
{
    require_once __DIR__ . '/onboarding_policy.php';
    $normalized = vayuma_onboarding_phone($phone);
    if ($normalized === null) throw new RuntimeException('Invalid phone identity');
    $key = hash('sha256', $normalized);
    $stmt = $conn->prepare('INSERT INTO onboarding_phone_locks (phone_key) VALUES (?) ON DUPLICATE KEY UPDATE phone_key=VALUES(phone_key)');
    if (!$stmt) throw new RuntimeException('Phone lock unavailable');
    $stmt->bind_param('s', $key);
    if (!$stmt->execute()) { $stmt->close(); throw new RuntimeException('Phone lock unavailable'); }
    $stmt->close();
    $stmt = $conn->prepare('SELECT phone_key FROM onboarding_phone_locks WHERE phone_key=? FOR UPDATE');
    if (!$stmt) throw new RuntimeException('Phone lock unavailable');
    $stmt->bind_param('s', $key);
    if (!$stmt->execute() || !$stmt->get_result()->fetch_assoc()) { $stmt->close(); throw new RuntimeException('Phone lock unavailable'); }
    $stmt->close();
}

/** Account-scoped, shared 15-minute window. Fail closed if storage is unavailable. */
function vayuma_onboarding_unlock_attempt($conn, int $staffId, string $supplied): string
{
    $configured = getenv('VAYUMA_STAFF_ONBOARD_KEY');
    $expected = $configured === false ? '+254vayuma' : $configured;
    if ($expected === '') throw new RuntimeException('Staff unlock unavailable');
    if (!$conn->begin_transaction()) throw new RuntimeException('Staff unlock unavailable');
    try {
        $stmt = $conn->prepare('INSERT INTO onboarding_unlock_limits (user_id, failures, window_started_at) VALUES (?, 0, UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE user_id=VALUES(user_id)');
        if (!$stmt) throw new RuntimeException('Staff unlock unavailable');
        $stmt->bind_param('i', $staffId); if (!$stmt->execute()) throw new RuntimeException('Staff unlock unavailable'); $stmt->close();
        $stmt = $conn->prepare('SELECT failures, (window_started_at <= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 15 MINUTE)) AS expired FROM onboarding_unlock_limits WHERE user_id=? FOR UPDATE');
        if (!$stmt) throw new RuntimeException('Staff unlock unavailable');
        $stmt->bind_param('i', $staffId); if (!$stmt->execute()) throw new RuntimeException('Staff unlock unavailable'); $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if (!$row) throw new RuntimeException('Staff unlock unavailable');
        if ((int)$row['expired'] === 1) {
            $stmt = $conn->prepare('UPDATE onboarding_unlock_limits SET failures=0, window_started_at=UTC_TIMESTAMP() WHERE user_id=?');
            if (!$stmt) throw new RuntimeException('Staff unlock unavailable'); $stmt->bind_param('i', $staffId); if (!$stmt->execute()) throw new RuntimeException('Staff unlock unavailable'); $stmt->close();
            $row['failures'] = 0;
        }
        if ((int)$row['failures'] >= 5) $result = 'limited';
        elseif (hash_equals($expected, $supplied)) {
            $stmt = $conn->prepare('UPDATE onboarding_unlock_limits SET failures=0, window_started_at=UTC_TIMESTAMP() WHERE user_id=?');
            if (!$stmt) throw new RuntimeException('Staff unlock unavailable'); $stmt->bind_param('i', $staffId); if (!$stmt->execute()) throw new RuntimeException('Staff unlock unavailable'); $stmt->close();
            $result = 'ok';
        } else {
            $stmt = $conn->prepare('UPDATE onboarding_unlock_limits SET failures=failures+1 WHERE user_id=?');
            if (!$stmt) throw new RuntimeException('Staff unlock unavailable'); $stmt->bind_param('i', $staffId); if (!$stmt->execute()) throw new RuntimeException('Staff unlock unavailable'); $stmt->close();
            $result = 'invalid';
        }
        if (!$conn->commit()) throw new RuntimeException('Staff unlock unavailable');
        return $result;
    } catch (Throwable $e) { try { $conn->rollback(); } catch (Throwable $ignored) {} throw $e; }
}
