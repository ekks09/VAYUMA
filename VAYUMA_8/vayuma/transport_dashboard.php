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

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Transport Dashboard | VAYUMA</title>


<link rel="stylesheet" href="frontend/css/tokens.css">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
<style>
.va-dash-sidebar { width: 260px; background: var(--va-surface); border-right: 1px solid var(--va-border); position: fixed; left: 0; top: 0; bottom: 0; padding: var(--va-space-5); overflow-y: auto; z-index: 100; }
.va-dash-sidebar--logo { padding: 0 var(--va-space-4) var(--va-space-6); }
.va-dash-sidebar--logo h1 { color: var(--va-primary); font-size: var(--va-font-size-xl); font-weight: var(--va-font-weight-bold); letter-spacing: 0.05em; margin: 0; }
.va-dash-sidebar--logo p { color: var(--va-text-muted); font-size: var(--va-font-size-xs); letter-spacing: 0.05em; margin-top: var(--va-space-1); }
.va-dash-sidebar--section { font-size: var(--va-font-size-xs); font-weight: var(--va-font-weight-bold); text-transform: uppercase; letter-spacing: 0.1em; color: var(--va-text-muted); margin: var(--va-space-6) var(--va-space-4) var(--va-space-2); }
.va-dash-sidebar--nav-item { display: flex; align-items: center; gap: var(--va-space-3); padding: var(--va-space-3) var(--va-space-4); margin: var(--va-space-1) 0; border-radius: var(--va-radius-md); text-decoration: none; color: var(--va-text-secondary); font-size: var(--va-font-size-sm); transition: color var(--va-transition), background-color var(--va-transition), border-color var(--va-transition); }
.va-dash-sidebar--nav-item:hover, .va-dash-sidebar--nav-item--active { background: var(--va-primary-light); color: var(--va-primary); }
.va-dash-sidebar--nav-item--active { font-weight: var(--va-font-weight-semibold); }
.va-dash-sidebar--icon { width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: var(--va-font-size-sm); flex-shrink: 0; }
.va-dash-main { margin-left: 260px; padding: var(--va-space-6); }
.va-dash-topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--va-space-6); }
.va-dash-search { width: 340px; background: var(--va-surface); border: 1px solid var(--va-border); border-radius: var(--va-radius-md); padding: var(--va-space-3) var(--va-space-4); color: var(--va-text-muted); font-size: var(--va-font-size-sm); }
.va-dash-profile { display: flex; align-items: center; gap: var(--va-space-3); }
.va-dash-profile-info { text-align: right; }
.va-dash-profile-info strong { display: block; font-size: var(--va-font-size-sm); color: var(--va-text-primary); }
.va-dash-profile-info small { color: var(--va-text-muted); font-size: var(--va-font-size-xs); }
.va-dash-avatar { width: 44px; height: 44px; border-radius: 50%; background: var(--va-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: var(--va-font-weight-bold); font-size: var(--va-font-size-sm); }
.va-dash-role-label { color: var(--va-primary); font-size: var(--va-font-size-xs); font-weight: var(--va-font-weight-bold); letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: var(--va-space-2); }
.va-dash-page-title { font-size: var(--va-font-size-3xl); margin-bottom: var(--va-space-2); color: var(--va-text-primary); }
.va-dash-subtitle { color: var(--va-text-secondary); font-size: var(--va-font-size-base); line-height: var(--va-line-height-relaxed); margin-bottom: var(--va-space-6); }
.va-dash-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--va-space-4); margin-bottom: var(--va-space-6); }
.va-dash-stat { background: var(--va-surface); border: 1px solid var(--va-border); border-radius: var(--va-radius-lg); padding: var(--va-space-5); }
.va-dash-stat-icon { font-size: var(--va-font-size-xl); margin-bottom: var(--va-space-3); }
.va-dash-stat h3 { color: var(--va-text-muted); font-size: var(--va-font-size-xs); margin-bottom: var(--va-space-2); font-weight: var(--va-font-weight-semibold); text-transform: uppercase; letter-spacing: 0.05em; }
.va-dash-number { color: var(--va-primary); font-size: var(--va-font-size-2xl); font-weight: var(--va-font-weight-bold); }
.va-dash-description { color: var(--va-text-muted); font-size: var(--va-font-size-xs); margin-top: var(--va-space-1); }
.va-dash-content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: var(--va-space-6); }
.va-dash-panel { background: var(--va-surface); border: 1px solid var(--va-border); border-radius: var(--va-radius-lg); padding: var(--va-space-5); }
.va-dash-panel h2 { font-size: var(--va-font-size-lg); margin-bottom: var(--va-space-4); color: var(--va-text-primary); }
.va-dash-feature { display: flex; align-items: center; gap: var(--va-space-3); padding: var(--va-space-3) 0; border-bottom: 1px solid var(--va-border-light); }
.va-dash-feature:last-child { border-bottom: none; }
.va-dash-feature-icon { width: 40px; height: 40px; border-radius: var(--va-radius-md); background: var(--va-primary-light); color: var(--va-primary); display: flex; align-items: center; justify-content: center; font-size: var(--va-font-size-sm); }
.va-dash-feature strong { display: block; margin-bottom: var(--va-space-1); color: var(--va-text-primary); font-size: var(--va-font-size-sm); }
.va-dash-feature span { color: var(--va-text-secondary); font-size: var(--va-font-size-xs); line-height: var(--va-line-height-relaxed); }
.va-dash-info { margin-top: var(--va-space-5); padding: var(--va-space-4); background: var(--va-primary-light); border-radius: var(--va-radius-md); }
.va-dash-info h3 { color: var(--va-primary); margin-bottom: var(--va-space-2); font-size: var(--va-font-size-sm); }
.va-dash-info p { color: var(--va-text-secondary); font-size: var(--va-font-size-xs); line-height: var(--va-line-height-relaxed); }
.va-dash-action { display: flex; align-items: center; gap: var(--va-space-3); padding: var(--va-space-3); margin-bottom: var(--va-space-2); border: 1px solid var(--va-border); border-radius: var(--va-radius-md); text-decoration: none; color: var(--va-text-secondary); font-size: var(--va-font-size-sm); transition: color var(--va-transition), background-color var(--va-transition), border-color var(--va-transition); }
.va-dash-action:hover { background: var(--va-primary-light); color: var(--va-primary); border-color: var(--va-primary); }
@media (max-width: 800px) { .va-dash-sidebar { width: 220px; } .va-dash-main { margin-left: 220px; } }
@media (max-width: 650px) { .va-dash-sidebar { position: fixed; left: -260px; transition: left 0.3s; box-shadow: var(--va-shadow-lg); z-index: 200; } .va-dash-sidebar--open { left: 0; } .va-dash-main { margin-left: 0; width: 100%; padding: var(--va-space-4); } .va-dash-stats { grid-template-columns: 1fr; } }
</style>

