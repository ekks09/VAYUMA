<?php
/**
 * VAYUMA Shared UI Sidebar Component
 * Include this on dashboard pages.
 *
 * Variables expected:
 *   $userRole   - string (current role)
 *   $activePage - string (current page filename for highlight)
 *   $userName   - string|null
 */
$userRole = $userRole ?? ($_SESSION['role'] ?? '');
$activePage = $activePage ?? basename($_SERVER['PHP_SELF']);
$userName = $userName ?? ($_SESSION['full_name'] ?? 'User');
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
require_once __DIR__ . '/role_routes.php';
$platformHomeUrl = vayuma_platform_home_route();

// Role-based navigation items: label, href, icon
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
    ['label' => 'VAYUMA AI', 'href' => 'ai_advisor.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-ai"/></svg>'],
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
    ['label' => 'VAYUMA AI', 'href' => 'ai_advisor.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-ai"/></svg>'],
    ['label' => 'My Profile', 'href' => 'buyer_profile.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-profile"/></svg>'],
  ];
} else {
  // Generic fallback for other roles
  $navItems = [
    ['label' => 'Dashboard', 'href' => 'dashboard.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-dashboard"/></svg>'],
    ['label' => 'Market Connect', 'href' => 'market_connect.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg>'],
    ['label' => 'My Orders', 'href' => 'my_orders.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-orders"/></svg>'],
    ['label' => 'Profile', 'href' => 'profile_setup.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-profile"/></svg>'],
    ['label' => 'VAYUMA AI', 'href' => 'ai_advisor.php', 'icon' => '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-ai"/></svg>'],
  ];
}
?>

<?php if ($currentUserId > 0): ?>
<div class="va-sidebar-overlay" id="vaSidebarOverlay" onclick="vaCloseSidebar()"></div>

<aside class="va-sidebar" id="vaSidebar">
  <div class="va-sidebar--logo">
    <a href="<?= htmlspecialchars($platformHomeUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="VAYUMA home">
      <img src="frontend/images/vayuma-logo.jpg" alt="VAYUMA">
      <div>
        <strong>VAYUMA</strong>
        <small>Smart Ecosystem</small>
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
    <a href="auth/logout.php" class="va-logout-item">
      <span>⇥</span>
      Logout
    </a>
  </div>
</aside>

<script>
function vaOpenSidebar() {
  document.getElementById('vaSidebar').classList.add('va-sidebar--open');
  document.getElementById('vaSidebarOverlay').classList.add('va-sidebar-overlay--active');
}
function vaCloseSidebar() {
  document.getElementById('vaSidebar').classList.remove('va-sidebar--open');
  document.getElementById('vaSidebarOverlay').classList.remove('va-sidebar-overlay--active');
}
// Close sidebar when clicking a nav item on mobile
document.querySelectorAll('.va-nav-item').forEach(function(item) {
  item.addEventListener('click', function() {
    if (window.innerWidth <= 1024) {
      vaCloseSidebar();
    }
  });
});
</script>
<?php endif; ?>
