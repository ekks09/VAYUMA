<?php
session_start();
require_once __DIR__ . '/includes/language.php';

/* Protect page: logged-in farmers only. */
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    header("Location: login.php");
    exit;
}

if (strtolower(trim((string)($_SESSION["role"] ?? ""))) !== "farmer") {
    header("Location: dashboard.php");
    exit;
}

require_once __DIR__ . "/config/db.php";

$full_name = $_SESSION["full_name"] ?? "Farmer";
$user_id = (int) $_SESSION["user_id"];

$profile = [
    "county" => "",
    "sub_county" => "",
    "farm_location" => "",
    "farm_size" => "",
    "farm_size_unit" => "",
    "soil_type" => "",
    "irrigation" => "",
    "main_crops" => "",
    "secondary_crop" => ""
];
$profileMessage = "";

$profileStmt = $conn->prepare(
    "SELECT county, sub_county, farm_location, farm_size, farm_size_unit,
            soil_type, irrigation, main_crops, secondary_crop
     FROM farmer_profiles
     WHERE user_id = ?
     LIMIT 1"
);

if ($profileStmt) {
    $profileStmt->bind_param("i", $user_id);
    if ($profileStmt->execute()) {
        $profileResult = $profileStmt->get_result();
        if ($profileResult && $profileResult->num_rows > 0) {
            $profile = array_merge($profile, $profileResult->fetch_assoc());
        } else {
            $profileMessage = "Complete your farmer profile to personalise these estimates.";
        }
    } else {
        $profileMessage = "Farm details could not be loaded. General suitability estimates are shown.";
    }
    $profileStmt->close();
} else {
    $profileMessage = "Farm details could not be loaded. General suitability estimates are shown.";
}

$county = trim((string)($profile["county"] ?? ""));
$subCounty = trim((string)($profile["sub_county"] ?? ""));
$farmLocation = trim((string)($profile["farm_location"] ?? ""));
$soil = strtolower(trim((string)($profile["soil_type"] ?? "")));
$irrigation = strtolower(trim((string)($profile["irrigation"] ?? "")));
$mainCrops = strtolower(trim((string)($profile["main_crops"] ?? "")));
$secondaryCrop = strtolower(trim((string)($profile["secondary_crop"] ?? "")));

function vayumaContainsAny(string $value, array $terms): bool {
    foreach ($terms as $term) {
        if ($term !== "" && strpos($value, $term) !== false) {
            return true;
        }
    }
    return false;
}

/*
 * These are transparent rule-based estimates, not predictions of yield,
 * profit, live weather, or market demand.
 */
$cropRules = [
    [
        "name" => "Maize", "icon" => "🌽", "base" => 70,
        "soils" => ["loam", "clay loam", "silt", "volcanic"],
        "water" => "Moderate",
        "description" => "Often suits well-drained soil when moisture is adequate, particularly during establishment and flowering."
    ],
    [
        "name" => "Beans", "icon" => "🫘", "base" => 72,
        "soils" => ["loam", "sandy loam", "volcanic"],
        "water" => "Moderate",
        "description" => "Can be useful in mixed or rotational farming. Avoid waterlogged soil and monitor moisture during flowering."
    ],
    [
        "name" => "Tomatoes", "icon" => "📋", "base" => 65,
        "soils" => ["loam", "sandy loam", "well-drained", "volcanic"],
        "water" => "High",
        "description" => "Needs reliable watering, good drainage, and regular pest and disease monitoring."
    ],
    [
        "name" => "Potatoes", "icon" => "🥔", "base" => 65,
        "soils" => ["loam", "sandy loam", "volcanic"],
        "water" => "Moderate",
        "description" => "Usually does best in suitable cool conditions and loose, well-drained soil."
    ]
];

$crops = [];
foreach ($cropRules as $rule) {
    $score = (int)$rule["base"];
    $reasons = [];

    if ($soil !== "") {
        if (vayumaContainsAny($soil, $rule["soils"])) {
            $score += 12;
            $reasons[] = "the recorded soil type appears compatible";
        } elseif (vayumaContainsAny($soil, ["heavy clay", "waterlogged"])) {
            $score -= 10;
            $reasons[] = "check drainage and soil structure before planting";
        } else {
            $reasons[] = "confirm soil suitability before planting";
        }
    } else {
        $reasons[] = "add your soil type to improve this estimate";
    }

    if ($irrigation !== "") {
        if (vayumaContainsAny($irrigation, ["yes", "available", "drip", "sprinkler", "irrigated"])) {
            $score += ($rule["name"] === "Tomatoes") ? 8 : 4;
            $reasons[] = "irrigation is recorded as available";
        } elseif (vayumaContainsAny($irrigation, ["no", "rain-fed", "rainfed", "none"])) {
            if ($rule["name"] === "Tomatoes") {
                $score -= 10;
                $reasons[] = "reliable water access is especially important";
            } elseif ($rule["name"] === "Beans" || $rule["name"] === "Maize") {
                $score -= 4;
                $reasons[] = "plan planting around dependable rainfall";
            }
        }
    } else {
        $reasons[] = "add irrigation details for a better estimate";
    }

    $cropKey = strtolower($rule["name"]);
    if (($mainCrops !== "" && strpos($mainCrops, $cropKey) !== false) ||
        ($secondaryCrop !== "" && strpos($secondaryCrop, $cropKey) !== false)) {
        $score += 3;
        $reasons[] = "you already list this crop on your farm";
    }

    $score = max(35, min(95, $score));
    if ($score >= 80) {
        $suitability = "Promising profile fit";
    } elseif ($score >= 68) {
        $suitability = "Worth considering";
    } else {
        $suitability = "Check conditions";
    }

    $crops[] = [
        "name" => $rule["name"],
        "icon" => $rule["icon"],
        "score" => $score,
        "climate" => $suitability,
        "market" => "Not checked",
        "water" => $rule["water"],
        "description" => $rule["description"],
        "reason" => implode("; ", array_slice(array_unique($reasons), 0, 2))
    ];
}

