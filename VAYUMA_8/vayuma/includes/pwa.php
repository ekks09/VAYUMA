<?php // Shared PWA metadata. Paths are relative to the application document root. ?>
<link rel="manifest" href="manifest.json?v=brand-2">
<link rel="icon" href="assets/images/brand/favicon.ico?v=brand-2" sizes="any">
<link rel="stylesheet" href="assets/css/brand.css?v=1">
<link rel="stylesheet" href="frontend/css/pwa-splash.css?v=1">
<script>
// Only installed apps get the temporary branded loading surface. It cannot
// block interaction and is removed even when a slow resource never finishes.
(function () {
  if (!(navigator.standalone === true || (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches))) return;
  var root = document.documentElement;
  var finish = function () { root.classList.remove('va-pwa-loading'); };
  root.classList.add('va-pwa-loading');
  window.addEventListener('load', finish, {once:true});
  window.addEventListener('pageshow', finish, {once:true});
  setTimeout(finish, 6000);
})();
</script>
<meta name="theme-color" content="#144e32">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="VAYUMA TRIX">
<link rel="apple-touch-icon" href="assets/images/brand/apple-touch-icon.png?v=brand-2">
<link rel="stylesheet" href="assets/css/vayuma-ui.css?v=3">
<link rel="stylesheet" href="frontend/css/pwa-install.css?v=install-2">
<script src="frontend/js/pwa.js?v=install-4" defer></script>
<link rel="stylesheet" href="frontend/css/user-menu.css?v=4">
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
<link rel="stylesheet" href="frontend/css/ai-chat.css?v=7">
<script src="frontend/js/ai-chat.js?v=9" defer></script>
<?php endif; ?>
