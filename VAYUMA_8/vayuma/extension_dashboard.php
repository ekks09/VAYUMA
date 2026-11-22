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
<html lang="<?= htmlspecialchars($current_language, ENT_QUOTES, 'UTF-8') ?>">
<head>
<?php require __DIR__ . '/includes/pwa.php'; ?>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Extension Officer Dashboard | VAYUMA</title>
<link rel="stylesheet" href="frontend/css/tokens.css">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
<link rel="stylesheet" href="frontend/css/dashboard-shell.css">
<link rel="stylesheet" href="assets/css/dashboard-layout.css?v=extension-ui-2">
</head>
<body class="va-extension-dashboard">
<div class="va-dash-layout">
<div class="va-sidebar-overlay" id="vaSidebarOverlay"></div>
<aside class="va-dash-sidebar" id="vaDashSidebar" aria-label="Extension dashboard navigation">
  <?php require __DIR__ . '/includes/dashboard_brand.php'; ?>
  <nav aria-label="Extension workspace">
<div class="va-dash-sidebar--section">MAIN</div>
<a class="va-dash-sidebar--nav-item va-dash-sidebar--nav-item--active" href="extension_dashboard.php" aria-current="page"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-dashboard"></use></svg><span>Dashboard</span></a>
<div class="va-dash-sidebar--section">FARMER SUPPORT</div>
<a class="va-dash-sidebar--nav-item" href="extension_farmers.php"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-farmers"></use></svg><span>Farmers</span></a>
<a class="va-dash-sidebar--nav-item" href="extension_visits.php"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-visit"></use></svg><span>Farm Visits</span></a>
<a class="va-dash-sidebar--nav-item" href="extension_reports.php"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-report"></use></svg><span>Farm Reports</span></a>
<a class="va-dash-sidebar--nav-item" href="feature_access.php?feature=ai"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-crop"></use></svg><span>Crop Advisory</span></a>
<a class="va-dash-sidebar--nav-item" href="disease_helper.php"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-pest"></use></svg><span>Disease &amp; Pest Help</span></a>
<div class="va-dash-sidebar--section">KNOWLEDGE</div>
<a class="va-dash-sidebar--nav-item" href="climate.php"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-weather"></use></svg><span>Climate Guidance</span></a>
<a class="va-dash-sidebar--nav-item" href="extension_resources.php"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-resources"></use></svg><span>Farming Resources</span></a>
<a class="va-dash-sidebar--nav-item" href="feature_access.php?feature=ai"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-ai"></use></svg><span>VAYUMA AI</span></a>
  </nav>
  <div class="va-ext-sidebar-footer">
    <?php vayuma_language_selector(); ?>
    <div class="va-sidebar--bottom" aria-label="Account utilities">
      <div class="va-dash-sidebar--section">ACCOUNT</div>
<a class="va-dash-sidebar--nav-item" href="extension_profile.php?from=extension_dashboard.php"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-profile"></use></svg><span>My Profile</span></a>
<a class="va-dash-sidebar--nav-item" href="subscription.php"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-subscription"></use></svg><span>Subscription</span></a>
<a class="va-dash-sidebar--nav-item" href="auth/logout.php"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-logout"></use></svg><span>Logout</span></a>
    </div>
  </div>
</aside>
<main class="va-dash-main" id="va-main-content">
  <header class="va-dash-topbar vy-user-menu__dashboard-header">
    <button type="button" class="va-mobile-menu-btn" aria-controls="vaDashSidebar" aria-expanded="false" aria-label="Open navigation">Menu</button>
    <div class="va-dash-search va-ext-search" aria-label="Search VAYUMA: not available yet"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-search"></use></svg><span>Search VAYUMA</span><small>Planned</small></div>
    <?php require __DIR__ . '/includes/header_user_menu.php'; ?>
  </header>
  <nav class="va-ext-actions" aria-label="Extension account actions">
    <a href="extension_profile.php?from=extension_dashboard.php">Edit extension profile</a>
    <a href="agricultural_support_requests.php?view=received">Manage farmer support requests</a>
    <a href="agricultural_support_requests.php?view=sent">My sent requests</a>
  </nav>
  <div class="va-dash-role-label">AGRICULTURAL EXTENSION OFFICER</div>
  <h1 class="va-dash-page-title">Good day, <?= htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8') ?></h1>
  <p class="va-dash-subtitle">Support farmers with practical agricultural knowledge, field visits, crop advice and climate-smart farming guidance through VAYUMA.</p>
  <section class="va-dash-stats va-ext-stats" aria-label="Extension activity summaries">
