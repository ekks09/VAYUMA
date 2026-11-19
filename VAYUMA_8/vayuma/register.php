<?php
session_start();

require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/role_routes.php';
$platformHomeUrl = vayuma_platform_home_route();

$error = $_SESSION['register_error'] ?? '';
unset($_SESSION['register_error']);
if ($current_language === 'sw') {
    $registerErrorTranslations = [
        'Your session expired. Refresh the form and try again.' => 'Muda wa kikao chako umekwisha. Pakia upya fomu na ujaribu tena.',
        'Please fill in all required fields.' => 'Tafadhali jaza sehemu zote zinazohitajika.',
        'Please enter a valid full name.' => 'Tafadhali weka jina kamili sahihi.',
        'Please enter a valid phone number.' => 'Tafadhali weka nambari sahihi ya simu.',
        'Please enter a valid email address.' => 'Tafadhali weka anwani sahihi ya barua pepe.',
        'Password must contain at least 8 characters.' => 'Nenosiri lazima liwe na angalau herufi 8.',
        'Passwords do not match.' => 'Manenosiri hayalingani.',
        'You must accept the Terms of Service and Privacy Policy.' => 'Lazima ukubali Masharti ya Huduma na Sera ya Faragha.',
        'An account with this phone number already exists.' => 'Akaunti yenye nambari hii ya simu tayari ipo.',
        'An account with this email already exists.' => 'Akaunti yenye barua pepe hii tayari ipo.',
        'That phone number or email is already in use.' => 'Nambari hiyo ya simu au barua pepe tayari inatumika.',
        'Unable to create your account. Please try again.' => 'Imeshindikana kufungua akaunti yako. Tafadhali jaribu tena.',
    ];
    $error = $registerErrorTranslations[$error] ?? $error;
}

?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_language, ENT_QUOTES, 'UTF-8') ?>">

<head>
    <?php require __DIR__ . '/includes/pwa.php'; ?>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Create your VAYUMA Smart Ecosystem account."
    >

    <title>Create Account | VAYUMA</title>

    <link rel="stylesheet" href="frontend/css/tokens.css">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css?v=account-nav-1">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
<link rel="stylesheet" href="assets/css/style.css?v=register-2">

</head>

