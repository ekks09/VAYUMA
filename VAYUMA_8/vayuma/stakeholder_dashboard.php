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
<head><?php require __DIR__ . '/includes/pwa.php'; ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($role_name, ENT_QUOTES, "UTF-8"); ?> Dashboard | VAYUMA</title>
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
    <main class="shell">
        <header class="top vy-user-menu__stakeholder-header"><div class="brand">VAYUMA<small>SMART ECOSYSTEM</small></div><nav><a class="va-dash-sidebar--nav-item" href="market_connect.php">Marketplace</a> &nbsp; <a class="va-dash-sidebar--nav-item" href="auth/logout.php">Logout</a></nav><?php require __DIR__ . '/includes/header_user_menu.php'; ?></header>
        <section class="hero"><small><?php echo htmlspecialchars(strtoupper($role_name), ENT_QUOTES, "UTF-8"); ?> DASHBOARD</small><h1>Welcome, <?php echo htmlspecialchars($full_name, ENT_QUOTES, "UTF-8"); ?> <?php echo $icon; ?></h1><p><?php echo htmlspecialchars($intro, ENT_QUOTES, "UTF-8"); ?></p></section>
        <section class="profile"><div><h2><?php echo htmlspecialchars($profile["organization_name"], ENT_QUOTES, "UTF-8"); ?></h2><p><?php echo htmlspecialchars($profile["county"], ENT_QUOTES, "UTF-8"); ?><?php echo $profile["service_focus"] ? " · " . htmlspecialchars($profile["service_focus"], ENT_QUOTES, "UTF-8") : ""; ?></p></div><a class="va-dash-sidebar--nav-item" class="button" href="stakeholder_profile.php?from=stakeholder_dashboard.php">Edit Profile</a></section>
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
