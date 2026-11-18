<?php
session_start();

require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/role_routes.php';
$platformHomeUrl = vayuma_platform_home_route();

$error = $_SESSION['register_error'] ?? '';
unset($_SESSION['register_error']);
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_language, ENT_QUOTES, 'UTF-8') ?>">

<head>

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
<link rel="stylesheet" href="assets/css/style.css?v=register-1">

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
                    Connecting Agriculture.
                    Empowering Every Harvest.
                </p>

                <div class="brand-line"></div>

                <div class="brand-message">

                    <span><svg class="va-icon" width="24" height="24"><use href="frontend/images/icons.svg#va-icon-crops"/></svg></span>

                    <p>
                        One platform for smarter farming,
                        better markets and stronger
                        agricultural communities.
                    </p>

                </div>

            </section>


            <!-- REGISTRATION FORM -->

            <section class="register-form-section">

                <div class="form-header">

                    <span class="form-badge">
                        STEP 1 OF 3
                    </span>

                    <h2>
                        Create Your Account
                    </h2>

                    <p>
                        Join the VAYUMA Smart Ecosystem.
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
                            Full Name
                        </label>

                        <div class="input-wrapper">

                            <span class="input-icon">
                                <svg class="va-icon input-icon" width="16" height="16"><use href="frontend/images/icons.svg#va-icon-profile"/></svg>
                            </span>

                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                placeholder="Enter your full name"
                                autocomplete="name"
                                required
                            >

                        </div>

                    </div>


                    <!-- PHONE -->

                    <div class="input-group">

                        <label for="phone">
                            Phone Number
                        </label>

                        <div class="input-wrapper">

                            <span class="input-icon">
                                <svg class="va-icon input-icon" width="16" height="16"><use href="frontend/images/icons.svg#va-icon-money"/></svg>
                            </span>

                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                placeholder="e.g. 0712345678"
                                autocomplete="tel"
                                required
                            >

                        </div>

                        <small>
                            Use the phone number you will use for VAYUMA services.
                        </small>

                    </div>


                    <!-- EMAIL -->

                    <div class="input-group">

                        <label for="email">
                            Email Address
                            <span class="optional">
                                Optional
                            </span>
                        </label>

                        <div class="input-wrapper">

                            <span class="input-icon">
                                <svg class="va-icon input-icon" width="16" height="16"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg>
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
                            Password
                        </label>

                        <div class="input-wrapper">

                            <span class="input-icon">
                                <svg class="va-icon input-icon" width="16" height="16"><use href="frontend/images/icons.svg#va-icon-bank"/></svg>
                            </span>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Create a strong password"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                onclick="togglePassword('password', this)"
                                aria-label="Show password"
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
                            Use at least 8 characters.
                        </small>

                    </div>


                    <!-- CONFIRM PASSWORD -->

                    <div class="input-group">

                        <label for="confirm_password">
                            Confirm Password
                        </label>

                        <div class="input-wrapper">

                            <span class="input-icon">
                                <svg class="va-icon input-icon" width="16" height="16"><use href="frontend/images/icons.svg#va-icon-bank"/></svg>
                            </span>

                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                placeholder="Confirm your password"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                onclick="togglePassword('confirm_password', this)"
                                aria-label="Show password"
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

                            I agree to the VAYUMA
                            <a href="#" onclick="return false;">
                                Terms of Service
                            </a>
                            and
                            <a href="#" onclick="return false;">
                                Privacy Policy
                            </a>.

                        </label>

                    </div>


                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        class="register-button"
                    >

                        Continue

                        <span>
                            →
                        </span>

                    </button>

                </form>


                <div class="login-link">

                    Already have a VAYUMA account?

                    <a href="login.php">
                        Login
                    </a>

                </div>

            </section>

        </div>

    </main>


<script>

function togglePassword(fieldId, button) {

    const field = document.getElementById(fieldId);
    const isVisible = field.type === "password";

    field.type = isVisible ? "text" : "password";
    button.setAttribute("aria-label", isVisible ? "Hide password" : "Show password");
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
            "Use at least 8 characters.";

    } else if (strength <= 1) {

        strengthBar.style.width = "25%";

        passwordMessage.textContent =
            "Weak password.";

    } else if (strength === 2) {

        strengthBar.style.width = "50%";

        passwordMessage.textContent =
            "Moderate password.";

    } else if (strength === 3) {

        strengthBar.style.width = "75%";

        passwordMessage.textContent =
            "Strong password.";

    } else {

        strengthBar.style.width = "100%";

        passwordMessage.textContent =
            "Very strong password.";

    }

});


confirmPassword.addEventListener("input", function () {

    if (confirmPassword.value === "") {

        matchMessage.textContent = "";

    } else if (password.value === confirmPassword.value) {

        matchMessage.textContent =
            "Passwords match.";

        matchMessage.className =
            "success-message";

    } else {

        matchMessage.textContent =
            "Passwords do not match.";

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

            alert("Password must contain at least 8 characters.");

            password.focus();

            return;
        }

        if (passwordValue !== confirmValue) {

            event.preventDefault();

            alert("Passwords do not match.");

            confirmPassword.focus();

            return;
        }

    });

</script>

</body>
</html>