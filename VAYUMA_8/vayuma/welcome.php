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
        content="width=device-width, initial-scale=1.0, interactive-widget=resizes-content"
    >

    <meta
        name="description"
        content="VAYUMA TRIX AGRITECH INNOVATIONS KENYA connects farmers, buyers and agricultural partners in one smart ecosystem."
    >
    <meta property="og:title" content="Welcome | VAYUMA TRIX AGRITECH INNOVATIONS KENYA">
    <meta property="og:description" content="VAYUMA TRIX AGRITECH INNOVATIONS KENYA connects farmers, buyers and agricultural partners in one smart ecosystem.">
    <meta property="og:type" content="website">

    <title>Welcome | VAYUMA TRIX AGRITECH INNOVATIONS KENYA</title>

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

                <a class="corporate-lockup" href="<?= htmlspecialchars($platformHomeUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="VAYUMA TRIX AGRITECH INNOVATIONS KENYA home">
                    <img src="assets/images/brand/shield-source.png" alt="" class="corporate-lockup__mark" width="466" height="492">
                    <span class="corporate-lockup__name">VAYUMA TRIX</span>
                    <span class="corporate-lockup__descriptor">AGRITECH INNOVATIONS</span>
                    <span class="corporate-lockup__country">KENYA</span>
                </a>

                <p>
                    <?= htmlspecialchars(t('welcome_intro'), ENT_QUOTES, 'UTF-8') ?>
                </p>

            </div>


            <!-- RIGHT SIDE -->

            <div class="welcome-right">

                <span class="welcome-badge">
                    <svg class="va-icon-lg" width="32" height="32"><use href="frontend/images/icons.svg#va-icon-crops"/></svg>
                </span>

                <h1>
                    <?= htmlspecialchars(t('welcome_title'), ENT_QUOTES, 'UTF-8') ?>
                </h1>

                <p class="welcome-description">

                    <?= htmlspecialchars(t('ecosystem_intro'), ENT_QUOTES, 'UTF-8') ?>

                </p>


                <!-- FEATURES -->

                <div class="welcome-features">

                    <a class="feature feature-link" href="feature.php?feature=climate">

                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
                                <path d="M12 4a4 4 0 0 0-4 4"/>
                                <path d="M12 2v1M4.9 4.9l.7.7M2 12h1M19.1 4.9l-.7.7M20 12h1"/>
                                <path d="M7 16a4 4 0 0 1 .8-7.9A5.5 5.5 0 0 1 18 10a3 3 0 0 1 0 6H7Z"/>
                                <path d="m8 19-1 2m5-2-1 2m5-2-1 2"/>
                            </svg>
                        </div>

                        <h3>
                            <?= htmlspecialchars(t('climate_guidance'), ENT_QUOTES, 'UTF-8') ?>
                        </h3>

                        <span>
                            <?= htmlspecialchars(t('climate_desc'), ENT_QUOTES, 'UTF-8') ?>
                        </span>

                    </a>


                    <a class="feature feature-link" href="feature.php?feature=soil">

                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
                                <path d="M3 8h18M12 3v5"/>
                                <path d="M12 8v4l-2 3-1 4-2 2M12 12l2 3 1 4 2 2M10 15l-4 1-2 3M14 15l4 1 2 3M9 19l-1 3M15 19l1 3M12 12v10"/>
                            </svg>
                        </div>

                        <h3>
                            <?= htmlspecialchars(t('soil_intelligence'), ENT_QUOTES, 'UTF-8') ?>
                        </h3>

                        <span>
                            <?= htmlspecialchars(t('soil_desc'), ENT_QUOTES, 'UTF-8') ?>
                        </span>

                    </a>


                    <a class="feature feature-link" href="feature.php?feature=ai">

                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
                                <path d="M17.5 7.2a8 8 0 1 0 0 9.6L12 12l5.5-4.8Z"/>
                                <circle cx="11" cy="8" r=".7" fill="currentColor" stroke="none"/>
                                <circle cx="20" cy="12" r="1" fill="currentColor" stroke="none"/>
                                <path d="M20 4v2m-1-1h2"/>
                            </svg>
                        </div>

                        <h3>
                            VAYUMA TRIX AI
                        </h3>

                        <span>
                            <?= htmlspecialchars(t('ai_guidance'), ENT_QUOTES, 'UTF-8') ?>
                        </span>

                    </a>


                    <div class="feature">

                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
                                <path d="M4 10v10h16V10M3 7l2-4h14l2 4v1a2 2 0 0 1-4 0 2 2 0 0 1-4 0 2 2 0 0 1-4 0 2 2 0 0 1-4 0 2 2 0 0 1-2-1Z"/>
                                <path d="M9 20v-6h6v6"/>
                            </svg>
                        </div>

                        <h3>
                            <?= htmlspecialchars(t('market_connect'), ENT_QUOTES, 'UTF-8') ?>
                        </h3>

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
    <footer class="welcome-footer">&copy; <?= date('Y') ?> VAYUMA TRIX AGRITECH INNOVATIONS KENYA. All rights reserved.</footer>

</body>

</html>