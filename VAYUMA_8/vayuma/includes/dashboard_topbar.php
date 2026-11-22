<header class="va-topbar">
  <a class="va-topbar--brand" href="<?= htmlspecialchars($platformHomeUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="VAYUMA home">
    <img src="frontend/images/pwa/icon-192.png" alt="VAYUMA">
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

  <?php if (!empty($topbarLanguage)) vayuma_language_selector(); ?>
  <div class="va-topbar--user">
    <?php if ($currentUserId > 0): ?>
      <?php require __DIR__ . '/header_user_menu.php'; ?>
    <?php else: ?>
      <a href="login.php" class="va-btn va-btn--outline va-btn--sm">Log in</a>
      <a href="register.php" class="va-btn va-btn--primary va-btn--sm">Sign up</a>
    <?php endif; ?>
  </div>
</header>
