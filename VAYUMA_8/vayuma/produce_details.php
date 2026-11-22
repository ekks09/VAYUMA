<?php
/**
 * VAYUMA — Produce Details (clean design).
 */
session_start();
require_once __DIR__ . '/includes/language.php';
$simpleShellOnly = true;
$showSidebarMenu = true;
$showNav = false;
require_once __DIR__ . '/includes/ui_simple.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$currentRole = strtolower(trim($_SESSION['role'] ?? ''));
$canPlaceOrder = in_array($currentRole, ['buyer', 'farmer', 'consumer', 'cooperative', 'manufacturer', 'retailer', 'agribusiness', 'input_supplier'], true);
$orderError = (string)($_SESSION['order_error'] ?? '');
unset($_SESSION['order_error']);
$dashboardLink = ($currentRole === 'farmer') ? 'farmer_dashboard.php' : (($currentRole === 'buyer') ? 'buyer_dashboard.php' : 'dashboard.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid produce listing.');
}
$produce_id = (int) $_GET['id'];

$stmt = $conn->prepare("
    SELECT p.*, u.full_name AS farmer_name, u.phone AS farmer_phone, u.email AS farmer_email
    FROM produce_listings p
    LEFT JOIN users u ON p.farmer_id = u.id
    WHERE p.id = ?
    LIMIT 1");
$stmt->bind_param("i", $produce_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die('Produce listing not found.');
}
$produce = $result->fetch_assoc();
$stmt->close();

$navLinks = [
    'Dashboard' => $dashboardLink,
    'Marketplace' => 'market_connect.php',
];

?>

<main class="va-page-main" id="va-main-content">
  <div class="va-container">
    <?php $workspaceBackMenu = false; require __DIR__ . '/includes/workspace_back.php'; ?>
    <?php if ($orderError !== ''): ?>
      <div class="va-alert va-alert--error" role="alert"><?= h($orderError) ?></div>
    <?php endif; ?>
    <?php if ($currentUserId > 0): ?>
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
            <span class="va-nav-icon"><?= $label === 'Dashboard' ? '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-dashboard"/></svg>' : '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg>' ?></span>
            <span><?= htmlspecialchars($label) ?></span>
          </a>
        <?php endforeach; ?>
      </nav>
      <div class="va-sidebar--bottom">
        <a href="auth/logout.php" class="va-logout-item"><span>⇥</span>Logout</a>
      </div>
    </aside>
    <?php endif; ?>

    <div class="va-main--content">
      <div class="va-section va-mb-4">
        <div class="va-d-flex va-justify-between va-items-center">
          <div>
            <a href="market_connect.php" class="va-btn va-btn--outline va-btn--sm va-mb-2" style="display:inline-block;">← Back to Market Connect</a>
            <h1 class="va-section--title"><?= h($produce['produce_name']) ?></h1>
          </div>
          <span class="va-badge va-badge--paid"><?= h($produce['status']) ?></span>
        </div>
      </div>

      <div class="va-card va-card--p va-mb-6">
        <?php if (!empty($produce['image'])): ?>
          <img src="<?= h($produce['image']) ?>" alt="<?= h($produce['produce_name']) ?>" style="width:100%;max-height:400px;object-fit:cover;border-radius:var(--va-radius);margin-bottom:var(--va-space-4);background:#edf4ed;">
        <?php endif; ?>

        <div class="va-d-flex va-justify-between va-items-start va-mb-4">
          <div>
            <h2 class="va-text-2xl va-font-bold va-text-primary"><?= h($produce['produce_name']) ?></h2>
            <?php
              $catBadgeColor = '#087d42';
              $catBadgeLabel = 'Agricultural Produce';
              $catBadgeLink = 'market_connect.php';
              if (!empty($produce['category_id'])) {
                  $catBadgeStmt = $conn->prepare('SELECT name, color_hex, slug FROM produce_categories WHERE id = ? LIMIT 1');
                  $catBadgeId = (int)$produce['category_id'];
                  $catBadgeStmt->bind_param('i', $catBadgeId);
                  $catBadgeStmt->execute();
                  $catBadgeRow = $catBadgeStmt->get_result()->fetch_assoc();
                  $catBadgeStmt->close();
                  if ($catBadgeRow) {
                      $catBadgeColor = $catBadgeRow['color_hex'];
                      $catBadgeLabel = $catBadgeRow['name'];
                      $catBadgeLink = 'category.php?slug=' . urlencode($catBadgeRow['slug']);
                  }
              } elseif (!empty($produce['category'])) {
                  $catBadgeLabel = $produce['category'];
              }
              ?>
              <a href="<?= $catBadgeLink ?>" class="va-badge va-badge--paid va-text-xs" style="background:<?= $catBadgeColor ?>;color:#fff;"><?= h($catBadgeLabel) ?></a>
          </div>
          <span class="va-badge va-badge--paid">Available</span>
        </div>

        <div class="va-d-grid va-gap-4 va-mb-6" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));">
          <div>
            <div class="va-text-xs va-text-muted va-font-semibold va-text-uppercase">Quantity Available</div>
            <div class="va-font-semibold va-text-primary"><?= h($produce['quantity']) ?> <?= h($produce['unit']) ?></div>
          </div>
          <div>
            <div class="va-text-xs va-text-muted va-font-semibold va-text-uppercase">Price Per Unit</div>
            <div class="va-font-semibold va-text-primary va-text-lg">KSh <?= number_format((float)$produce['price_per_unit'], 2) ?></div>
          </div>
          <div>
            <div class="va-text-xs va-text-muted va-font-semibold va-text-uppercase">Location</div>
            <div class="va-font-semibold va-text-sm"><?= h($produce['location'] ?? 'Location not specified') ?></div>
          </div>
          <div>
            <div class="va-text-xs va-text-muted va-font-semibold va-text-uppercase">Harvest Date</div>
            <div class="va-font-semibold va-text-sm"><?= h($produce['harvest_date']) ?></div>
          </div>
        </div>

        <div class="va-divider va-mb-4"></div>

        <div class="va-mb-6">
          <h3 class="va-font-semibold va-text-primary va-mb-2">About This Produce</h3>
          <div class="va-text-secondary va-text-sm" style="line-height:1.7;background:#f8fafc;padding:var(--va-space-4);border-radius:var(--va-radius);border:1px solid var(--va-border);">
            <?= nl2br(h($produce['description'] ?? 'No description provided by the farmer.')) ?>
          </div>
        </div>

        <div class="va-card va-card--p va-mb-6" style="background:#ecfdf5;border-color:#d1fae5;">
          <h3 class="va-font-semibold va-text-success va-mb-2"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> Farmer Information</h3>
          <div class="va-text-sm va-text-secondary va-mb-1">
            <strong>Name:</strong> <?= h($produce['farmer_name'] ?? 'Farmer') ?>
          </div>
          <?php if (!empty($produce['farmer_phone'])): ?>
            <div class="va-text-sm va-text-secondary va-mb-3">
              <strong>Phone:</strong> <?= h($produce['farmer_phone']) ?>
            </div>
            <a href="tel:<?= h($produce['farmer_phone']) ?>" class="va-btn va-btn--success va-btn--sm">📞 Contact Farmer</a>
          <?php endif; ?>
        </div>

        <?php if ($currentUserId > 0 && $canPlaceOrder && (int)$produce['farmer_id'] !== $currentUserId && $produce['status'] === 'available'): ?>
        <div class="va-card va-card--p va-mb-6" style="background:#f8fafc;border-color:#e2e8f0;">
          <h3 class="va-font-semibold va-text-primary va-mb-2"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg> Request This Produce</h3>
          <p class="va-text-muted va-text-sm va-mb-3">Enter the quantity you need. Your request will be sent to the farmer for confirmation.</p>
          <form action="place_order.php" method="POST" class="va-d-flex va-gap-3 va-wrap va-mb-4">
            <?= vayuma_csrf_field() ?>
            <input type="hidden" name="produce_listing_id" value="<?= (int)$produce['id'] ?>">
            <div class="va-field va-w-full" style="min-width:200px;">
              <label class="va-field-label" for="order-quantity">Quantity (<?= h($produce['unit']) ?>)</label>
              <input id="order-quantity" type="number" name="quantity" min="0.01" max="<?= h($produce['quantity']) ?>" step="0.01" placeholder="e.g. 10" required class="va-input">
            </div>
            <button type="submit" class="va-btn va-btn--primary va-btn--lg va-w-full">Request Produce</button>
          </form>
        </div>
        <?php elseif ($currentUserId > 0 && (int)$produce['farmer_id'] === $currentUserId): ?>
        <div class="va-card va-card--p va-mb-6" style="background:#fffbeb;border-color:#fde3a7;">
          <h3 class="va-font-semibold va-text-warning va-mb-2">This is Your Listing</h3>
          <p class="va-text-muted va-text-sm">You cannot purchase your own produce listing. Manage it from My Produce.</p>
        </div>
        <?php elseif ($currentUserId === 0): ?>
        <div class="va-card va-card--p va-mb-6" style="background:#f8fafc;border-color:#e2e8f0;">
          <h3 class="va-font-semibold va-text-primary va-mb-2">Want to buy this produce?</h3>
          <p class="va-text-muted va-text-sm">Please log in first to send a purchase request to the farmer.</p>
          <a href="login.php" class="va-btn va-btn--primary va-btn--sm">Log In to Request</a>
        </div>
        <?php elseif (!$canPlaceOrder): ?>
        <div class="va-card va-card--p va-mb-6" style="background:#f8fafc;border-color:#e2e8f0;" role="status">
          <h3 class="va-font-semibold va-text-primary va-mb-2">Purchasing is unavailable for this account</h3>
          <p class="va-text-muted va-text-sm">Your current account role cannot submit produce requests.</p>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</main>


