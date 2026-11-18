<?php
/**
 * VAYUMA — Market Connect (clean design).
 * Produce marketplace with search, category filters, and genuine listings.
 */
session_start();
require_once __DIR__ . '/includes/language.php';
$simpleShellOnly = true;
$showSidebarMenu = true;
$showNav = false;
require_once __DIR__ . '/includes/ui_simple.php';
require_once __DIR__ . '/config/db.php';

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$currentRole = strtolower(trim($_SESSION['role'] ?? ''));
$orderError = (string)($_SESSION['order_error'] ?? '');
unset($_SESSION['order_error']);
$dashboardLink = ($currentRole === 'farmer') ? 'farmer_dashboard.php' : (($currentRole === 'buyer') ? 'buyer_dashboard.php' : 'dashboard.php');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoryId = (int)($_GET['category_id'] ?? 0);
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$minPrice = isset($_GET['min_price']) ? trim($_GET['min_price']) : '';
$maxPrice = isset($_GET['max_price']) ? trim($_GET['max_price']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'latest';

$allowedSorts = ['latest' => 'pl.created_at DESC', 'price_low' => 'pl.price_per_unit ASC', 'price_high' => 'pl.price_per_unit DESC', 'name' => 'pl.produce_name ASC'];
$sortSql = $allowedSorts[$sort] ?? 'pl.created_at DESC';

$sql = "
    SELECT
        pl.id, pl.farmer_id, pl.produce_name, pl.category, pl.quantity,
        pl.unit, pl.price_per_unit, pl.location, pl.harvest_date,
        pl.description, pl.image, pl.status, pl.created_at,
        u.full_name AS farmer_name
    FROM produce_listings pl
    LEFT JOIN users u ON pl.farmer_id = u.id
    WHERE pl.status = 'available'
";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (pl.produce_name LIKE ? OR pl.category LIKE ? OR pl.location LIKE ? OR pl.description LIKE ?)";
    $st = "%" . $search . "%";
    $params[] = $st; $params[] = $st; $params[] = $st; $params[] = $st;
    $types .= 'ssss';
}
if ($categoryId > 0) {
    $sql .= " AND pl.category_id = ?";
    $params[] = $categoryId;
    $types .= 'i';
}
if ($location !== '') {
    $sql .= " AND pl.location LIKE ?";
    $st = "%" . $location . "%";
    $params[] = $st;
    $types .= 's';
}
if ($minPrice !== '' && is_numeric($minPrice)) {
    $sql .= " AND pl.price_per_unit >= ?";
    $params[] = (float)$minPrice;
    $types .= 'd';
}
if ($maxPrice !== '' && is_numeric($maxPrice)) {
    $sql .= " AND pl.price_per_unit <= ?";
    $params[] = (float)$maxPrice;
    $types .= 'd';
}
$sql .= " ORDER BY " . $sortSql;

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$totalListings = $result->num_rows;

$catSql = "SELECT COUNT(DISTINCT category) AS total_categories FROM produce_listings WHERE status = 'available'";
$catResult = $conn->query($catSql);
$totalCategories = 0;
if ($catResult) {
    $row = $catResult->fetch_assoc();
    $totalCategories = (int)($row['total_categories'] ?? 0);
}

$navLinks = [
    'Dashboard' => $dashboardLink,
    'Add Produce' => 'add_produce.php',
    'My Produce' => 'my_produce.php',
];

?>

