<?php
session_start();
require_once __DIR__ . '/includes/role_routes.php';
require_once __DIR__ . '/includes/language.php';

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true
) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/config/db.php";

$userId = (int) $_SESSION["user_id"];
$savedSoilType = "";
$profileNotice = "";

$stmt = $conn->prepare(
    "SELECT soil_type FROM farmer_profiles WHERE user_id = ? LIMIT 1"
);
if ($stmt) {
    $stmt->bind_param("i", $userId);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $savedSoilType = trim((string)($row["soil_type"] ?? ""));
        } else {
            $profileNotice = "No saved soil type was found in your farmer profile. You can still select it below.";
        }
    } else {
        $profileNotice = "We could not load your saved soil type. You can still continue by selecting it below.";
    }
    $stmt->close();
} else {
    $profileNotice = "We could not load your saved soil type. You can still continue by selecting it below.";
}

function soilOptionSelected(string $option, string $saved): string {
    return strcasecmp(trim($option), trim($saved)) === 0 ? " selected" : "";
}
?>

<!DOCTYPE html>

<html lang="en">

<head><?php require __DIR__ . '/includes/pwa.php'; ?>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Soil Analysis | VAYUMA</title>

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

<link rel="stylesheet" href="frontend/css/agriculture-tools.css?v=1">
</head>

<body class="va-soil-tool">
<?php vayuma_language_selector(); ?>

<div class="va-profile-page">

    <div class="header">

        <div class="logo">
            VAYUMA
        </div>

        <a
            href="<?= htmlspecialchars(vayuma_dashboard_return_route(), ENT_QUOTES, 'UTF-8') ?>"
            class="back"
        >
            ← Back to Dashboard
        </a>

    </div>


    <div class="hero">

        <small>
            VAYUMA SOIL INTELLIGENCE
        </small>

        <h1>
            Understand your soil
        </h1>

        <p>
            Enter measurements from a soil test if you have them, or select your soil type
            for general guidance. VAYUMA does not measure soil directly, and nutrient advice
            depends on accurate test results.
        </p>

    </div>

    <div class="hint" style="font-size:14px; margin:0 0 22px; line-height:1.6;">
        <strong>Important:</strong> Enter pH and nutrient values only if you have measured them
        or received them from a soil laboratory. Do not guess test values.
        <?php if ($profileNotice !== ""): ?>
            <br><?php echo htmlspecialchars($profileNotice, ENT_QUOTES, "UTF-8"); ?>
        <?php elseif ($savedSoilType !== ""): ?>
            <br>We found your saved soil type: <strong><?php echo htmlspecialchars($savedSoilType, ENT_QUOTES, "UTF-8"); ?></strong>.
        <?php endif; ?>
    </div>

    <div class="form-card">

        <h2>
            <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> Soil Information
        </h2>


        <p><a href="soil_records.php">Save or view laboratory test history →</a> · <a href="stakeholder_directory.php?role=extension_officer">Find an extension officer →</a></p>
        <form id="soilForm">


            <div class="va-profile-form-grid">


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field" for="soil_type">
                        Soil Type
                    </label>

                    <select
                        id="soil_type"
                        required
                    >

                        <option value="">
                            Select soil type
                        </option>

                        <option value="Loamy"<?php echo soilOptionSelected("Loamy", $savedSoilType); ?>>
                            Loamy
                        </option>

                        <option value="Sandy"<?php echo soilOptionSelected("Sandy", $savedSoilType); ?>>
                            Sandy
                        </option>

                        <option value="Clay"<?php echo soilOptionSelected("Clay", $savedSoilType); ?>>
                            Clay
                        </option>

                        <option value="Silty"<?php echo soilOptionSelected("Silty", $savedSoilType); ?>>
                            Silty
                        </option>

                        <option value="Sandy Loam"<?php echo soilOptionSelected("Sandy Loam", $savedSoilType); ?>>
                            Sandy Loam
                        </option>

                    </select>

                </div>


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field" for="ph_level">
                        Soil pH
                    </label>

                    <input
                        type="number"
                        id="ph_level"
                        step="0.1"
                        min="0"
                        max="14"
                        placeholder="e.g. 6.5 (optional)"
                    >

                    <span class="va-profile-hint">
                        Interpret pH for your crop using your laboratory method and local advice.
                    </span>

                </div>


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field" for="nitrogen">
                        Nitrogen (N)
                    </label>

                    <input
                        type="number"
                        id="nitrogen"
                        step="0.01"
                        placeholder="e.g. 25"
                    >

                    <span class="va-profile-hint">
                        Enter value from your soil test.
                    </span>

                </div>


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field" for="phosphorus">
                        Phosphorus (P)
                    </label>

                    <input
                        type="number"
                        id="phosphorus"
                        step="0.01"
                        placeholder="e.g. 15"
                    >

                </div>


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field" for="potassium">
                        Potassium (K)
                    </label>

                    <input
                        type="number"
                        id="potassium"
                        step="0.01"
                        placeholder="e.g. 30"
                    >

                </div>


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field" for="organic_matter">
                        Organic Matter (%)
                    </label>

                    <input
                        type="number"
                        id="organic_matter"
                        step="0.01"
                        placeholder="e.g. 3"
                    >

                </div>


                <div class="form-group full">

                    <label class="va-profile-label-field" for="moisture">
                        Soil Moisture (%)
                    </label>

                    <input
                        type="number"
                        id="moisture"
                        step="0.01"
                        min="0"
                        max="100"
                        placeholder="e.g. 45"
                    >

                </div>


            </div>


            <button
                type="submit"
                class="button"
            >
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Show General Guidance
            </button>


        </form>


        <div
            class="result"
            id="result"
        >

            <h2>
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> General Soil Guidance
            </h2>

            <p id="resultText"></p>

        </div>


    </div>

</div>


<script>
document.getElementById('soilForm').addEventListener('submit', function(event) {
  event.preventDefault();
  const ph = document.getElementById('ph_level').value;
  const soil = document.getElementById('soil_type').value;
  let result = 'Soil type is your observation, not a laboratory measurement. ';
  if (soil) result += 'Selected type: ' + soil + '. ';
  if (ph !== '') result += 'You entered pH ' + ph + '. pH describes acidity or alkalinity; suitable ranges depend on crop and test method. ';
  else result += 'No pH value was supplied. ';
  result += 'Nutrient values cannot be inferred from soil type. Compare measured values and units with your laboratory report and ask an extension officer. No fertilizer or lime rate is calculated.';
  document.getElementById('resultText').textContent = result;
  document.getElementById('result').style.display = 'block';
});
</script>

<script src="assets/js/vayuma-language.js" defer></script>
</body>

</html>