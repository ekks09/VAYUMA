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

<head>

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
<style>
.va-dash-sidebar { width: 260px; background: var(--va-surface); border-right: 1px solid var(--va-border); position: fixed; left: 0; top: 0; bottom: 0; padding: var(--va-space-5); overflow-y: auto; z-index: 100; }
.va-dash-sidebar--logo { padding: 0 var(--va-space-4) var(--va-space-6); }
.va-dash-sidebar--logo h1 { color: var(--va-primary); font-size: var(--va-font-size-xl); font-weight: var(--va-font-weight-bold); letter-spacing: 0.05em; margin: 0; }
.va-dash-sidebar--logo p { color: var(--va-text-muted); font-size: var(--va-font-size-xs); letter-spacing: 0.05em; margin-top: var(--va-space-1); }
.va-dash-sidebar--section { font-size: var(--va-font-size-xs); font-weight: var(--va-font-weight-bold); text-transform: uppercase; letter-spacing: 0.1em; color: var(--va-text-muted); margin: var(--va-space-6) var(--va-space-4) var(--va-space-2); }
.va-dash-sidebar--nav-item { display: flex; align-items: center; gap: var(--va-space-3); padding: var(--va-space-3) var(--va-space-4); margin: var(--va-space-1) 0; border-radius: var(--va-radius-md); text-decoration: none; color: var(--va-text-secondary); font-size: var(--va-font-size-sm); transition: all var(--va-transition); }
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
.va-dash-action { display: flex; align-items: center; gap: var(--va-space-3); padding: var(--va-space-3); margin-bottom: var(--va-space-2); border: 1px solid var(--va-border); border-radius: var(--va-radius-md); text-decoration: none; color: var(--va-text-secondary); font-size: var(--va-font-size-sm); transition: all var(--va-transition); }
.va-dash-action:hover { background: var(--va-primary-light); color: var(--va-primary); border-color: var(--va-primary); }
@media (max-width: 800px) { .va-dash-sidebar { width: 220px; } .va-dash-main { margin-left: 220px; } }
@media (max-width: 650px) { .va-dash-sidebar { position: fixed; left: -260px; transition: left 0.3s; box-shadow: var(--va-shadow-lg); z-index: 200; } .va-dash-sidebar--open { left: 0; } .va-dash-main { margin-left: 0; width: 100%; padding: var(--va-space-4); } .va-dash-stats { grid-template-columns: 1fr; } }
</style>

</head>

<body>
<?php vayuma_language_selector(); ?>
<div style="max-width:1180px;margin:12px auto;padding:12px 18px;background:#eaf6ed;border-radius:10px;display:flex;gap:18px;flex-wrap:wrap"><a class="va-dash-sidebar--nav-item" style="color:#087d42;font-weight:700" href="extension_profile.php">Edit extension profile</a><a class="va-dash-sidebar--nav-item" style="color:#087d42;font-weight:700" href="agricultural_support_requests.php?view=received">Manage farmer support requests</a><a class="va-dash-sidebar--nav-item" style="color:#087d42;font-weight:700" href="agricultural_support_requests.php?view=sent">My sent requests</a></div>

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


    <a class="va-dash-sidebar--nav-item" href="extension_profile.php" class="nav-item">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-profile"/></svg></span>
        My Profile
    </a>


    <a class="va-dash-sidebar--nav-item" href="logout.php" class="nav-item">
        <span class="va-dash-sidebar--icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-logout"/></svg></span>
        Logout
    </a>

</aside>


<!-- =====================================================
     MAIN
====================================================== -->

<main class="va-dash-main">


    <!-- TOP BAR -->

    <div class="va-dash-topbar">

        <div class="va-dash-search">
            <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> &nbsp; Search VAYUMA
        </div>


        <div class="va-dash-profile">

            <div class="va-dash-profile-info">

                <strong>
                    <?php echo htmlspecialchars($full_name); ?>
                </strong>

                <small>
                    Agricultural Extension Officer
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
</body>

</html>
