<?php // Shared PWA metadata. Paths are relative to the application document root. ?>
<link rel="manifest" href="manifest.json?v=brand-1">
<link rel="icon" href="assets/images/brand/favicon.ico" sizes="any">
<link rel="stylesheet" href="assets/css/brand.css?v=1">
<meta name="theme-color" content="#064e3b">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="VAYUMA TRIX">
<link rel="apple-touch-icon" href="assets/images/brand/apple-touch-icon.png">
<link rel="stylesheet" href="assets/css/vayuma-ui.css?v=3">
<link rel="stylesheet" href="frontend/css/pwa-install.css?v=install-2">
<script src="frontend/js/pwa.js?v=install-4" defer></script>
<link rel="stylesheet" href="frontend/css/user-menu.css?v=3">
<script src="frontend/js/user-menu.js?v=1" defer></script>
<script src="frontend/js/feedback.js?v=1" defer></script>
<link rel="stylesheet" href="frontend/css/dashboard-sidebar-icons.css?v=1">
<script src="frontend/js/dashboard-sidebar-icons.js?v=1" defer></script>
<?php
// The launcher is available to signed-in accounts. The full AI page mounts its own instance.
if (($_SESSION['logged_in'] ?? false) === true && isset($_SESSION['user_id']) && basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'ai_advisor.php'):
    require_once __DIR__ . '/csrf.php';
?>
<meta name="vayuma-ai-csrf" content="<?= htmlspecialchars($_SESSION['vayuma_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="frontend/css/ai-chat.css?v=5">
<script src="frontend/js/ai-chat.js?v=7" defer></script>
<?php endif; ?>