<!-- Related Produce -->
<?php
$relatedCategoryId = !empty($produce['category_id']) ? (int)$produce['category_id'] : null;
$relatedCategoryText = !empty($produce['category']) ? $produce['category'] : '';
$relatedListings = [];
$relatedProduceId = (int)$produce['id'];
if ($relatedCategoryId > 0) {
    $relStmt = $conn->prepare(
        'SELECT pl.id, pl.produce_name, pl.category, pl.image, pl.location,
                pl.quantity, pl.unit, pl.price_per_unit, u.full_name AS farmer_name
         FROM produce_listings pl
         LEFT JOIN users u ON pl.farmer_id = u.id
         WHERE pl.category_id = ? AND pl.id != ? AND pl.status = "available"
         ORDER BY pl.created_at DESC
         LIMIT 4'
    );
    $relStmt->bind_param('ii', $relatedCategoryId, $relatedProduceId);
} elseif ($relatedCategoryText !== '') {
    $relStmt = $conn->prepare(
        'SELECT pl.id, pl.produce_name, pl.category, pl.image, pl.location,
                pl.quantity, pl.unit, pl.price_per_unit, u.full_name AS farmer_name
         FROM produce_listings pl
         LEFT JOIN users u ON pl.farmer_id = u.id
         WHERE pl.category = ? AND pl.id != ? AND pl.status = "available"
         ORDER BY pl.created_at DESC
         LIMIT 4'
    );
    $relStmt->bind_param('si', $relatedCategoryText, $relatedProduceId);
}
if (isset($relStmt)) {
    $relStmt->execute();
    $relResult = $relStmt->get_result();
    while ($row = $relResult->fetch_assoc()) {
        $relatedListings[] = $row;
    }
    $relStmt->close();
}
?>
<?php if (!empty($relatedListings)): ?>
<div class="va-section va-mb-6">
  <div class="va-section--title">Related Produce</h2>
  <p class="va-text-muted va-text-sm">More from this category</p>
