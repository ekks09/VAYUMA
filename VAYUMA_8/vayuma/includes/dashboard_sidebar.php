<?php // Common navigation shell; $navLinks and the icon partial belong to the role. ?>
<div class="va-sidebar-overlay" id="vaSidebarOverlay"></div>
<aside class="va-sidebar" id="vaSidebar" aria-label="Dashboard navigation">
  <div class="va-sidebar--logo">
    <a href="<?= htmlspecialchars($platformHomeUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="VAYUMA TRIX home">
      <img src="assets/images/brand/app-icon-192.png" alt="">
      <div><strong>VAYUMA TRIX</strong><small>Agritech Innovations Kenya</small></div>
    </a>
  </div>
  <nav class="va-sidebar--nav">
    <?php $categoryLinkStyle = 'simple'; require __DIR__ . '/dashboard_category_link.php'; ?>
    <?php foreach ($navLinks as $label => $href): ?>
      <?php if (parse_url($href, PHP_URL_PATH) === 'profile.php') continue; // Personal details link is provided above. ?>
      <?php $isActive = ($activePage === $href); ?>
      <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>" class="va-nav-item<?= $isActive ? ' va-nav-item--active' : '' ?>" <?= $isActive ? 'aria-current="page"' : '' ?>>
        <?php require __DIR__ . '/' . $dashboardIconPartial; ?>
        <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
  <?php vayuma_language_selector(); ?>
  <div class="va-sidebar--bottom">
    <a href="subscription.php" class="va-btn va-btn--outline va-btn--sm va-mb-3" style="width:auto;">Subscription</a>
    <a href="auth/logout.php" class="va-logout-item"><span aria-hidden="true">⇥</span> Logout</a>
  </div>
</aside>
