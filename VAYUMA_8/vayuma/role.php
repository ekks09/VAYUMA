<?php

session_start();
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/role_routes.php';
$platformHomeUrl = vayuma_platform_home_route();
$roleAccountName = (string)($_SESSION['full_name'] ?? 'VAYUMA member');
$roleAccountEmail = (string)($_SESSION['email'] ?? $_SESSION['phone'] ?? '');

/* Make sure the user has completed registration. */
if (!isset($_SESSION["user_id"])) {
    header("Location: register.php");
    exit;
}

$role_error = $_SESSION["role_error"] ?? "";
unset($_SESSION["role_error"]);

$role_groups = [
    "Agriculture & Market" => [
        [
            "Farming & producer organisations",
            "Grow, manage or market agricultural production.",
            [
                ["farmer", "🌾", "Farmer", "Plan, grow, monitor and sell your agricultural produce.", "Climate • Soil • AI • Markets", "farmer-icon"],
                ["cooperative", "🤝", "Cooperative", "Manage members, collective produce and market opportunities.", "Members • Produce • Markets", "cooperative-icon"],
            ],
        ],
        [
            "Buying, retail & trade",
            "Source produce, serve customers or trade across agricultural markets.",
            [
                ["buyer", "🛒", "Buyer", "Discover quality produce and connect with verified farmers.", "Marketplace • Orders • Suppliers", "buyer-icon"],
                ["consumer", "🥬", "Consumer", "Find trusted agricultural products and make informed purchases.", "Products • Safety • Orders", "consumer-icon"],
                ["retailer", "🏬", "Retailer", "Purchase agricultural products for resale to customers.", "Products • Sales • Orders", "buyer-icon"],
                ["agribusiness", "🌽", "Agribusiness / Trader", "Aggregate, trade and distribute agricultural products.", "Trading • Supply • Markets", "transport-icon"],
            ],
        ],
        [
            "Inputs, logistics & processing",
            "Support the movement, storage and transformation of agricultural goods.",
            [
                ["input_supplier", "🧰", "Input Supplier", "Supply seeds, fertilizer, equipment and other farm inputs.", "Inputs • Inventory • Delivery", "farmer-icon"],
                ["transport_provider", "🚚", "Transport Provider", "Move agricultural produce safely from farms to markets.", "Transport • Jobs • Delivery", "transport-icon"],
                ["warehouse_provider", "📦", "Warehouse Provider", "Provide safe storage and warehouse services for agricultural goods.", "Storage • Inventory • Logistics", "consumer-icon"],
                ["manufacturer", "🏭", "Manufacturer", "Transform agricultural raw materials into finished products.", "Sourcing • Production • Orders", "buyer-icon"],
                ["agricultural_service_provider", "🔧", "Agricultural Service Provider", "Provide machinery, irrigation, spraying and other farm services.", "Machinery • Irrigation • Services", "transport-icon"],
            ],
        ],
    ],
    "Professional & Institutional" => [
        [
            "Research, advisory & animal health",
            "Bring specialist knowledge and professional support to the agricultural community.",
            [
                ["researcher", "🔬", "Researcher", "Share agricultural research, innovation and knowledge.", "Research • Innovation • Knowledge", "researcher-icon"],
                ["extension_officer", "👩‍🌾", "Agricultural Extension Officer", "Provide farmers with professional agricultural guidance.", "Advisory • Farmers • Field Support", "extension-icon"],
                ["veterinary_officer", "🐄", "Veterinary Officer", "Provide livestock health advice and veterinary services.", "Livestock • Health • Advisory", "farmer-icon"],
            ],
        ],
        [
            "Finance, insurance & public institutions",
            "Serve agricultural businesses through finance, risk management or public programmes.",
            [
                ["financial_institution", "🏦", "Financial Institution", "Provide agricultural financing, loans and financial services.", "Finance • Loans • Agriculture", "finance-icon"],
                ["insurance_provider", "🛡️", "Insurance Provider", "Provide crop, livestock and agricultural insurance.", "Policies • Claims • Risk", "consumer-icon"],
                ["government_officer", "🏛️", "Government Officer", "Support agricultural programs, regulation and public services.", "Programs • Regulation • Support", "extension-icon"],
            ],
        ],
        [
            "Development & strategic partnerships",
            "Build programmes and partnerships that strengthen agricultural communities.",
            [
                ["ngo_partner", "🌍", "NGO / Development Partner", "Support agricultural development programs and initiatives.", "Programs • Grants • Training", "cooperative-icon"],
                ["strategic_partner", "🤝", "Strategic Partner", "Collaborate with VAYUMA through a tailored strategic partnership.", "Partnership • Growth • Impact", "cooperative-icon"],
            ],
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_language, ENT_QUOTES, 'UTF-8') ?>">
<head><?php require __DIR__ . '/includes/pwa.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Choose your role in the VAYUMA Smart Ecosystem.">
    <title>Choose Your Role | VAYUMA</title>
    <link rel="stylesheet" href="frontend/css/tokens.css">
    <link rel="stylesheet" href="frontend/css/components.css">
    <link rel="stylesheet" href="frontend/css/components_addition.css?v=account-nav-1">
    <link rel="stylesheet" href="frontend/css/layout.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .role-topbar {
            position: relative;
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            width: min(1250px, calc(100% - 40px));
            min-height: 76px;
            margin: 0 auto;
            padding: 12px 0;
            border-bottom: 1px solid rgba(31, 83, 52, .12);
        }
        .role-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            flex: 0 0 auto;
            color: #087d42;
            text-decoration: none;
            font-weight: 850;
            letter-spacing: .04em;
        }
        .role-brand img { width: 42px; height: 42px; border-radius: 12px; object-fit: cover; }
        .role-brand span { display: grid; gap: 2px; }
        .role-brand small { color: #718078; font-size: 10px; font-weight: 650; letter-spacing: .1em; }
        .role-topbar .va-language-selector { margin: 0 auto; padding: 0; justify-content: center; flex-wrap: nowrap; }
        .role-account {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }
        .role-account-avatar {
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            flex: 0 0 auto;
            border-radius: 50%;
            background: #e4f4e9;
            color: #087d42;
            font-weight: 800;
        }
        .role-account-copy { display: grid; min-width: 0; gap: 2px; }
        .role-account-copy strong { overflow: hidden; max-width: 190px; color: #203d2d; text-overflow: ellipsis; white-space: nowrap; font-size: 13px; }
        .role-account-copy small { overflow: hidden; max-width: 190px; color: #748078; text-overflow: ellipsis; white-space: nowrap; font-size: 11px; }
        .role-logout {
            padding: 8px 11px;
            border: 1px solid #d5e2d8;
            border-radius: 10px;
            color: #486454;
            text-decoration: none;
            font-size: 12px;
            font-weight: 750;
            white-space: nowrap;
        }
        .role-logout:hover { border-color: #087d42; color: #087d42; background: #f1faf3; }
        .role-category { margin-top: 40px; }
        .role-category-header { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 20px; }
        .role-category-index { flex: 0 0 auto; padding: 6px 10px; border-radius: 999px; background: #e5f5e9; color: #087d42; font-size: 12px; font-weight: 800; letter-spacing: .08em; }
        .role-category-header h2 { margin: 0 0 5px; color: #173d2a; font-size: clamp(22px, 3vw, 28px); }
        .role-category-header p, .role-family > p { margin: 0; color: #69786e; line-height: 1.55; }
        .role-family { margin: 0 0 28px; padding: 22px; border: 1px solid #e1ebe3; border-radius: 18px; background: #fbfefc; }
        .role-family > h3 { margin: 0 0 5px; color: #173d2a; font-size: 18px; }
        .role-family > p { margin-bottom: 16px; font-size: 13px; }
        .role-option { cursor: pointer; }
        .role-option input:focus-visible + .role-inner { outline: 3px solid rgba(8, 125, 66, .35); outline-offset: 3px; }
        .role-selection-hint { min-height: 24px; margin: 16px auto 0; color: #087d42; font-size: 14px; font-weight: 700; }
        @media (max-width: 600px) {
            .role-topbar { width: calc(100% - 24px); min-height: auto; flex-wrap: wrap; gap: 8px 12px; padding: 10px 0; }
            .role-topbar .va-language-selector { order: 3; width: 100%; margin: 0; justify-content: flex-start; }
            .role-account { margin-left: auto; gap: 7px; }
            .role-account-copy { display: none; }
            .role-account-avatar { width: 34px; height: 34px; }
            .role-logout { padding: 7px 9px; }
            .role-family { padding: 15px; }
            .role-category-header { gap: 10px; }
        }
    </style>
</head>
<body class="role-body">
    <header class="role-topbar">
        <a class="role-brand" href="<?= htmlspecialchars($platformHomeUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="VAYUMA home">
            <img src="assets/images/vayuma-logo.jpg" alt="">
            <span>VAYUMA<small>SMART ECOSYSTEM</small></span>
        </a>
        <div class="role-topbar-locale"><?php vayuma_language_selector(); ?></div>
        <div class="role-account" aria-label="Your account">
            <span class="role-account-avatar" aria-hidden="true"><?= htmlspecialchars(strtoupper(substr($roleAccountName, 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
            <span class="role-account-copy">
                <strong><?= htmlspecialchars($roleAccountName, ENT_QUOTES, 'UTF-8') ?></strong>
                <small><?= htmlspecialchars($roleAccountEmail !== '' ? $roleAccountEmail : 'Account setup', ENT_QUOTES, 'UTF-8') ?></small>
            </span>
            <a class="role-logout" href="auth/logout.php">Log out</a>
        </div>
    </header>
    <main class="role-page">
        <section class="role-card">
            <div class="role-header">
                <div class="step-badge">STEP 2 OF 3</div>
                <h1>Choose Your Role</h1>
                <p>Welcome, <strong><?= htmlspecialchars($roleAccountName, ENT_QUOTES, 'UTF-8') ?></strong></p>
                <span>Tell us how you will use VAYUMA so we can personalize your experience.</span>
            </div>

            <form action="auth/save_role.php" method="POST" id="roleForm" novalidate>
                <?= vayuma_csrf_field() ?>
                <?php $categoryIndex = 0; foreach ($role_groups as $group_name => $families): $categoryIndex++; ?>
                    <section class="role-category" aria-labelledby="role-category-<?= $categoryIndex ?>">
                        <header class="role-category-header">
                            <span class="role-category-index">0<?= $categoryIndex ?></span>
                            <div>
                                <h2 id="role-category-<?= $categoryIndex ?>"><?php echo htmlspecialchars($group_name, ENT_QUOTES, "UTF-8"); ?></h2>
                                <p><?= $categoryIndex === 1 ? 'For people and businesses working in production, trade and agricultural supply.' : 'For specialists, institutions and organisations supporting the agriculture sector.' ?></p>
                            </div>
                        </header>
                        <?php foreach ($families as [$familyName, $familyDescription, $roles]): ?>
                            <section class="role-family" aria-label="<?= htmlspecialchars($familyName, ENT_QUOTES, "UTF-8") ?>">
                                <h3><?= htmlspecialchars($familyName, ENT_QUOTES, "UTF-8") ?></h3>
                                <p><?= htmlspecialchars($familyDescription, ENT_QUOTES, "UTF-8") ?></p>
                                <div class="role-grid">
                                    <?php foreach ($roles as [$value, $icon, $name, $description, $features, $icon_class]): ?>
                                        <label class="role-option">
                                            <input type="radio" name="role" value="<?php echo htmlspecialchars($value, ENT_QUOTES, "UTF-8"); ?>" aria-label="<?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?>">
                                            <div class="role-inner">
                                                <div class="role-icon <?php echo htmlspecialchars($icon_class, ENT_QUOTES, "UTF-8"); ?>"><?php echo $icon; ?></div>
                                                <div class="role-content">
                                                    <h3><?php echo htmlspecialchars($name, ENT_QUOTES, "UTF-8"); ?></h3>
                                                    <p><?php echo htmlspecialchars($description, ENT_QUOTES, "UTF-8"); ?></p>
                                                    <div class="role-features"><?php echo htmlspecialchars($features, ENT_QUOTES, "UTF-8"); ?></div>
                                                </div>
                                                <div class="role-check" aria-hidden="true">✓</div>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </section>
                <?php endforeach; ?>

                <div id="roleError" class="role-error" role="alert" aria-live="assertive"<?php echo $role_error === "" ? "" : " style=\"display: block;\""; ?>><?php echo htmlspecialchars($role_error, ENT_QUOTES, "UTF-8"); ?></div>
                <p id="roleSelectionHint" class="role-selection-hint" aria-live="polite"></p>
                <div class="role-footer">
                    <button type="submit" class="role-button">Continue <span>→</span></button>
                    <p>Select a role and press Continue, or double-click a role card to continue.</p>
                </div>
            </form>
        </section>
    </main>

    <script>
    const roleForm = document.getElementById("roleForm");
    const roleError = document.getElementById("roleError");

    roleForm.addEventListener("submit", function (event) {
        const selectedRole = document.querySelector('input[name="role"]:checked');
        if (!selectedRole) {
            event.preventDefault();
            roleError.textContent = "Please select a role before continuing.";
            roleError.style.display = "block";
            window.scrollTo({ top: document.body.scrollHeight, behavior: "smooth" });
        } else {
            roleError.style.display = "none";
        }
    });

    const selectionHint = document.getElementById("roleSelectionHint");
    const roleOptions = document.querySelectorAll(".role-option");
    roleOptions.forEach(function (option) {
        const radio = option.querySelector('input[name="role"]');
        radio.addEventListener("change", function () {
            const title = option.querySelector(".role-content h3");
            selectionHint.textContent = title ? "Selected: " + title.textContent.trim() : "";
            roleError.style.display = "none";
        });
        option.addEventListener("dblclick", function () {
            radio.checked = true;
            radio.dispatchEvent(new Event("change", { bubbles: true }));
            if (typeof roleForm.requestSubmit === "function") {
                roleForm.requestSubmit();
            } else {
                roleForm.submit();
            }
        });
    });
    </script>
<script src="assets/js/vayuma-language.js" defer></script>
</body>
</html>
