<?php

session_start();
require_once __DIR__ . '/includes/language.php';

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true
) {
    header("Location: login.php");
    exit;
}

if (strtolower(trim((string)($_SESSION['role'] ?? ''))) !== 'farmer') {
    header("Location: dashboard.php");
    exit;
}

require_once __DIR__ . '/config/db.php';

$full_name = $_SESSION['full_name'] ?? 'Farmer';
$user_id = (int) $_SESSION['user_id'];

$profile = [
    'county' => '',
    'sub_county' => '',
    'farm_location' => '',
    'farm_size' => '',
    'farm_size_unit' => '',
    'soil_type' => '',
    'irrigation' => '',
    'main_crops' => '',
    'secondary_crop' => '',
    'farming_experience' => ''
];

$profileLoaded = false;
$profileMessage = '';

$stmt = $conn->prepare(
    "SELECT county, sub_county, farm_location, farm_size, farm_size_unit,
            soil_type, irrigation, main_crops, secondary_crop, farming_experience
     FROM farmer_profiles
     WHERE user_id = ?
     LIMIT 1"
);

if ($stmt) {
    $stmt->bind_param('i', $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $profile = array_merge($profile, $result->fetch_assoc());
            $profileLoaded = true;
        } else {
            $profileMessage = 'Complete your farmer profile to receive a more personalised score.';
        }
    } else {
        $profileMessage = 'Your farm profile could not be read. The score below reflects missing information.';
    }
    $stmt->close();
} else {
    $profileMessage = 'Your farm profile could not be read. The score below reflects missing information.';
}

$soilType = strtolower(trim((string)($profile['soil_type'] ?? '')));
$irrigation = strtolower(trim((string)($profile['irrigation'] ?? '')));
$mainCrops = trim((string)($profile['main_crops'] ?? ''));
$secondaryCrop = trim((string)($profile['secondary_crop'] ?? ''));
$county = trim((string)($profile['county'] ?? ''));
$subCounty = trim((string)($profile['sub_county'] ?? ''));
$farmLocation = trim((string)($profile['farm_location'] ?? ''));
$farmSize = trim((string)($profile['farm_size'] ?? ''));
$farmSizeUnit = trim((string)($profile['farm_size_unit'] ?? ''));
$experience = trim((string)($profile['farming_experience'] ?? ''));

function vayumaHasAny(string $value, array $terms): bool {
    foreach ($terms as $term) {
        if ($term !== '' && strpos($value, $term) !== false) {
            return true;
        }
    }
    return false;
}

function vayumaPercent(int $value): int {
    return max(0, min(100, $value));
}

/*
 * These are profile-completeness/readiness estimates, not laboratory,
 * sensor, live-weather, yield, or profitability measurements.
 */
$soilScore = 40;
if ($soilType !== '') {
    $soilScore = 65;
    if (vayumaHasAny($soilType, ['loam', 'sandy loam', 'clay loam', 'volcanic', 'silt'])) {
        $soilScore = 82;
    } elseif (vayumaHasAny($soilType, ['clay', 'sandy', 'black cotton'])) {
        $soilScore = 70;
    }
}

$waterScore = 40;
if ($irrigation !== '') {
    if (vayumaHasAny($irrigation, ['yes', 'available', 'drip', 'sprinkler', 'irrigated'])) {
        $waterScore = 82;
    } elseif (vayumaHasAny($irrigation, ['rain-fed', 'rainfed', 'seasonal'])) {
        $waterScore = 65;
    } elseif (vayumaHasAny($irrigation, ['no', 'none', 'unavailable'])) {
        $waterScore = 48;
    } else {
        $waterScore = 60;
    }
}

$cropScore = 40;
if ($mainCrops !== '' || $secondaryCrop !== '') {
    $cropScore = 72;
    if ($mainCrops !== '' && $secondaryCrop !== '') {
        $cropScore = 82;
    }
}

$profileScore = 30;
$profileFields = [
    $county,
    $subCounty,
    $farmLocation,
    $farmSize,
    $farmSizeUnit,
    $soilType,
    $irrigation,
    $mainCrops,
    $experience
];
$filledFields = 0;
foreach ($profileFields as $field) {
    if (trim((string)$field) !== '') {
        $filledFields++;
    }
}
$profileScore = (int) round(($filledFields / count($profileFields)) * 100);

$score = (int) round(
    ($soilScore * 0.25) +
    ($waterScore * 0.25) +
    ($cropScore * 0.25) +
    ($profileScore * 0.25)
);

if ($score >= 85) {
    $statusText = 'Strong Farm Profile Readiness';
    $statusMessage = 'Your saved farm information provides a useful starting point for planning. Continue checking local weather, soil conditions and crop requirements before making planting decisions.';
} elseif ($score >= 70) {
    $statusText = 'Good Farm Profile Readiness';
    $statusMessage = 'Your profile provides a reasonable starting point. Complete any missing details and check local growing conditions to strengthen your farm plan.';
} elseif ($score >= 50) {
    $statusText = 'Developing Farm Readiness';
    $statusMessage = 'Some important farm details may be missing. Update your soil type, water access, crop information and farm location to improve this assessment.';
} else {
    $statusText = 'More Farm Details Needed';
    $statusMessage = 'Add more information to your farmer profile. This score is low mainly because limited information is available, not because it proves your farm is performing poorly.';
}

