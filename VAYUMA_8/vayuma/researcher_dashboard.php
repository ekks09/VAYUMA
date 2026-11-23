<?php
session_start();
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/subscription_guard.php';

/*
|--------------------------------------------------------------------------
| RESEARCHER DASHBOARD
|--------------------------------------------------------------------------
*/

/* Protect dashboard */
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

if ($role !== 'researcher') {
    header("Location: dashboard.php");
    exit;
}

/* User information */
$full_name = $_SESSION['full_name'] ?? 'Researcher';

?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_language, ENT_QUOTES, 'UTF-8') ?>">
<head><?php require __DIR__ . '/includes/pwa.php'; ?>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

<title>Researcher Dashboard | VAYUMA TRIX</title>

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
    <?php require __DIR__ . '/includes/dashboard_category_link.php'; ?>

    <div class="nav-title">MAIN</div>

    <a class="va-dash-sidebar--nav-item va-dash-sidebar--nav-item--active" href="researcher_dashboard.php" aria-current="page">
        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-dashboard"/></svg> <span>Dashboard</span>
    </a>

    <div class="nav-title">RESEARCH</div>

    <span class="va-dash-sidebar--nav-item" aria-disabled="true" title="Research projects are not available yet">
        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> <span>Research Projects</span>
        <small>Planned</small>
    </span>

    <span class="va-dash-sidebar--nav-item" aria-disabled="true" title="Research analytics are not available yet">
        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> <span>Data & Analytics</span>
        <small>Planned</small>
    </span>

    <span class="va-dash-sidebar--nav-item" aria-disabled="true" title="Crop research tools are not available yet">
        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> <span>Crop Studies</span>
        <small>Planned</small>
    </span>

    <a class="va-dash-sidebar--nav-item" href="climate.php">
        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-weather"/></svg> <span>Climate Guidance</span>
    </a>

    <span class="va-dash-sidebar--nav-item" aria-disabled="true" title="Field trial tools are not available yet">
        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-soil"/></svg> <span>Field Trials</span>
        <small>Planned</small>
    </span>

    <div class="nav-title">COLLABORATION</div>

    <span class="va-dash-sidebar--nav-item" aria-disabled="true" title="Researcher collaboration tools are not available yet">
        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> <span>Farmers</span>
        <small>Planned</small>
    </span>

    <span class="va-dash-sidebar--nav-item" aria-disabled="true" title="Researcher collaboration tools are not available yet">
        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> <span>Collaborations</span>
        <small>Planned</small>
    </span>

    <span class="va-dash-sidebar--nav-item" aria-disabled="true" title="Research reports are not available yet">
        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> <span>Reports</span>
        <small>Planned</small>
    </span>

    <div class="nav-title">ACCOUNT</div>

    <a class="va-dash-sidebar--nav-item" href="researcher_profile.php?from=researcher_dashboard.php">
        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-profile"/></svg> <span>My Profile</span>
    </a>



    <?php vayuma_language_selector(); ?>

    <div class="va-sidebar--bottom" aria-label="Account utilities">
      <a class="va-dash-sidebar--nav-item" href="subscription.php"><svg class="va-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg#va-icon-subscription"/></svg> <span>Subscription</span></a>
      <a class="va-dash-sidebar--nav-item" href="auth/logout.php">
        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-logout"/></svg> <span>Logout</span>
    </a>
    </div>
</aside>


<!-- MAIN CONTENT -->

<main class="va-dash-main"><?php require_once __DIR__ . "/includes/subscription_banner.php"; echo render_subscription_banner($conn, (int)$_SESSION["user_id"]); ?>


    <div class="va-dash-topbar vy-user-menu__dashboard-header">
    <button type="button" class="va-mobile-menu-btn" aria-controls="vaDashSidebar" aria-expanded="false" aria-label="Open navigation">Menu</button>

        <div class="va-dash-search">
            <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> &nbsp; Search VAYUMA · Planned
        </div>

        <?php require __DIR__ . '/includes/header_user_menu.php'; ?>

    </div>


    <div class="va-dash-role-label">
        RESEARCHER DASHBOARD
    </div>

    <h1 class="va-dash-page-title">
        Good day, <?php echo htmlspecialchars($full_name); ?>
    </h1>

    <p class="va-dash-subtitle">
        Discover agricultural insights, conduct research and help shape
        the future of smart agriculture.
    </p>


    <!-- STATISTICS -->

    <div class="va-dash-stats">

        <div class="va-dash-stat">
            <div class="va-dash-stat-icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg></div>
            <h3>Research Projects</h3>
            <div class="va-dash-number">—</div>
            <div class="va-dash-description">Research project tracking is not available yet</div>
        </div>

        <div class="va-dash-stat">
            <div class="va-dash-stat-icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg></div>
            <h3>Data Sets</h3>
            <div class="va-dash-number">—</div>
            <div class="va-dash-description">Research datasets are not connected yet</div>
        </div>

        <div class="va-dash-stat">
            <div class="va-dash-stat-icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg></div>
            <h3>Field Trials</h3>
            <div class="va-dash-number">—</div>
            <div class="va-dash-description">Field trial tracking is not available yet</div>
        </div>

        <div class="va-dash-stat">
            <div class="va-dash-stat-icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg></div>
            <h3>Reports</h3>
            <div class="va-dash-number">—</div>
            <div class="va-dash-description">Research report tracking is not available yet</div>
        </div>

    </div>


    <!-- LOWER CONTENT -->

    <div class="va-dash-content-grid">

        <div class="va-dash-panel">

            <h2>Example research areas</h2>

            <div class="research-item">

                <div class="research-info">

                    <div class="research-icon">
                        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg>
                    </div>

                    <div>
                        <strong>Crop Productivity Study</strong>
                        <span>Maize productivity research</span>
                    </div>

                </div>

                <span class="status">
                    Planned
                </span>

            </div>


            <div class="research-item">

                <div class="research-info">

                    <div class="research-icon">
                        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-weather"/></svg>
                    </div>

                    <div>
                        <strong>Climate Impact Analysis</strong>
                        <span>Weather and crop performance</span>
                    </div>

                </div>

                <span class="status">
                    Planned
                </span>

            </div>


            <div class="research-item">

                <div class="research-info">

                    <div class="research-icon">
                        <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-soil"/></svg>
                    </div>

                    <div>
                        <strong>Soil Health Research</strong>
                        <span>Soil quality and crop health</span>
                    </div>

                </div>

                <span class="status">
                    Planned
                </span>

            </div>

        </div>


        <div class="va-dash-panel">

            <h2>Quick Actions</h2>

            <span class="va-dash-action" aria-disabled="true" title="Research project creation is not available yet">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Start Research Project
                <small>Planned</small>
            </span>

            <span class="va-dash-action" aria-disabled="true" title="Research analytics are not available yet">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Analyze Agricultural Data
                <small>Planned</small>
            </span>

            <span class="va-dash-action" aria-disabled="true" title="Field trial tools are not available yet">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> Add Field Trial
                <small>Planned</small>
            </span>

            <span class="va-dash-action" aria-disabled="true" title="Research reports are not available yet">
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Create Research Report
                <small>Planned</small>
            </span>

        </div>

    </div>

</main>
</div>

<script src="frontend/js/vayuma-ui.js" defer></script>
<script src="assets/js/vayuma-language.js" defer></script>
</body>
</html>
