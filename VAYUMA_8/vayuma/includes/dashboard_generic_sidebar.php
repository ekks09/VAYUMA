<?php // Shared stakeholder/admin shell. Callers supply only their authorised navigation links. ?>
<div class="va-sidebar-overlay" id="vaSidebarOverlay"></div>
<aside class="va-dash-sidebar" id="vaDashSidebar" aria-label="Dashboard navigation">
  <div class="va-dash-sidebar--logo"><h1>VAYUMA</h1><p>SMART ECOSYSTEM</p></div>
  <nav aria-label="Workspace">
    <?php foreach ($dashboardLinks as $label => $href): ?>
      <a class="va-dash-sidebar--nav-item" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
    <?php endforeach; ?>
    <a class="va-dash-sidebar--nav-item" href="subscription.php">Subscription</a>
    <a class="va-dash-sidebar--nav-item" href="role.php">Account category</a>
    <a class="va-dash-sidebar--nav-item" href="auth/logout.php">Sign out</a>
  </nav>
  <?php vayuma_language_selector(); ?>
</aside>
