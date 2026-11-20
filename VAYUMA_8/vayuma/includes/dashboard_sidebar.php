<?php // Common navigation shell; $navLinks and the icon partial belong to the role. ?>
<div class="va-sidebar-overlay" id="vaSidebarOverlay"></div>
<aside class="va-sidebar" id="vaSidebar" aria-label="Dashboard navigation">
  <div class="va-sidebar--logo">
    <a href="<?= htmlspecialchars($platformHomeUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="VAYUMA home">
      <img src="frontend/images/vayuma-logo.jpg" alt="">
      <div><strong>VAYUMA</strong><small>Smart Ecosystem</small></div>
    </a>
  </div>
  <nav class="va-sidebar--nav">
    <?php foreach ($navLinks as $label => $href): ?>
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