<body class="register-body">
<?php vayuma_language_selector(); ?>

    <main class="register-page">

        <div class="register-card">

            <!-- LEFT BRAND PANEL -->

            <section class="register-brand">

                <a class="auth-brand-link" href="<?= htmlspecialchars($platformHomeUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="VAYUMA home">
                    <img
                        src="assets/images/vayuma-logo.jpg"
                        alt="VAYUMA Smart Ecosystem"
                        class="register-logo"
                    >
                </a>

                <h1>VAYUMA</h1>

                <h2>SMART ECOSYSTEM</h2>

                <p>
                    <?= htmlspecialchars(t('auth_tagline'), ENT_QUOTES, 'UTF-8') ?>
                </p>

                <div class="brand-line"></div>

                <div class="brand-message">

                    <span><svg class="va-icon" width="24" height="24"><use href="frontend/images/icons.svg#va-icon-crops"/></svg></span>

                    <p>
                        <?= htmlspecialchars(t('register_brand_message'), ENT_QUOTES, 'UTF-8') ?>
                    </p>

                </div>

            </section>


            <!-- REGISTRATION FORM -->

            <section class="register-form-section">

                <div class="form-header">

                    <span class="form-badge">
                        <?= htmlspecialchars(t('register_step'), ENT_QUOTES, 'UTF-8') ?>
                    </span>

                    <h2>
                        <?= htmlspecialchars(t('register_title'), ENT_QUOTES, 'UTF-8') ?>
                    </h2>

                    <p>
                        <?= htmlspecialchars(t('register_intro'), ENT_QUOTES, 'UTF-8') ?>
                    </p>

                </div>


                <?php if (!empty($error)): ?>

                    <div class="form-alert">
                        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> <?php echo htmlspecialchars($error); ?>
                    </div>

                <?php endif; ?>


                <form
                    action="auth/register_process.php"
                    method="POST"
                    id="registrationForm"
                    novalidate
                >
                    <?= vayuma_csrf_field() ?>

                    <!-- FULL NAME -->

                    <div class="input-group">

                        <label for="full_name">
                            <?= htmlspecialchars(t('full_name'), ENT_QUOTES, 'UTF-8') ?>
                        </label>

                        <div class="input-wrapper">

                            <span class="input-icon">
                                <svg class="va-icon" aria-hidden="true" focusable="false"><use href="frontend/images/icons.svg?v=auth-icons-1#va-icon-profile"/></svg>
                            </span>

                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                placeholder="<?= htmlspecialchars(t('enter_full_name'), ENT_QUOTES, 'UTF-8') ?>"
                                autocomplete="name"
                                required
                            >

                        </div>

                    </div>


                    <!-- PHONE -->

                    <div class="input-group">

                        <label for="phone">
                            <?= htmlspecialchars(t('phone_number'), ENT_QUOTES, 'UTF-8') ?>
                        </label>

                        <div class="input-wrapper">

                            <span class="input-icon">
                                <svg class="va-icon" aria-hidden="true" focusable="false"><use href="frontend/images/icons.svg?v=auth-icons-1#va-icon-phone"/></svg>
                            </span>

                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                placeholder="<?= htmlspecialchars(t('phone_example'), ENT_QUOTES, 'UTF-8') ?>"
                                autocomplete="tel"
                                required
                            >

                        </div>

                        <small>
                            <?= htmlspecialchars(t('phone_hint'), ENT_QUOTES, 'UTF-8') ?>
                        </small>

                    </div>


                    <!-- EMAIL -->

                    <div class="input-group">

                        <label for="email">
                            <?= htmlspecialchars(t('email_address'), ENT_QUOTES, 'UTF-8') ?>
                            <span class="optional">
                                <?= htmlspecialchars(t('optional'), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </label>

                        <div class="input-wrapper">

                            <span class="input-icon">
                                <svg class="va-icon" aria-hidden="true" focusable="false"><use href="frontend/images/icons.svg?v=auth-icons-1#va-icon-email"/></svg>
                            </span>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="you@example.com"
                                autocomplete="email"
                            >

                        </div>

                    </div>


                    <!-- PASSWORD -->

                    <div class="input-group">

                        <label for="password">
                            <?= htmlspecialchars(t('password'), ENT_QUOTES, 'UTF-8') ?>
                        </label>

                        <div class="input-wrapper">

                            <span class="input-icon">
                                <svg class="va-icon" aria-hidden="true" focusable="false"><use href="frontend/images/icons.svg?v=auth-icons-1#va-icon-lock"/></svg>
                            </span>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="<?= htmlspecialchars(t('create_password'), ENT_QUOTES, 'UTF-8') ?>"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                onclick="togglePassword('password', this)"
                                aria-label="<?= htmlspecialchars(t('show_password'), ENT_QUOTES, 'UTF-8') ?>"
                                aria-pressed="false"
                            >
                                <svg class="password-eye-show" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="password-eye-hide" viewBox="0 0 24 24" aria-hidden="true" hidden><path d="m3 3 18 18"/><path d="M10.6 5.2A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7a15.7 15.7 0 0 1-3.1 3.9M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1.4 0 2.7-.4 3.8-.9"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                            </button>

                        </div>

                        <div class="password-strength">

                            <div
                                id="strengthBar"
                                class="strength-bar"
                            ></div>

                        </div>

                        <small id="passwordMessage">
                            <?= htmlspecialchars(t('password_hint'), ENT_QUOTES, 'UTF-8') ?>
                        </small>

                    </div>


                    <!-- CONFIRM PASSWORD -->

                    <div class="input-group">

                        <label for="confirm_password">
                            <?= htmlspecialchars(t('confirm_password'), ENT_QUOTES, 'UTF-8') ?>
                        </label>

                        <div class="input-wrapper">

                            <span class="input-icon">
                                <svg class="va-icon" aria-hidden="true" focusable="false"><use href="frontend/images/icons.svg?v=auth-icons-1#va-icon-lock"/></svg>
                            </span>

                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                placeholder="<?= htmlspecialchars(t('confirm_password_placeholder'), ENT_QUOTES, 'UTF-8') ?>"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                onclick="togglePassword('confirm_password', this)"
                                aria-label="<?= htmlspecialchars(t('show_password'), ENT_QUOTES, 'UTF-8') ?>"
                                aria-pressed="false"
                            >
                                <svg class="password-eye-show" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="password-eye-hide" viewBox="0 0 24 24" aria-hidden="true" hidden><path d="m3 3 18 18"/><path d="M10.6 5.2A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7a15.7 15.7 0 0 1-3.1 3.9M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1.4 0 2.7-.4 3.8-.9"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                            </button>

                        </div>

                        <small id="matchMessage"></small>

                    </div>


                    <!-- TERMS -->

                    <div class="terms-row">

                        <input
                            type="checkbox"
                            id="terms"
                            name="terms"
                            value="accepted"
                            required
                        >

                        <label for="terms">

                            <?= htmlspecialchars(t('agree_terms'), ENT_QUOTES, 'UTF-8') ?>
                            <a href="#" onclick="return false;">
                                <?= htmlspecialchars(t('terms_of_service'), ENT_QUOTES, 'UTF-8') ?>
                            </a>
                            <?= htmlspecialchars(t('and'), ENT_QUOTES, 'UTF-8') ?>
                            <a href="#" onclick="return false;">
                                <?= htmlspecialchars(t('privacy_policy'), ENT_QUOTES, 'UTF-8') ?>
                            </a>.

                        </label>

                    </div>


                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        class="register-button"
                    >

                        <?= htmlspecialchars(t('continue'), ENT_QUOTES, 'UTF-8') ?>

                        <span>
                            →
                        </span>

                    </button>

                </form>


                <div class="login-link">

                    <?= htmlspecialchars(t('already_account'), ENT_QUOTES, 'UTF-8') ?>

                    <a href="login.php">
                        <?= htmlspecialchars(t('login'), ENT_QUOTES, 'UTF-8') ?>
                    </a>

                </div>

            </section>

        </div>

    </main>


<script>
const authText = <?= json_encode(array_map('t', ['show_password','hide_password','password_hint','weak_password','moderate_password','strong_password','very_strong_password','passwords_match','passwords_differ','password_minimum']), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;

function togglePassword(fieldId, button) {

    const field = document.getElementById(fieldId);
    const isVisible = field.type === "password";

    field.type = isVisible ? "text" : "password";
    button.setAttribute("aria-label", isVisible ? authText[1] : authText[0]);
    button.setAttribute("aria-pressed", isVisible ? "true" : "false");
    button.querySelector(".password-eye-show").hidden = isVisible;
    button.querySelector(".password-eye-hide").hidden = !isVisible;

}


const password = document.getElementById("password");
const confirmPassword = document.getElementById("confirm_password");

const strengthBar = document.getElementById("strengthBar");
const passwordMessage = document.getElementById("passwordMessage");
const matchMessage = document.getElementById("matchMessage");


password.addEventListener("input", function () {

    const value = password.value;

    let strength = 0;

    if (value.length >= 8) {
        strength++;
    }

    if (/[A-Z]/.test(value)) {
        strength++;
    }

    if (/[0-9]/.test(value)) {
        strength++;
    }

    if (/[^A-Za-z0-9]/.test(value)) {
        strength++;
    }


    if (value.length === 0) {

        strengthBar.style.width = "0%";

        passwordMessage.textContent =
            authText[2];

    } else if (strength <= 1) {

        strengthBar.style.width = "25%";

        passwordMessage.textContent =
            authText[3];

    } else if (strength === 2) {

        strengthBar.style.width = "50%";

        passwordMessage.textContent =
            authText[4];

    } else if (strength === 3) {

        strengthBar.style.width = "75%";

        passwordMessage.textContent =
            authText[5];

    } else {

        strengthBar.style.width = "100%";

        passwordMessage.textContent =
            authText[6];

    }

});


confirmPassword.addEventListener("input", function () {

    if (confirmPassword.value === "") {

        matchMessage.textContent = "";

    } else if (password.value === confirmPassword.value) {

        matchMessage.textContent =
            authText[7];

        matchMessage.className =
            "success-message";

    } else {

        matchMessage.textContent =
            authText[8];

        matchMessage.className =
            "error-message";

    }

});


document.getElementById("registrationForm")
    .addEventListener("submit", function (event) {

        const passwordValue = password.value;
        const confirmValue = confirmPassword.value;

        if (passwordValue.length < 8) {

            event.preventDefault();

            alert(authText[9]);

            password.focus();

            return;
        }

        if (passwordValue !== confirmValue) {

            event.preventDefault();

            alert(authText[8]);

            confirmPassword.focus();

            return;
        }

    });

</script>

</body>
</html>