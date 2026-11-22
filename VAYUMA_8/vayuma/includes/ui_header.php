<?php
/**
 * VAYUMA — Unified top navigation bar.
 * Include this at the top of every page inside <body>.
 *
 * Variables (optional, set before include):
 *   $pageTitle   - string
 *   $showNav     - bool
 *   $activePage  - string (current filename for highlight)
 *   $userName    - string
 *   $userRole    - string
 *   $currentUserId - int
 */
$userName = $userName ?? ($_SESSION['full_name'] ?? '');
$userRole = $userRole ?? ($_SESSION['role'] ?? '');
$pageTitle = $pageTitle ?? 'VAYUMA';
$showNav = $showNav ?? true;
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$navLinks = $navLinks ?? [];
require_once __DIR__ . '/role_routes.php';
$platformHomeUrl = vayuma_platform_home_route();

$isAdmin = $currentUserId > 0 && function_exists('is_vayuma_admin') && is_vayuma_admin($currentUserId);
?>
<header class="va-topbar">
  <div class="va-topbar--brand">
    <a href="<?= htmlspecialchars($platformHomeUrl, ENT_QUOTES, 'UTF-8') ?>" style="text-decoration:none;display:flex;align-items:center;gap:var(--va-space-2);color:inherit;" aria-label="VAYUMA TRIX home">
      <img src="assets/images/brand/app-icon-192.png" alt="VAYUMA TRIX">
      <strong>VAYUMA TRIX</strong>
      <small>Agritech Innovations Kenya</small>
    </a>
  </div>

  <?php if ($showNav && $currentUserId > 0): ?>
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
      <a href="subscription.php" class="va-btn va-btn--outline va-btn--sm va-mr-2">Subscription</a>
      <?php if ($isAdmin): ?>
        <a href="admin_subscriptions.php" class="va-btn va-btn--outline va-btn--sm va-mr-2">Admin Pricing</a>
      <?php endif; ?>
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

<style>
.va-topbar--user {
  display: flex;
  align-items: center;
  gap: var(--va-space-2);
}
.va-topbar-user-info {
  display: none;
}
@media (min-width: 769px) {
  .va-topbar-user-info {
    display: block;
  }
}
.va-topbar-user-info > div {
  line-height: 1.2;
}
.va-mr-2 {
  margin-right: var(--va-space-2);
}
</style>
