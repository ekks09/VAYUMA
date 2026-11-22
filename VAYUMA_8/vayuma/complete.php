<?php

session_start();
require_once __DIR__ . '/includes/language.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: register.php");
    exit;
}

$full_name = $_SESSION["full_name"] ?? "User";

$role = $_SESSION["role"] ?? "";

?>

<!DOCTYPE html>
<html lang="en">

<head><?php require __DIR__ . '/includes/pwa.php'; ?>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Profile Complete | VAYUMA TRIX</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body class="complete-body">
<?php vayuma_language_selector(); ?>

    <main class="complete-page">

        <div class="complete-card">
            <?php require __DIR__ . '/includes/workspace_back.php'; ?>

            <div class="success-circle">
                ✓
            </div>

            <div class="complete-badge">
                VAYUMA PROFILE READY
            </div>

            <h1>
                You're All Set!
            </h1>

            <p>
                Welcome to VAYUMA,
                <strong>
                    <?php echo htmlspecialchars($full_name); ?>
                </strong>.
            </p>

            <p class="complete-description">

                Your profile has been successfully created.
                VAYUMA will now personalize your experience
                around your role and agricultural needs.

            </p>

            <div class="complete-role">

                Your Role:

                <strong>
                    <?php echo htmlspecialchars(
                        ucwords(str_replace("_", " ", $role))
                    ); ?>
                </strong>

            </div>

            <a
                href="dashboard.php"
                class="complete-button"
            >
                Enter VAYUMA Dashboard →
            </a>

        </div>

    </main>

<script src="assets/js/vayuma-language.js" defer></script>
</body>

</html>