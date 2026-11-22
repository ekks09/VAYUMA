<?php

session_start();
require_once __DIR__ . '/includes/language.php';

require_once __DIR__ . "/config/db.php";
require_once __DIR__ . '/includes/csrf.php';


/* ==============================
   CHECK LOGIN
============================== */

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    header("Location: login.php");
    exit;
}


$farmer_id =
    $_SESSION["user_id"];

$full_name =
    $_SESSION["full_name"] ?? "Farmer";
$listingSuccess = !empty($_SESSION['produce_listing_success']);
$listingNotice = (string)($_SESSION['produce_listing_notice'] ?? '');
unset($_SESSION['produce_listing_success'], $_SESSION['produce_listing_notice']);


/* ==============================
   GET FARMER LISTINGS
============================== */

$sql = "
    SELECT *
    FROM produce_listings
    WHERE farmer_id = ?
    ORDER BY created_at DESC
";


$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $farmer_id
);

$stmt->execute();

$result =
    $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_language, ENT_QUOTES, 'UTF-8') ?>">

<head><?php require __DIR__ . '/includes/pwa.php'; ?>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>My Produce | VAYUMA TRIX</title>

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
.listing-status { display: inline-block; margin: var(--va-space-2) 0; padding: var(--va-space-1) var(--va-space-3); border-radius: var(--va-radius-full); background: var(--va-primary-light); color: var(--va-primary); }
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

    <p>
        Smart Farming Ecosystem
    </p>

</header>


<main class="container"><?php require __DIR__ . '/includes/workspace_back.php'; ?>


    <div class="top">

        <div>

            <h2>
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> My Produce
            </h2>

            <p>
                Manage your listings and see which produce is available in the VAYUMA marketplace.
            </p>

        </div>


        <a
            href="add_produce.php"
            class="add-button"
        >

            + List Produce

        </a>

    </div>


<?php if ($listingSuccess): ?>

    <div class="success" role="status">

        Your produce listing is now published and available to buyers.

    </div>

<?php endif; ?>
<?php if ($listingNotice !== ''): ?>
    <div class="success" role="status"><?= htmlspecialchars($listingNotice, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>


<?php if ($result->num_rows > 0): ?>

    <div class="listings">


<?php while ($row = $result->fetch_assoc()): ?>


        <div class="va-profile-card">

            <h3>

                <?php echo htmlspecialchars(
                    $row["produce_name"]
                ); ?>

            </h3>


            <div class="category">

                <?php echo htmlspecialchars(
                    $row["category"]
                ); ?>

            </div>

            <p class="listing-status">
                Status: <strong><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string)($row['status'] ?? 'unknown'))), ENT_QUOTES, 'UTF-8') ?></strong>
            </p>

            <div class="quantity">

                <small>
                    <?= ($row['status'] ?? '') === 'available' ? 'Available Quantity' : 'Quantity Recorded' ?>
                </small>

                <strong>

                    <?php echo number_format(
                        $row["quantity"],
                        2
                    ); ?>

                    <?php echo htmlspecialchars(
                        $row["unit"]
                    ); ?>

                </strong>

            </div>


            <div class="detail">

                <span>
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-money"/></svg> Price
                </span>

                <strong>

                    KSh
                    <?php echo number_format(
                        $row["price_per_unit"],
                        2
                    ); ?>

                    /
                    <?php echo htmlspecialchars(
                        $row["unit"]
                    ); ?>

                </strong>

            </div>


            <div class="detail">

                <span>
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Location
                </span>

                <strong>

                    <?php echo htmlspecialchars(
                        $row["location"]
                    ); ?>

                </strong>

            </div>


            <?php if (!empty($row["harvest_date"])): ?>

            <div class="detail">

                <span>
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Harvest
                </span>

                <strong>

                    <?php echo htmlspecialchars(
                        $row["harvest_date"]
                    ); ?>

                </strong>

            </div>

            <?php endif; ?>


            <?php if (!empty($row["description"])): ?>

            <p>

                <?php echo nl2br(
                    htmlspecialchars(
                        $row["description"]
                    )
                ); ?>

            </p>

            <?php endif; ?>


            <?php if (($row['status'] ?? '') === 'available'): ?>
            <form action="auth/remove_produce.php" method="POST" onsubmit="return confirm('Remove this produce listing from the marketplace?');">
                <?= vayuma_csrf_field() ?>
                <input type="hidden" name="listing_id" value="<?= (int)$row['id'] ?>">
                <button type="submit" class="remove-button">Remove Listing</button>
            </form>
            <?php endif; ?>

        </div>


<?php endwhile; ?>


    </div>


<?php else: ?>


    <div class="empty">

        <h3>
            <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> No Produce Listed Yet
        </h3>

        <p>
            You haven't listed any produce yet.
            Add available produce and it will appear in the marketplace for buyers.
        </p>

        <a
            href="add_produce.php"
            class="add-button"
        >

            + List Your First Produce

        </a>

    </div>


<?php endif; ?>


    <a
        href="market_connect.php"
        class="back"
    >

        ← Back to Market Connect

    </a>


</main>

<script src="assets/js/vayuma-language.js" defer></script>
</body>

</html>