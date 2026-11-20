<?php
session_start();
require_once __DIR__ . '/includes/language.php';
header('Cache-Control: no-store');
$sw = $current_language === 'sw';
$h = static fn($text) => htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
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
                <p><?= $h($sw ? 'Urejeshaji kwa barua pepe unaandaliwa kwa usalama.' : 'Email password recovery is being prepared securely.') ?></p>
            </div>
            <div class="login-error" role="status"><?= $h($sw ? 'Bado hatuwezi kutuma viungo vya kurejesha nenosiri. Tafadhali subiri huduma hii ianzishwe.' : 'Password reset emails cannot be sent yet. Please wait until this service is enabled.') ?></div>
            <div class="login-field">
                <label for="recoveryEmail"><?= $h($sw ? 'Barua pepe ya akaunti' : 'Account email address') ?></label>
                <input id="recoveryEmail" type="email" autocomplete="email" disabled placeholder="you@example.com">
            </div>
            <button type="button" class="login-button" disabled><?= $h($sw ? 'Tuma kiungo cha kurejesha' : 'Send reset link') ?></button>
            <p class="register-prompt"><?= $h($sw ? 'Akaunti zinazotumia simu pekee haziwezi kutumia urejeshaji wa barua pepe.' : 'Phone-only accounts cannot use email recovery.') ?></p>
            <p class="register-prompt"><a href="login.php"><?= $h($sw ? 'Rudi kwenye kuingia' : 'Back to sign in') ?></a></p>
        </section>
    </div>
</main>
</body>
</html>
