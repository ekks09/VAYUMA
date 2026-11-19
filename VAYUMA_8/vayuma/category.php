<?php
/**
 * VAYUMA — Category Browse Page
 * Public page showing a category and its active produce listings.
 * Supports pagination and graceful handling of missing/inactive categories.
 */
session_start();
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/config/db.php';

$catIdOrSlug = trim($_GET['id'] ?? $_GET['slug'] ?? '');
if ($catIdOrSlug === '') {
    header('Location: market_connect.php');
    exit;
}

$category = null;
$isNumeric = is_numeric($catIdOrSlug);

if ($isNumeric) {
    $stmt = $conn->prepare('SELECT * FROM produce_categories WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', (int)$catIdOrSlug);
} else {
    $stmt = $conn->prepare('SELECT * FROM produce_categories WHERE slug = ? LIMIT 1');
    $stmt->bind_param('s', $catIdOrSlug);
}
$stmt->execute();
$category = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$category) {
    http_response_code(404);
    echo '<!doctype html><html><head><title>Category Not Found | VAYUMA</title></head><body><h1>Category Not Found</h1><p>The requested category does not exist.</p><a href="market_connect.php">Browse Marketplace</a></body></html>';
    exit;
}

if (!$category['is_active']) {
    http_response_code(404);
    echo '<!doctype html><html><head><title>Category Unavailable | VAYUMA</title></head><body><h1>Category Unavailable</h1><p>This category is currently inactive.</p><a href="market_connect.php">Browse Marketplace</a></body></html>';
    exit;
}

// ── Pagination ──
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Total count
$stmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM produce_listings WHERE category_id = ? AND status = ?');
$stmt->bind_param('is', (int)$category['id'], 'available');
$stmt->execute();
$total = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
$stmt->close();
$totalPages = max(1, ceil($total / $perPage));

// Listings
$listings = [];
$stmt = $conn->prepare(
    'SELECT pl.id, pl.produce_name, pl.category, pl.quantity, pl.unit, pl.price_per_unit,
            pl.location, pl.harvest_date, pl.image, pl.created_at,
            u.full_name AS farmer_name
     FROM produce_listings pl
     LEFT JOIN users u ON pl.farmer_id = u.id
     WHERE pl.category_id = ? AND pl.status = ?
     ORDER BY pl.created_at DESC
     LIMIT ? OFFSET ?'
);
$stmt->bind_param('issi', (int)$category['id'], 'available', $perPage, $offset);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $listings[] = $row;
}
$stmt->close();

function cat_h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

