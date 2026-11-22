<?php
/**
 * VAYUMA Shared UI Header Component
 * Include this at the top of any page that needs the topbar.
 *
 * Variables expected (set before include):
 *   $pageTitle   - string
 *   $userName    - string|null
 *   $userRole    - string|null
 *   $activePage  - string|null (for highlight)
 */
$userName = $userName ?? ($_SESSION['full_name'] ?? 'User');
$userRole = $userRole ?? ($_SESSION['role'] ?? '');
$pageTitle = $pageTitle ?? 'VAYUMA';
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
require_once __DIR__ . '/role_routes.php';
$platformHomeUrl = vayuma_platform_home_route();
?>

<?php if (isset($showTopbar) && $showTopbar !== false): ?>
<header class="va-topbar">
  <a class="va-topbar--brand" href="<?= htmlspecialchars($platformHomeUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="VAYUMA TRIX home">
    <img src="assets/images/brand/app-icon-192.png" alt="VAYUMA TRIX">
    <div>
      <strong>VAYUMA TRIX</strong>
      <small>Agritech Innovations Kenya</small>
    </div>
  </a>

  <?php if ($currentUserId > 0): ?>
    <div class="va-topbar--search">
      <input
        type="text"
        class="va-input"
        placeholder="Search VAYUMA..."
        aria-label="Search"
      >
    </div>

    <div class="va-topbar--actions">
      <button class="va-mobile-menu-btn" aria-label="Open menu">☰</button>
      <button class="va-theme-toggle" aria-label="Toggle dark mode" title="Toggle dark mode">
        <svg class="va-icon" width="18" height="18"><use href="frontend/images/icons.svg#va-icon-weather"/></svg>
      </button>
      <?php
      $accountProfileUrl = vayuma_role_route((string)$userRole, 'profile') ?? 'profile_setup.php';
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
          <a href="<?= htmlspecialchars($platformHomeUrl, ENT_QUOTES, 'UTF-8') ?>">Dashboard</a>
          <a href="<?= htmlspecialchars($accountProfileUrl, ENT_QUOTES, 'UTF-8') ?>">My profile</a>
          <a href="subscription.php">Subscription</a>
          <a href="auth/logout.php">Log out</a>
        </div>
      </details>
    </div>
  <?php else: ?>
    <div class="va-topbar--actions">
      <a href="login.php" class="va-btn va-btn--outline va-btn--sm">Log in</a>
      <a href="register.php" class="va-btn va-btn--primary va-btn--sm">Sign up</a>
    </div>
  <?php endif; ?>
</header>
<?php endif; ?>
