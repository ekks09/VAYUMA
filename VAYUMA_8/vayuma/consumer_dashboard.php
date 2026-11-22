<?php
/**
 * VAYUMA — Consumer Dashboard (clean design).
 */
session_start();
require_once __DIR__ . '/includes/language.php';
$simpleShellOnly = true;
$showSidebarMenu = true;
$dashboardShell = true;
$showNav = false;
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/subscription_guard.php';
require_once __DIR__ . '/includes/subscription_banner.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'consumer') {
    header("Location: dashboard.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'Consumer';
$userRole = (string)$_SESSION['role'];
$activePage = basename($_SERVER['PHP_SELF']);

$navLinks = [
    'Dashboard' => 'consumer_dashboard.php',
    'Marketplace' => 'market_connect.php',
    'My Orders' => 'my_orders.php',
    'VAYUMA AI' => 'feature_access.php?feature=ai',
    'My Profile' => 'consumer_profile.php?from=consumer_dashboard.php',
    'Profile & Settings' => 'profile.php?return_to=consumer_dashboard.php',
];

require_once __DIR__ . '/includes/ui_simple.php';
?>

<main class="va-page-main" id="va-main-content">
  <div class="va-container va-simple-dashboard-grid">
    <?php if ($user_id > 0): ?>
    <?php $dashboardIconPartial = 'dashboard_consumer_icon.php'; require __DIR__ . '/includes/dashboard_sidebar.php'; ?>
    <?php endif; ?>

    <div class="va-main--content">
      <div class="va-section va-mb-4">
        <div class="va-d-flex va-justify-between va-items-center">
          <div>
            <h1 class="va-section--title">Consumer Dashboard</h1>
            <p class="va-section--sub">Good day, <?= h($full_name) ?>. Discover fresh agricultural products through VAYUMA.</p>
          </div>
        </div>
      </div>

      <?php
      echo render_subscription_banner($conn, $user_id);
      ?>

      <section class="dashboard-quick-links va-mb-6" aria-labelledby="consumer-quick-title">
        <div><h2 id="consumer-quick-title">Quick access</h2><p>Open the tools you use most.</p></div>
        <nav aria-label="Consumer quick access">
          <a href="market_connect.php"><svg class="va-icon" aria-hidden="true"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg> Marketplace</a>
          <a href="my_orders.php"><svg class="va-icon" aria-hidden="true"><use href="frontend/images/icons.svg#va-icon-orders"/></svg> My Orders</a>
          <a href="feature_access.php?feature=ai"><svg class="va-icon" aria-hidden="true"><use href="frontend/images/icons.svg#va-icon-ai"/></svg> VAYUMA AI</a>
          <a href="profile.php?return_to=consumer_dashboard.php"><svg class="va-icon" aria-hidden="true"><use href="frontend/images/icons.svg#va-icon-profile"/></svg> Profile &amp; settings</a>
        </nav>
      </section>

      <div class="va-dashboard-grid va-mb-6">
        <div class="va-stat-card">
          <div class="va-stat-card--header">
            <div><div class="va-stat-card--label">Marketplace</div></div>
            <div class="va-stat-card--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg></div>
          </div>
          <div class="va-stat-card--value">Browse</div>
          <div class="va-stat-card--sub">Fresh produce</div>
        </div>
        <div class="va-stat-card">
          <div class="va-stat-card--header">
            <div><div class="va-stat-card--label">My Orders</div></div>
            <div class="va-stat-card--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-orders"/></svg></div>
          </div>
          <div class="va-stat-card--value">Track</div>
          <div class="va-stat-card--sub">Your purchases</div>
        </div>
        <div class="va-stat-card">
          <div class="va-stat-card--header">
            <div><div class="va-stat-card--label">VAYUMA AI</div></div>
            <div class="va-stat-card--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-ai"/></svg></div>
          </div>
          <div class="va-stat-card--value">Ask</div>
          <div class="va-stat-card--sub">Agricultural guidance</div>
        </div>
      </div>

      <div class="va-section va-mb-6">
        <h2 class="va-section--title">Discover Fresh Produce</h2>
      </div>
      <div class="va-d-grid va-gap-4 va-mb-6" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr));">
        <?php
        $listingsSql = "
            SELECT pl.id, pl.produce_name, pl.category, pl.image, pl.location,
                   pl.quantity, pl.unit, pl.price_per_unit, u.full_name AS farmer_name
            FROM produce_listings pl
            LEFT JOIN users u ON pl.farmer_id = u.id
            WHERE pl.status = 'available'
            ORDER BY pl.created_at DESC
            LIMIT 4";
        try { $listingsResult = $conn->query($listingsSql); if (!$listingsResult) throw new RuntimeException('Listings query failed'); } catch (Throwable $e) { error_log('Consumer listings unavailable: '.$e->getMessage()); $listingsResult=false; }
        $hasListings = false;
        if ($listingsResult) {
            while ($row = $listingsResult->fetch_assoc()) {
                $hasListings = true;
                ?>
                <div class="va-card va-card--p va-mb-4">
                  <?php if (!empty($row['image'])): ?>
                    <img src="<?= h($row['image']) ?>" alt="<?= h($row['produce_name']) ?>" style="width:100%;height:140px;object-fit:cover;border-radius:var(--va-radius);margin-bottom:var(--va-space-3);background:#edf4ed;">
                  <?php else: ?>
                    <div style="width:100%;height:140px;border-radius:var(--va-radius);background:#edf4ed;display:flex;align-items:center;justify-content:center;font-size:2rem;margin-bottom:var(--va-space-3);">
                      <?php
                      $cat = strtolower($row['category'] ?? '');
                      echo strpos($cat, 'vegetable') !== false ? '🥬' : (strpos($cat, 'fruit') !== false ? '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-apple"/></svg>' : (strpos($cat, 'livestock') !== false ? '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-livestock"/></svg>' : '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg>'));
                      ?>
                    </div>
                  <?php endif; ?>
                  <div class="va-font-semibold va-text-primary va-text-lg"><?= h($row['produce_name']) ?></div>
                  <div class="va-text-muted va-text-sm"><?= h($row['quantity']) ?> <?= h($row['unit']) ?> · KSh <?= number_format((float)$row['price_per_unit'], 2) ?>/<?= h($row['unit']) ?></div>
                  <a href="produce_details.php?id=<?= (int)$row['id'] ?>" class="va-btn va-btn--outline va-btn--sm va-w-full va-mt-3" aria-label="View <?= h($row['produce_name']) ?> details">View details</a>
                </div>
                <?php
            }
        }
        if (!$hasListings): ?>
            <div class="va-empty">
              <div class="va-empty--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg></div>
              <div class="va-empty--title"><?= $listingsResult ? 'No produce available' : 'Produce listings unavailable' ?></div>
              <div class="va-empty--desc"><?= $listingsResult ? 'No produce is currently available.' : 'Please try again later.' ?></div>
              <a href="market_connect.php" class="va-btn va-btn--primary va-btn--sm">Open Market Connect</a>
            </div>
        <?php endif; ?>
      </div>

      <div class="va-section va-mb-6">
        <h2 class="va-section--title">Quick Actions</h2>
      </div>
      <div class="va-d-grid va-gap-3 va-mb-6" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));">
        <a href="market_connect.php" class="va-btn va-btn--outline va-btn--sm va-w-full"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg> Browse Marketplace</a>
        <a href="my_orders.php" class="va-btn va-btn--outline va-btn--sm va-w-full"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-orders"/></svg> My Orders</a>
        <a href="consumer_profile.php?from=consumer_dashboard.php" class="va-btn va-btn--outline va-btn--sm va-w-full"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-profile"/></svg> My Profile</a>
        <a href="feature_access.php?feature=ai" class="va-btn va-btn--outline va-btn--sm va-w-full"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-ai"/></svg> Ask VAYUMA AI</a>
      </div>

    </div>
  </div>
</main>

<?php require_once __DIR__ . '/includes/ui_simple_close.php'; ?>

<?php
function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
