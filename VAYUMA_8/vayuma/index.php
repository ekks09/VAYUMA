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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>VAYUMA TRIX AGRITECH INNOVATIONS KENYA</title>

    <meta name="description"
          content="VAYUMA TRIX AGRITECH INNOVATIONS KENYA - <?= htmlspecialchars(t('tagline'), ENT_QUOTES, 'UTF-8') ?>">

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="splash-body">
<?php vayuma_language_selector(); ?>

    <main class="splash-screen">

        <div class="splash-content">

            <div class="logo-wrapper">
                <a href="<?= htmlspecialchars($platformHomeUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="VAYUMA TRIX home">
                    <img
                        src="assets/images/brand/shield-source.png"
                        alt="VAYUMA TRIX AGRITECH INNOVATIONS KENYA Logo"
                        class="splash-logo"
                    >
                </a>
            </div>

            <h1>VAYUMA TRIX</h1>

            <h2>AGRITECH INNOVATIONS KENYA</h2>

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