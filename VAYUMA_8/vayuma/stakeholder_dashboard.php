<?php

session_start();
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . '/subscription_guard.php';

if (!isset($_SESSION["user_id"]) || ($_SESSION["logged_in"] ?? false) !== true) {
    header("Location: login.php");
    exit;
}

$role = strtolower(trim($_SESSION["role"] ?? ""));
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
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($role_name, ENT_QUOTES, "UTF-8"); ?> Dashboard | VAYUMA</title>
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
    <main class="shell">
        <header class="top"><div class="brand">VAYUMA<small>SMART ECOSYSTEM</small></div><nav><a class="va-dash-sidebar--nav-item" href="market_connect.php">Marketplace</a> &nbsp; <a class="va-dash-sidebar--nav-item" href="auth/logout.php">Logout</a></nav></header>
        <section class="hero"><small><?php echo htmlspecialchars(strtoupper($role_name), ENT_QUOTES, "UTF-8"); ?> DASHBOARD</small><h1>Welcome, <?php echo htmlspecialchars($full_name, ENT_QUOTES, "UTF-8"); ?> <?php echo $icon; ?></h1><p><?php echo htmlspecialchars($intro, ENT_QUOTES, "UTF-8"); ?></p></section>
        <section class="profile"><div><h2><?php echo htmlspecialchars($profile["organization_name"], ENT_QUOTES, "UTF-8"); ?></h2><p><?php echo htmlspecialchars($profile["county"], ENT_QUOTES, "UTF-8"); ?><?php echo $profile["service_focus"] ? " · " . htmlspecialchars($profile["service_focus"], ENT_QUOTES, "UTF-8") : ""; ?></p></div><a class="va-dash-sidebar--nav-item" class="button" href="stakeholder_profile.php">Edit Profile</a></section>
        <h2 class="section-title">Your workspace</h2>
        <section class="grid">
            <?php foreach ($cards as [$card_icon, $title, $description]): ?>
                <?php
                $request_words = ["Request", "Application", "Claim", "Booking", "Appointment"];
                $is_request_module = false;
                foreach ($request_words as $word) {
                    if (strpos($title, $word) !== false) {
                        $is_request_module = true;
                        break;
                    }
                }
                $is_browse_module = strpos($title, "Marketplace") !== false || strpos($title, "Network") !== false;
                $module_link = $is_request_module
                    ? "stakeholder_requests.php"
                    : ($is_browse_module ? "stakeholder_offers.php" : "stakeholder_offers.php?view=manage");
                ?>
                <article class="card"><div class="icon"><?php echo $card_icon; ?></div><h3><?php echo htmlspecialchars($title, ENT_QUOTES, "UTF-8"); ?></h3><p><?php echo htmlspecialchars($description, ENT_QUOTES, "UTF-8"); ?></p><a class="va-dash-sidebar--nav-item" href="<?php echo htmlspecialchars($module_link, ENT_QUOTES, "UTF-8"); ?>">Open →</a></article>
            <?php endforeach; ?>
        </section>
    </main>
<script src="assets/js/vayuma-language.js" defer></script>
</body>
</html>
