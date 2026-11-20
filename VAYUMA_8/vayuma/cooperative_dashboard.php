<?php

session_start();
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/subscription_guard.php';
require_once __DIR__ . '/config/db.php';


/*
|--------------------------------------------------------------------------
| REQUIRE LOGIN
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
| REQUIRE COOPERATIVE ROLE
|--------------------------------------------------------------------------
*/

$role = strtolower(trim($_SESSION['role'] ?? ''));

if ($role !== 'cooperative') {
    header("Location: dashboard.php");
    exit;
}


$full_name = $_SESSION['full_name'] ?? 'Cooperative User';
$userId = (int)$_SESSION['user_id'];
$cooperativeStats = null;
try {
    $statsStmt = $conn->prepare(
        "SELECT
            (SELECT COUNT(*) FROM produce_listings WHERE farmer_id=? AND status='available') AS listing_count,
            (SELECT COUNT(*) FROM orders WHERE seller_id=?) AS order_count,
            (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE seller_id=? AND status='paid') AS sales_total"
    );
    if (!$statsStmt) {
        throw new mysqli_sql_exception('Unable to prepare cooperative dashboard metrics.');
    }
    $statsStmt->bind_param('iii', $userId, $userId, $userId);
    $statsStmt->execute();
    $cooperativeStats = $statsStmt->get_result()->fetch_assoc();
    $statsStmt->close();
} catch (mysqli_sql_exception $exception) {
    error_log('Cooperative dashboard metrics unavailable: ' . $exception->getMessage());
}

?>

<!DOCTYPE html>

<html lang="<?= htmlspecialchars($current_language, ENT_QUOTES, 'UTF-8') ?>">

<head><?php require __DIR__ . '/includes/pwa.php'; ?>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0, viewport-fit=cover"
>

<title>Cooperative Dashboard | VAYUMA</title>


<link rel="stylesheet" href="frontend/css/tokens.css">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
<link rel="stylesheet" href="frontend/css/dashboard-shell.css">
<link rel="stylesheet" href="frontend/css/dashboard-shell-transitions.css">
<style>
</style>
<link rel="stylesheet" href="assets/css/dashboard-layout.css?v=dashboard-ui-4">

</head>


<body>


<div class="va-dash-layout">


<div class="va-sidebar-overlay" id="vaSidebarOverlay"></div>
<!-- SIDEBAR -->

<aside class="va-dash-sidebar" id="vaDashSidebar">


    <?php require __DIR__ . '/includes/dashboard_brand.php'; ?>


    <div class="va-dash-sidebar--section">
        MAIN
    </div>


    <a
        href="cooperative_dashboard.php"
        class="va-dash-sidebar--nav-item va-dash-sidebar--nav-item--active"
    >
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-dashboard"/></svg></span>
        Dashboard
    </a>


    <div class="va-dash-sidebar--section">
        COOPERATIVE
    </div>


    <span class="va-dash-sidebar--nav-item" aria-disabled="true" title="Member management is not available yet">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg></span>
        Members <small>Planned</small>
    </span>


    <a class="va-dash-sidebar--nav-item" href="my_produce.php">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg></span>
        Produce
    </a>


    <span class="va-dash-sidebar--nav-item" aria-disabled="true" title="Cooperative inventory is not available yet">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-orders"/></svg></span>
        Inventory <small>Planned</small>
    </span>


    <a class="va-dash-sidebar--nav-item" href="market_connect.php">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg></span>
        Market Connect
    </a>


    <span class="va-dash-sidebar--nav-item" aria-disabled="true" title="Cooperative transport coordination is not available yet">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-transport"/></svg></span>
        Transport <small>Planned</small>
    </span>


    <div class="va-dash-sidebar--section">
        SERVICES
    </div>


    <a class="va-dash-sidebar--nav-item" href="climate.php">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-weather"/></svg></span>
        Climate Guidance
    </a>


    <a class="va-dash-sidebar--nav-item" href="feature_access.php?feature=ai">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-ai"/></svg></span>
        VAYUMA AI
    </a>


    <span class="va-dash-sidebar--nav-item" aria-disabled="true" title="Cooperative reporting is not available yet">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg></span>
        Reports <small>Planned</small>
    </span>


    <div class="va-dash-sidebar--section">
        ACCOUNT
    </div>


    <a class="va-dash-sidebar--nav-item" href="cooperative_profile.php?from=cooperative_dashboard.php">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-profile"/></svg></span>
        My Profile
    </a>


    <a class="va-dash-sidebar--nav-item" href="auth/logout.php" class="nav-item">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-logout"/></svg></span>
        Logout
    </a>


    <?php vayuma_language_selector(); ?>
</aside>


<!-- MAIN -->

