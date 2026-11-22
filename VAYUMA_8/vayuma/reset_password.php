<?php
session_start();
header('Cache-Control: no-store, max-age=0');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/password_recovery.php';
$config = vayuma_recovery_config();
$sw = $current_language === 'sw';
$h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$token = ''; $error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $config !== null) {
    $token = is_string($_POST['token'] ?? null) ? $_POST['token'] : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirm = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
    if (!vayuma_csrf_valid()) $error='Your session expired. Refresh the page and retry.';
    elseif (!preg_match('/^[a-f0-9]{64}$/D', $token)) $error='This reset code is invalid or has expired. Request another code.';
    elseif (strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0")) $error='Choose a password between 8 and 72 bytes.';
    elseif ($password !== $confirm) $error='Passwords do not match.';
    else {
        $inTransaction=false;
        try {
            $vayumaDbReadinessProbe=true; require __DIR__ . '/config/db.php';
            if (!isset($conn) || !$conn) throw new RuntimeException('Database unavailable');
            if (!vayuma_recovery_limit($conn,'reset_ip',(string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'),$config['key'],60)) throw new DomainException('Too many attempts. Please try again later.');
            $hash=hash('sha256',$token); $newHash=password_hash($password,PASSWORD_DEFAULT);
            if (!is_string($newHash) || !$conn->begin_transaction()) throw new RuntimeException('Reset unavailable');
            $inTransaction=true;
            $stmt=$conn->prepare('SELECT t.user_id,u.email FROM password_reset_tokens t JOIN users u ON u.id=t.user_id WHERE t.token_hash=? AND t.expires_at>UTC_TIMESTAMP() AND u.email_identity=t.email_identity LIMIT 1 FOR UPDATE');
            if (!$stmt) throw new RuntimeException('Reset lookup unavailable');
            $stmt->bind_param('s',$hash); if (!$stmt->execute()) throw new RuntimeException('Reset lookup unavailable');
            $result=$stmt->get_result(); if (!$result) throw new RuntimeException('Reset lookup unavailable');
            $row=$result->fetch_assoc(); $stmt->close();
            if (!$row) throw new DomainException('This reset code is invalid or has expired. Request another code.');
            $id=(int)$row['user_id'];
            $stmt=$conn->prepare('UPDATE users SET password=? WHERE id=?');
            if (!$stmt) throw new RuntimeException('Reset update unavailable');
            $stmt->bind_param('si',$newHash,$id); if (!$stmt->execute() || $stmt->affected_rows !== 1) throw new RuntimeException('Reset update unavailable'); $stmt->close();
            $stmt=$conn->prepare('SELECT password FROM users WHERE id=? LIMIT 1');
            if (!$stmt) throw new RuntimeException('Reset verification unavailable');
            $stmt->bind_param('i',$id); if (!$stmt->execute()) throw new RuntimeException('Reset verification unavailable');
            $result=$stmt->get_result(); if (!$result) throw new RuntimeException('Reset verification unavailable');
            $stored=$result->fetch_assoc(); $stmt->close();
            if (!is_string($stored['password'] ?? null) || !hash_equals($newHash,$stored['password']) || !password_verify($password,$stored['password'])) throw new RuntimeException('Reset verification unavailable');
            $stmt=$conn->prepare('DELETE FROM password_reset_tokens WHERE user_id=? AND token_hash=?');
            if (!$stmt) throw new RuntimeException('Reset consume unavailable');
            $stmt->bind_param('is',$id,$hash); if (!$stmt->execute() || $stmt->affected_rows !== 1) throw new RuntimeException('Reset consume unavailable'); $stmt->close();
            if (!$conn->commit()) throw new RuntimeException('Reset commit unavailable');
            $inTransaction=false;
            try { if (is_string($row['email'] ?? null) && !vayuma_recovery_notice($config,$row['email'])) error_log('VAYUMA password-change notification unavailable.'); }
            catch (Throwable $ignored) { error_log('VAYUMA password-change notification unavailable.'); }
            $_SESSION=[]; session_destroy();
            header('Location: login.php?reset=1'); exit;
        } catch (DomainException $e) {
            if ($inTransaction) { try { $conn->rollback(); } catch (Throwable $ignored) {} }
            $error=$e->getMessage();
        } catch (Throwable $ignored) {
            if ($inTransaction) { try { $conn->rollback(); } catch (Throwable $ignoredAgain) {} }
            error_log('VAYUMA password reset unavailable.'); http_response_code(503);
            $error='Password reset is temporarily unavailable. Please try again later.';
        }
    }
    unset($password, $confirm, $newHash, $hash);
}
?><!doctype html><html lang="<?= $h($current_language) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="referrer" content="no-referrer"><title>Reset password | VAYUMA TRIX</title><link rel="stylesheet" href="frontend/css/tokens.css"><link rel="stylesheet" href="assets/css/vayuma-ui.css?v=2"><link rel="stylesheet" href="assets/css/style.css?v=login-1"></head><body class="login-body"><main class="login-page"><div class="login-card"><section class="login-brand"><a href="welcome.php" class="auth-brand-link"><img src="assets/images/brand/shield-source.png" alt="VAYUMA TRIX" class="login-brand-logo"></a><p class="login-brand-kicker">VAYUMA TRIX AGRITECH INNOVATIONS KENYA</p><h2>Secure access to your account</h2></section><section class="login-content"><div class="login-header"><div class="login-badge">ACCOUNT HELP</div><h1>Choose a new password</h1><p>Use the secure link in your email. It expires after 30 minutes.</p></div>
<?php if ($config === null): ?><div class="login-error" role="status">Password recovery is not available yet.</div>
<?php else: ?>
<?php if ($error !== ''): ?><div class="login-error" role="alert"><?= $h($error) ?></div><?php endif; ?>
<form method="post" action="reset_password.php" id="resetForm"><?= vayuma_csrf_field() ?><div class="login-field"><label for="resetToken">One-time code from your reset link</label><input type="text" name="token" id="resetToken" value="<?= preg_match('/^[a-f0-9]{64}$/D',$token) ? $h($token) : '' ?>" maxlength="64" minlength="64" autocomplete="off" required></div><div class="login-field"><label for="newPassword">New password</label><input id="newPassword" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required></div><div class="login-field"><label for="confirmPassword">Confirm password</label><input id="confirmPassword" name="confirm_password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required></div><button type="submit" class="login-button">Reset password</button></form>
<?php endif; ?><p class="register-prompt"><a href="forgot_password.php">Request another link</a> · <a href="login.php">Back to sign in</a></p></section></div></main><script src="frontend/js/password-reset.js" defer></script></body></html>
