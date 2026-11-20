<?php
/**
 * VAYUMA — Farmer Dashboard (clean design).
 */
session_start();
require_once __DIR__ . '/includes/language.php';
$simpleShellOnly = true;
$showSidebarMenu = true;
$showNav = false;
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/subscription_guard.php';
require_once __DIR__ . '/includes/subscription_banner.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit;
}
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "farmer") {
    header("Location: dashboard.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];
$full_name = $_SESSION["full_name"] ?? "Farmer";
$userRole = (string)$_SESSION["role"];
$activePage = basename($_SERVER['PHP_SELF']);

$navLinks = [
    'Dashboard' => 'farmer_dashboard.php',
    'Climate Guidance' => 'climate.php',
    'Soil Analysis' => 'soil_analysis.php',
    'Farming Score' => 'farming_score.php',
    'Crop Opportunities' => 'crop_opportunities.php',
    'Crop Calendar' => 'crop_calendar.php',
    'Disease Helper' => 'disease_helper.php',
    'Budget Planner' => 'budget_planner.php',
    'Market Connect' => 'market_connect.php',
    'Add Produce' => 'add_produce.php',
    'My Produce' => 'my_produce.php',
    'My Orders' => 'my_orders.php',
    'Produce Requests' => 'seller_orders.php',
    'VAYUMA AI' => 'ai_advisor.php',
    'Profile & Settings' => 'profile.php?return_to=farmer_dashboard.php',
];

// ── Category stats for farmer tabs ──
$categoryStats = [];
$totalListings = 0;
$stmt = $conn->prepare(
    'SELECT pc.id, pc.name, pc.color_hex, pc.slug, COUNT(pl.id) AS listing_count
     FROM produce_categories pc
     LEFT JOIN produce_listings pl ON pl.category_id = pc.id AND pl.farmer_id = ? AND pl.status = "available"
     WHERE pc.is_active = 1
     GROUP BY pc.id
     ORDER BY pc.sort_order ASC, pc.name ASC'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$catResult = $stmt->get_result();
while ($row = $catResult->fetch_assoc()) {
    $categoryStats[] = $row;
    $totalListings += (int)($row['listing_count'] ?? 0);
}
$stmt->close();

require_once __DIR__ . '/includes/ui_simple.php';
?>

<main class="va-page-main" id="va-main-content">
  <div class="va-container">
    <?php if ($userId > 0): ?>
    <div class="va-sidebar-overlay" id="vaSidebarOverlay" onclick="vaCloseSidebar()"></div>
    <aside class="va-sidebar" id="vaSidebar">
      <div class="va-sidebar--logo">
        <a href="<?= htmlspecialchars($platformHomeUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="VAYUMA home">
          <img src="frontend/images/vayuma-logo.jpg" alt="VAYUMA">
          <div><strong>VAYUMA</strong><small>Smart Ecosystem</small></div>
        </a>
      </div>
      <nav class="va-sidebar--nav">
        <?php foreach ($navLinks as $label => $href): ?>
          <?php $isActive = ($activePage === $href); ?>
          <a href="<?= htmlspecialchars($href) ?>" class="va-nav-item<?= $isActive ? ' va-nav-item--active' : '' ?>" <?= $isActive ? 'aria-current="page"' : '' ?>>
            <span class="va-nav-icon"><?= $label === 'Dashboard' ? '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-dashboard"/></svg>' : ($label === 'Market Connect' ? '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg>' : ($label === 'Add Produce' ? '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg>' : ($label === 'My Produce' ? '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg>' : '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg>'))) ?></span>
            <span><?= htmlspecialchars($label) ?></span>
          </a>
        <?php endforeach; ?>
      </nav>
      <div class="va-sidebar--bottom">
        <a href="subscription.php" class="va-btn va-btn--outline va-btn--sm va-mb-3" style="width:auto;">Subscription</a>
        <a href="auth/logout.php" class="va-logout-item"><span>⇥</span>Logout</a>
      </div>
    </aside>
    <?php endif; ?>

    <div class="va-main--content">
      <div class="va-section va-mb-4">
        <div class="va-d-flex va-justify-between va-items-center">
          <div>
            <h1 class="va-section--title">Farmer Dashboard</h1>
            <p class="va-section--sub">Good day, <?= h($full_name) ?>. Manage your farm business with VAYUMA.</p>
          </div>
          <a href="ai_advisor.php" class="va-btn va-btn--primary va-btn--sm"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-ai"/></svg> Ask VAYUMA AI</a>
        </div>
      </div>

      <?php
      echo render_subscription_banner($conn, $userId);
      ?>

      <div class="va-dashboard-grid va-mb-6">
        <div class="va-stat-card">
          <div class="va-stat-card--header">
            <div><div class="va-stat-card--label">Total Listings</div></div>
            <div class="va-stat-card--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg></div>
          </div>
          <div class="va-stat-card--value"><?= $totalListings ?></div>
          <div class="va-stat-card--sub">Active produce listings</div>
        </div>
        <?php foreach ($categoryStats as $catStat): ?>
        <div class="va-stat-card">
          <div class="va-stat-card--header">
            <div><div class="va-stat-card--label"><?= h($catStat['name']) ?></div></div>
            <div class="va-stat-card--icon" style="background:<?= h($catStat['color_hex'] ?? '#087d42') ?>;color:#fff;"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg></div>
          </div>
          <div class="va-stat-card--value"><?= (int)($catStat['listing_count'] ?? 0) ?></div>
          <div class="va-stat-card--sub">active listings</div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="va-section va-mb-6">
        <h2 class="va-section--title">Manage your farm business</h2>
      </div>
      <div class="va-feature-grid va-feature-grid--farmer va-mb-6">
        <div class="va-feature-card">
          <div class="va-feature-card--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-money"/></svg></div>
          <div class="va-feature-card--content">
            <div class="va-feature-card--badge">Budget Planner</div>
            <div class="va-feature-card--title">Track your farm finances</div>
            <div class="va-feature-card--desc">Plan your farming expenses, estimate profits and make better financial decisions.</div>
            <a href="budget_planner.php" class="va-btn va-btn--outline va-btn--sm va-mt-3">Open Budget Planner</a>
          </div>
        </div>
        <div class="va-feature-card">
          <div class="va-feature-card--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg></div>
          <div class="va-feature-card--content">
            <div class="va-feature-card--badge">Market Connect</div>
            <div class="va-feature-card--title">Sell your produce or buy from other farmers</div>
            <div class="va-feature-card--desc">List your harvest, discover available produce from other farmers, compare prices and manage your marketplace requests.</div>
            <div class="va-d-flex va-gap-2 va-mt-3">
              <a href="add_produce.php" class="va-btn va-btn--primary va-btn--sm"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Add Produce</a>
              <a href="market_connect.php" class="va-btn va-btn--outline va-btn--sm"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg> Explore Market</a>
            </div>
          </div>
        </div>
        <div class="va-feature-card">
          <div class="va-feature-card--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg></div>
          <div class="va-feature-card--content">
            <div class="va-feature-card--badge">Soil Analysis</div>
            <div class="va-feature-card--title">Understand your soil</div>
            <div class="va-feature-card--desc">Get soil recommendations and improve crop productivity.</div>
            <a href="soil_analysis.php" class="va-btn va-btn--outline va-btn--sm">Analyze Soil</a>
          </div>
        </div>
        <div class="va-feature-card">
          <div class="va-feature-card--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-health"/></svg></div>
          <div class="va-feature-card--content">
            <div class="va-feature-card--badge">Disease Helper</div>
            <div class="va-feature-card--title">Protect your crops</div>
            <div class="va-feature-card--desc">Identify crop diseases and receive treatment guidance.</div>
            <a href="disease_helper.php" class="va-btn va-btn--outline va-btn--sm">Check Crop Health</a>
          </div>
        </div>
      </div>

      <!-- Produce Category Tabs -->
      <?php if (!empty($categoryStats)): ?>
      <div class="va-section va-mb-6">
        <h2 class="va-section--title">Your Produce by Category</h2>
        <p class="va-text-muted va-text-sm">Click a category to view listings in that category.</p>
      </div>
      <div class="va-d-grid va-gap-3 va-mb-6" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr));">
        <?php foreach ($categoryStats as $catStat): ?>
        <a href="market_connect.php?category_id=<?= (int)$catStat['id'] ?>" class="va-btn va-btn--outline va-btn--sm va-w-full" style="text-align:left;position:relative;overflow:hidden;">
          <div class="va-d-flex va-justify-between va-items-center va-mb-2">
            <div class="va-font-semibold va-text-primary"><?= h($catStat['name']) ?></div>
            <span class="va-badge va-badge--paid va-text-xs" style="background:<?= h($catStat['color_hex'] ?? '#087d42') ?>;"><?= (int)($catStat['listing_count'] ?? 0) ?></span>
          </div>
          <div class="va-text-xs va-text-muted">Manage your <?= h(strtolower($catStat['name'])) ?> listings</div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="va-card va-card--p va-mb-6" style="background:linear-gradient(120deg,#059669,#047857);color:#ffffff;border:none;border-radius:var(--va-radius-lg);">
        <div class="va-d-flex va-justify-between va-items-center va-mb-4">
          <div class="va-flex-1">
            <div class="va-text-xs va-text-primary va-font-semibold va-text-uppercase va-mb-1">VAYUMA AI ASSISTANT</div>
            <div class="va-text-xl va-font-bold">Have a farming question?</div>
            <div class="va-text-sm va-text-primary va-opacity-90">Ask VAYUMA about crops, diseases, weather, soil, markets or farm management.</div>
          </div>
          <div class="va-avatar" style="width:64px;height:64px;font-size:2rem;"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-ai"/></svg></div>
        </div>
        <a href="ai_advisor.php" class="va-btn va-btn--primary va-btn--lg va-w-full">Ask VAYUMA AI →</a>
      </div>

    </div>
  </div>
</main>

<?php require_once __DIR__ . '/includes/ui_simple_close.php'; ?>

<?php
function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
