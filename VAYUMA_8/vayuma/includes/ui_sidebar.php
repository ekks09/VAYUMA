<?php
/**
 * VAYUMA — Unified role-based sidebar navigation.
 * Include this on dashboard pages.
 *
 * Variables (optional, set before include):
 *   $userRole   - string
 *   $activePage - string (current filename for highlight)
 *   $userName   - string
 *   $currentUserId - int
 */
$userRole = $userRole ?? ($_SESSION['role'] ?? '');
$activePage = $activePage ?? basename($_SERVER['PHP_SELF']);
$userName = $userName ?? ($_SESSION['full_name'] ?? '');
$currentUserId = (int)($_SESSION['user_id'] ?? 0);

$isAdmin = $currentUserId > 0 && function_exists('is_vayuma_admin') && is_vayuma_admin($currentUserId);

$navItems = [];

if ($userRole === 'farmer') {
  $navItems = [
    ['label' => 'Dashboard', 'href' => 'farmer_dashboard.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-dashboard"/></svg>'],
    ['label' => 'Climate Guidance', 'href' => 'climate.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-weather"/></svg>'],
    ['label' => 'Soil Analysis', 'href' => 'soil_analysis.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg>'],
    ['label' => 'Farming Score', 'href' => 'farming_score.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg>'],
    ['label' => 'Crop Opportunities', 'href' => 'crop_opportunities.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg>'],
    ['label' => 'Crop Calendar', 'href' => 'crop_calendar.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg>'],
    ['label' => 'Disease Helper', 'href' => 'disease_helper.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg>'],
    ['label' => 'Budget Planner', 'href' => 'budget_planner.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg>'],
    ['label' => 'Market Connect', 'href' => 'market_connect.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg>'],
    ['label' => 'Add Produce', 'href' => 'add_produce.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg>'],
    ['label' => 'My Produce', 'href' => 'my_produce.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg>'],
    ['label' => 'My Orders', 'href' => 'my_orders.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-orders"/></svg>'],
    ['label' => 'Produce Requests', 'href' => 'seller_orders.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg>'],
    ['label' => 'VAYUMA TRIX AI', 'href' => 'ai_advisor.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-ai"/></svg>'],
    ['label' => 'Settings', 'href' => 'service_placeholder.php?service=Settings', 'icon' => '⚙'],
  ];
} elseif ($userRole === 'buyer') {
  $navItems = [
    ['label' => 'Dashboard', 'href' => 'buyer_dashboard.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-dashboard"/></svg>'],
    ['label' => 'Marketplace', 'href' => 'market_connect.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg>'],
    ['label' => 'My Orders', 'href' => 'my_orders.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-orders"/></svg>'],
    ['label' => 'Deliveries', 'href' => 'deliveries.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-transport"/></svg>'],
    ['label' => 'Agricultural Products', 'href' => 'market_connect.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-maize"/></svg>'],
    ['label' => 'Recommended', 'href' => 'recommended.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg>'],
    ['label' => 'VAYUMA TRIX AI', 'href' => 'ai_advisor.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-ai"/></svg>'],
    ['label' => 'My Profile', 'href' => 'buyer_profile.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-profile"/></svg>'],
  ];
} elseif ($userRole === 'consumer') {
  $navItems = [
    ['label' => 'Dashboard', 'href' => 'consumer_dashboard.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-dashboard"/></svg>'],
    ['label' => 'Marketplace', 'href' => 'market_connect.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg>'],
    ['label' => 'My Orders', 'href' => 'my_orders.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-orders"/></svg>'],
    ['label' => 'VAYUMA TRIX AI', 'href' => 'ai_advisor.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-ai"/></svg>'],
    ['label' => 'My Profile', 'href' => 'consumer_profile.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-profile"/></svg>'],
  ];
} elseif ($userRole === 'cooperative') {
  $navItems = [
    ['label' => 'Dashboard', 'href' => 'cooperative_dashboard.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-dashboard"/></svg>'],
    ['label' => 'Marketplace', 'href' => 'market_connect.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg>'],
    ['label' => 'My Orders', 'href' => 'my_orders.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-orders"/></svg>'],
    ['label' => 'VAYUMA TRIX AI', 'href' => 'ai_advisor.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-ai"/></svg>'],
    ['label' => 'My Profile', 'href' => 'cooperative_profile.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-profile"/></svg>'],
  ];
} elseif ($userRole === 'farmer') {
  // handled above
} else {
  $navItems = [
    ['label' => 'Dashboard', 'href' => 'dashboard.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-dashboard"/></svg>'],
    ['label' => 'Market Connect', 'href' => 'market_connect.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg>'],
    ['label' => 'My Orders', 'href' => 'my_orders.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-orders"/></svg>'],
    ['label' => 'Profile', 'href' => 'profile_setup.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-profile"/></svg>'],
    ['label' => 'VAYUMA TRIX AI', 'href' => 'ai_advisor.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-ai"/></svg>'],
  ];
}

if ($isAdmin) {
  $navItems[] = ['label' => 'Admin Pricing', 'href' => 'admin_subscriptions.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-money"/></svg>'];
  $navItems[] = ['label' => 'Verification', 'href' => 'admin_verification.php', 'icon' => '✓'];
}
?>
<?php if ($currentUserId > 0): ?>
<div class="va-sidebar-overlay" id="vaSidebarOverlay" onclick="vaCloseSidebar()"></div>

<aside class="va-sidebar" id="vaSidebar">
  <?php
  require_once __DIR__ . '/role_routes.php';
  $platformHomeUrl = vayuma_platform_home_route();
  ?>
  <div class="va-sidebar--logo">
    <a href="<?= htmlspecialchars($platformHomeUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="VAYUMA TRIX home">
      <img src="assets/images/brand/app-icon-192.png" alt="VAYUMA TRIX">
      <div>
        <strong>VAYUMA TRIX</strong>
        <small>Agritech Innovations Kenya</small>
      </div>
    </a>
  </div>

  <nav class="va-sidebar--nav">
    <?php foreach ($navItems as $item): ?>
      <?php
      $isActive = ($activePage === $item['href']) || (strpos($activePage, $item['href']) !== false);
      $itemClass = 'va-nav-item' . ($isActive ? ' va-nav-item--active' : '');
      ?>
      <a href="<?= htmlspecialchars($item['href']) ?>" class="<?= $itemClass ?>" <?= $isActive ? 'aria-current="page"' : '' ?>>
        <span class="va-nav-icon"><?= htmlspecialchars($item['icon']) ?></span>
        <span><?= htmlspecialchars($item['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="va-sidebar--bottom">
    <a href="subscription.php" class="va-btn va-btn--outline va-btn--sm va-mb-3" style="width:auto;">Subscription</a>
    <a href="auth/logout.php" class="va-logout-item">
      <span>⇥</span>
      Logout
    </a>
  </div>
</aside>

<script>
function vaOpenSidebar() {
  const s = document.getElementById('vaSidebar');
  const o = document.getElementById('vaSidebarOverlay');
  if (s) s.classList.add('va-sidebar--open');
  if (o) o.classList.add('va-sidebar-overlay--active');
}
function vaCloseSidebar() {
  const s = document.getElementById('vaSidebar');
  const o = document.getElementById('vaSidebarOverlay');
  if (s) s.classList.remove('va-sidebar--open');
  if (o) o.classList.remove('va-sidebar-overlay--active');
}
document.querySelectorAll('.va-nav-item').forEach(function(item) {
  item.addEventListener('click', function() {
    if (window.innerWidth <= 1024) vaCloseSidebar();
  });
});
</script>
<?php endif; ?>
