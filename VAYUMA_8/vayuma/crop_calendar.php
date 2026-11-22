<?php
session_start();
require_once __DIR__ . '/includes/language.php';

if (!isset($_SESSION["user_id"]) || ($_SESSION["logged_in"] ?? false) !== true) {
    header("Location: login.php");
    exit;
}
if (strtolower(trim((string)($_SESSION["role"] ?? ""))) !== "farmer") {
    header("Location: dashboard.php");
    exit;
}

require_once __DIR__ . "/config/db.php";

$full_name = $_SESSION["full_name"] ?? "Farmer";
$user_id = (int)$_SESSION["user_id"];

$profile = [
    "county" => "", "sub_county" => "", "farm_location" => "",
    "main_crops" => "", "secondary_crop" => "", "soil_type" => ""
];
$profileStmt = $conn->prepare(
    "SELECT county, sub_county, farm_location, main_crops, secondary_crop, soil_type
     FROM farmer_profiles WHERE user_id = ? LIMIT 1"
);
if ($profileStmt) {
    $profileStmt->bind_param("i", $user_id);
    if ($profileStmt->execute()) {
        $result = $profileStmt->get_result();
        if ($result && $result->num_rows > 0) {
            $profile = array_merge($profile, $result->fetch_assoc());
        }
    }
    $profileStmt->close();
}

$cropPlans = [
    "Maize" => [
        "duration" => "Often about 3–5 months, depending on variety and conditions",
        "water" => "Needs dependable moisture, especially during establishment, flowering and grain filling.",
        "steps" => [
            ["Land preparation", "Clear weeds, prepare a suitable seedbed and make sure water can drain properly.", "Before planting"],
            ["Planting", "Use a locally recommended variety and follow seed-pack or extension spacing guidance.", "Planting time"],
            ["Establishment", "Check germination, fill gaps where appropriate and control early weeds.", "First 2–4 weeks"],
            ["Nutrient and moisture care", "Apply inputs according to local recommendations; avoid applying fertilizer without following product guidance.", "Vegetative growth"],
            ["Pest and disease checks", "Inspect leaves and stems regularly, including for fall armyworm symptoms.", "Throughout growth"],
            ["Harvest and storage", "Harvest at maturity, dry grain properly and store it safely to reduce mould and losses.", "At maturity"]
        ]
    ],
    "Beans" => [
        "duration" => "Often about 2–4 months, depending on variety",
        "water" => "Maintain moisture during establishment, flowering and pod formation; avoid waterlogging.",
        "steps" => [
            ["Land preparation", "Prepare a fine, well-drained seedbed and remove competing weeds.", "Before planting"],
            ["Planting", "Choose a locally suitable bean variety and follow recommended spacing.", "Planting time"],
            ["Early weed control", "Remove weeds while plants are small and avoid damaging roots.", "First weeks"],
            ["Flowering and pod formation", "Monitor moisture and inspect plants for pests and disease.", "Mid-season"],
            ["Crop monitoring", "Remove or manage affected plants according to local extension advice.", "Throughout growth"],
            ["Harvest and storage", "Harvest when pods mature and dry beans thoroughly before storage.", "At maturity"]
        ]
    ],
    "Tomatoes" => [
        "duration" => "Often about 3–5 months after transplanting, depending on variety",
        "water" => "Needs consistent watering and good drainage; avoid prolonged wet foliage where possible.",
        "steps" => [
            ["Plan and prepare", "Choose a well-drained site and plan a reliable water source.", "Before planting"],
            ["Seedling stage", "Use healthy seedlings from a reliable source and harden them before transplanting.", "Nursery stage"],
            ["Transplanting", "Transplant carefully and use locally recommended spacing.", "When seedlings are ready"],
            ["Water and support", "Water consistently, mulch if suitable, and stake or support plants as needed.", "After transplanting"],
            ["Pest and disease checks", "Inspect frequently for leaf spots, wilting, insects and fruit damage.", "Throughout growth"],
            ["Harvest", "Pick fruit at the appropriate maturity and handle carefully.", "As fruit ripens"]
        ]
    ],
    "Potatoes" => [
        "duration" => "Often about 3–4 months, depending on variety and conditions",
        "water" => "Needs adequate moisture but does poorly in waterlogged soil.",
        "steps" => [
            ["Site and soil preparation", "Use loose, well-drained soil and healthy certified or recommended seed tubers.", "Before planting"],
            ["Planting", "Plant at locally recommended depth and spacing.", "Planting time"],
            ["Earthing up", "Earth up around plants as recommended to protect developing tubers.", "During early growth"],
            ["Moisture and nutrition", "Keep moisture reasonably steady and follow local fertilizer guidance.", "During growth"],
            ["Pest and disease checks", "Check for blight symptoms and other problems; seek extension advice if unsure.", "Throughout growth"],
            ["Harvest and curing", "Harvest when mature and handle tubers carefully before storage.", "At maturity"]
        ]
    ]
];