</div>
<div class="va-d-grid va-gap-4 va-mb-6" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr));">
  <?php foreach ($relatedListings as $rel): ?>
  <div class="va-card va-card--p va-mb-4" style="cursor:pointer;" onclick="window.location.href='produce_details.php?id=<?= (int)$rel['id'] ?>'">
    <?php if (!empty($rel['image'])): ?>
    <img src="<?= h($rel['image']) ?>" alt="<?= h($rel['produce_name']) ?>" style="width:100%;height:140px;object-fit:cover;border-radius:var(--va-radius);margin-bottom:var(--va-space-3);background:#edf4ed;">
    <?php else: ?>
    <div style="width:100%;height:140px;border-radius:var(--va-radius);background:#edf4ed;display:flex;align-items:center;justify-content:center;font-size:2rem;margin-bottom:var(--va-space-3);">🌾</div>
    <?php endif; ?>
    <div class="va-font-semibold va-text-primary va-text-lg"><?= h($rel['produce_name']) ?></div>
    <div class="va-text-muted va-text-sm"><?= h($rel['quantity'] ?? '1') ?> <?= h($rel['unit']) ?> · KSh <?= number_format((float)$rel['price_per_unit'], 2) ?>/<?= h($rel['unit']) ?></div>
    <a href="produce_details.php?id=<?= (int)$rel['id'] ?>" class="va-btn va-btn--outline va-btn--sm va-w-full va-mt-3">View</a>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/ui_simple_close.php'; ?>

<?php
function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
