<?php
/**
 * VAYUMA CLEAN — Shared UI header
 * Minimal top navigation for all pages.
 *
 * Variables (optional, set before include):
 *   $pageTitle   - string
 *   $showNav     - bool (show nav links, default true)
 *   $activePage  - string (current filename for highlight)
 */
$userName = $userName ?? ($_SESSION['full_name'] ?? '');
$userRole = $userRole ?? ($_SESSION['role'] ?? '');
$pageTitle = $pageTitle ?? 'VAYUMA';
$showNav = $showNav ?? true;
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$navLinks = $navLinks ?? [];
$simpleShellOnly = $simpleShellOnly ?? false;
$showSidebarMenu = $showSidebarMenu ?? false;
$currentLanguage = $current_language ?? 'en';
require_once __DIR__ . '/role_routes.php';
$platformHomeUrl = $platformHomeUrl ?? vayuma_platform_home_route();
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($currentLanguage, ENT_QUOTES, 'UTF-8') ?>">
<head>
<?php require __DIR__ . '/pwa.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= htmlspecialchars($pageTitle) ?> | VAYUMA</title>
<link rel="stylesheet" href="frontend/css/tokens.css?v=20261110">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css?v=20261120-subscription-nav">
<link rel="stylesheet" href="frontend/css/layout.css?v=mobile-header-2">
<link rel="stylesheet" href="frontend/css/pages.css">
<?php if (!empty($dashboardShell)): ?><link rel="stylesheet" href="assets/css/dashboard-layout.css?v=dashboard-ui-4"><?php endif; ?>
</head>
<body class="va-page<?= !empty($dashboardShell) ? ' va-simple-dashboard' : '' ?>">
  <!-- Skip to content link for keyboard users -->
  <a href="#va-main-content" class="va-skip-link">Skip to main content</a>
<?php require_once __DIR__ . '/language.php'; ?>
<?php if (empty($dashboardShell)) vayuma_language_selector(); ?>

<?php require __DIR__ . '/dashboard_topbar.php'; ?>

<?php if (!$simpleShellOnly): ?>
<main class="va-page-main" id="va-main-content">
  <div class="va-container">
<?php endif; ?>
