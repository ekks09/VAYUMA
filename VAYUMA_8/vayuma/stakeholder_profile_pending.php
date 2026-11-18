<?php

session_start();
require_once __DIR__ . '/includes/language.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$role = strtolower(trim($_SESSION["role"] ?? ""));
$roles_needing_profiles = [
    "input_supplier" => ["Input Supplier", "🌾"],
    "manufacturer" => ["Manufacturer", "🏭"],
    "warehouse_provider" => ["Warehouse Provider", "📦"],
    "retailer" => ["Retailer", "🏪"],
    "agribusiness" => ["Agribusiness / Trader", "✅"],
    "veterinary_officer" => ["Veterinary Officer", "🐄"],
    "insurance_provider" => ["Insurance Provider", "✅"],
    "government_officer" => ["Government Officer", "✅"],
    "ngo_partner" => ["NGO / Development Partner", "🌍"],
    "agricultural_service_provider" => ["Agricultural Service Provider", "🔧"],
];

if (!isset($roles_needing_profiles[$role])) {
    header("Location: dashboard.php");
    exit;
}

[$role_name, $icon] = $roles_needing_profiles[$role];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($role_name, ENT_QUOTES, "UTF-8"); ?> Profile | VAYUMA</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="role-body">
<?php vayuma_language_selector(); ?>
    <main class="role-page">
        <section class="role-card" style="max-width: 720px; text-align: center;">
            <div class="role-header">
                <div style="font-size: 54px; margin-bottom: 14px;"><?php echo $icon; ?></div>
                <div class="step-badge">PROFILE SETUP</div>
                <h1><?php echo htmlspecialchars($role_name, ENT_QUOTES, "UTF-8"); ?></h1>
                <span>Your role has been saved. Its dedicated profile form is the next VAYUMA onboarding feature to build.</span>
            </div>
            <p style="color: #53675c; line-height: 1.6;">You have not been sent to the Farmer Profile. Please return to role selection if you selected the wrong role.</p>
            <p style="margin-top: 28px;"><a class="role-button" style="display: inline-block; text-decoration: none; padding: 16px 28px;" href="role.php">Choose a different role</a></p>
        </section>
    </main>
<script src="assets/js/vayuma-language.js" defer></script>
</body>
</html>
