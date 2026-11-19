<?php
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/includes/role_routes.php';
$platformHomeUrl = vayuma_platform_home_route();
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
        content="VAYUMA Smart Ecosystem - Smart agriculture for every stakeholder."
    >

    <title>Welcome | VAYUMA Smart Ecosystem</title>

    <link rel="stylesheet" href="frontend/css/tokens.css">
    <link rel="stylesheet" href="frontend/css/components_addition.css?v=account-nav-1">
    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body class="welcome-body">
<?php vayuma_language_selector(); ?>

    <main class="welcome-container">

        <section class="welcome-card">

            <!-- LEFT SIDE -->

            <div class="welcome-left">

                <a class="auth-brand-link" href="<?= htmlspecialchars($platformHomeUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="VAYUMA home">
                    <img
                        src="assets/images/vayuma-logo.jpg"
                        alt="VAYUMA Smart Ecosystem"
                        class="welcome-logo"
                    >
                </a>

                <h1>VAYUMA</h1>

                <p>
                    <?= htmlspecialchars(t('welcome_intro'), ENT_QUOTES, 'UTF-8') ?>
                </p>

            </div>


            <!-- RIGHT SIDE -->

            <div class="welcome-right">

                <span class="welcome-badge">
                    <svg class="va-icon-lg" width="32" height="32"><use href="frontend/images/icons.svg#va-icon-crops"/></svg>
                </span>

                <h2>
                    <?= htmlspecialchars(t('welcome_title'), ENT_QUOTES, 'UTF-8') ?>
                </h2>

                <p class="welcome-description">

                    <?= htmlspecialchars(t('ecosystem_intro'), ENT_QUOTES, 'UTF-8') ?>

                </p>


                <!-- FEATURES -->

                <div class="welcome-features">

                    <div class="feature">

                        <div class="feature-icon">
                            <svg class="va-icon-lg" width="32" height="32"><use href="frontend/images/icons.svg#va-icon-weather"/></svg>
                        </div>

                        <strong>
                            <?= htmlspecialchars(t('climate_guidance'), ENT_QUOTES, 'UTF-8') ?>
                        </strong>

                        <span>
                            <?= htmlspecialchars(t('climate_desc'), ENT_QUOTES, 'UTF-8') ?>
                        </span>

                    </div>


                    <div class="feature">

                        <div class="feature-icon">
                            <svg class="va-icon-lg" width="32" height="32"><use href="frontend/images/icons.svg#va-icon-soil"/></svg>
                        </div>

                        <strong>
                            <?= htmlspecialchars(t('soil_intelligence'), ENT_QUOTES, 'UTF-8') ?>
                        </strong>

                        <span>
                            <?= htmlspecialchars(t('soil_desc'), ENT_QUOTES, 'UTF-8') ?>
                        </span>

                    </div>


                    <div class="feature">

                        <div class="feature-icon">
                            <svg class="va-icon-lg" width="32" height="32"><use href="frontend/images/icons.svg#va-icon-ai"/></svg>
                        </div>

                        <strong>
                            VAYUMA AI
                        </strong>

                        <span>
                            <?= htmlspecialchars(t('ai_guidance'), ENT_QUOTES, 'UTF-8') ?>
                        </span>

                    </div>


                    <div class="feature">

                        <div class="feature-icon">
                            <svg class="va-icon-lg" width="32" height="32"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg>
                        </div>

                        <strong>
                            <?= htmlspecialchars(t('market_connect'), ENT_QUOTES, 'UTF-8') ?>
                        </strong>

                        <span>
                            <?= htmlspecialchars(t('market_desc'), ENT_QUOTES, 'UTF-8') ?>
                        </span>

                    </div>

                </div>


                <!-- BUTTONS -->

                <div class="welcome-buttons">

                    <a
                        href="login.php"
                        class="btn btn-primary"
                    >
                        <?= htmlspecialchars(t('login'), ENT_QUOTES, 'UTF-8') ?>
                    </a>

                    <a
                        href="register.php"
                        class="btn btn-secondary"
                    >
                        <?= htmlspecialchars(t('create_account'), ENT_QUOTES, 'UTF-8') ?>
                    </a>

                </div>

            </div>

        </section>

    </main>

</body>

</html>