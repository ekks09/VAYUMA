<?php
session_start();
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    header("Location: login.php");
    exit;
}

$full_name = $_SESSION["full_name"] ?? "Farmer";
$listingError = (string)($_SESSION['produce_listing_error'] ?? '');
unset($_SESSION['produce_listing_error']);
$categories = [];
$categoryLoadError = false;

try {
    $catStmt = $conn->prepare('SELECT id, name FROM produce_categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC');
    if (!$catStmt) {
        throw new mysqli_sql_exception('Unable to prepare produce categories lookup.');
    }
    $catStmt->execute();
    $catResult = $catStmt->get_result();
    while ($catRow = $catResult->fetch_assoc()) {
        $categories[] = $catRow;
    }
    $catStmt->close();
} catch (mysqli_sql_exception $exception) {
    error_log('Produce category lookup failed: ' . $exception->getMessage());
    $categoryLoadError = true;
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_language, ENT_QUOTES, 'UTF-8') ?>">

<head><?php require __DIR__ . '/includes/pwa.php'; ?>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0, interactive-widget=resizes-content">

<title>Add Produce | VAYUMA TRIX</title>

<link rel="stylesheet" href="frontend/css/tokens.css">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
<style>
.va-profile-page { max-width: 950px; margin: 0 auto; padding: var(--va-space-6); }
.va-profile-brand { text-align: center; margin-bottom: var(--va-space-6); }
.va-profile-brand h1 { color: var(--va-primary); font-size: var(--va-font-size-3xl); letter-spacing: 0.05em; margin: 0; }
.va-profile-brand p { color: var(--va-text-muted); font-size: var(--va-font-size-xs); letter-spacing: 0.05em; margin-top: var(--va-space-1); }
.va-profile-card { background: var(--va-surface); border-radius: var(--va-radius-lg); padding: var(--va-space-6); box-shadow: var(--va-shadow-sm); border: 1px solid var(--va-border); }
.va-profile-heading { margin-bottom: var(--va-space-5); }
.va-profile-label { color: var(--va-primary); font-size: var(--va-font-size-xs); font-weight: var(--va-font-weight-bold); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: var(--va-space-2); }
.va-profile-heading h2 { font-size: var(--va-font-size-2xl); margin-bottom: var(--va-space-2); color: var(--va-text-primary); }
.va-profile-heading p { color: var(--va-text-secondary); line-height: var(--va-line-height-relaxed); }
.va-profile-welcome { background: var(--va-primary-light); border-radius: var(--va-radius-md); padding: var(--va-space-4); margin: var(--va-space-6) 0; color: #17663a; line-height: var(--va-line-height-relaxed); }
.va-profile-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: var(--va-space-4); }
.va-profile-form-group { display: flex; flex-direction: column; }
.va-profile-full { grid-column: 1 / -1; }
.va-profile-label-field { font-size: var(--va-font-size-sm); font-weight: var(--va-font-weight-bold); margin-bottom: var(--va-space-2); color: var(--va-text-secondary); }
.va-profile-input { width: 100%; padding: 0.75rem 1rem; font-size: var(--va-font-size-base); font-family: inherit; border: 1px solid var(--va-border); border-radius: var(--va-radius); background: var(--va-surface); color: var(--va-text-primary); }
.va-profile-input:focus { outline: none; border-color: var(--va-border-focus); box-shadow: 0 0 0 3px rgba(10, 138, 73, 0.1); }
.va-profile-hint { font-size: var(--va-font-size-xs); color: var(--va-text-muted); margin-top: var(--va-space-1); }
.va-profile-button-area { margin-top: var(--va-space-6); }
.va-profile-btn { width: 100%; border: none; padding: var(--va-space-4); background: var(--va-primary); color: #fff; border-radius: var(--va-radius); font-size: var(--va-font-size-base); font-weight: var(--va-font-weight-bold); cursor: pointer; }
.va-profile-btn:hover { background: var(--va-primary-hover); }
.va-profile-back { display: inline-block; margin-top: var(--va-space-4); color: var(--va-primary); text-decoration: none; font-weight: var(--va-font-weight-bold); }
.va-profile-error { background: #fde7e7; color: #a32121; padding: var(--va-space-3); border-radius: var(--va-radius-md); margin-bottom: var(--va-space-4); }
@media (max-width: 700px) {
  .va-profile-page { padding: var(--va-space-4); }
  .va-profile-card { padding: var(--va-space-4); }
  .va-profile-form-grid { grid-template-columns: 1fr; }
  .va-profile-full { grid-column: auto; }
}
</style>

</head>

<body>
<?php vayuma_language_selector(); ?>

<header class="header">

    <h1><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> VAYUMA TRIX</h1>

    <p>Smart Farming Ecosystem</p>

</header>


<main class="container"><?php require __DIR__ . '/includes/workspace_back.php'; ?>

    <div class="page-title">

        <h2><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> List Your Produce</h2>

        <p>
            Hello
            <?php echo htmlspecialchars($full_name); ?>.

            Add produce that you currently have available
            for potential buyers.
        </p>

    </div>

    <?php if ($listingError !== ''): ?>
      <div class="va-profile-error" role="alert" aria-live="assertive"><?= htmlspecialchars($listingError, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($categoryLoadError): ?>
      <div class="va-profile-error" role="alert">Produce categories are temporarily unavailable. Please refresh the page before creating a listing.</div>
    <?php elseif (!$categories): ?>
      <div class="va-profile-error" role="status">No produce categories are available right now. Please try again later.</div>
    <?php endif; ?>

    <div class="va-profile-card">

        <form
            action="auth/save_produce_listing.php"
            method="POST" enctype="multipart/form-data">
            <?= vayuma_csrf_field() ?>

            <div class="va-profile-form-group">

                <label for="produce_name">
                    Produce Name
                </label>

                <input
                    type="text"
                    id="produce_name"
                    name="produce_name"
                    placeholder="e.g. Maize"
                    required
                >

            </div>

            <div class="va-profile-form-group">
                <label for="image">Product Image</label>

                <input
                    type="file"
                    id="image"
                    name="image"
                    accept="image/jpeg,image/png,image/webp"
                >

                <small>
                    Upload a clear photo of your produce.
                    JPG, PNG or WEBP. Maximum 5MB.
                </small>
            </div>


            <div class="va-profile-form-group">

                <label for="category">
                    Category
                </label>

                <select
                    id="category"
                    name="category_id"
                    required
                >
                    <option value="">Select category</option>
                    <?php foreach ($categories as $catRow): ?>
                    <option value="<?= (int)$catRow['id'] ?>"><?= htmlspecialchars($catRow['name']) ?></option>
                    <?php endforeach; ?>
                </select>

            </div>


            <div class="two-columns">

                <div class="va-profile-form-group">

                    <label for="quantity">
                        Quantity Available
                    </label>

                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        min="0.01"
                        step="0.01"
                        placeholder="e.g. 500"
                        required
                    >

                </div>


                <div class="va-profile-form-group">

                    <label for="unit">
                        Unit
                    </label>

                    <select
                        id="unit"
                        name="unit"
                        required
                    >

                        <option value="kg">
                            Kilograms (kg)
                        </option>

                        <option value="bags">
                            Bags
                        </option>

                        <option value="crates">
                            Crates
                        </option>

                        <option value="pieces">
                            Pieces
                        </option>

                        <option value="litres">
                            Litres
                        </option>

                        <option value="animals">
                            Animals
                        </option>

                    </select>

                </div>

            </div>


            <div class="va-profile-form-group">

                <label for="price_per_unit">
                    Expected Price per Unit (KSh)
                </label>

                <input
                    type="number"
                    id="price_per_unit"
                    name="price_per_unit"
                    min="0"
                    step="0.01"
                    placeholder="e.g. 80"
                    required
                >

            </div>


            <div class="va-profile-form-group">

                <label for="location">
                    Location
                </label>

                <input
                    type="text"
                    id="location"
                    name="location"
                    placeholder="e.g. Nakuru"
                    required
                >

            </div>


            <div class="va-profile-form-group">

                <label for="harvest_date">
                    Harvest Date
                </label>

                <input
                    type="date"
                    id="harvest_date"
                    name="harvest_date"
                >

            </div>


            <div class="va-profile-form-group">

                <label for="description">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    placeholder="Describe the quality, variety, packaging, availability, etc."
                ></textarea>

            </div>


            <button
                type="submit"
                class="submit-button"
                <?= ($categoryLoadError || !$categories) ? 'disabled' : '' ?>
            >

                📤 Publish Produce Listing

            </button>

        </form>


        <div class="note">

            💡 <strong>VAYUMA Tip:</strong>

            Provide accurate quantities and prices.             Listings marked available are published to the VAYUMA marketplace and can be found by buyers.

        </div>


        <a
            href="market_connect.php"
            class="back-button"
        >

            ← Back to Market Connect

        </a>

    </div>

</main>

<script src="assets/js/vayuma-language.js" defer></script>
</body>

</html>