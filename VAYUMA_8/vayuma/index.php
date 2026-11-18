<?php
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/includes/role_routes.php';
$platformHomeUrl = vayuma_platform_home_route();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_language, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>VAYUMA Smart Ecosystem</title>

    <meta name="description"
          content="VAYUMA Smart Ecosystem - <?= htmlspecialchars(t('tagline'), ENT_QUOTES, 'UTF-8') ?>">

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="splash-body">
<?php vayuma_language_selector(); ?>

    <main class="splash-screen">

        <div class="splash-content">

            <div class="logo-wrapper">
                <a href="<?= htmlspecialchars($platformHomeUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="VAYUMA home">
                    <img
                        src="assets/images/vayuma-logo.jpg"
                        alt="VAYUMA Smart Ecosystem Logo"
                        class="splash-logo"
                    >
                </a>
            </div>

            <h1>VAYUMA</h1>

            <h2>SMART ECOSYSTEM</h2>

            <p class="splash-tagline">
                <?= htmlspecialchars(t('tagline'), ENT_QUOTES, 'UTF-8') ?>
            </p>

            <div class="loading-area">

                <div class="loading-bar">
                    <div class="loading-progress"></div>
                </div>

                <p>
                    <?= $current_language === 'sw' ? 'VAYUMA inaanzishwa...' : 'Initializing VAYUMA...' ?>
                </p>

            </div>

        </div>

    </main>

    <script>
        setTimeout(function () {
            window.location.href = <?= json_encode($platformHomeUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        }, 3000);
    </script>

</body>
</html>