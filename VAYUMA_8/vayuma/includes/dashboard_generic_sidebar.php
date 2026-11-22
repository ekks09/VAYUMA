<?php // Shared stakeholder/admin shell. Callers supply only their authorised navigation links. ?>
<div class="va-sidebar-overlay" id="vaSidebarOverlay"></div>
<aside class="va-dash-sidebar" id="vaDashSidebar" aria-label="Dashboard navigation">
  <?php require __DIR__ . '/dashboard_brand.php'; ?>
  <nav aria-label="Workspace">
    <?php foreach ($dashboardLinks as $label => $href): ?>
      <?php
        $isActive = basename((string)($_SERVER['SCRIPT_NAME'] ?? '')) === $href;
        $iconName = str_contains($href, 'dashboard') ? 'dashboard' : (str_contains($href, 'profile') ? 'profile' : (str_contains($href, 'market') || str_contains($href, 'offer') ? 'marketplace' : (str_contains($href, 'delivery') ? 'transport' : 'checkmark')));
      ?>
      <a class="va-dash-sidebar--nav-item" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"<?= $isActive ? ' aria-current="page"' : '' ?>><svg class="va-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg#va-icon-<?= htmlspecialchars($iconName, ENT_QUOTES, 'UTF-8') ?>"/></svg><span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span></a>
    <?php endforeach; ?>
  </nav>
  <?php vayuma_language_selector(); ?>
  <div class="va-sidebar--bottom" aria-label="Account utilities">
    <a class="va-dash-sidebar--nav-item" href="subscription.php">Subscription</a>
    <a class="va-dash-sidebar--nav-item" href="role.php">Account category</a>
    <a class="va-dash-sidebar--nav-item" href="auth/logout.php">Sign out</a>
  </div>
</aside>
