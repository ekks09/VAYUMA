<?php
/**
 * VAYUMA — Category Administration
 * Authorised administrators can view, create, edit, activate/deactivate categories.
 * Protected against deletion of categories referenced by produce listings.
 */
session_start();
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/admin.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true || !is_vayuma_admin((int)$_SESSION['user_id'])) {
    http_response_code(403);
    exit('Administrator access is not enabled for this account.');
}

function cat_h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$message = '';
$messageType = '';

// ── Handle mutations (create, edit, delete, toggle active) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && vayuma_csrf_valid()) {
    $action = trim($_POST['action'] ?? '');
    $conn->begin_transaction();
    try {
        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $color = trim($_POST['color_hex'] ?? '#087d42');
            $sort = (int)($_POST['sort_order'] ?? 0);

            if ($name === '' || $slug === '') {
                throw new DomainException('Category name and slug are required.');
            }
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                throw new DomainException('Invalid colour format.');
            }

            $stmt = $conn->prepare('INSERT INTO produce_categories (name, slug, description, color_hex, sort_order) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('ssssi', $name, $slug, $description, $color, $sort);
            $stmt->execute();
            $stmt->close();
            $message = 'Category created successfully.';
            $messageType = 'success';
        } elseif ($action === 'edit') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $color = trim($_POST['color_hex'] ?? '#087d42');
            $sort = (int)($_POST['sort_order'] ?? 0);

            if ($id < 1 || $name === '' || $slug === '') {
                throw new DomainException('Invalid category data.');
            }

            $stmt = $conn->prepare('UPDATE produce_categories SET name=?, slug=?, description=?, color_hex=?, sort_order=?, updated_at=NOW() WHERE id=?');
            $stmt->bind_param('ssssii', $name, $slug, $description, $color, $sort, $id);
            $stmt->execute();
            $stmt->close();
            $message = 'Category updated successfully.';
            $messageType = 'success';
        } elseif ($action === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);
            $isActive = (int)($_POST['is_active'] ?? 1);
            if ($id < 1) throw new DomainException('Invalid category.');

            $stmt = $conn->prepare('UPDATE produce_categories SET is_active=?, updated_at=NOW() WHERE id=?');
            $stmt->bind_param('ii', $isActive, $id);
            $stmt->execute();
            $stmt->close();
            $message = $isActive ? 'Category activated.' : 'Category deactivated.';
            $messageType = 'success';
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id < 1) throw new DomainException('Invalid category.');

            // Prevent deletion if listings reference this category
            $stmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM produce_listings WHERE category_id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $cnt = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
            $stmt->close();

            if ($cnt > 0) {
                throw new DomainException('Cannot delete category with ' . $cnt . ' associated listing(s). Deactivate instead.');
            }

            $stmt = $conn->prepare('DELETE FROM produce_categories WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $message = 'Category deleted successfully.';
            $messageType = 'success';
        }
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        $message = $e instanceof DomainException ? $e->getMessage() : 'Unable to update the category. Please try again.';
        if (!($e instanceof DomainException)) error_log('VAYUMA category update unavailable.');
        $messageType = 'error';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = 'Session token expired. Please refresh and try again.';
    $messageType = 'error';
}

