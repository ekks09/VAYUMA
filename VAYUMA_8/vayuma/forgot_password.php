<?php
session_start();
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/password_recovery.php';
header('Cache-Control: no-store');
$sw = $current_language === 'sw';
$h = static fn($text) => htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
$config = vayuma_recovery_config();
$sent = !empty($_SESSION['recovery_request_sent']); unset($_SESSION['recovery_request_sent']);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $config !== null) {
    $started = microtime(true);
    if (!vayuma_csrf_valid()) { http_response_code(403); $formError = $sw ? 'Muda wa kikao umekwisha. Pakia upya na ujaribu tena.' : 'Your session expired. Refresh and try again.'; }
    else {
        $email = is_string($_POST['email'] ?? null) ? strtolower(trim($_POST['email'])) : '';
        // Same public result for unknown, unavailable, throttled and known accounts.
        try {
            $vayumaDbReadinessProbe = true; require __DIR__ . '/config/db.php';
            if (!isset($conn) || !$conn) throw new RuntimeException('Database unavailable');
            $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
            if (vayuma_recovery_limit($conn, 'ip', $ip, $config['key'], 60) && strlen($email) <= 100 && filter_var($email, FILTER_VALIDATE_EMAIL) && vayuma_recovery_limit($conn, 'email', $email, $config['key'], 3)) {
                $stmt = $conn->prepare('SELECT id,email FROM users WHERE email_identity=? LIMIT 2');
                if (!$stmt) throw new RuntimeException('Lookup unavailable');
                $stmt->bind_param('s', $email); if (!$stmt->execute()) throw new RuntimeException('Lookup unavailable');
                $result = $stmt->get_result(); if (!$result) throw new RuntimeException('Lookup unavailable');
                $user=$result->fetch_assoc(); $duplicate=$result->fetch_assoc(); $stmt->close();
                if ($user && !$duplicate && (int)$user['id'] > 0 && is_string($user['email'] ?? null) && strtolower(trim($user['email'])) === $email) {
                    $token=bin2hex(random_bytes(32)); $hash=hash('sha256',$token); $id=(int)$user['id'];
                    $stmt=$conn->prepare('INSERT INTO password_reset_tokens (user_id,token_hash,email_identity,created_at,expires_at) VALUES (?,?,?,UTC_TIMESTAMP(),DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 MINUTE)) ON DUPLICATE KEY UPDATE token_hash=VALUES(token_hash),email_identity=VALUES(email_identity),created_at=VALUES(created_at),expires_at=VALUES(expires_at)');
                    if (!$stmt) throw new RuntimeException('Token storage unavailable');
                    $stmt->bind_param('iss',$id,$hash,$email); if (!$stmt->execute()) throw new RuntimeException('Token storage unavailable'); $stmt->close();
                    if (!vayuma_recovery_send($config,$email,$token)) {
                        error_log('VAYUMA recovery email unavailable.');
                        $stmt=$conn->prepare('DELETE FROM password_reset_tokens WHERE user_id=? AND token_hash=?');
                        if ($stmt) { $stmt->bind_param('is',$id,$hash); $stmt->execute(); $stmt->close(); }
                    }
                    unset($token, $hash);
                }
            }
            // Bound stale rate-limit storage without retaining plain addresses.
            if (random_int(1,100) === 1) $conn->query('DELETE FROM password_reset_limits WHERE window_started_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 DAY) LIMIT 200');
        } catch (Throwable $ignored) { error_log('VAYUMA password recovery request unavailable.'); }
        $_SESSION['recovery_request_sent'] = true;
        $remaining = 4.0 - (microtime(true) - $started); if ($remaining > 0) usleep((int)($remaining * 1000000));
        header('Location: forgot_password.php'); exit;
    }
}

?>
<!doctype html>
<html lang="<?= $h($current_language) ?>">
<head>
    <?php require __DIR__ . '/includes/pwa.php'; ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $h($sw ? 'Rejesha nenosiri | VAYUMA' : 'Recover password | VAYUMA') ?></title>
    <link rel="stylesheet" href="frontend/css/tokens.css">
    <link rel="stylesheet" href="frontend/css/components.css">
    <link rel="stylesheet" href="frontend/css/components_addition.css?v=account-nav-1">
    <link rel="stylesheet" href="frontend/css/layout.css">
    <link rel="stylesheet" href="frontend/css/pages.css">
    <link rel="stylesheet" href="assets/css/style.css?v=login-1">
</head>
<body class="login-body">
<?php vayuma_language_selector(); ?>
<main class="login-page">
    <div class="login-card">
        <section class="login-brand" aria-label="VAYUMA Smart Ecosystem">
            <a class="auth-brand-link" href="welcome.php" aria-label="VAYUMA home"><img src="assets/images/vayuma-logo.jpg" alt="VAYUMA" class="login-brand-logo"></a>
            <p class="login-brand-kicker">VAYUMA SMART ECOSYSTEM</p>
            <h2><?= $h($sw ? 'Ufikiaji salama wa akaunti yako' : 'Secure access to your account') ?></h2>
            <p class="login-brand-copy"><?= $h($sw ? 'Tunalinda akaunti yako wakati wa kurejesha ufikiaji.' : 'Protecting your account while restoring access.') ?></p>
        </section>
        <section class="login-content">
            <div class="login-header">
                <div class="login-badge"><?= $h($sw ? 'MSAADA WA AKAUNTI' : 'ACCOUNT HELP') ?></div>
                <h1><?= $h($sw ? 'Umesahau nenosiri?' : 'Forgot your password?') ?></h1>
                <p><?= $h($sw ? 'Tuma ombi la kiungo cha kurejesha nenosiri.' : 'Request a link to choose a new password.') ?></p>
            </div>
            <?php if ($config === null): ?>
                <div class="login-error" role="status"><?= $h($sw ? 'Urejeshaji wa nenosiri haupatikani kwa sasa.' : 'Password recovery is not available yet.') ?></div>
            <?php elseif ($sent): ?>
                <div class="login-success" role="status"><?= $h($sw ? 'Ikiwa akaunti inayostahili inapatikana, utapokea kiungo kwa barua pepe. Angalia pia spam.' : 'If an eligible account exists, a reset link may arrive by email. Check your spam folder too.') ?></div>
            <?php else: ?>
                <?php if (!empty($formError)): ?><div class="login-error" role="alert"><?= $h($formError) ?></div><?php endif; ?>
                <form method="post" action="forgot_password.php"><?= vayuma_csrf_field() ?>
                    <div class="login-field"><label for="recoveryEmail"><?= $h($sw ? 'Barua pepe ya akaunti' : 'Account email address') ?></label><input id="recoveryEmail" name="email" type="email" autocomplete="email" maxlength="100" required placeholder="you@example.com"></div>
                    <button type="submit" class="login-button"><?= $h($sw ? 'Tuma kiungo' : 'Send reset link') ?></button>
                </form>
            <?php endif; ?>
            <p class="register-prompt"><?= $h($sw ? 'Akaunti zinazotumia simu pekee haziwezi kutumia urejeshaji wa barua pepe.' : 'Phone-only accounts cannot use email recovery.') ?></p>
            <p class="register-prompt"><a href="login.php"><?= $h($sw ? 'Rudi kwenye kuingia' : 'Back to sign in') ?></a></p>
        </section>
    </div>
</main>
</body>
</html>
