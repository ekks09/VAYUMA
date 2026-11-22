<?php
/* Render's PHP auto-prepend guard. A changed password invalidates every older
 * authenticated session on its next request. No database schema change is needed.
 * Existing sessions without a fingerprint must sign in again after deployment. */
define('VAYUMA_SESSION_GUARD_ACTIVE', true);
function vayuma_session_fingerprint(string $passwordHash): string {
    return hash('sha256', 'vayuma-session-password-v1:' . $passwordHash);
}
function vayuma_session_matches($conn, int $userId, $fingerprint): bool {
    if ($userId <= 0 || !is_string($fingerprint) || !preg_match('/^[a-f0-9]{64}$/D', $fingerprint)) return false;
    $stmt = $conn->prepare('SELECT password FROM users WHERE id=? LIMIT 1');
    if (!$stmt) throw new RuntimeException('Session verification unavailable');
    $stmt->bind_param('i', $userId);
    if (!$stmt->execute()) throw new RuntimeException('Session verification unavailable');
    $result = $stmt->get_result();
    if (!$result) throw new RuntimeException('Session verification unavailable');
    $row = $result->fetch_assoc(); $stmt->close();
    return is_string($row['password'] ?? null) && hash_equals(vayuma_session_fingerprint($row['password']), $fingerprint);
}
function vayuma_session_guard(): void {
    if (empty($_COOKIE[session_name()])) return;
    if (!session_start()) { http_response_code(503); exit('Session service is temporarily unavailable.'); }
    if (($_SESSION['logged_in'] ?? false) !== true) { session_write_close(); return; }
    try {
        // Includes execute in this function's scope; expose only the connection
        // so later require_once calls can reuse it without leaking DB variables.
        $vayumaDbReadinessProbe = true;
        require __DIR__ . '/../config/db.php';
        if (!isset($conn) || !$conn) throw new RuntimeException('Database unavailable');
        $GLOBALS['conn'] = $conn;
        $valid = vayuma_session_matches($conn, (int)($_SESSION['user_id'] ?? 0), $_SESSION['vayuma_auth_fingerprint'] ?? null);
    } catch (Throwable $ignored) {
        session_write_close(); error_log('VAYUMA session verification unavailable.');
        http_response_code(503); exit('Session verification is temporarily unavailable. Please try again.');
    }
    if (!$valid) {
        $_SESSION = []; session_destroy();
        header('Cache-Control: no-store');
        header('Location: /login.php?error=session'); exit;
    }
    session_write_close();
}
vayuma_session_guard();