<main class="va-page-main" id="va-main-content">
  <div class="va-container">
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
            <span class="va-nav-icon"><?= $label === 'Dashboard' ? '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-dashboard"/></svg>' : ($label === 'Add Produce' ? '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg>' : '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg>') ?></span>
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
      <?php if ($currentUserRole === 'farmer'): ?>
      <div class="va-section va-mb-4">
        <div class="va-d-flex va-justify-between va-items-center">
          <div>
            <h1 class="va-section--title">Fresh Produce Marketplace</h1>
            <p class="va-section--sub">Discover agricultural products directly from farmers on the VAYUMA ecosystem.</p>
          </div>
          <a href="add_produce.php" class="va-btn va-btn--primary"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Add Produce</a>
        </div>
      </div>
      <?php else: ?>
      <div class="va-section va-mb-4">
        <div>
          <h1 class="va-section--title">Fresh Produce Marketplace</h1>
          <p class="va-section--sub">Discover agricultural products directly from farmers on the VAYUMA ecosystem.</p>
        </div>
      </div>
      <?php endif; ?>

      <div class="va-dashboard-grid va-mb-6">
        <div class="va-stat-card">
          <div class="va-stat-card--header">
            <div><div class="va-stat-card--label">Available Listings</div></div>
            <div class="va-stat-card--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg></div>
          </div>
          <div class="va-stat-card--value"><?= number_format($totalListings) ?></div>
          <div class="va-stat-card--sub">produce listings</div>
        </div>
        <div class="va-stat-card">
          <div class="va-stat-card--header">
            <div><div class="va-stat-card--label">Produce Categories</div></div>
            <div class="va-stat-card--icon">🥕</div>
          </div>
          <div class="va-stat-card--value"><?= number_format($totalCategories) ?></div>
          <div class="va-stat-card--sub">categories</div>
        </div>
      </div>

      <div class="va-card va-card--p va-mb-6">
        <h2 class="va-section--title">🔎 Search VAYUMA Market</h2>
        <form method="GET" action="market_connect.php" class="va-d-flex va-gap-2 va-wrap va-mb-4">
          <input type="text" name="search" placeholder="Search maize, beans, tomatoes, location..." value="<?= h($search) ?>" class="va-input va-w-full" style="max-width:260px;">
          <input type="text" name="location" placeholder="Location" value="<?= h($location) ?>" class="va-input va-text-sm" style="max-width:140px;">
          <input type="number" name="min_price" placeholder="Min KSh" value="<?= h($minPrice) ?>" class="va-input va-text-sm" style="max-width:110px;">
          <input type="number" name="max_price" placeholder="Max KSh" value="<?= h($maxPrice) ?>" class="va-input va-text-sm" style="max-width:110px;">
          <select name="category_id" class="va-input va-text-sm">
            <option value="">All categories</option>
            <?php
            $catFilterStmt = $conn->prepare('SELECT id, name FROM produce_categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC');
            $catFilterStmt->execute();
            $catFilterResult = $catFilterStmt->get_result();
            while ($catFilterRow = $catFilterResult->fetch_assoc()):
            ?>
            <option value="<?= (int)$catFilterRow['id'] ?>" <?= (isset($categoryId) && $categoryId == $catFilterRow['id']) ? 'selected' : '' ?>><?= htmlspecialchars($catFilterRow['name']) ?></option>
            <?php endwhile; $catFilterStmt->close(); ?>
          </select>
          <select name="sort" class="va-input va-text-sm">
            <option value="latest" <?= $sort === 'latest' ? 'selected' : '' ?>>Newest first</option>
            <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: low to high</option>
            <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: high to low</option>
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name: A to Z</option>
          </select>
          <button type="submit" class="va-btn va-btn--primary va-btn--sm">Search</button>
          <?php if ($search !== '' || $categoryId > 0 || $location !== '' || $minPrice !== '' || $maxPrice !== ''): ?>
            <a href="market_connect.php" class="va-btn va-btn--outline va-btn--sm">Clear</a>
          <?php endif; ?>
        </form>
      </div>

      <div class="va-section va-mb-4">
        <div class="va-d-flex va-justify-between va-items-center va-mb-3">
          <div>
            <h2 class="va-section--title">Fresh Farmer Produce</h2>
          </div>
          <div class="va-text-muted va-text-sm"><?= number_format($totalListings) ?> listing(s)</div>
        </div>

        <div class="va-d-grid va-gap-4" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr));">
          <?php if ($totalListings > 0): ?>
            <?php while ($product = $result->fetch_assoc()): ?>
            <article class="va-card va-card--p va-mb-4" style="cursor:pointer;" onclick="window.location.href='produce_details.php?id=<?= (int)$product['id'] ?>'">
              <?php if (!empty($product['image'])): ?>
                <img src="<?= h($product['image']) ?>" alt="<?= h($product['produce_name']) ?>" style="width:100%;height:200px;object-fit:cover;border-radius:var(--va-radius);margin-bottom:var(--va-space-3);background:#edf4ed;">
              <?php else: ?>
                <div style="width:100%;height:200px;border-radius:var(--va-radius);background:#edf4ed;display:flex;align-items:center;justify-content:center;font-size:2rem;margin-bottom:var(--va-space-3);">
                  <?php
                  $cat = strtolower($product['category'] ?? '');
                  echo strpos($cat, 'vegetable') !== false ? '🥬' : (strpos($cat, 'fruit') !== false ? '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-apple"/></svg>' : (strpos($cat, 'livestock') !== false ? '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-cattle"/></svg>' : '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg>'));
                  ?>
                </div>
              <?php endif; ?>
              <div class="va-d-flex va-justify-between va-items-center va-mb-2">
                <span class="va-badge va-badge--paid va-text-xs">Available</span>
              </div>
              <?php
              $catBadgeColor = '#087d42';
              $catBadgeLabel = 'Agricultural Produce';
              if (!empty($product['category_id'])) {
                  $catBadgeStmt = $conn->prepare('SELECT name, color_hex FROM produce_categories WHERE id = ? LIMIT 1');
                  $catBadgeStmt->bind_param('i', (int)$product['category_id']);
                  $catBadgeStmt->execute();
                  $catBadgeRow = $catBadgeStmt->get_result()->fetch_assoc();
                  $catBadgeStmt->close();
                  if ($catBadgeRow) {
                      $catBadgeColor = $catBadgeRow['color_hex'];
                      $catBadgeLabel = $catBadgeRow['name'];
                  }
              } elseif (!empty($product['category'])) {
                  $catBadgeLabel = $product['category'];
              }
              ?>
              <div class="va-text-xs va-font-semibold va-text-uppercase va-text-xs va-mb-1" style="color:<?= $catBadgeColor ?>;"><?= h($catBadgeLabel) ?></div>
              <h3 class="va-text-lg va-font-bold va-text-primary va-mb-2"><?= h($product['produce_name']) ?></h3>
              <p class="va-text-sm va-text-secondary va-mb-3" style="line-height:1.5;min-height:45px;"><?= h($product['description'] ?? 'Fresh agricultural produce from a VAYUMA farmer.') ?></p>
              <div class="va-d-grid va-gap-2" style="grid-template-columns:1fr 1fr;border-top:1px solid var(--va-border);padding-top:var(--va-space-3);margin-top:var(--va-space-3);">
                <div>
                  <div class="va-text-xs va-text-muted">Quantity</div>
                  <div class="va-font-semibold va-text-sm"><?= h($product['quantity']) ?> <?= h($product['unit']) ?></div>
                </div>
                <div>
                  <div class="va-text-xs va-text-muted">Price</div>
                  <div class="va-font-semibold va-text-sm va-text-primary">KSh <?= number_format((float)$product['price_per_unit'], 2) ?>/<?= h($product['unit']) ?></div>
                </div>
                <div style="grid-column:1/-1;">
                  <div class="va-text-xs va-text-muted">Location</div>
                  <div class="va-font-semibold va-text-sm"><?= h($product['location'] ?? 'Location not specified') ?></div>
                </div>
                <div style="grid-column:1/-1;">
                  <div class="va-text-xs va-text-muted">Farmer</div>
                  <div class="va-font-semibold va-text-sm"><?= h($product['farmer_name'] ?? 'VAYUMA Farmer') ?></div>
                </div>
              </div>
              <a href="produce_details.php?id=<?= (int)$product['id'] ?>" class="va-btn va-btn--primary va-btn--sm va-w-full va-mt-3">View Produce</a>
            </article>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="va-empty">
              <div class="va-empty--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg></div>
              <div class="va-empty--title">No produce available yet</div>
              <div class="va-empty--desc">
                <?php if ($search !== '' || $category !== ''): ?>
                  We couldn't find any active listings matching your search. Try another search.
                <?php else: ?>
                  Farmers have not listed any produce on VAYUMA yet.
                <?php endif; ?>
              </div>
              <?php if ($currentUserRole === 'farmer'): ?>
                <a href="add_produce.php" class="va-btn va-btn--primary">Add Your First Produce</a>
              <?php else: ?>
                <a href="dashboard.php" class="va-btn va-btn--primary">Go to Dashboard</a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</main>

<!-- Search debounce -->
<script>
(function() {
    var searchInput = document.querySelector('input[name="search"]');
    if (!searchInput) return;
    var timeout = null;
    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            var form = searchInput.closest('form');
            if (form) form.submit();
        }, 500);
    });
})();
</script>

<?php require_once __DIR__ . '/includes/ui_simple_close.php'; ?>
