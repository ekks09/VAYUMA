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
| REQUIRE FINANCIAL INSTITUTION ROLE
|--------------------------------------------------------------------------
*/

$role = strtolower(trim($_SESSION['role'] ?? ''));

if ($role !== 'financial_institution') {
    header("Location: dashboard.php");
    exit;
}


$full_name = $_SESSION['full_name'] ?? 'Financial Institution';
$verifiedCooperatives = null;
try {
    $cooperativeResult = $conn->query("SELECT COUNT(*) AS total FROM stakeholder_profiles WHERE role='cooperative' AND verification_status='verified'");
    if (!$cooperativeResult) {
        throw new mysqli_sql_exception('Unable to count verified cooperative partners.');
    }
    $verifiedCooperatives = $cooperativeResult->fetch_assoc()['total'] ?? null;
} catch (mysqli_sql_exception $exception) {
    error_log('Verified cooperative count unavailable to finance dashboard: ' . $exception->getMessage());
}

?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_language, ENT_QUOTES, 'UTF-8') ?>">

<head><?php require __DIR__ . '/includes/pwa.php'; ?>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Financial Dashboard | VAYUMA</title>


<link rel="stylesheet" href="frontend/css/tokens.css">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
<link rel="stylesheet" href="frontend/css/dashboard-shell.css">
<link rel="stylesheet" href="frontend/css/dashboard-shell-transitions.css">
<style>
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
        href="finance_dashboard.php"
        class="va-dash-sidebar--nav-item va-dash-sidebar--nav-item--active"
    >
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-dashboard"/></svg></span>
        Dashboard
    </a>


    <div class="va-dash-sidebar--section">
        AGRICULTURAL FINANCE
    </div>


    <a class="va-dash-sidebar--nav-item" href="stakeholder_directory.php">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg></span>
        Partner Directory
    </a>


    <span class="va-dash-sidebar--nav-item" aria-disabled="true" title="Loan request management is not available yet">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-bank"/></svg></span>
        Loan Requests <small>Planned</small>
    </span>


    <span class="va-dash-sidebar--nav-item" aria-disabled="true" title="Financing management is not available yet">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-money"/></svg></span>
        Financing <small>Planned</small>
    </span>


    <span class="va-dash-sidebar--nav-item" aria-disabled="true" title="Insurance management is not available yet">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg></span>
        Insurance <small>Planned</small>
    </span>


    <span class="va-dash-sidebar--nav-item" aria-disabled="true" title="Financing portfolio is not available yet">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg></span>
        Portfolio <small>Planned</small>
    </span>


    <div class="va-dash-sidebar--section">
        VAYUMA NETWORK
    </div>


    <a class="va-dash-sidebar--nav-item" href="stakeholder_directory.php">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg></span>
        Partner Directory
    </a>


    <a class="va-dash-sidebar--nav-item" href="market_connect.php">
        <span class="va-dash-sidebar--icon">📈</span>
        Marketplace
    </a>


    <a class="va-dash-sidebar--nav-item" href="ai_advisor.php">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-ai"/></svg></span>
        VAYUMA AI
    </a>


    <div class="va-dash-sidebar--section">
        ACCOUNT
    </div>


    <a class="va-dash-sidebar--nav-item" href="finance_profile.php?from=finance_dashboard.php">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-profile"/></svg></span>
        My Profile
    </a>


    <a class="va-dash-sidebar--nav-item" href="auth/logout.php" class="nav-item">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-logout"/></svg></span>
        Logout
    </a>


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
        FINANCIAL INSTITUTION
    </div>


    <h1 class="va-dash-page-title">

        Welcome,
        <?php
        echo htmlspecialchars($full_name);
        ?>

    </h1>


    <p class="va-dash-subtitle">

        Connect agricultural finance with farmers,
        cooperatives and businesses across the VAYUMA
        ecosystem.

    </p>


    <!-- STATS -->

    <div class="va-dash-stats">


        <div class="va-dash-stat">

            <div class="va-dash-stat-icon">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg>
            </div>

            <h3>
                Farmer Applications
            </h3>

            <div class="number">—</div>
            <div class="description">Financing records are not connected yet</div>

        </div>


        <div class="va-dash-stat">

            <div class="va-dash-stat-icon">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-money"/></svg>
            </div>

            <h3>
                Active Financing
            </h3>

            <div class="number">—</div>
            <div class="description">Financing records are not connected yet</div>

        </div>


        <div class="va-dash-stat">

            <div class="va-dash-stat-icon">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg>
            </div>

            <h3>
                Insurance
            </h3>

            <div class="number">—</div>
            <div class="description">Insurance records are not connected yet</div>

        </div>


        <div class="va-dash-stat">

            <div class="va-dash-stat-icon">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-bank"/></svg>
            </div>

            <h3>
                Cooperatives
            </h3>

            <div class="number"><?= $verifiedCooperatives === null ? '—' : number_format((int)$verifiedCooperatives) ?></div>
            <div class="description"><?= $verifiedCooperatives === null ? 'Partner data is unavailable' : 'Verified cooperative partners' ?></div>

        </div>


    </div>


    <!-- CONTENT -->

    <div class="va-dash-content-grid">


        <div class="va-dash-panel">


            <h2>
                Agricultural Finance Services
            </h2>


            <div class="service">

                <div class="service-icon">
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg>
                </div>

                <div>

                    <strong>
                        Farmer Financing
                    </strong>

                    <span>
                        Review financing opportunities and
                        support farmers with agricultural credit.
                    </span>

                </div>

            </div>


            <div class="service">

                <div class="service-icon">
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-bank"/></svg>
                </div>

                <div>

                    <strong>
                        Loan Management
                    </strong>

                    <span>
                        Manage agricultural loan applications
                        and financing decisions.
                    </span>

                </div>

            </div>


            <div class="service">

                <div class="service-icon">
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg>
                </div>

                <div>

                    <strong>
                        Agricultural Insurance
                    </strong>

                    <span>
                        Provide farmers with agricultural
                        insurance and risk-management solutions.
                    </span>

                </div>

            </div>


            <div class="service">

                <div class="service-icon">
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg>
                </div>

                <div>

                    <strong>
                        Cooperative Financing
                    </strong>

                    <span>
                        Connect registered cooperatives with
                        suitable agricultural finance options.
                    </span>

                </div>

            </div>


            <div class="service">

                <div class="service-icon">
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg>
                </div>

                <div>

                    <strong>
                        Agricultural Risk Insights
                    </strong>

                    <span>
                        Use VAYUMA agricultural and market
                        information to support better financing
                        decisions.
                    </span>

                </div>

            </div>


            <div class="va-dash-info">

                <h3>
                    VAYUMA Agricultural Finance
                </h3>

                <p>
                    The financial layer of VAYUMA is designed
                    to connect farmers and agricultural
                    organizations with appropriate financing,
                    insurance and financial services.
                </p>

            </div>


        </div>


        <!-- QUICK ACTIONS -->

        <div class="va-dash-panel">


            <h2>
                Quick Actions
            </h2>


            <a class="va-dash-action" href="stakeholder_directory.php">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> &nbsp; Browse Partner Directory
            </a>


            <span class="va-dash-action" aria-disabled="true" title="Loan request management is not available yet">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-bank"/></svg> &nbsp; Review Loan Requests
                <small>Planned</small>
            </span>


            <span class="va-dash-action" aria-disabled="true" title="Financing management is not available yet">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-money"/></svg> &nbsp; Manage Financing
                <small>Planned</small>
            </span>


            <span class="va-dash-action" aria-disabled="true" title="Insurance management is not available yet">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> &nbsp; Manage Insurance
                <small>Planned</small>
            </span>


            <span class="va-dash-action" aria-disabled="true" title="Finance reports are not available yet">
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