</head>


<body>
<?php vayuma_language_selector(); ?>


<div class="va-sidebar-overlay" id="vaSidebarOverlay"></div>
<!-- SIDEBAR -->

<aside class="va-dash-sidebar" id="vaDashSidebar">

    <div class="va-dash-sidebar--logo">

        <h1>VAYUMA</h1><p>Smart Ecosystem</p>

        <p>SMART ECOSYSTEM</p>

    </div>


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

        <a class="va-dash-sidebar--nav-item" href="transport_profile.php">
            <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-profile"/></svg> My Profile
        </a>

        <a class="va-dash-sidebar--nav-item" href="logout.php">
            <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-logout"/></svg> Logout
        </a>

    </div>

</aside>


<!-- MAIN CONTENT -->

<main class="va-dash-main">
    <button type="button" class="va-mobile-menu-btn" aria-controls="vaDashSidebar" aria-expanded="false" aria-label="Open navigation">Menu</button>


    <div class="va-dash-topbar">

        <div class="va-dash-search">
            <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Search VAYUMA
        </div>

        <div>
            <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-transport"/></svg> Transport Provider
        </div>

    </div>


    <section class="welcome">

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

    <section class="cards">

        <div class="card">

            <div class="icon">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-transport"/></svg>
            </div>

            <h3>
                Available Requests
            </h3>

            <p>
                Find farmers and buyers who need
                reliable transportation.
            </p>

        </div>


        <div class="card">

            <div class="icon">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg>
            </div>

            <h3>
                Active Deliveries
            </h3>

            <p>
                Track your current agricultural
                deliveries.
            </p>

        </div>


        <div class="card">

            <div class="icon">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-money"/></svg>
            </div>

            <h3>
                Earnings
            </h3>

            <p>
                Monitor your transportation
                earnings and completed jobs.
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


<script src="frontend/js/vayuma-ui.js" defer></script>
<script src="assets/js/vayuma-language.js" defer></script>
</body>

</html>
