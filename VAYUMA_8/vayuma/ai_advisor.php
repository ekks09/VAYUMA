<?php
session_start();
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/role_routes.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) { header('Location: login.php'); exit; }
$dashboard = vayuma_dashboard_return_route();
?><!doctype html>
<html lang="<?= htmlspecialchars($current_language, ENT_QUOTES, 'UTF-8') ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover, interactive-widget=resizes-content">
<title>VAYUMA TRIX AI</title>
<?php require __DIR__ . '/includes/pwa.php'; ?>
<link rel="stylesheet" href="frontend/css/ai-chat.css?v=8">
<meta name="vayuma-ai-csrf" content="<?= htmlspecialchars($_SESSION['vayuma_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
<script src="frontend/js/ai-chat.js?v=10" defer></script>
</head>
<body class="vayuma-ai-page" data-dashboard="<?= htmlspecialchars($dashboard, ENT_QUOTES, 'UTF-8') ?>">
<header class="vayuma-ai-page-header"><?php require __DIR__ . '/includes/workspace_back.php'; ?></header>
<main class="vayuma-ai-page-main"><div id="vayuma-ai-page" data-ai-page></div></main>
</body></html>