usort($crops, function ($a, $b) {
    return $b["score"] <=> $a["score"];
});
?>

<!DOCTYPE html>
<html lang="en">

<head><?php require __DIR__ . '/includes/pwa.php'; ?>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Crop Opportunities | VAYUMA</title>

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

</head>

<body>
<?php vayuma_language_selector(); ?>

<header class="header">

    <h1>🌾 VAYUMA</h1>

    <p>Smart Farming Ecosystem</p>

</header>


<main class="container"><?php require __DIR__ . '/includes/workspace_back.php'; ?>

    <div class="page-title">

        <h2>🌾 Crop Opportunities</h2>

        <p>
            Hello <?php echo htmlspecialchars($full_name); ?>.
            Explore preliminary crop-suitability estimates based on your saved farm profile.
            Scores are guidance only; they are not live weather calculations, market prices,
            guaranteed yields or profit predictions.
        </p>

    </div>

    <div class="recommendation" style="margin-bottom:22px;">
        <strong>Farm profile:</strong>
        <?php
        $profileParts = array_filter([$farmLocation, $subCounty, $county]);
        echo $profileParts ? htmlspecialchars(implode(", ", $profileParts)) : "Location not saved";
        ?>
        <?php if ($soil !== ""): ?>
            · <strong>Soil:</strong> <?php echo htmlspecialchars((string)$profile["soil_type"]); ?>
        <?php endif; ?>
        <?php if ($irrigation !== ""): ?>
            · <strong>Irrigation:</strong> <?php echo htmlspecialchars((string)$profile["irrigation"]); ?>
        <?php endif; ?>
    </div>

    <?php if ($profileMessage !== ""): ?>
        <div class="recommendation" style="margin-bottom:22px;">
            <?php echo htmlspecialchars($profileMessage); ?>
        </div>
    <?php endif; ?>

    <section class="crop-grid">

        <?php foreach ($crops as $crop): ?>

            <div class="crop-card">

                <div class="crop-header">

                    <div class="crop-name">

                        <div class="crop-icon">
                            <?php echo $crop["icon"]; ?>
                        </div>

                        <div>

                            <h3>
                                <?php echo htmlspecialchars($crop["name"]); ?>
                            </h3>

                            <p>
                                Profile-based estimate
                            </p>

                        </div>

                    </div>


                    <div class="opportunity-score">

                        <div class="score-number">
                            <?php echo $crop["score"]; ?>
                        </div>

                        <div class="score-label">
                            OPPORTUNITY
                        </div>

                    </div>

                </div>


                <p class="description">
                    <?php echo htmlspecialchars($crop["description"]); ?>
                </p>


                <div class="crop-info">

                    <div class="info-box">

                        <span>Climate</span>

                        <strong>
                            <?php echo htmlspecialchars($crop["climate"]); ?>
                        </strong>

                    </div>


                    <div class="info-box">

                        <span>Market</span>

                        <strong>
                            <?php echo htmlspecialchars($crop["market"]); ?>
                        </strong>

                    </div>


                    <div class="info-box">

                        <span>Water</span>

                        <strong>
                            <?php echo htmlspecialchars($crop["water"]); ?>
                        </strong>

                    </div>

                </div>


                <div class="progress">

                    <div
                        class="progress-bar"
                        style="width: <?php echo $crop["score"]; ?>%;">
                    </div>

                </div>


                <div class="recommendation">

                    <strong><?php echo htmlspecialchars($crop["climate"]); ?>.</strong>
                    <?php echo htmlspecialchars($crop["reason"]); ?>.
                    Confirm local growing conditions, suitable seed varieties and buyer prices before investing.

                </div>

            </div>

        <?php endforeach; ?>

    </section>


    <a href="farmer_dashboard.php" class="back-button">
        ← Back to Farmer Dashboard
    </a>

</main>

<script src="assets/js/vayuma-language.js" defer></script>
</body>

</html>