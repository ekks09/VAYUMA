<?php // Shared PWA metadata. Paths are relative to the application document root. ?>
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#087d42">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="VAYUMA">
<link rel="apple-touch-icon" href="frontend/images/pwa/apple-touch-icon.png">
<link rel="stylesheet" href="frontend/css/pwa-install.css?v=install-1">
<script src="frontend/js/pwa.js?v=install-2" defer></script>
<link rel="stylesheet" href="frontend/css/user-menu.css?v=1">
<script src="frontend/js/user-menu.js?v=1" defer></script>
<?php
// Only show the shortcut in signed-in workspaces, not authentication, admin or the assistant itself.
$aiShortcutPages = ['dashboard.php','stakeholder_dashboard.php','farmer_dashboard.php','buyer_dashboard.php','consumer_dashboard.php','extension_dashboard.php','veterinary_officer_dashboard.php','agribusiness_dashboard.php','agricultural_service_provider_dashboard.php','cooperative_dashboard.php','finance_dashboard.php','government_officer_dashboard.php','input_supplier_dashboard.php','insurance_provider_dashboard.php','manufacturer_dashboard.php','ngo_partner_dashboard.php','researcher_dashboard.php','retailer_dashboard.php','transport_dashboard.php','warehouse_provider_dashboard.php','climate.php','soil_analysis.php','soil_records.php','market_connect.php','budget_planner.php'];
if (($_SESSION['logged_in'] ?? false) === true && isset($_SESSION['user_id']) && in_array(basename($_SERVER['SCRIPT_NAME'] ?? ''), $aiShortcutPages, true)):
?>
<link rel="stylesheet" href="frontend/css/ai-shortcut.css?v=1">
<script src="frontend/js/ai-shortcut.js?v=1" defer></script>
<?php endif; ?>
