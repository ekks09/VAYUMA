<?php

session_start();
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/subscription_guard.php';


/*
|--------------------------------------------------------------------------
| PROTECT DASHBOARD
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true
) {

    header("Location: login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| CHECK ROLE
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'transport_provider'
) {

    header("Location: dashboard.php");
    exit;
}


$full_name = $_SESSION['full_name'] ?? "Transport Provider";

?>

<!DOCTYPE html>

<html lang="<?= htmlspecialchars($current_language, ENT_QUOTES, 'UTF-8') ?>">

<head><?php require __DIR__ . '/includes/pwa.php'; ?>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0, viewport-fit=cover"
>

<title>Transport Dashboard | VAYUMA</title>


<link rel="stylesheet" href="frontend/css/tokens.css">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
<link rel="stylesheet" href="frontend/css/dashboard-shell.css">
<link rel="stylesheet" href="frontend/css/dashboard-shell-transitions.css">
<style>
</style>
<link rel="stylesheet" href="assets/css/dashboard-layout.css?v=dashboard-ui-5">

</head>


<body>
<div class="va-dash-layout">


<div class="va-sidebar-overlay" id="vaSidebarOverlay"></div>
<!-- SIDEBAR -->

<aside class="va-dash-sidebar" id="vaDashSidebar">

    <?php require __DIR__ . '/includes/dashboard_brand.php'; ?>


    <div class="menu-title">
        MAIN
    </div>

    <div class="menu">

        <a class="va-dash-sidebar--nav-item va-dash-sidebar--nav-item--active" href="transport_dashboard.php" aria-current="page">
            <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-dashboard"/></svg> Dashboard
        </a>

        <a class="va-dash-sidebar--nav-item" href="transport_deliveries.php">
            <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-orders"/></svg> Transport Requests
        </a>

        <span class="va-dash-sidebar--nav-item" aria-disabled="true" title="Vehicle management is not available yet">
            <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-transport"/></svg> My Vehicles
            <small>Planned</small>
        </span>

        <span class="va-dash-sidebar--nav-item" aria-disabled="true" title="Delivery status management is not available yet">
            <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Active Deliveries
            <small>Planned</small>
        </span>

        <span class="va-dash-sidebar--nav-item" aria-disabled="true" title="Delivery history is not available yet">
            <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Delivery History
            <small>Planned</small>
        </span>

    </div>


    <div class="menu-title">
        SERVICES
    </div>

    <div class="menu">

        <span class="va-dash-sidebar--nav-item" aria-disabled="true" title="Farmer directory is not available yet">
            <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> Farmers
            <small>Planned</small>
        </span>

        <span class="va-dash-sidebar--nav-item" aria-disabled="true" title="Buyer directory is not available yet">
            <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg> Buyers
            <small>Planned</small>
        </span>

        <span class="va-dash-sidebar--nav-item" aria-disabled="true" title="Earnings tracking is not available yet">
            <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Earnings
            <small>Planned</small>
        </span>

    </div>


    <div class="menu-title">
        ACCOUNT
    </div>

    <div class="menu">

        <a class="va-dash-sidebar--nav-item" href="transport_profile.php?from=transport_dashboard.php">
            <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-profile"/></svg> My Profile
        </a>



    </div>

    <?php vayuma_language_selector(); ?>

    <div class="va-sidebar--bottom" aria-label="Account utilities">
      <a class="va-dash-sidebar--nav-item" href="subscription.php"><svg class="va-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg#va-icon-subscription"/></svg> <span>Subscription</span></a>
      <a class="va-dash-sidebar--nav-item" href="auth/logout.php">
            <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-logout"/></svg> Logout
        </a>
    </div>
</aside>


<!-- MAIN CONTENT -->

<main class="va-dash-main">



    <div class="va-dash-topbar vy-user-menu__dashboard-header">
    <button type="button" class="va-mobile-menu-btn" aria-controls="vaDashSidebar" aria-expanded="false" aria-label="Open navigation">Menu</button>

        <div class="va-dash-search">
            <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Search VAYUMA · Planned
        </div>

        <?php require __DIR__ . '/includes/header_user_menu.php'; ?>

    </div>


    <section class="welcome va-transport-welcome">

        <small>
            TRANSPORT PROVIDER DASHBOARD
        </small>

        <h2>
            Good day,
            <?php echo htmlspecialchars($full_name); ?>

        </h2>

        <p>
            Connect with farmers and buyers and manage
            agricultural deliveries through VAYUMA.
        </p>

    </section>


    <!-- SUMMARY CARDS -->

    <section class="va-dash-stats va-transport-cards">

        <div class="va-dash-stat">

            <div class="va-dash-stat-icon">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-transport"/></svg>
            </div>

            <h3>
                Available Requests
            </h3>

            <p>
                Browse available transport requests.
            </p>
            <a href="transport_deliveries.php">View transport requests →</a>

        </div>


        <div class="va-dash-stat">

            <div class="va-dash-stat-icon">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg>
            </div>

            <h3>
                Active Deliveries
            </h3>

            <p>
                Active delivery tracking is planned and not available yet.
            </p>

        </div>


        <div class="va-dash-stat">

            <div class="va-dash-stat-icon">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-money"/></svg>
            </div>

            <h3>
                Earnings
            </h3>

            <p>
                Earnings and delivery history are planned and not available yet.
            </p>

        </div>

    </section>


    <!-- SERVICES -->

    <section class="section">

        <h2>
            Transport Services
        </h2>


        <div class="service-grid">


            <div class="service">

                <h3>
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> Farm to Market
                </h3>

                <p>
                    Help farmers transport their
                    produce to local markets.
                </p>

            </div>


            <div class="service">

                <h3>
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-tractor"/></svg> Farm to Buyer
                </h3>

                <p>
                    Deliver agricultural products
                    directly to buyers.
                </p>

            </div>


            <div class="service">

                <h3>
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-factory"/></svg> Farm to Warehouse
                </h3>

                <p>
                    Transport harvested produce
                    to storage facilities.
                </p>

            </div>


            <div class="service">

                <h3>
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-orders"/></svg> General Agricultural Transport
                </h3>

                <p>
                    Provide flexible transportation
                    services across the agricultural
                    ecosystem.
                </p>

            </div>


        </div>

    </section>


</main>
</div>


<script src="frontend/js/vayuma-ui.js" defer></script>
<script src="assets/js/vayuma-language.js" defer></script>
</body>

</html>
