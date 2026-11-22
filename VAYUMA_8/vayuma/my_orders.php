<?php

session_start();
require_once __DIR__ . '/includes/role_routes.php';
require_once __DIR__ . '/includes/language.php';

require_once __DIR__ . '/config/db.php';

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['logged_in'] ?? false) !== true
) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

$role = strtolower(trim($_SESSION['role'] ?? ''));
$bankNotice = (string)($_SESSION['bank_notice'] ?? '');
$bankError = (string)($_SESSION['bank_error'] ?? '');
$deliveryError = (string)($_SESSION['delivery_error'] ?? '');
$orderCreated = !empty($_SESSION['order_created']);
unset($_SESSION['bank_notice'], $_SESSION['bank_error'], $_SESSION['delivery_error'], $_SESSION['order_created']);

if (!in_array($role, ['buyer','farmer','consumer','cooperative','manufacturer','retailer','agribusiness','input_supplier'], true)) {
    header('Location: dashboard.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| GET USER ORDERS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        o.*,
        pl.produce_name,
        pl.location,
        pl.image,
        u.full_name AS seller_name
    FROM orders o

    LEFT JOIN produce_listings pl
        ON o.produce_listing_id = pl.id

    LEFT JOIN users u
        ON o.seller_id = u.id

    WHERE o.buyer_id = ?

    ORDER BY o.created_at DESC
");

if (!$stmt) {
    error_log('Unable to prepare the buyer orders query: ' . $conn->error);
    http_response_code(500);
    exit('Your orders could not be loaded right now. Please try again later.');
}

$stmt->bind_param('i', $userId);

$stmt->execute();

$orders = $stmt->get_result();

?>

<!doctype html>

<html lang="en">

<head><?php require __DIR__ . '/includes/pwa.php'; ?>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<title>My Orders | VAYUMA</title>

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


<header class="top">

    <div class="va-profile-brand">

        VAYUMA

        <small>
            SMART ECOSYSTEM
        </small>

    </div>


    <a
        class="back"
        href="<?= htmlspecialchars(vayuma_dashboard_return_route(), ENT_QUOTES, 'UTF-8') ?>""
    >
        ← Dashboard
    </a>

</header>


<main class="container"><?php require __DIR__ . '/includes/workspace_back.php'; ?>


<div class="eyebrow">
    MARKET CONNECT
</div>


<h1 class="title">
    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-orders"/></svg> My Orders
</h1>


<p class="muted">
    Orders and produce requests you have made,
    including farmer-to-farmer purchases.
</p>

<?php if ($orderCreated): ?>
<p class="success" role="status">Your produce request was sent to the farmer.</p>
<?php endif; ?>
<?php if ($bankNotice !== ''): ?>
<p class="success" role="status"><?= htmlspecialchars($bankNotice, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<?php if ($bankError !== ''): ?>
<p class="va-profile-error" role="alert"><?= htmlspecialchars($bankError, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<?php if ($deliveryError !== ''): ?>
<p class="va-profile-error" role="alert"><?= htmlspecialchars($deliveryError, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>


<div class="va-profile-card">


<div class="table-wrap">


<?php if ($orders->num_rows): ?>


<table class="orders">


<thead>

<tr>

<th>
    Order
</th>

<th>
    Produce
</th>

<th>
    Seller
</th>

<th>
    Quantity
</th>

<th>
    Total
</th>

<th>
    Status
</th>

<th>
    Date
</th>

<th>
    Action
</th>

</tr>

</thead>


<tbody>


<?php while ($o = $orders->fetch_assoc()): ?>


<?php

$status = strtolower(
    trim($o['status'] ?? 'pending')
);

?>


<tr>


<!-- ORDER -->

<td>

<strong>
    #<?= (int)$o['id'] ?>
</strong>

</td>



<!-- PRODUCE -->

<td>

<strong>

<?= htmlspecialchars(
    $o['produce_name'] ?? 'Produce'
) ?>

</strong>

<br>

<span style="color:#777">

<?= htmlspecialchars(
    $o['location'] ?? ''
) ?>

</span>

</td>



<!-- SELLER -->

<td>

<?= htmlspecialchars(
    $o['seller_name'] ?? 'Farmer'
) ?>

</td>



<!-- QUANTITY -->

<td>

<?= htmlspecialchars(
    $o['quantity'] ?? '0'
) ?>

<?= htmlspecialchars(
    $o['unit'] ?? ''
) ?>

</td>



<!-- TOTAL -->

<td>

<strong>

KSh
<?= number_format(
    (float)($o['total_amount'] ?? 0),
    2
) ?>

</strong>

</td>



<!-- STATUS -->

<td>

<span
    class="badge <?= htmlspecialchars($status) ?>"
>

<?= htmlspecialchars(
    ucfirst($status)
) ?>

</span>

</td>



<!-- DATE -->

<td>

<?= htmlspecialchars(
    $o['created_at'] ?? ''
) ?>

</td>



<!-- ACTION -->

<td>


<?php if ($status === 'accepted'): ?>


<a
    href="payment.php?order_id=<?= (int)$o['id'] ?>"
    class="btn payment"
>
    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Pay Now
</a>


<?php elseif ($status === 'paid'): ?>


<span class="paid-btn">
    ✓ Paid
</span>


<?php elseif ($status === 'completed'): ?>


<span class="paid-btn">
    ✓ Completed
</span>


<?php elseif ($status === 'pending'): ?>


<span style="color:#946200;font-size:13px;">
    Waiting for seller
</span>


<?php elseif ($status === 'rejected'): ?>


<span style="color:#a32121;font-size:13px;">
    Request rejected
</span>


<?php elseif ($status === 'cancelled'): ?>


<span style="color:#a32121;font-size:13px;">
    Cancelled
</span>


<?php endif; ?>


</td>


</tr>


<?php endwhile; ?>


</tbody>


</table>


<?php else: ?>


<div class="empty">

<div style="font-size:40px;">
    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-orders"/></svg>
</div>

<h3>
    No orders yet
</h3>

<p>
    Browse Market Connect and request
    produce when you find something you need.
</p>


<a
    class="btn"
    href="market_connect.php"
>
    Browse Marketplace
</a>


</div>


<?php endif; ?>


</div>

</div>


</main>


<script src="assets/js/vayuma-language.js" defer></script>
</body>

</html>