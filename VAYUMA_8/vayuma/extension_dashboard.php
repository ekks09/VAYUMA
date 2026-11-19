<?php
session_start();
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/subscription_guard.php';

/*
|--------------------------------------------------------------------------
| EXTENSION OFFICER DASHBOARD
|--------------------------------------------------------------------------
*/

/* Check login */
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true
) {
    header("Location: login.php");
    exit;
}

/* Check role */
$role = strtolower(trim($_SESSION['role'] ?? ''));

if ($role !== 'extension_officer') {
    header("Location: dashboard.php");
    exit;
}

/* User information */
$full_name = $_SESSION['full_name'] ?? 'Extension Officer';

?>
<!DOCTYPE html>
<html lang="en">

<head><?php require __DIR__ . '/includes/pwa.php'; ?>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Extension Officer Dashboard | VAYUMA</title>

<link rel="stylesheet" href="frontend/css/tokens.css">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
<link rel="stylesheet" href="frontend/css/dashboard-shell.css">
<style>
</style>

</head>

<body>
<?php vayuma_language_selector(); ?>
<div style="max-width:1180px;margin:12px auto;padding:12px 18px;background:#eaf6ed;border-radius:10px;display:flex;gap:18px;flex-wrap:wrap"><a class="va-dash-sidebar--nav-item" style="color:#087d42;font-weight:700" href="extension_profile.php?from=extension_dashboard.php">Edit extension profile</a><a class="va-dash-sidebar--nav-item" style="color:#087d42;font-weight:700" href="agricultural_support_requests.php?view=received">Manage farmer support requests</a><a class="va-dash-sidebar--nav-item" style="color:#087d42;font-weight:700" href="agricultural_support_requests.php?view=sent">My sent requests</a></div>

<div class="va-dash-layout">


<!-- =====================================================
     SIDEBAR
====================================================== -->

<aside class="va-dash-sidebar" id="vaDashSidebar">

    <div class="va-dash-sidebar--logo">

        <h1>VAYUMA</h1><p>Smart Ecosystem</p>

        <p>SMART ECOSYSTEM</p>

    </div>


    <div class="va-dash-sidebar--section">
        MAIN
    </div>


    <a
        href="extension_dashboard.php"
        class="va-dash-sidebar--nav-item va-dash-sidebar--nav-item--active"
    >
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-dashboard"/></svg></span>
        Dashboard
    </a>


    <div class="va-dash-sidebar--section">
        FARMER SUPPORT
    </div>


    <a class="va-dash-sidebar--nav-item" href="extension_farmers.php" class="nav-item">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg></span>
        Farmers
    </a>


    <a class="va-dash-sidebar--nav-item" href="extension_visits.php" class="nav-item">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg></span>
        Farm Visits
    </a>


    <a class="va-dash-sidebar--nav-item" href="extension_reports.php" class="nav-item">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg></span>
        Farm Reports
    </a>


    <a class="va-dash-sidebar--nav-item" href="ai_advisor.php" class="nav-item">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg></span>
        Crop Advisory
    </a>


    <a class="va-dash-sidebar--nav-item" href="disease_helper.php" class="nav-item">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-diseases"/></svg></span>
        Disease & Pest Help
    </a>


    <div class="va-dash-sidebar--section">
        KNOWLEDGE
    </div>


    <a class="va-dash-sidebar--nav-item" href="climate.php" class="nav-item">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-weather"/></svg></span>
        Climate Guidance
    </a>


    <a class="va-dash-sidebar--nav-item" href="extension_resources.php" class="nav-item">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg></span>
        Farming Resources
    </a>


    <a class="va-dash-sidebar--nav-item" href="ai_advisor.php" class="nav-item">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-ai"/></svg></span>
        VAYUMA AI
    </a>


    <div class="va-dash-sidebar--section">
        ACCOUNT
    </div>


    <a class="va-dash-sidebar--nav-item" href="extension_profile.php?from=extension_dashboard.php" class="nav-item">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-profile"/></svg></span>
        My Profile
    </a>


    <a class="va-dash-sidebar--nav-item" href="auth/logout.php" class="nav-item">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-logout"/></svg></span>
        Logout
    </a>

</aside>


<!-- =====================================================
     MAIN
====================================================== -->