// ── Fetch all categories with listing counts ──
$categories = [];
$stmt = $conn->prepare(
    'SELECT c.*, COUNT(l.id) AS listing_count
     FROM produce_categories c
     LEFT JOIN produce_listings l ON l.category_id = c.id
     GROUP BY c.id
     ORDER BY c.sort_order ASC, c.name ASC'
);
$stmt->execute();
$categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<!doctype html>
<html lang="en">
<head><?php require __DIR__ . '/includes/pwa.php'; ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Category Management | VAYUMA Admin</title>
<link rel="stylesheet" href="frontend/css/tokens.css">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
<style>
.va-admin-page { max-width: 1200px; margin: 0 auto; padding: var(--va-space-6); }
.va-admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--va-space-6); }
.va-admin-header h1 { font-size: var(--va-font-size-2xl); color: var(--va-text-primary); margin: 0; }
.va-admin-card { background: var(--va-surface); border: 1px solid var(--va-border); border-radius: var(--va-radius-lg); padding: var(--va-space-5); box-shadow: var(--va-shadow-sm); margin-bottom: var(--va-space-6); }
.va-admin-table { width: 100%; border-collapse: collapse; }
.va-admin-table th, .va-admin-table td { padding: var(--va-space-3) var(--va-space-4); text-align: left; border-bottom: 1px solid var(--va-border-light); }
.va-admin-table th { font-size: var(--va-font-size-xs); font-weight: var(--va-font-weight-bold); text-transform: uppercase; color: var(--va-text-muted); }
.va-color-swatch { display: inline-block; width: 20px; height: 20px; border-radius: 4px; border: 1px solid var(--va-border); margin-right: var(--va-space-2); vertical-align: middle; }
.va-badge-inline { display: inline-flex; align-items: center; gap: var(--va-space-2); padding: 0.25rem 0.6rem; font-size: var(--va-font-size-xs); font-weight: var(--va-font-weight-bold); border-radius: var(--va-radius-full); text-transform: uppercase; letter-spacing: 0.05em; }
.va-badge--active { background: #e4f6e9; color: #17663a; }
.va-badge--inactive { background: #fde7e7; color: #a32121; }
.va-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 50; backdrop-filter: blur(2px); }
.va-modal--open { display: flex; align-items: center; justify-content: center; }
.va-modal-content { background: var(--va-surface); border-radius: var(--va-radius-lg); padding: var(--va-space-5); width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; }
.va-modal-close { position: absolute; top: var(--va-space-3); right: var(--va-space-3); background: none; border: none; font-size: var(--va-font-size-lg); cursor: pointer; color: var(--va-text-muted); }
.va-form-row { margin-bottom: var(--va-space-3); }
.va-form-label { display: block; font-size: var(--va-font-size-xs); font-weight: var(--va-font-weight-bold); margin-bottom: var(--va-space-1); color: var(--va-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; }
.va-form-input { width: 100%; padding: var(--va-space-2) var(--va-space-3); border: 1px solid var(--va-border); border-radius: var(--va-radius); font: inherit; background: var(--va-surface); color: var(--va-text-primary); }
.va-form-input:focus { outline: none; border-color: var(--va-border-focus); box-shadow: 0 0 0 3px rgba(10, 138, 73, 0.1); }
.va-toast { position: fixed; top: var(--va-space-4); right: var(--va-space-4); z-index: 60; padding: var(--va-space-3) var(--va-space-4); border-radius: var(--va-radius); color: #fff; font-weight: var(--va-font-weight-semibold); box-shadow: var(--va-shadow-lg); }
.va-toast--success { background: #17663a; }
.va-toast--error { background: #a32121; }
</style>
</head>
<body>
<?php vayuma_language_selector(); ?>
<?php require __DIR__ . '/includes/workspace_back.php'; ?>

<?php if ($message): ?>
<div class="va-toast va-toast--<?= $messageType === 'error' ? 'error' : 'success' ?>" id="vaToast"><?= cat_h($message) ?></div>
<?php endif; ?>

<header class="va-admin-header">
    <h1>Category Management</h1>
    <a href="admin_dashboard.php" class="va-btn va-btn--outline va-btn--sm">← Admin Dashboard</a>
</header>

<?php if ($message): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        var t = document.getElementById('vaToast');
        if (t) { t.style.opacity = '0'; setTimeout(function(){ t.remove(); }, 300); }
    }, 4000);
});
</script>
<?php endif; ?>

<!-- Create / Edit Modal -->
<div class="va-modal" id="vaCategoryModal">
    <div class="va-modal-content">
        <button class="va-modal-close" onclick="document.getElementById('vaCategoryModal').classList.remove('va-modal--open');">×</button>
        <h2 id="vaModalTitle">Add Category</h2>
        <form id="vaCategoryForm" method="POST">
            <?= vayuma_csrf_field() ?>
            <input type="hidden" name="action" id="vaFormAction" value="create">
            <input type="hidden" name="id" id="vaFormId" value="0">
            <div class="va-form-row">
                <label class="va-form-label">Category Name</label>
                <input class="va-form-input" name="name" id="vaFormName" required maxlength="100">
            </div>
            <div class="va-form-row">
                <label class="va-form-label">Slug (URL-friendly)</label>
                <input class="va-form-input" name="slug" id="vaFormSlug" required maxlength="100" placeholder="e.g. cereals-grains">
                <small style="color:var(--va-text-muted);font-size:var(--va-font-size-xs);">Lowercase, hyphens only. Must be unique.</small>
            </div>
            <div class="va-form-row">
                <label class="va-form-label">Description</label>
                <textarea class="va-form-input" name="description" id="vaFormDescription" rows="3" placeholder="Brief description of this category"></textarea>
            </div>
            <div class="va-form-row">
                <label class="va-form-label">Colour</label>
                <input class="va-form-input" name="color_hex" id="vaFormColor" type="color" value="#087d42">
            </div>
            <div class="va-form-row">
                <label class="va-form-label">Sort Order</label>
                <input class="va-form-input" type="number" name="sort_order" id="vaFormSort" value="0" min="0">
            </div>
            <div class="va-form-row" style="display:flex;gap:var(--va-space-2);">
                <button type="button" class="va-btn va-btn--outline va-btn--sm" onclick="document.getElementById('vaCategoryModal').classList.remove('va-modal--open');">Cancel</button>
                <button type="submit" class="va-btn va-btn--primary va-btn--sm">Save Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Categories Table -->
<div class="va-admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--va-space-4);">
        <h2 style="margin:0;font-size:var(--va-font-size-lg);color:var(--va-text-primary);">All Categories</h2>
        <button class="va-btn va-btn--primary va-btn--sm" onclick="openCategoryModal('create')">+ Add Category</button>
    </div>

    <?php if (empty($categories)): ?>
    <div class="va-empty">
        <div class="va-empty--icon">📂</div>
        <div class="va-empty--title">No categories yet</div>
        <div class="va-empty--desc">Create your first category to organise produce listings.</div>
        <button class="va-btn va-btn--primary va-btn--sm" onclick="openCategoryModal('create')">Add Category</button>
    </div>
    <?php else: ?>
    <table class="va-admin-table">
        <thead>
        <tr>
            <th>Name</th>
            <th>Slug</th>
            <th>Colour</th>
            <th>Listings</th>
            <th>Status</th>
            <th>Sort</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($categories as $cat): ?>
        <tr>
            <td><?= cat_h($cat['name']) ?></td>
            <td><code><?= cat_h($cat['slug']) ?></code></td>
            <td><span class="va-color-swatch" style="background:<?= cat_h($cat['color_hex']) ?>;"></span><?= cat_h($cat['color_hex']) ?></td>
            <td><?= (int)($cat['listing_count'] ?? 0) ?></td>
            <td><span class="va-badge-inline va-badge--<?= $cat['is_active'] ? 'active' : 'inactive' ?>"><?= $cat['is_active'] ? 'Active' : 'Inactive' ?></span></td>
            <td><?= (int)$cat['sort_order'] ?></td>
            <td>
                <button class="va-btn va-btn--outline va-btn--xs" onclick="openCategoryModal('edit', <?= (int)$cat['id'] ?>, <?= cat_h(json_encode($cat['name'])) ?>, <?= cat_h(json_encode($cat['slug'])) ?>, <?= cat_h(json_encode($cat['description'] ?? '')) ?>, <?= cat_h(json_encode($cat['color_hex'])) ?>, <?= (int)$cat['sort_order'] ?>)">Edit</button>
                <button class="va-btn va-btn--outline va-btn--xs" onclick="toggleCategory(<?= (int)$cat['id'] ?>, <?= (int)$cat['is_active'] ?>)"><?= $cat['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                <?php if ((int)($cat['listing_count'] ?? 0) === 0): ?>
                <button class="va-btn va-btn--danger va-btn--xs" onclick="deleteCategory(<?= (int)$cat['id'] ?>, <?= cat_h(json_encode($cat['name'])) ?>)">Delete</button>
                <?php else: ?>
                <button class="va-btn va-btn--outline va-btn--xs" disabled title="Cannot delete category with listings">Delete</button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<script>
function openCategoryModal(action, id = 0, name = '', slug = '', description = '', color = '#087d42', sort = 0) {
    const modal = document.getElementById('vaCategoryModal');
    const form = document.getElementById('vaCategoryForm');
    const title = document.getElementById('vaModalTitle');
    const actionInput = document.getElementById('vaFormAction');
    const idInput = document.getElementById('vaFormId');

    actionInput.value = action;
    idInput.value = id;
    title.textContent = action === 'create' ? 'Add Category' : 'Edit Category';
    document.getElementById('vaFormName').value = name;
    document.getElementById('vaFormSlug').value = slug;
    document.getElementById('vaFormDescription').value = description;
    document.getElementById('vaFormColor').value = color;
    document.getElementById('vaFormSort').value = sort;

    modal.classList.add('va-modal--open');
}

function toggleCategory(id, currentActive) {
    if (!confirm('Are you sure you want to ' + (currentActive ? 'deactivate' : 'activate') + ' this category?')) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<?= vayuma_csrf_field() ?>' +
        '<input type="hidden" name="action" value="toggle">' +
        '<input type="hidden" name="id" value="' + id + '">' +
        '<input type="hidden" name="is_active" value="' + (currentActive ? 0 : 1) + '">';
    document.body.appendChild(form);
    form.submit();
}

function deleteCategory(id, name) {
    if (!confirm('Delete category "' + name + '"?\n\nThis is only allowed if no listings reference this category.')) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<?= vayuma_csrf_field() ?>' +
        '<input type="hidden" name="action" value="delete">' +
        '<input type="hidden" name="id" value="' + id + '">';
    document.body.appendChild(form);
    form.submit();
}

// Auto-slug from name
document.getElementById('vaFormName').addEventListener('input', function() {
    const slugInput = document.getElementById('vaFormSlug');
    if (slugInput.value === '') {
        slugInput.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    }
});
</script>
</body>
</html>
