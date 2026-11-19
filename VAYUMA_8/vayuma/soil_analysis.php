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

</head>

<body>
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


        <form id="soilForm">


            <div class="va-profile-form-grid">


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">
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

                    <label class="va-profile-label-field">
                        Soil pH
                    </label>

                    <input
                        type="number"
                        id="ph_level"
                        step="0.1"
                        min="0"
                        max="14"
                        placeholder="e.g. 6.5"
                        required
                    >

                    <span class="va-profile-hint">
                        Most crops prefer approximately pH 5.5–7.5.
                    </span>

                </div>


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">
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

                    <label class="va-profile-label-field">
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

                    <label class="va-profile-label-field">
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

                    <label class="va-profile-label-field">
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

                    <label class="va-profile-label-field">
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
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Analyze My Soil
            </button>


        </form>


        <div
            class="result"
            id="result"
        >

            <h2>
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> VAYUMA Soil Assessment
            </h2>

            <p id="resultText"></p>

        </div>


    </div>

</div>


<script>

document
.getElementById("soilForm")
.addEventListener(
    "submit",
    function(event) {

        event.preventDefault();


        const ph =
            parseFloat(
                document.getElementById(
                    "ph_level"
                ).value
            );


        let result = "";


        /*
        |--------------------------------------------------------------------------
        | PH ANALYSIS
        |--------------------------------------------------------------------------
        */

        if (ph < 5.5) {

            result +=
                "<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Your soil is acidic. "
                + "Consider soil amendments recommended "
                + "by a qualified agricultural professional. ";

        }

        else if (ph > 7.5) {

            result +=
                "<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Your soil is alkaline. "
                + "Consider management practices suitable "
                + "for alkaline soils. ";

        }

        else {

            result +=
                "<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Your soil pH is within a generally "
                + "suitable range for many crops. ";

        }


        /*
        |--------------------------------------------------------------------------
        | SOIL TYPE
        |--------------------------------------------------------------------------
        */

        const soil =
            document.getElementById(
                "soil_type"
            ).value;


        if (soil === "Loamy") {

            result +=
                "Loamy soil generally provides a good "
                + "balance of drainage and nutrient retention. ";

        }

        else if (soil === "Sandy") {

            result +=
                "Sandy soil usually drains quickly. "
                + "Pay attention to irrigation and organic matter. ";

        }

        else if (soil === "Clay") {

            result +=
                "Clay soil can retain water and nutrients "
                + "but may require careful drainage management. ";

        } else if (soil === "Silty") {

            result +=
                "Silty soil often holds moisture and nutrients; protect it from erosion and compaction. ";

        } else if (soil === "Sandy Loam") {

            result +=
                "Sandy loam often balances drainage and moisture retention; add organic matter when appropriate. ";

        }


        /*
        |--------------------------------------------------------------------------
        | MOISTURE
        |--------------------------------------------------------------------------
        */

        const moisture =
            parseFloat(
                document.getElementById(
                    "moisture"
                ).value
            );


        if (!isNaN(moisture)) {

            if (moisture < 25) {

                result +=
                    "💧 Soil moisture appears low. "
                    + "Check irrigation needs and local weather "
                    + "conditions before watering. ";

            }

            else if (moisture > 75) {

                result +=
                    "💧 Soil moisture is high. "
                    + "Monitor drainage and avoid unnecessary "
                    + "irrigation. ";

            }

            else {

                result +=
                    "💧 Soil moisture is currently "
                    + "within a moderate range. ";

            }

        }


        /*
        |--------------------------------------------------------------------------
        | OPTIONAL NUTRIENT / ORGANIC MATTER NOTES
        |--------------------------------------------------------------------------
        | Values are not interpreted as low/normal/high because units and
        | laboratory methods can differ. Remind users to follow their report.
        */
        const nitrogen = document.getElementById("nitrogen").value.trim();
        const phosphorus = document.getElementById("phosphorus").value.trim();
        const potassium = document.getElementById("potassium").value.trim();
        const organicMatter = document.getElementById("organic_matter").value.trim();

        if (nitrogen || phosphorus || potassium || organicMatter) {
            result += "You entered optional nutrient or organic-matter test values. Compare these with the units and interpretation on your laboratory report; nutrient ranges vary by test method and soil. ";
        } else {
            result += "No nutrient test values were entered, so this assessment cannot determine nitrogen, phosphorus or potassium levels. A soil laboratory test can provide that information. ";
        }

        /*
        |--------------------------------------------------------------------------
        | DISPLAY RESULT
        |--------------------------------------------------------------------------
        */

        document.getElementById(
            "resultText"
        ).textContent = result;


        document.getElementById(
            "result"
        ).style.display = "block";

    }
);

</script>

<script src="assets/js/vayuma-language.js" defer></script>
</body>

</html>