<main class="va-dash-main">



    <!-- TOP BAR -->

    <div class="va-dash-topbar vy-user-menu__dashboard-header">
    <button type="button" class="va-mobile-menu-btn" aria-controls="vaDashSidebar" aria-expanded="false" aria-label="Open navigation">Menu</button>

        <div class="va-dash-search">
            <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> &nbsp; Search VAYUMA
        </div>


        <?php require __DIR__ . '/includes/header_user_menu.php'; ?>

    </div>


    <!-- PAGE HEADER -->

    <div class="va-dash-role-label">
        AGRICULTURAL EXTENSION OFFICER
    </div>


    <h1 class="va-dash-page-title">

        Good day,
        <?php echo htmlspecialchars($full_name); ?>
        

    </h1>


    <p class="va-dash-subtitle">

        Support farmers with practical agricultural knowledge,
        field visits, crop advice and climate-smart farming
        guidance through VAYUMA.

    </p>


    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="va-dash-stats">


        <div class="va-dash-stat">

            <div class="va-dash-stat-icon">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg>
            </div>

            <h3>
                Farmers Supported
            </h3>

            <div class="stat-number">
                0
            </div>

            <div class="stat-description">
                Farmers under your support
            </div>

        </div>


        <div class="va-dash-stat">

            <div class="va-dash-stat-icon">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg>
            </div>

            <h3>
                Farm Visits
            </h3>

            <div class="stat-number">
                0
            </div>

            <div class="stat-description">
                Scheduled visits
            </div>

        </div>


        <div class="va-dash-stat">

            <div class="va-dash-stat-icon">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg>
            </div>

            <h3>
                Farm Reports
            </h3>

            <div class="stat-number">
                0
            </div>

            <div class="stat-description">
                Reports submitted
            </div>

        </div>


        <div class="va-dash-stat">

            <div class="va-dash-stat-icon">
                💬
            </div>

            <h3>
                Farmer Advisories
            </h3>

            <div class="stat-number">
                0
            </div>

            <div class="stat-description">
                Advice sessions
            </div>

        </div>

    </div>


    <!-- =================================================
         LOWER CONTENT
    ================================================== -->

    <div class="va-dash-content-grid">


        <!-- ACTIVITIES -->

        <div class="va-dash-panel">

            <h2 class="va-dash-panel h2">
                Extension Activities
            </h2>


            <div class="va-dash-feature">

                <div class="va-dash-feature-icon">
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg>
                </div>

                <div class="activity-content">

                    <strong>
                        Farmer Support
                    </strong>

                    <span>
                        Connect with farmers and provide
                        personalized agricultural guidance.
                    </span>

                </div>

            </div>


            <div class="va-dash-feature">

                <div class="va-dash-feature-icon">
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg>
                </div>

                <div class="activity-content">

                    <strong>
                        Crop Advisory
                    </strong>

                    <span>
                        Recommend suitable crops, planting
                        practices and crop management methods.
                    </span>

                </div>

            </div>


            <div class="va-dash-feature">

                <div class="va-dash-feature-icon">
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-diseases"/></svg>
                </div>

                <div class="activity-content">

                    <strong>
                        Disease & Pest Assistance
                    </strong>

                    <span>
                        Help farmers identify crop diseases,
                        pests and appropriate management practices.
                    </span>

                </div>

            </div>


            <div class="va-dash-feature">

                <div class="va-dash-feature-icon">
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-weather"/></svg>
                </div>

                <div class="activity-content">

                    <strong>
                        Climate-Smart Guidance
                    </strong>

                    <span>
                        Help farmers make decisions based on
                        climate and weather information.
                    </span>

                </div>

            </div>


            <div class="va-dash-feature">

                <div class="va-dash-feature-icon">
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg>
                </div>

                <div class="activity-content">

                    <strong>
                        Field Reports
                    </strong>

                    <span>
                        Record farm observations, challenges
                        and recommendations.
                    </span>

                </div>

            </div>


            <div class="support-box">

                <h3>
                    VAYUMA Mission
                </h3>

                <p>
                    Help connect agricultural knowledge with
                    farmers so they can make better,
                    climate-smart and profitable decisions.
                </p>

            </div>

        </div>


        <!-- QUICK ACTIONS -->

        <div class="va-dash-panel">

            <h2 class="va-dash-panel h2">
                Quick Actions
            </h2>


            <a class="va-dash-sidebar--nav-item" href="extension_farmers.php" class="quick-action">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> &nbsp; View / Find Farmers
            </a>


            <a class="va-dash-sidebar--nav-item" href="extension_visits.php" class="quick-action">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> &nbsp; Schedule Farm Visit
            </a>


            <a class="va-dash-sidebar--nav-item" href="extension_reports.php" class="quick-action">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> &nbsp; Create Farm Report
            </a>


            <a class="va-dash-sidebar--nav-item" href="ai_advisor.php" class="quick-action">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> &nbsp; Give Crop Advice
            </a>


            <a class="va-dash-sidebar--nav-item" href="disease_helper.php" class="quick-action">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-diseases"/></svg> &nbsp; Open Disease & Pest Helper
            </a>


            <a class="va-dash-sidebar--nav-item" href="ai_advisor.php" class="quick-action">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-ai"/></svg> &nbsp; Ask VAYUMA AI
            </a>

        </div>

    </div>

</main>

</div>

<script src="assets/js/vayuma-language.js" defer></script>
<script src="frontend/js/vayuma-ui.js" defer></script>
</body>

</html>
