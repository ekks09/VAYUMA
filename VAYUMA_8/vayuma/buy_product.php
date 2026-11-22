<?php

session_start();
require_once __DIR__ . '/includes/language.php';

require_once __DIR__ . "/config/db.php";
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/payment_feedback.php';


/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    header("Location: login.php");
    exit;
}


$user_id = (int) $_SESSION["user_id"];
$role = strtolower(trim($_SESSION["role"] ?? ""));


$allowedRoles = ['buyer', 'farmer', 'consumer', 'cooperative', 'manufacturer', 'retailer', 'agribusiness', 'input_supplier'];
if (!in_array($role, $allowedRoles, true)) {
    vayuma_payment_feedback('Ordering unavailable', 'Your account is not allowed to place produce requests.', 403, 'dashboard.php');
}


/*
|--------------------------------------------------------------------------
| PRODUCE ID
|--------------------------------------------------------------------------
*/

$produce_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($produce_id === false || $produce_id === null) {
    vayuma_payment_feedback('Invalid listing', 'Choose a valid produce listing from the marketplace.', 400, 'market_connect.php');
}


/*
|--------------------------------------------------------------------------
| GET PRODUCE
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        p.id,
        p.farmer_id,
        p.produce_name,
        p.category,
        p.quantity,
        p.unit,
        p.price_per_unit,
        p.location,
        p.harvest_date,
        p.description,
        p.image,
        p.status,
        u.full_name AS farmer_name
    FROM produce_listings p
    LEFT JOIN users u
        ON p.farmer_id = u.id
    WHERE p.id = ?
    LIMIT 1
";


try {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new mysqli_sql_exception('Unable to prepare produce listing lookup.');
    }
    $stmt->bind_param('i', $produce_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $produce = $result->fetch_assoc();
    $stmt->close();
} catch (mysqli_sql_exception $exception) {
    error_log('Produce request page listing lookup failed: ' . $exception->getMessage());
    vayuma_payment_feedback('Listing unavailable', 'We could not load this produce listing. Please try again.', 500, 'market_connect.php');
}

if (!$produce) {
    vayuma_payment_feedback('Listing not found', 'This produce listing is no longer available.', 404, 'market_connect.php');
}


/*
|--------------------------------------------------------------------------
| CHECK AVAILABILITY
|--------------------------------------------------------------------------
*/

if ($produce["status"] !== "available") {
    vayuma_payment_feedback('Listing unavailable', 'This produce is no longer available.', 409, 'market_connect.php');
}


if ((float)$produce["quantity"] <= 0) {
    vayuma_payment_feedback('Out of stock', 'This produce is currently out of stock.', 409, 'market_connect.php');
}

?>

<!DOCTYPE html>

<html lang="en">

<head><?php require __DIR__ . '/includes/pwa.php'; ?>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Request Produce | VAYUMA
</title>


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
<?php require __DIR__ . '/includes/workspace_back.php'; ?>


<header class="header">

    <h1><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> VAYUMA TRIX</h1>

</header>


<div class="va-profile-page">

    <a
        href="produce_details.php?id=<?= $produce_id ?>"
        class="back"
    >
        ← Back to Produce
    </a>


    <div class="va-profile-card">


        <div class="product">


            <div class="product-image">

                <?php if (!empty($produce["image"])): ?>

                    <img
                        src="<?= htmlspecialchars($produce["image"]) ?>"
                        alt="<?= htmlspecialchars($produce["produce_name"]) ?>"
                    >

                <?php else: ?>

                    <div class="no-image">
                        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg>
                    </div>

                <?php endif; ?>

            </div>


            <div>

                <h2>
                    <?= htmlspecialchars($produce["produce_name"]) ?>
                </h2>

                <p>
                    <strong>
                        Category:
                    </strong>

                    <?= htmlspecialchars($produce["category"]) ?>
                </p>

                <p>
                    <strong>
                        Farmer:
                    </strong>

                    <?= htmlspecialchars(
                        $produce["farmer_name"] ?? "VAYUMA Farmer"
                    ) ?>
                </p>

                <p>
                    <strong>
                        Location:
                    </strong>

                    <?= htmlspecialchars($produce["location"]) ?>
                </p>

                <div class="info">

                    <p class="price">

                        KSh
                        <?= number_format(
                            (float)$produce["price_per_unit"],
                            2
                        ) ?>

                        /
                        <?= htmlspecialchars($produce["unit"]) ?>

                    </p>

                    <p>

                        Available:

                        <strong>

                            <?= number_format(
                                (float)$produce["quantity"],
                                2
                            ) ?>

                            <?= htmlspecialchars($produce["unit"]) ?>

                        </strong>

                    </p>

                </div>

            </div>

        </div>


        <form
            action="place_order.php"
            method="POST"
        >
            <?= vayuma_csrf_field() ?>

            <input
                type="hidden"
                name="produce_listing_id"
                value="<?= $produce_id ?>"
            >


            <div class="va-profile-form-group">

                <label for="quantity">

                    Quantity to Request

                </label>

                <input
                    type="number"
                    id="quantity"
                    name="quantity"
                    min="0.01"
                    max="<?= htmlspecialchars($produce["quantity"]) ?>"
                    step="0.01"
                    value="1"
                    required
                >

            </div>


            <div class="total-box">

                <div class="total-label">
                    Estimated Order Total
                </div>

                <div class="total">

                    KSh
                    <span id="total">
                        <?= number_format(
                            (float)$produce["price_per_unit"],
                            2
                        ) ?>
                    </span>

                </div>

            </div>


            <div class="note">

                ℹ️ Your order will initially be marked
                <strong>Pending</strong>.
                The farmer must accept the request before
                it proceeds to fulfillment.

            </div>


            <br>


            <button type="submit" class="va-profile-btn">

                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg> Place Produce Request

            </button>

        </form>

    </div>

</div>


<script>

const quantityInput =
    document.getElementById("quantity");

const totalElement =
    document.getElementById("total");

const price =
    <?= json_encode((float)$produce["price_per_unit"]) ?>;


function updateTotal() {

    let quantity =
        parseFloat(quantityInput.value) || 0;

    let total =
        quantity * price;

    totalElement.textContent =
        total.toLocaleString(
            "en-KE",
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );
}


quantityInput.addEventListener(
    "input",
    updateTotal
);

updateTotal();

</script>


<script src="assets/js/vayuma-language.js" defer></script>
</body>

</html>