<article class="va-dash-stat va-ext-stat"><div class="va-ext-stat__heading"><span class="va-ext-stat__icon"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-farmers"></use></svg></span><h3>Farmers Supported</h3></div><div class="va-ext-stat__value" aria-label="Count not available">—</div><p>Support count not available yet.</p><a href="extension_farmers.php">Browse farmers <span aria-hidden="true">→</span></a></article>
<article class="va-dash-stat va-ext-stat"><div class="va-ext-stat__heading"><span class="va-ext-stat__icon"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-visit"></use></svg></span><h3>Farm Visits</h3></div><div class="va-ext-stat__value" aria-label="Count not available">—</div><p>Visit count not available yet.</p><a href="extension_visits.php">View visits <span aria-hidden="true">→</span></a></article>
<article class="va-dash-stat va-ext-stat"><div class="va-ext-stat__heading"><span class="va-ext-stat__icon"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-report"></use></svg></span><h3>Farm Reports</h3></div><div class="va-ext-stat__value" aria-label="Count not available">—</div><p>Report count not available yet.</p><a href="extension_reports.php">View reports <span aria-hidden="true">→</span></a></article>
<article class="va-dash-stat va-ext-stat"><div class="va-ext-stat__heading"><span class="va-ext-stat__icon"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-advice"></use></svg></span><h3>Farmer Advisories</h3></div><div class="va-ext-stat__value" aria-label="Count not available">—</div><p>Advisory count not available yet.</p><a href="feature_access.php?feature=ai">Open crop advisory <span aria-hidden="true">→</span></a></article>
  </section>
  <div class="va-dash-content-grid va-ext-content-grid">
    <section class="va-dash-panel va-ext-activities" aria-labelledby="extension-activities-title">
      <h2 id="extension-activities-title">Extension Activities</h2>
<div class="va-dash-feature"><div class="va-dash-feature-icon"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-farmers"></use></svg></div><div><strong>Farmer Support</strong><span>Connect with farmers and provide personalized agricultural guidance.</span></div></div>
<div class="va-dash-feature"><div class="va-dash-feature-icon"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-crop"></use></svg></div><div><strong>Crop Advisory</strong><span>Recommend suitable crops, planting practices and crop management methods.</span></div></div>
<div class="va-dash-feature"><div class="va-dash-feature-icon"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-pest"></use></svg></div><div><strong>Disease &amp; Pest Assistance</strong><span>Help farmers identify crop diseases, pests and appropriate management practices.</span></div></div>
<div class="va-dash-feature"><div class="va-dash-feature-icon"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-weather"></use></svg></div><div><strong>Climate-Smart Guidance</strong><span>Help farmers make decisions based on climate and weather information.</span></div></div>
<div class="va-dash-feature"><div class="va-dash-feature-icon"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-report"></use></svg></div><div><strong>Field Reports</strong><span>Record farm observations, challenges and recommendations.</span></div></div>
    </section>
    <section class="va-dash-panel va-ext-quick" aria-labelledby="extension-quick-title">
      <h2 id="extension-quick-title">Quick Actions</h2>
<a class="va-dash-action va-ext-action" href="extension_farmers.php"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-farmers"></use></svg><span>View / Find Farmers</span><span class="va-ext-action__arrow" aria-hidden="true">→</span></a>
<a class="va-dash-action va-ext-action" href="extension_visits.php"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-visit"></use></svg><span>Schedule Farm Visit</span><span class="va-ext-action__arrow" aria-hidden="true">→</span></a>
<a class="va-dash-action va-ext-action" href="extension_reports.php"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-report"></use></svg><span>Create Farm Report</span><span class="va-ext-action__arrow" aria-hidden="true">→</span></a>
<a class="va-dash-action va-ext-action" href="feature_access.php?feature=ai"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-crop"></use></svg><span>Give Crop Advice</span><span class="va-ext-action__arrow" aria-hidden="true">→</span></a>
<a class="va-dash-action va-ext-action" href="disease_helper.php"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-pest"></use></svg><span>Open Disease &amp; Pest Helper</span><span class="va-ext-action__arrow" aria-hidden="true">→</span></a>
<a class="va-dash-action va-ext-action" href="feature_access.php?feature=ai"><svg class="va-ext-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg?v=extension-1#va-ext-ai"></use></svg><span>Ask VAYUMA AI</span><span class="va-ext-action__arrow" aria-hidden="true">→</span></a>
    </section>
    <section class="va-dash-panel va-ext-mission" aria-labelledby="extension-mission-title">
      <h2 id="extension-mission-title">VAYUMA Mission</h2>
      <p>Help connect agricultural knowledge with farmers so they can make better, climate-smart and profitable decisions.</p>
    </section>
  </div>
</main>
</div>
<script src="assets/js/vayuma-language.js" defer></script>
<script src="frontend/js/vayuma-ui.js" defer></script>
</body></html>