<main class="va-dash-main">



    <div class="va-dash-topbar vy-user-menu__dashboard-header">
    <button type="button" class="va-mobile-menu-btn" aria-controls="vaDashSidebar" aria-expanded="false" aria-label="Open navigation">Menu</button>


        <div class="va-dash-search">
            <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> &nbsp; Search VAYUMA
        </div>


        <?php require __DIR__ . '/includes/header_user_menu.php'; ?>


    </div>


    <div class="va-dash-role-label">
        COOPERATIVE
    </div>


    <h1 class="va-dash-page-title">

        Welcome,
        <?php
        echo htmlspecialchars($full_name);
        ?>

    </h1>


    <p class="va-dash-subtitle">

        Manage cooperative members, agricultural produce,
        market opportunities and farmer services through
        the VAYUMA ecosystem.

    </p>


    <!-- STATS -->

    <div class="va-dash-stats">


        <div class="va-dash-stat">

            <div class="va-dash-stat-icon">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg>
            </div>

            <h3>
                Members
            </h3>

            <div class="number">—</div>
            <div class="description">Member records are not connected yet</div>

        </div>


        <div class="va-dash-stat">

            <div class="va-dash-stat-icon">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg>
            </div>

            <h3>
                Produce
            </h3>

            <div class="number"><?= $cooperativeStats === null ? '—' : number_format((int)$cooperativeStats['listing_count']) ?></div>
            <div class="description"><?= $cooperativeStats === null ? 'Listing data is unavailable' : 'Available listings on your account' ?></div>

        </div>


        <div class="va-dash-stat">

            <div class="va-dash-stat-icon">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg>
            </div>

            <h3>
                Market Orders
            </h3>

            <div class="number"><?= $cooperativeStats === null ? '—' : number_format((int)$cooperativeStats['order_count']) ?></div>
            <div class="description"><?= $cooperativeStats === null ? 'Order data is unavailable' : 'Orders for your listings' ?></div>

        </div>


        <div class="va-dash-stat">

            <div class="va-dash-stat-icon">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-money"/></svg>
            </div>

            <h3>
                Sales
            </h3>

            <div class="number"><?= $cooperativeStats === null ? '—' : 'KSh ' . number_format((float)$cooperativeStats['sales_total'], 2) ?></div>
            <div class="description"><?= $cooperativeStats === null ? 'Payment data is unavailable' : 'Verified paid orders' ?></div>

        </div>


    </div>


    <!-- CONTENT -->

    <div class="va-dash-content-grid">


        <div class="va-dash-panel">


            <h2>
                Cooperative Services
            </h2>


            <div class="va-dash-feature">

                <div class="va-dash-feature-icon">
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg>
                </div>

                <div>

                    <strong>
                        Member Management
                    </strong>

                    <span>
                        Manage farmers and members associated
                        with your cooperative.
                    </span>

                </div>

            </div>


            <div class="va-dash-feature">

                <div class="va-dash-feature-icon">
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg>
                </div>

                <div>

                    <strong>
                        Produce Management
                    </strong>

                    <span>
                        Record and manage agricultural produce
                        available from cooperative members.
                    </span>

                </div>

            </div>


            <div class="va-dash-feature">

                <div class="va-dash-feature-icon">
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg>
                </div>

                <div>

                    <strong>
                        Market Connect
                    </strong>

                    <span>
                        Connect cooperative produce with verified
                        buyers through VAYUMA.
                    </span>

                </div>

            </div>


            <div class="va-dash-feature">

                <div class="va-dash-feature-icon">
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-transport"/></svg>
                </div>

                <div>

                    <strong>
                        Transport Coordination
                    </strong>

                    <span>
                        Organize transportation of produce from
                        farms to markets and buyers.
                    </span>

                </div>

            </div>


            <div class="va-dash-feature">

                <div class="va-dash-feature-icon">
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg>
                </div>

                <div>

                    <strong>
                        Cooperative Reports
                    </strong>

                    <span>
                        Track production, members, sales and
                        market performance.
                    </span>

                </div>

            </div>


            <div class="va-dash-info">

                <h3>
                    VAYUMA Cooperative Network
                </h3>

                <p>
                    VAYUMA helps cooperatives aggregate farmer
                    produce, access markets, coordinate logistics
                    and improve agricultural decision-making.
                </p>

            </div>


        </div>


        <!-- QUICK ACTIONS -->

        <div class="va-dash-panel">


            <h2>
                Quick Actions
            </h2>


            <span class="va-dash-action" aria-disabled="true" title="Member management is not available yet">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> &nbsp; Manage Members
                <small>Planned</small>
            </span>


            <a class="va-dash-action" href="add_produce.php">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> &nbsp; Add Produce
            </a>


            <a class="va-dash-action" href="market_connect.php">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg> &nbsp; Find Buyers
            </a>


            <span class="va-dash-action" aria-disabled="true" title="Transport coordination is not available yet">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-transport"/></svg> &nbsp; Arrange Transport
                <small>Planned</small>
            </span>


            <span class="va-dash-action" aria-disabled="true" title="Cooperative reporting is not available yet">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> &nbsp; View Reports
                <small>Planned</small>
            </span>


            <a class="va-dash-action" href="feature_access.php?feature=ai">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-ai"/></svg> &nbsp; Ask VAYUMA AI
            </a>


        </div>


    </div>


</main>


</div>


<script src="frontend/js/vayuma-ui.js" defer></script>
<script src="assets/js/vayuma-language.js" defer></script>
</body>

</html>
