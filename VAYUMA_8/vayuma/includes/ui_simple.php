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
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?> | VAYUMA</title>
<link rel="stylesheet" href="frontend/css/tokens.css?v=20261110">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css?v=20261110-account-nav">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
</head>
<body class="va-page">
  <!-- Skip to content link for keyboard users -->
  <a href="#va-main-content" class="va-skip-link">Skip to main content</a>
<?php require_once __DIR__ . '/language.php'; ?>
<?php vayuma_language_selector(); ?>

<header class="va-topbar">
  <a class="va-topbar--brand" href="<?= htmlspecialchars($platformHomeUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="VAYUMA home">
    <img src="frontend/images/vayuma-logo.jpg" alt="VAYUMA">
    <strong>VAYUMA</strong>
  </a>

  <?php if ($showSidebarMenu): ?>
    <button
      class="va-mobile-menu-btn"
      type="button"
      aria-controls="vaSidebar"
      aria-expanded="false"
      aria-label="Open navigation"
    >
      <span aria-hidden="true">☰</span>
    </button>
  <?php endif; ?>

  <?php if ($showNav && !$simpleShellOnly): ?>
  <nav class="va-topbar--nav">
    <?php foreach ($navLinks as $label => $href): ?>
      <a href="<?= htmlspecialchars($href) ?>" class="<?= ($activePage ?? basename($_SERVER['PHP_SELF'])) === $href ? 'va-text-primary va-font-bold' : '' ?>">
        <?= htmlspecialchars($label) ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <?php endif; ?>

  <div class="va-topbar--user">
    <?php if ($currentUserId > 0): ?>
      <?php
      $accountProfileUrl = vayuma_role_route((string)$userRole, 'profile') ?? 'profile_setup.php';
      $accountDashboardUrl = vayuma_platform_home_route();
      ?>
      <details class="va-account-menu">
        <summary aria-label="Open account menu">
          <span class="va-avatar"><?= htmlspecialchars(strtoupper(substr((string)$userName, 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
          <span class="va-account-menu__identity">
            <strong><?= htmlspecialchars((string)$userName, ENT_QUOTES, 'UTF-8') ?></strong>
            <small><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string)$userRole)), ENT_QUOTES, 'UTF-8') ?></small>
          </span>
          <span class="va-account-menu__chevron" aria-hidden="true">⌄</span>
        </summary>
        <div class="va-account-menu__panel">
          <a href="<?= htmlspecialchars($accountDashboardUrl, ENT_QUOTES, 'UTF-8') ?>">Dashboard</a>
          <a href="<?= htmlspecialchars($accountProfileUrl, ENT_QUOTES, 'UTF-8') ?>">My profile</a>
          <a href="subscription.php">Subscription</a>
          <a href="auth/logout.php">Log out</a>
        </div>
      </details>
    <?php else: ?>
      <a href="login.php" class="va-btn va-btn--outline va-btn--sm">Log in</a>
      <a href="register.php" class="va-btn va-btn--primary va-btn--sm">Sign up</a>
    <?php endif; ?>
  </div>
</header>

<?php if (!$simpleShellOnly): ?>
<main class="va-page-main" id="va-main-content">
  <div class="va-container">
<?php endif; ?>
