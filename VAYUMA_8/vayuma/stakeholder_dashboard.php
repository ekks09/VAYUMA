<?php

session_start();
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . '/subscription_guard.php';
require_once __DIR__ . '/includes/role_routes.php';

if (!isset($_SESSION["user_id"]) || ($_SESSION["logged_in"] ?? false) !== true) {
    header("Location: login.php");
    exit;
}

$role = strtolower(trim($_SESSION["role"] ?? ""));
// This route is the strategic-partner dashboard; other roles have dedicated routes.
if ($role !== 'strategic_partner') { header('Location: dashboard.php'); exit; }

$dashboards = [
    "input_supplier" => ["Input Supplier", "🌾", "Manage farm inputs and support farmers.", [["📦", "Inventory", "Manage seeds, fertiliser, equipment and stock."], ["🛒", "Farmer Requests", "Respond to input requests from farmers."], ["🚚", "Delivery", "Plan deliveries to your customers."]]],
    "manufacturer" => ["Manufacturer", "🏭", "Source agricultural raw materials and manage production.", [["🌽", "Raw Material Marketplace", "Find produce supplied through VAYUMA."], ["🏭", "Production", "Plan production requirements and capacity."], ["📦", "Warehousing", "Coordinate storage of raw materials and products."]]],
    "warehouse_provider" => ["Warehouse Provider", "📦", "Make storage capacity visible across the value chain.", [["🏢", "Storage Capacity", "Publish available storage space."], ["📥", "Storage Requests", "Review produce storage requests."], ["✅", "Inventory Records", "Track incoming and outgoing goods."]]],
    "retailer" => ["Retailer", "🏪", "Source agricultural products and serve your customers.", [["🛒", "Marketplace", "Find products from farmers and suppliers."], ["📦", "Orders", "Manage purchase orders and deliveries."], ["📋️", "Retail Catalogue", "Organise products for resale."]]],
    "agribusiness" => ["Agribusiness / Trader", "✅", "Aggregate, trade and distribute agricultural produce.", [["🌽", "Produce Marketplace", "Discover available produce."], ["✅", "Supplier Network", "Connect with farmers and cooperatives."], ["🚚", "Distribution", "Coordinate transport and deliveries."]]],
    "veterinary_officer" => ["Veterinary Officer", "🐄", "Support healthy livestock and connect with farmers.", [["🐄", "Livestock Requests", "View livestock-health service requests."], ["✅", "Advisories", "Publish animal-health guidance."], ["✅", "Appointments", "Manage field visits and consultations."]]],
    "insurance_provider" => ["Insurance Provider", "✅", "Offer agricultural insurance and manage risk services.", [["✅", "Insurance Products", "Manage crop and livestock cover."], ["✅", "Applications", "Review insurance applications."], ["✅", "Claims", "Track claims and risk assessments."]]],
    "government_officer" => ["Government Officer", "✅", "Coordinate agricultural programmes and public services.", [["📋", "Programmes", "Publish government programmes and opportunities."], ["✅", "Agricultural Insights", "Review ecosystem data and trends."], ["✅", "Verification", "Support farmer and stakeholder verification."]]],
    "ngo_partner" => ["NGO / Development Partner", "🌍", "Deliver agricultural development programmes and support.", [["🌾", "Projects", "Manage agricultural development projects."], ["🎓", "Training", "Publish farmer training opportunities."], ["📋", "Grants", "Share grant and support programmes."]]],
    "agricultural_service_provider" => ["Agricultural Service Provider", "🔧", "Offer practical services that help farms thrive.", [["📋", "Service Catalogue", "List machinery, irrigation, spraying and other services."], ["✅", "Bookings", "Manage farmer service bookings."], ["✅", "Service Areas", "Set the locations you serve."]]],
    "strategic_partner" => ["Strategic Partner", "✅", "Collaborate with VAYUMA through a tailored strategic partnership.", [["✅", "Partnership", "Coordinate your partnership activities."], ["✅", "Impact", "Track shared agricultural impact."], ["🌍", "Network", "Connect across the VAYUMA ecosystem."]]],
];

