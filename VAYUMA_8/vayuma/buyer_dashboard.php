<?php
/**
 * VAYUMA — Buyer Dashboard (clean design).
 */
session_start();
require_once __DIR__ . '/includes/language.php';
$simpleShellOnly = true;
$showSidebarMenu = true;
$showNav = false;
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/subscription_guard.php';
require_once __DIR__ . '/includes/subscription_banner.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'buyer') {
    header("Location: dashboard.php");
    exit;
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
$full_name = $_SESSION['full_name'] ?? 'Buyer';
$userRole = (string)$_SESSION['role'];
$activePage = basename($_SERVER['PHP_SELF']);

$navLinks = [
    'Dashboard' => 'buyer_dashboard.php',
    'Marketplace' => 'market_connect.php',
    'My Orders' => 'my_orders.php',
    'Deliveries' => 'deliveries.php',
    'Agricultural Products' => 'market_connect.php',
    'Recommended' => 'recommended.php',
    'VAYUMA AI' => 'ai_advisor.php',
    'My Profile' => 'buyer_profile.php?from=buyer_dashboard.php',
];

// ── Saved items ──
$savedItems = [];
$stmt = $conn->prepare(
    'SELECT si.id, si.created_at, pl.id AS listing_id, pl.produce_name, pl.category,
            pl.price_per_unit, pl.unit, pl.location, pl.image, u.full_name AS farmer_name
     FROM saved_items si
     INNER JOIN produce_listings pl ON pl.id = si.produce_listing_id
     LEFT JOIN users u ON pl.farmer_id = u.id
     WHERE si.user_id = ?
     ORDER BY si.created_at DESC
     LIMIT 6'
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$savedResult = $stmt->get_result();
while ($row = $savedResult->fetch_assoc()) {
    $savedItems[] = $row;
}
$stmt->close();
$savedCount = count($savedItems);

// ── Recent orders with timeline ──
$recentOrders = [];
$stmt = $conn->prepare(
    'SELECT o.id, o.produce_listing_id, o.quantity, o.unit, o.price_per_unit, o.total_amount,
            o.status, o.created_at, pl.produce_name, pl.image,
            u.full_name AS seller_name
     FROM orders o
     LEFT JOIN produce_listings pl ON pl.id = o.produce_listing_id
     LEFT JOIN users u ON o.seller_id = u.id
     WHERE o.buyer_id = ?
     ORDER BY o.created_at DESC
     LIMIT 5'
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$ordersResult = $stmt->get_result();
while ($row = $ordersResult->fetch_assoc()) {
    $recentOrders[] = $row;
}
$stmt->close();

require_once __DIR__ . '/includes/ui_simple.php';
?>

<main class="va-page-main" id="va-main-content">
  <div class="va-container">
    <?php if ($user_id > 0): ?>
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
            <span class="va-nav-icon"><?= $label === 'Dashboard' ? '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-dashboard"/></svg>' : ($label === 'Marketplace' ? '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg>' : ($label === 'My Orders' ? '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-orders"/></svg>' : '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-profile"/></svg>')) ?></span>
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
            <h1 class="va-section--title">Buyer Dashboard</h1>
            <p class="va-section--sub">Good day, <?= h($full_name) ?>. Find quality agricultural products through VAYUMA.</p>
          </div>
        </div>
      </div>

      <?php
      echo render_subscription_banner($conn, $user_id);
      ?>

      <div class="va-dashboard-grid va-mb-6">
        <div class="va-stat-card">
          <div class="va-stat-card--header">
            <div><div class="va-stat-card--label">Marketplace</div></div>
            <div class="va-stat-card--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg></div>
          </div>
          <div class="va-stat-card--value">Browse</div>
          <div class="va-stat-card--sub">Agricultural products</div>
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
            <div><div class="va-stat-card--label">Deliveries</div></div>
            <div class="va-stat-card--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-transport"/></svg></div>
          </div>
          <div class="va-stat-card--value">Track</div>
          <div class="va-stat-card--sub">Your deliveries</div>
        </div>
        <div class="va-stat-card">
          <div class="va-stat-card--header">
            <div><div class="va-stat-card--label">Payments</div></div>
            <div class="va-stat-card--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg></div>
          </div>
          <div class="va-stat-card--value">Manage</div>
          <div class="va-stat-card--sub">Your payments</div>
        </div>
        <div class="va-stat-card">
          <div class="va-stat-card--header">
            <div><div class="va-stat-card--label">Saved Items</div></div>
            <div class="va-stat-card--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg></div>
          </div>
          <div class="va-stat-card--value"><?= $savedCount ?></div>
          <div class="va-stat-card--sub">Saved listings</div>
        </div>
      </div>

      <div class="va-section va-mb-6">
        <div class="va-section--title">Agricultural Marketplace</h2>
        <a href="market_connect.php" class="va-text-sm va-font-semibold va-text-primary va-mb-2" style="display:inline-block;">View all →</a>
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
        $listingsResult = $conn->query($listingsSql);
        $hasListings = false;
        if ($listingsResult) {
            while ($row = $listingsResult->fetch_assoc()) {
                $hasListings = true;
                ?>
                <div class="va-card va-card--p va-mb-4" style="cursor:pointer;" onclick="window.location.href='produce_details.php?id=<?= (int)$row['id'] ?>'">
                  <?php if (!empty($row['image'])): ?>
                    <img src="<?= h($row['image']) ?>" alt="<?= h($row['produce_name']) ?>" style="width:100%;height:140px;object-fit:cover;border-radius:var(--va-radius);margin-bottom:var(--va-space-3);background:#edf4ed;">
                  <?php else: ?>
                    <div style="width:100%;height:140px;border-radius:var(--va-radius);background:#edf4ed;display:flex;align-items:center;justify-content:center;font-size:2rem;margin-bottom:var(--va-space-3);">
                      <?php
                      $cat = strtolower($row['category'] ?? '');
                      echo strpos($cat, 'vegetable') !== false ? '🥬' : (strpos($cat, 'fruit') !== false ? '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-apple"/></svg>' : (strpos($cat, 'livestock') !== false ? '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-cattle"/></svg>' : '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg>'));
                      ?>
                    </div>
                  <?php endif; ?>
                  <div class="va-font-semibold va-text-primary va-text-lg"><?= h($row['produce_name']) ?></div>
                  <div class="va-text-muted va-text-sm"><?= h($row['quantity']) ?> <?= h($row['unit']) ?> · KSh <?= number_format((float)$row['price_per_unit'], 2) ?>/<?= h($row['unit']) ?></div>
                  <a href="produce_details.php?id=<?= (int)$row['id'] ?>" class="va-btn va-btn--outline va-btn--sm va-w-full va-mt-3">View</a>
                </div>
                <?php
            }
        }
        if (!$hasListings): ?>
            <div class="va-empty">
              <div class="va-empty--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg></div>
              <div class="va-empty--title">No produce available</div>
              <div class="va-empty--desc">No produce is currently available.</div>
              <a href="market_connect.php" class="va-btn va-btn--primary va-btn--sm">Open Market Connect</a>
            </div>
        <?php endif; ?>
      </div>

      <?php if ($savedCount > 0): ?>
      <div class="va-section va-mb-6">
        <div class="va-section--title">Saved Items</h2>
        <p class="va-text-muted va-text-sm">Your saved produce listings. Click any item to view details.</p>
      </div>
      <div class="va-d-grid va-gap-4 va-mb-6" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr));">
        <?php foreach ($savedItems as $saved): ?>
        <div class="va-card va-card--p va-mb-4">
          <?php if (!empty($saved['image'])): ?>
          <img src="<?= h($saved['image']) ?>" alt="<?= h($saved['produce_name']) ?>" style="width:100%;height:140px;object-fit:cover;border-radius:var(--va-radius);margin-bottom:var(--va-space-3);background:#edf4ed;">
          <?php else: ?>
          <div style="width:100%;height:140px;border-radius:var(--va-radius);background:#edf4ed;display:flex;align-items:center;justify-content:center;font-size:2rem;margin-bottom:var(--va-space-3);"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg></div>
          <?php endif; ?>
          <div class="va-font-semibold va-text-primary va-text-lg"><?= h($saved['produce_name']) ?></div>
          <div class="va-text-muted va-text-sm"><?= h($saved['quantity'] ?? '1') ?> <?= h($saved['unit']) ?> · KSh <?= number_format((float)$saved['price_per_unit'], 2) ?>/<?= h($saved['unit']) ?></div>
          <form action="auth/toggle_saved_item.php" method="POST" style="display:inline;">
            <?= vayuma_csrf_field() ?>
            <input type="hidden" name="produce_listing_id" value="<?= (int)$saved['listing_id'] ?>">
            <input type="hidden" name="action" value="remove">
            <button type="submit" class="va-btn va-btn--outline va-btn--sm va-w-full va-mt-3" onclick="return confirm('Remove this saved item?')">Remove</button>
          </form>
          <a href="produce_details.php?id=<?= (int)$saved['listing_id'] ?>" class="va-btn va-btn--primary va-btn--sm va-w-full va-mt-2">View</a>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="va-section va-mb-6">
        <div class="va-section--title">Quick Actions</h2>
      </div>
      <div class="va-d-grid va-gap-3 va-mb-6" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));">
        <a href="market_connect.php" class="va-btn va-btn--outline va-btn--sm va-w-full"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg> Browse Marketplace</a>
        <a href="my_orders.php" class="va-btn va-btn--outline va-btn--sm va-w-full"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-orders"/></svg> My Orders</a>
        <a href="deliveries.php" class="va-btn va-btn--outline va-btn--sm va-w-full"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-transport"/></svg> Track Delivery</a>
        <a href="buyer_profile.php?from=buyer_dashboard.php" class="va-btn va-btn--outline va-btn--sm va-w-full"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-profile"/></svg> My Profile</a>
        <a href="ai_advisor.php" class="va-btn va-btn--outline va-btn--sm va-w-full"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-ai"/></svg> Ask VAYUMA AI</a>
      </div>

    </div>
  </div>
      <!-- Recent Orders Timeline -->
      <div class="va-section va-mb-6">
        <div class="va-section--title">Recent Orders</h2>
        <p class="va-text-muted va-text-sm">Your order history and status timeline.</p>
      </div>
      <div class="va-card va-card--p va-mb-6">
        <?php if (empty($recentOrders)): ?>
        <div class="va-empty">
          <div class="va-empty--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-orders"/></svg></div>
          <div class="va-empty--title">No orders yet</div>
          <div class="va-empty--desc">Browse Market Connect and request produce when you find something you need.</div>
          <a href="market_connect.php" class="va-btn va-btn--primary va-btn--sm">Browse Marketplace</a>
        </div>
        <?php else: ?>
        <div class="va-d-grid va-gap-4" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr));">
          <?php foreach ($recentOrders as $order): ?>
          <div class="va-card va-card--p va-mb-4">
            <div class="va-d-flex va-justify-between va-items-start va-mb-3">
              <div>
                <h3 class="va-text-lg va-font-bold va-text-primary va-mb-1">Order #<?= (int)$order['id'] ?></h3>
                <div class="va-text-xs va-text-muted"><?= h($order['produce_name'] ?? 'Produce') ?></div>
              </div>
              <span class="va-badge va-badge--<?= $order['status'] === 'paid' ? 'active' : ($order['status'] === 'pending' ? 'warning' : ($order['status'] === 'completed' ? 'active' : 'danger')) ?>" style="font-size:var(--va-font-size-xs);"><?= h(ucfirst($order['status'])) ?></span>
            </div>
            <div class="va-text-muted va-text-sm va-mb-3">
              <?= h($order['quantity']) ?> <?= h($order['unit']) ?> · KSh <?= number_format((float)$order['total_amount'], 2) ?>
              <?php if (!empty($order['seller_name'])): ?> · Seller: <?= h($order['seller_name']) ?><?php endif; ?>
            </div>
            <div style="font-size:var(--va-font-size-xs);color:var(--va-text-muted);">
              <?= h($order['created_at']) ?>
            </div>
            <?php if ($order['status'] === 'accepted'): ?>
            <a href="payment.php?order_id=<?= (int)$order['id'] ?>" class="va-btn va-btn--primary va-btn--sm va-w-full va-mt-3">Pay Now</a>
            <?php elseif ($order['status'] === 'paid'): ?>
            <span class="va-btn va-btn--outline va-btn--sm va-w-full va-mt-3" style="cursor:default;">✓ Paid</span>
            <?php elseif ($order['status'] === 'completed'): ?>
            <span class="va-btn va-btn--outline va-btn--sm va-w-full va-mt-3" style="cursor:default;">✓ Completed</span>
            <?php else: ?>
            <span class="va-btn va-btn--outline va-btn--sm va-w-full va-mt-3" style="cursor:default;">⏳ Pending</span>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

</main>

<?php require_once __DIR__ . '/includes/ui_simple_close.php'; ?>

<?php
function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