?>
<!doctype html>
<html lang="en">
<head><?php require __DIR__ . '/includes/pwa.php'; ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= cat_h($category['name']) ?> | VAYUMA</title>
<link rel="stylesheet" href="frontend/css/tokens.css">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
<style>
.va-category-page { max-width: 1200px; margin: 0 auto; padding: var(--va-space-6); }
.va-category-header { display: flex; align-items: center; gap: var(--va-space-3); margin-bottom: var(--va-space-6); }
.va-category-badge { display: inline-flex; align-items: center; gap: var(--va-space-2); padding: 0.5rem 1rem; font-size: var(--va-font-size-sm); font-weight: var(--va-font-weight-bold); border-radius: var(--va-radius-full); color: #fff; }
.va-category-actions { margin-left: auto; }
.va-listings-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: var(--va-space-4); }
.va-listing-card { background: var(--va-surface); border: 1px solid var(--va-border); border-radius: var(--va-radius-lg); box-shadow: var(--va-shadow-sm); display: flex; flex-direction: column; overflow: hidden; transition: all var(--va-transition); }
.va-listing-card:hover { border-color: var(--va-primary-light); box-shadow: var(--va-shadow); transform: translateY(-2px); }
.va-listing-image { width: 100%; height: 200px; object-fit: cover; background: #f1f5f9; }
.va-listing-image--placeholder { width: 100%; height: 200px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: var(--va-text-muted); font-size: 2rem; }
.va-listing-content { padding: var(--va-space-3); flex: 1; display: flex; flex-direction: column; }
.va-listing-name { font-size: var(--va-font-size-lg); font-weight: var(--va-font-weight-bold); color: var(--va-text-primary); margin: 0 0 var(--va-space-1) 0; }
.va-listing-category { font-size: var(--va-font-size-xs); font-weight: var(--va-font-weight-bold); text-transform: uppercase; letter-spacing: 0.05em; color: var(--va-primary); margin-bottom: var(--va-space-1); }
.va-listing-price { font-size: var(--va-font-size-xl); font-weight: var(--va-font-weight-bold); color: var(--va-primary); }
.va-listing-meta { font-size: var(--va-font-size-xs); color: var(--va-text-muted); margin-top: auto; }
.va-pagination { display: flex; justify-content: center; align-items: center; gap: var(--va-space-2); margin-top: var(--va-space-6); }
.va-pagination a, .va-pagination span { padding: 0.5rem 0.875rem; border: 1px solid var(--va-border); border-radius: var(--va-radius); color: var(--va-text-secondary); text-decoration: none; font-size: var(--va-font-size-sm); }
.va-pagination a:hover { border-color: var(--va-primary); color: var(--va-primary); }
.va-pagination .va-pagination--current { background: var(--va-primary); color: #fff; border-color: var(--va-primary); }
.va-empty { text-align: center; padding: 4rem 1rem; }
.va-empty--icon { font-size: 3rem; margin-bottom: var(--va-space-3); }
.va-empty--title { font-size: var(--va-font-size-xl); font-weight: var(--va-font-weight-bold); color: var(--va-text-primary); margin-bottom: var(--va-space-2); }
.va-empty--desc { color: var(--va-text-secondary); margin-bottom: var(--va-space-4); }
</style>
</head>
<body>
<?php vayuma_language_selector(); ?>

<div class="va-category-page">
    <header class="va-category-header">
        <span class="va-category-badge" style="background:<?= cat_h($category['color_hex']) ?>;">
            <span class="va-color-swatch" style="background:<?= cat_h($category['color_hex']) ?>;"></span>
            <?= cat_h($category['name']) ?>
        </span>
        <?php if (!empty($category['description'])): ?>
        <p style="color:var(--va-text-secondary);font-size:var(--va-font-size-sm);margin:0;"><?= cat_h($category['description']) ?></p>
        <?php endif; ?>
        <div class="va-category-actions">
            <a href="market_connect.php" class="va-btn va-btn--outline va-btn--sm">← Browse All</a>
        </div>
    </header>

    <?php if (empty($listings)): ?>
    <div class="va-empty">
        <div class="va-empty--icon">📦</div>
        <div class="va-empty--title">No produce available</div>
        <div class="va-empty--desc">There are no active listings in the <?= cat_h($category['name']) ?> category yet.</div>
        <a href="market_connect.php" class="va-btn va-btn--primary va-btn--sm">Browse Marketplace</a>
    </div>
    <?php else: ?>
    <div class="va-listings-grid">
        <?php foreach ($listings as $listing): ?>
        <article class="va-listing-card">
            <?php if (!empty($listing['image'])): ?>
            <img src="<?= cat_h($listing['image']) ?>" alt="<?= cat_h($listing['produce_name']) ?>" class="va-listing-image">
            <?php else: ?>
            <div class="va-listing-image--placeholder">🌾</div>
            <?php endif; ?>
            <div class="va-listing-content">
                <div class="va-listing-category"><?= cat_h($listing['category'] ?? 'Uncategorised') ?></div>
                <h2 class="va-listing-name"><?= cat_h($listing['produce_name']) ?></h2>
                <div class="va-listing-price">KSh <?= number_format((float)$listing['price_per_unit'], 2) ?> / <?= cat_h($listing['unit']) ?></div>
                <div class="va-listing-meta">
                    <?= (float)$listing['quantity'] ?> <?= cat_h($listing['unit']) ?> available
                    <?php if (!empty($listing['location'])): ?> · <?= cat_h($listing['location']) ?><?php endif; ?>
                    <?php if (!empty($listing['farmer_name'])): ?> · by <?= cat_h($listing['farmer_name']) ?><?php endif; ?>
                </div>
                <a href="produce_details.php?id=<?= (int)$listing['id'] ?>" class="va-btn va-btn--primary va-btn--sm va-w-full" style="margin-top:var(--va-space-2);">View Details</a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="va-pagination">
        <?php if ($page > 1): ?>
        <a href="category.php?id=<?= urlencode($catIdOrSlug) ?>&page=<?= $page - 1 ?>">← Previous</a>
        <?php else: ?>
        <span class="va-pagination--current">← Previous</span>
        <?php endif; ?>
        <span class="va-pagination--current">Page <?= $page ?> of <?= $totalPages ?></span>
        <?php if ($page < $totalPages): ?>
        <a href="category.php?id=<?= urlencode($catIdOrSlug) ?>&page=<?= $page + 1 ?>">Next →</a>
        <?php else: ?>
        <span class="va-pagination--current">Next →</span>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
</div>

</body>
</html>