$selectedCrop = trim((string)($_GET["crop"] ?? ""));
if (!isset($cropPlans[$selectedCrop])) {
    $savedCrops = trim((string)($profile["main_crops"] ?? ""));
    $savedSecondary = trim((string)($profile["secondary_crop"] ?? ""));
    $combined = strtolower($savedCrops . " " . $savedSecondary);
    $selectedCrop = "Maize";
    foreach (array_keys($cropPlans) as $cropName) {
        if (strpos($combined, strtolower($cropName)) !== false) {
            $selectedCrop = $cropName;
            break;
        }
    }
}
$plan = $cropPlans[$selectedCrop];
$locationParts = array_filter([
    trim((string)($profile["farm_location"] ?? "")),
    trim((string)($profile["sub_county"] ?? "")),
    trim((string)($profile["county"] ?? ""))
]);
$locationLabel = $locationParts ? implode(", ", $locationParts) : "Your farm location is not saved";
?>
<!DOCTYPE html>
<html lang="en">
<head><?php require __DIR__ . '/includes/pwa.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crop Calendar | VAYUMA TRIX</title>
<link rel="stylesheet" href="frontend/css/tokens.css">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
<style>
.va-profile-page { max-width: 950px; margin: 0 auto; padding: var(--va-space-6); }
.va-profile-brand { text-align: center; margin-bottom: var(--va-space-6); }
.va-profile-brand h1 { color: var(--va-primary); font-size: var(--va-font-size-3xl); letter-spacing: 0.05em; margin: 0; }
.va-profile-brand p { color: var(--va-text-muted); font-size: var(--va-font-size-xs); letter-spacing: 0.05em; margin-top: var(--va-space-1); }
.va-profile-card { background: var(--va-surface); border-radius: var(--va-radius-lg); padding: var(--va-space-6); box-shadow: var(--va-shadow-sm); border: 1px solid var(--va-border); }
.va-profile-heading { margin-bottom: var(--va-space-5); }
.va-profile-label { color: var(--va-primary); font-size: var(--va-font-size-xs); font-weight: var(--va-font-weight-bold); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: var(--va-space-2); }
.va-profile-heading h2 { font-size: var(--va-font-size-2xl); margin-bottom: var(--va-space-2); color: var(--va-text-primary); }
.va-profile-heading p { color: var(--va-text-secondary); line-height: var(--va-line-height-relaxed); }
.va-profile-welcome { background: var(--va-primary-light); border-radius: var(--va-radius-md); padding: var(--va-space-4); margin: var(--va-space-6) 0; color: #17663a; line-height: var(--va-line-height-relaxed); }
.va-profile-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: var(--va-space-4); }
.va-profile-form-group { display: flex; flex-direction: column; }
.va-profile-full { grid-column: 1 / -1; }
.va-profile-label-field { font-size: var(--va-font-size-sm); font-weight: var(--va-font-weight-bold); margin-bottom: var(--va-space-2); color: var(--va-text-secondary); }
.va-profile-input { width: 100%; padding: 0.75rem 1rem; font-size: var(--va-font-size-base); font-family: inherit; border: 1px solid var(--va-border); border-radius: var(--va-radius); background: var(--va-surface); color: var(--va-text-primary); }
.va-profile-input:focus { outline: none; border-color: var(--va-border-focus); box-shadow: 0 0 0 3px rgba(10, 138, 73, 0.1); }
.va-profile-hint { font-size: var(--va-font-size-xs); color: var(--va-text-muted); margin-top: var(--va-space-1); }
.va-profile-button-area { margin-top: var(--va-space-6); }
.va-profile-btn { width: 100%; border: none; padding: var(--va-space-4); background: var(--va-primary); color: #fff; border-radius: var(--va-radius); font-size: var(--va-font-size-base); font-weight: var(--va-font-weight-bold); cursor: pointer; }
.va-profile-btn:hover { background: var(--va-primary-hover); }
.va-profile-back { display: inline-block; margin-top: var(--va-space-4); color: var(--va-primary); text-decoration: none; font-weight: var(--va-font-weight-bold); }
.va-profile-error { background: #fde7e7; color: #a32121; padding: var(--va-space-3); border-radius: var(--va-radius-md); margin-bottom: var(--va-space-4); }
@media (max-width: 700px) {
  .va-profile-page { padding: var(--va-space-4); }
  .va-profile-card { padding: var(--va-space-4); }
  .va-profile-form-grid { grid-template-columns: 1fr; }
  .va-profile-full { grid-column: auto; }
}
</style>
<style>
.crop-select-form { display:flex; flex-wrap:wrap; gap:12px; align-items:end; margin-bottom:24px; }
.crop-select-form label { display:block; font-weight:bold; margin-bottom:7px; }
.crop-select-form select { min-width:220px; padding:12px; border:1px solid #ccd8cc; border-radius:9px; background:white; font-size:15px; }
.crop-select-form button { border:0; padding:13px 18px; background:#247a3b; color:white; border-radius:10px; font-weight:bold; cursor:pointer; }
.note { background:#fff8e7; border:1px solid #f0dfb2; color:#594b24; padding:16px 18px; border-radius:12px; margin:18px 0 24px; line-height:1.55; }
.location-line { color:#667066; margin-top:8px; }
@media(max-width:600px){.crop-select-form select,.crop-select-form button{width:100%;}}
</style>
</head>
<body>
<?php vayuma_language_selector(); ?>
<header class="header">
    <h1><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> VAYUMA TRIX</h1>
    <p>Smart Farming Ecosystem</p>
</header>
<main class="container"><?php require __DIR__ . '/includes/workspace_back.php'; ?>
    <div class="page-title">
        <h2><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Crop Calendar</h2>
        <p>Hello <?php echo htmlspecialchars((string)$full_name, ENT_QUOTES, "UTF-8"); ?>. Use this guide to plan key activities throughout the growing season.</p>
        <p class="location-line"><strong>Farm location:</strong> <?php echo htmlspecialchars($locationLabel, ENT_QUOTES, "UTF-8"); ?></p>
    </div>

    <form class="crop-select-form" method="get" action="crop_calendar.php">
        <div>
            <label for="crop">Choose a crop</label>
            <select class="va-profile-input" name="crop" id="crop">
                <?php foreach (array_keys($cropPlans) as $cropName): ?>
                    <option value="<?php echo htmlspecialchars($cropName, ENT_QUOTES, "UTF-8"); ?>" <?php echo $selectedCrop === $cropName ? "selected" : ""; ?>>
                        <?php echo htmlspecialchars($cropName, ENT_QUOTES, "UTF-8"); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="va-profile-btn">Show Calendar</button>
    </form>

    <section class="current-card">
        <h3><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> Your Farming Plan</h3>
        <div class="current-info">
            <div>
                <h2><?php echo htmlspecialchars($selectedCrop, ENT_QUOTES, "UTF-8"); ?></h2>
                <p><?php echo htmlspecialchars($plan["duration"], ENT_QUOTES, "UTF-8"); ?></p>
                <p><strong>Water guidance:</strong> <?php echo htmlspecialchars($plan["water"], ENT_QUOTES, "UTF-8"); ?></p>
            </div>
            <div class="season">Season guide</div>
        </div>
    </section>

    <section class="timeline">
        <h3><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> Farming Timeline for <?php echo htmlspecialchars($selectedCrop, ENT_QUOTES, "UTF-8"); ?></h3>
        <?php foreach ($plan["steps"] as $index => $step): ?>
            <div class="step">
                <div class="step-number"><?php echo $index + 1; ?></div>
                <div class="step-content">
                    <h4><?php echo htmlspecialchars($step[0], ENT_QUOTES, "UTF-8"); ?></h4>
                    <p><?php echo htmlspecialchars($step[1], ENT_QUOTES, "UTF-8"); ?></p>
                    <span class="date"><?php echo htmlspecialchars($step[2], ENT_QUOTES, "UTF-8"); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="tips">
        <h3>💡 VAYUMA Tips</h3>
        <ul>
            <li>Planting dates vary by county, altitude, rainfall onset and crop variety.</li>
            <li>Use local agricultural extension advice for exact planting dates, spacing and fertilizer rates.</li>
            <li>Check weather forecasts before planting and during sensitive growth stages.</li>
            <li>Inspect crops regularly for pests and disease; ask an extension officer if you cannot identify a problem.</li>
            <li>Keep records of planting date, inputs, observations and harvest to improve future planning.</li>
        </ul>
    </section>

    <div class="note">
        <strong>Important:</strong> This is a general crop activity guide, not a live county-specific planting forecast. It does not yet use current weather records to calculate planting dates. Confirm timing with local extension officers and current weather information.
    </div>

    <a href="farmer_dashboard.php" class="back-button">← Back to Farmer Dashboard</a>
</main>
<script src="assets/js/vayuma-language.js" defer></script>
</body>
</html>