if (!isset($dashboards[$role])) {
    header("Location: dashboard.php");
    exit;
}

[$role_name, $icon, $intro, $cards] = $dashboards[$role];
$user_id = (int) $_SESSION["user_id"];
$profile = null;
$stmt = $conn->prepare("SELECT organization_name, county, service_focus FROM stakeholder_profiles WHERE user_id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$profile) {
    header("Location: stakeholder_profile.php");
    exit;
}

$full_name = $_SESSION["full_name"] ?? "Member";
?>
<!doctype html>
<html lang="en">
<head><?php require __DIR__ . '/includes/pwa.php'; ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?php echo htmlspecialchars($role_name, ENT_QUOTES, "UTF-8"); ?> Dashboard | VAYUMA</title>
    <link rel="stylesheet" href="frontend/css/tokens.css">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
<link rel="stylesheet" href="frontend/css/dashboard-shell.css">
<style>
</style>
<link rel="stylesheet" href="assets/css/dashboard-layout.css?v=dashboard-ui-4">
</head>
<body>
<div class="va-dash-layout"><?php
$dashboardLinks = ['Dashboard' => 'stakeholder_dashboard.php', 'Marketplace' => 'market_connect.php', 'Exchange' => 'stakeholder_offers.php', 'Requests' => 'stakeholder_requests.php', 'Edit profile' => 'stakeholder_profile.php?from=stakeholder_dashboard.php'];
require __DIR__ . '/includes/dashboard_generic_sidebar.php'; ?>
    <main class="shell va-generic-main">
        <header class="top vy-user-menu__stakeholder-header"><button type="button" class="va-mobile-menu-btn" aria-controls="vaDashSidebar" aria-expanded="false" aria-label="Open navigation">Menu</button><div class="brand">VAYUMA<small>SMART ECOSYSTEM</small></div><nav><a class="va-dash-sidebar--nav-item" href="market_connect.php">Marketplace</a> &nbsp; <a class="va-dash-sidebar--nav-item" href="auth/logout.php">Logout</a></nav><?php require __DIR__ . '/includes/header_user_menu.php'; ?></header>
        <section class="hero"><small><?php echo htmlspecialchars(strtoupper($role_name), ENT_QUOTES, "UTF-8"); ?> DASHBOARD</small><h1>Welcome, <?php echo htmlspecialchars($full_name, ENT_QUOTES, "UTF-8"); ?> <?php echo $icon; ?></h1><p><?php echo htmlspecialchars($intro, ENT_QUOTES, "UTF-8"); ?></p></section>
        <section class="profile"><div><h2><?php echo htmlspecialchars($profile["organization_name"], ENT_QUOTES, "UTF-8"); ?></h2><p><?php echo htmlspecialchars($profile["county"], ENT_QUOTES, "UTF-8"); ?><?php echo $profile["service_focus"] ? " · " . htmlspecialchars($profile["service_focus"], ENT_QUOTES, "UTF-8") : ""; ?></p></div><a class="va-dash-sidebar--nav-item button" href="stakeholder_profile.php?from=stakeholder_dashboard.php">Edit Profile</a></section>
        <h2 class="section-title">Your workspace</h2>
        <section class="grid">
            <article class="card"><h3>Partnership activities</h3><p>Dedicated partnership coordination tools are not available yet.</p><span aria-disabled="true">Planned</span></article>
            <article class="card"><h3>Impact reporting</h3><p>Partnership impact tracking is not available yet.</p><span aria-disabled="true">Planned</span></article>
            <article class="card"><h3>Stakeholder directory</h3><p>Browse stakeholder profiles and their verification status.</p><a class="va-dash-sidebar--nav-item" href="stakeholder_directory.php">Browse directory →</a></article>
        </section>
    </main>
</div>
<script src="frontend/js/vayuma-ui.js" defer></script>
<script src="assets/js/vayuma-language.js" defer></script>
</body>
</html>
