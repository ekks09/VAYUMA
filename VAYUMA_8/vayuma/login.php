<?php

session_start();

require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/includes/role_routes.php';
$platformHomeUrl = vayuma_platform_home_route();

/*
|--------------------------------------------------------------------------
| If already logged in, don't show login again
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: ' . $platformHomeUrl);
    exit;
}

?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_language, ENT_QUOTES, 'UTF-8') ?>">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Login | VAYUMA
    </title>

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
                <a class="auth-brand-link" href="<?= htmlspecialchars($platformHomeUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="VAYUMA home">
                    <img
                        src="assets/images/vayuma-logo.jpg"
                        alt="VAYUMA"
                        class="login-brand-logo"
                    >
                </a>
                <p class="login-brand-kicker">VAYUMA SMART ECOSYSTEM</p>
                <h2>Growing opportunity, together.</h2>
                <p class="login-brand-copy">
                    A connected place for farmers, buyers and agricultural partners to build a stronger food system.
                </p>
                <div class="login-brand-footer">
                    <span>Market access</span>
                    <span>Farm insights</span>
                    <span>Trusted connections</span>
                </div>
            </section>

            <section class="login-content">
                <div class="login-header">
                    <div class="login-badge">MEMBER SIGN IN</div>
                    <h1><?= htmlspecialchars(t('welcome_back'), ENT_QUOTES, 'UTF-8') ?></h1>
                    <p><?= htmlspecialchars(t('login_intro'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <?php if (isset($_GET["error"])): ?>
                    <div class="login-error" role="alert">
                        <?php
                        $error = $_GET["error"];
                        if ($error === "invalid") {
                            echo htmlspecialchars(t("invalid_login"), ENT_QUOTES, "UTF-8");
                        } elseif ($error === "empty") {
                            echo htmlspecialchars(t("fill_required"), ENT_QUOTES, "UTF-8");
                        } elseif ($error === "profile") {
                            echo htmlspecialchars(t("complete_profile"), ENT_QUOTES, "UTF-8");
                        } else {
                            echo htmlspecialchars(t("login_failed"), ENT_QUOTES, "UTF-8");
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <form action="auth/login.php" method="POST" class="login-form">
                    <div class="login-field">
                        <label for="loginIdentifier">
                            <?= htmlspecialchars(t('email_phone'), ENT_QUOTES, 'UTF-8') ?>
                        </label>
                        <input
                            type="text"
                            id="loginIdentifier"
                            name="identifier"
                            placeholder="<?= htmlspecialchars(t('enter_email_phone'), ENT_QUOTES, 'UTF-8') ?>"
                            autocomplete="username"
                            required
                        >
                    </div>

                    <div class="login-field">
                        <div class="password-label-row">
                            <label for="loginPassword">
                                <?= htmlspecialchars(t('password'), ENT_QUOTES, 'UTF-8') ?>
                            </label>
                            <span class="password-help">Password recovery is not available yet.</span>
                        </div>
                        <div class="password-wrapper">
                            <input
                                type="password"
                                name="password"
                                id="loginPassword"
                                placeholder="<?= htmlspecialchars(t('enter_password'), ENT_QUOTES, 'UTF-8') ?>"
                                autocomplete="current-password"
                                required
                            >
                            <button
                                type="button"
                                class="password-toggle"
                                aria-controls="loginPassword"
                                aria-label="Show password"
                                aria-pressed="false"
                                onclick="togglePassword()"
                            >
                                <svg class="password-eye-show" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="password-eye-hide" viewBox="0 0 24 24" aria-hidden="true" hidden><path d="m3 3 18 18"/><path d="M10.6 5.2A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7a15.7 15.7 0 0 1-3.1 3.9M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1.4 0 2.7-.4 3.8-.9"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="login-button">
                        <?= htmlspecialchars(t('sign_in'), ENT_QUOTES, 'UTF-8') ?>
                        <span aria-hidden="true">→</span>
                    </button>
                </form>

                <div class="login-divider"><span>OR</span></div>
                <p class="register-prompt">
                    <?= htmlspecialchars(t('no_account'), ENT_QUOTES, 'UTF-8') ?>
                    <a href="register.php">
                        <?= htmlspecialchars(t('create_account'), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </p>
                <div class="login-trust">
                    🔒 <?= htmlspecialchars(t('secure_account'), ENT_QUOTES, 'UTF-8') ?>
                </div>
            </section>
        </div>

    </main>


<script>

function togglePassword() {

    const password =
        document.getElementById("loginPassword");

    const button =
        document.querySelector(".password-toggle");

    const isVisible = password.type === "password";
    password.type = isVisible ? "text" : "password";
    button.setAttribute("aria-label", isVisible ? "Hide password" : "Show password");
    button.setAttribute("aria-pressed", isVisible ? "true" : "false");
    button.querySelector(".password-eye-show").hidden = isVisible;
    button.querySelector(".password-eye-hide").hidden = !isVisible;

}

</script>

</body>

</html>