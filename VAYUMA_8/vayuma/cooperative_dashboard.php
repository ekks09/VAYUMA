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

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Cooperative Dashboard | VAYUMA</title>


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


<div class="va-dash-layout">


<div class="va-sidebar-overlay" id="vaSidebarOverlay"></div>
<!-- SIDEBAR -->

<aside class="va-dash-sidebar" id="vaDashSidebar">


    <div class="va-dash-sidebar--logo">

        <h1>VAYUMA</h1><p>Smart Ecosystem</p>

        <p>SMART ECOSYSTEM</p>

    </div>


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


    <a class="va-dash-sidebar--nav-item" href="ai_advisor.php">
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


    <a class="va-dash-sidebar--nav-item" href="cooperative_profile.php">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-profile"/></svg></span>
        My Profile
    </a>


    <a class="va-dash-sidebar--nav-item" href="logout.php" class="nav-item">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-logout"/></svg></span>
        Logout
    </a>


</aside>


<!-- MAIN -->

<main class="va-dash-main">
    <button type="button" class="va-mobile-menu-btn" aria-controls="vaDashSidebar" aria-expanded="false" aria-label="Open navigation">Menu</button>


    <div class="va-dash-topbar">


        <div class="va-dash-search">
            <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> &nbsp; Search VAYUMA
        </div>


        <div class="va-dash-profile">


            <div class="va-dash-profile-info">

                <strong>
                    <?php
                    echo htmlspecialchars($full_name);
                    ?>
                </strong>

                <small>
                    Cooperative
                </small>

            </div>


            <div class="va-dash-avatar">

                <?php

                echo strtoupper(
                    substr($full_name, 0, 1)
                );

                ?>

            </div>


        </div>


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


            <a class="va-dash-action" href="ai_advisor.php">
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