$recommendations = [];
if ($soilType === '') {
    $recommendations[] = 'Add your soil type. For accurate nutrient and pH results, consider a soil test.';
}
if ($irrigation === '') {
    $recommendations[] = 'Record whether you use irrigation or rely on rainfall.';
}
if ($mainCrops === '' && $secondaryCrop === '') {
    $recommendations[] = 'Add the crops you currently grow or plan to grow.';
}
if ($county === '' && $farmLocation === '') {
    $recommendations[] = 'Add your county or farm location so local guidance can be more relevant.';
}
if ($farmSize === '') {
    $recommendations[] = 'Record your farm size to help with planting and input planning.';
}
if (!$recommendations) {
    $recommendations[] = 'Your profile has useful planning details. Check the Climate Guidance page and crop-specific requirements before planting.';
}

$locationParts = array_filter([$farmLocation, $subCounty, $county]);
$locationText = $locationParts ? implode(', ', $locationParts) : 'Not provided';
$soilDisplay = trim((string)($profile['soil_type'] ?? '')) ?: 'Not provided';
$waterDisplay = trim((string)($profile['irrigation'] ?? '')) ?: 'Not provided';
?>

<!DOCTYPE html>
<html lang="en">

<head><?php require __DIR__ . '/includes/pwa.php'; ?>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Farming Score | VAYUMA</title>

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
<?php require __DIR__ . '/includes/workspace_back.php'; ?>

<div class="va-profile-page">

    <div class="header">

        <div class="logo">
            VAYUMA
        </div>

        <a
            href="farmer_dashboard.php"
            class="back"
        >
            ← Farmer Dashboard
        </a>

    </div>


    <div class="hero">

        <small>
            VAYUMA SMART FARMING
        </small>

        <h1>
            Your Farming Score
        </h1>

        <p>
            Hello <?php echo htmlspecialchars($full_name); ?>.
            Your farming score gives you a simple overview
            of your current farming readiness.
        </p>

    </div>


    <div class="score-card">

        <div class="score-circle">

            <div
                class="score-number"
                id="score"
            >
                <?php echo $score; ?>
            </div>

            <div class="score-label">
                / 100
            </div>

        </div>


        <div
            class="status"
            id="status"
        >
            <?php echo htmlspecialchars($statusText); ?>
        </div>


        <p
            class="message"
            id="message"
        >
            <?php echo htmlspecialchars($statusMessage); ?>
        </p>

    </div>


    <div class="tip" style="margin-bottom:25px;">
        <h2><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-herbs"/></svg> Your Farm Profile</h2>
        <p><strong>Location:</strong> <?php echo htmlspecialchars($locationText); ?></p>
        <p><strong>Soil type:</strong> <?php echo htmlspecialchars($soilDisplay); ?></p>
        <p><strong>Water access:</strong> <?php echo htmlspecialchars($waterDisplay); ?></p>
        <?php if ($profileMessage !== ''): ?>
            <p><?php echo htmlspecialchars($profileMessage); ?></p>
        <?php endif; ?>
    </div>

    <div class="factors">


        <div class="factor">

            <h3>
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> Soil Health
            </h3>

            <div class="factor-value"><?php echo $soilScore; ?>%</div>

            <div class="bar">

                <div
                    class="bar-fill"
                    style="width:80%"
                ></div>

            </div>

        </div>

<div class="factor">

            <h3>
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Farm Profile Completeness
            </h3>

            <div class="factor-value"><?php echo $profileScore; ?>%</div>

            <div class="bar">

                <div
                    class="bar-fill"
                    style="width:75%"
                ></div>

            </div>

        </div>

<div class="factor">

            <h3>
                💧 Water Management
            </h3>

            <div class="factor-value"><?php echo $waterScore; ?>%</div>

            <div class="bar">

                <div
                    class="bar-fill"
                    style="width:82%"
                ></div>

            </div>

        </div>

<div class="factor">

            <h3>
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> Crop Readiness
            </h3>

            <div class="factor-value"><?php echo $cropScore; ?>%</div>

            <div class="bar">

                <div
                    class="bar-fill"
                    style="width:76%"
                ></div>

            </div>

        </div>


    </div>

<div class="tip">

        <h2>
            💡 VAYUMA Recommendation
        </h2>

        <p>
            <?php echo htmlspecialchars($recommendations[0]); ?>
            This score uses saved profile details and simple guidance rules. It does not measure actual soil nutrients, live weather, crop yield or profit.
            <br><br><strong>Next improvement:</strong> <?php echo htmlspecialchars($recommendations[0]); ?>
        </p>

    </div>

</div>


<script>
/* Farming readiness values are calculated in PHP from the saved farmer profile. */
</script>

<script src="assets/js/vayuma-language.js" defer></script>
</body>

</html>