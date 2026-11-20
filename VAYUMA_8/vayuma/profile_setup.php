<?php
session_start();
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/language.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php'); exit;
}
if (($_SESSION['role'] ?? '') !== 'farmer') { http_response_code(403); exit('Access denied.'); }
require_once __DIR__ . '/config/db.php';
$user_id = (int)$_SESSION['user_id'];
$farmProfile = []; $profileDuplicate = false;
try {
    $stmt = $conn->prepare('SELECT farm_name,county,sub_county,farm_location,farm_size,farm_size_unit,farming_experience,main_crops,soil_type,irrigation,secondary_crop FROM farmer_profiles WHERE user_id=? ORDER BY id LIMIT 2');
    $stmt->bind_param('i', $user_id); $stmt->execute(); $result = $stmt->get_result();
    $farmProfile = $result->fetch_assoc() ?: []; $profileDuplicate = (bool)$result->fetch_assoc(); $stmt->close();
} catch (Throwable $e) {
    error_log('VAYUMA farm profile lookup failed.');
    http_response_code(503); exit('Farm profile is temporarily unavailable. Please try again.');
}
$profileError = (string)($_SESSION['farm_profile_error'] ?? ''); unset($_SESSION['farm_profile_error']);
$old = $_SESSION['farm_profile_old'] ?? []; unset($_SESSION['farm_profile_old']);
if (is_array($old)) foreach ($old as $key => $value) if (is_string($value)) $farmProfile[$key] = $value;
$farmValue = static fn($key) => htmlspecialchars((string)($farmProfile[$key] ?? ''), ENT_QUOTES, 'UTF-8');
$farmSelected = static fn($key, $value) => (string)($farmProfile[$key] ?? '') === $value ? ' selected' : '';
$full_name = $_SESSION['full_name'] ?? '';
$phone = $_SESSION['phone'] ?? '';
$email = $_SESSION['email'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">

<head><?php require __DIR__ . '/includes/pwa.php'; ?>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Farmer Profile | VAYUMA</title>

    <link rel="stylesheet" href="frontend/css/tokens.css?v=20261110">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css?v=20261110">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
<link rel="stylesheet" href="frontend/css/profile_forms.css?v=20261110-3">
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

<body class="va-profile-document">
<?php vayuma_language_selector(); ?>
<?php require __DIR__ . '/includes/profile_navigation.php'; ?>


<header class="header">

    <div class="logo-area">

        <!-- Change this path if your logo is stored elsewhere -->
        <img src="images/vayuma-logo.jpg"
             alt="VAYUMA Logo">

        <div>
            <h2>VAYUMA</h2>
            <small>SMART ECOSYSTEM</small>
        </div>

    </div>

</header>


<main class="container">

    <div class="va-profile-heading">

        <span>FARMER PROFILE</span>

        <h1>
            Tell us about your farm <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg>
        </h1>

        <p>
            This information helps VAYUMA provide
            personalized agricultural guidance.
        </p>

    </div>


    <?php if ($profileDuplicate): ?><p role="alert">Multiple farm profiles were found. Please contact support before editing.</p><?php elseif ($profileError !== ''): ?><p role="alert"><?= htmlspecialchars($profileError, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <form action="auth/save_farmer.php"
          method="POST"><?= vayuma_csrf_field() ?>


        <!-- PERSONAL INFORMATION -->

        <div class="va-profile-card">

            <div class="card-title">

                <h2><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-profile"/></svg> Personal Information</h2>

                <p>
                    Your information from your VAYUMA account.
                </p>

            </div>


            <div class="va-profile-form-grid">

                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">Full Name</label>

                    <input
                        type="text"
                        value="<?php echo htmlspecialchars($full_name); ?>"
                        readonly
                    >

                </div>


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">Phone Number</label>

                    <input
                        type="text"
                        value="<?php echo htmlspecialchars($phone); ?>"
                        readonly
                    >

                </div>


                <div class="form-group full">

                    <label class="va-profile-label-field">Email Address</label>

                    <input
                        type="email"
                        value="<?php echo htmlspecialchars($email); ?>"
                        readonly
                    >

                </div>

            </div>

        </div>



        <!-- FARM INFORMATION -->

        <div class="va-profile-card">

            <div class="card-title">

                <h2><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-tractor"/></svg> Farm Information</h2>

                <p>
                    Tell VAYUMA about your farm.
                </p>

            </div>


            <div class="va-profile-form-grid">


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">
                        Farm Name
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        name="farm_name"
                        value="<?= $farmValue('farm_name') ?>"
                        placeholder="e.g. Green Valley Farm"
                        required
                    >

                </div>


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">
                        County
                        <span>*</span>
                    </label>

                    <select
                        name="county"
                        required
                    >
<?php if (!empty($farmProfile['county']) && !in_array($farmProfile['county'], ['Baringo', 'Bomet', 'Bungoma', 'Busia', 'Elgeyo-Marakwet', 'Embu', 'Garissa', 'Homa Bay', 'Isiolo', 'Kajiado', 'Kakamega', 'Kericho', 'Kiambu', 'Kilifi', 'Kirinyaga', 'Kisii', 'Kisumu', 'Kitui', 'Kwale', 'Laikipia', 'Lamu', 'Machakos', 'Makueni', 'Mandera', 'Marsabit', 'Meru', 'Migori', 'Mombasa', 'Murang\'a', 'Nairobi', 'Nakuru', 'Nandi', 'Narok', 'Nyamira', 'Nyandarua', 'Nyeri', 'Samburu', 'Siaya', 'Taita-Taveta', 'Tana River', 'Tharaka-Nithi', 'Trans Nzoia', 'Turkana', 'Uasin Gishu', 'Vihiga', 'Wajir', 'West Pokot'], true)): ?><option value="<?= $farmValue('county') ?>" selected><?= $farmValue('county') ?> (existing)</option><?php endif; ?>


                        <option value="">
                            Select County
                        </option>

                        <option<?= $farmSelected('county', 'Baringo') ?>>Baringo</option>
                        <option<?= $farmSelected('county', 'Bomet') ?>>Bomet</option>
                        <option<?= $farmSelected('county', 'Bungoma') ?>>Bungoma</option>
                        <option<?= $farmSelected('county', 'Busia') ?>>Busia</option>
                        <option<?= $farmSelected('county', 'Elgeyo-Marakwet') ?>>Elgeyo-Marakwet</option>
                        <option<?= $farmSelected('county', 'Embu') ?>>Embu</option>
                        <option<?= $farmSelected('county', 'Garissa') ?>>Garissa</option>
                        <option<?= $farmSelected('county', 'Homa Bay') ?>>Homa Bay</option>
                        <option<?= $farmSelected('county', 'Isiolo') ?>>Isiolo</option>
                        <option<?= $farmSelected('county', 'Kajiado') ?>>Kajiado</option>
                        <option<?= $farmSelected('county', 'Kakamega') ?>>Kakamega</option>
                        <option<?= $farmSelected('county', 'Kericho') ?>>Kericho</option>
                        <option<?= $farmSelected('county', 'Kiambu') ?>>Kiambu</option>
                        <option<?= $farmSelected('county', 'Kilifi') ?>>Kilifi</option>
                        <option<?= $farmSelected('county', 'Kirinyaga') ?>>Kirinyaga</option>
                        <option<?= $farmSelected('county', 'Kisii') ?>>Kisii</option>
                        <option<?= $farmSelected('county', 'Kisumu') ?>>Kisumu</option>
                        <option<?= $farmSelected('county', 'Kitui') ?>>Kitui</option>
                        <option<?= $farmSelected('county', 'Kwale') ?>>Kwale</option>
                        <option<?= $farmSelected('county', 'Laikipia') ?>>Laikipia</option>
                        <option<?= $farmSelected('county', 'Lamu') ?>>Lamu</option>
                        <option<?= $farmSelected('county', 'Machakos') ?>>Machakos</option>
                        <option<?= $farmSelected('county', 'Makueni') ?>>Makueni</option>
                        <option<?= $farmSelected('county', 'Mandera') ?>>Mandera</option>
                        <option<?= $farmSelected('county', 'Marsabit') ?>>Marsabit</option>
                        <option<?= $farmSelected('county', 'Meru') ?>>Meru</option>
                        <option<?= $farmSelected('county', 'Migori') ?>>Migori</option>
                        <option<?= $farmSelected('county', 'Mombasa') ?>>Mombasa</option>
                        <option<?= $farmSelected('county', 'Murang\'a') ?>>Murang'a</option>
                        <option<?= $farmSelected('county', 'Nairobi') ?>>Nairobi</option>
                        <option<?= $farmSelected('county', 'Nakuru') ?>>Nakuru</option>
                        <option<?= $farmSelected('county', 'Nandi') ?>>Nandi</option>
                        <option<?= $farmSelected('county', 'Narok') ?>>Narok</option>
                        <option<?= $farmSelected('county', 'Nyamira') ?>>Nyamira</option>
                        <option<?= $farmSelected('county', 'Nyandarua') ?>>Nyandarua</option>
                        <option<?= $farmSelected('county', 'Nyeri') ?>>Nyeri</option>
                        <option<?= $farmSelected('county', 'Samburu') ?>>Samburu</option>
                        <option<?= $farmSelected('county', 'Siaya') ?>>Siaya</option>
                        <option<?= $farmSelected('county', 'Taita-Taveta') ?>>Taita-Taveta</option>
                        <option<?= $farmSelected('county', 'Tana River') ?>>Tana River</option>
                        <option<?= $farmSelected('county', 'Tharaka-Nithi') ?>>Tharaka-Nithi</option>
                        <option<?= $farmSelected('county', 'Trans Nzoia') ?>>Trans Nzoia</option>
                        <option<?= $farmSelected('county', 'Turkana') ?>>Turkana</option>
                        <option<?= $farmSelected('county', 'Uasin Gishu') ?>>Uasin Gishu</option>
                        <option<?= $farmSelected('county', 'Vihiga') ?>>Vihiga</option>
                        <option<?= $farmSelected('county', 'Wajir') ?>>Wajir</option>
                        <option<?= $farmSelected('county', 'West Pokot') ?>>West Pokot</option>

                    </select>

                </div>


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">
                        Sub-County
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        name="sub_county"
                        value="<?= $farmValue('sub_county') ?>"
                        placeholder="e.g. Githunguri"
                        required
                    >

                </div>


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">
                        Farm Location
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        name="farm_location"
                        value="<?= $farmValue('farm_location') ?>"
                        placeholder="e.g. Limuru"
                        required
                    >

                </div>


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">
                        Farm Size
                        <span>*</span>
                    </label>

                    <div class="unit-box">

                        <input
                            type="text"
                            name="farm_size"
                        value="<?= $farmValue('farm_size') ?>"
                            placeholder="e.g. 5"
                            required
                        >

                        <select class="va-profile-input" name="farm_size_unit">
<?php if (!empty($farmProfile['farm_size_unit']) && !in_array($farmProfile['farm_size_unit'], ['acres', 'hectares'], true)): ?><option value="<?= $farmValue('farm_size_unit') ?>" selected><?= $farmValue('farm_size_unit') ?> (existing)</option><?php endif; ?>


                            <option value="acres"<?= $farmSelected('farm_size_unit', 'acres') ?>>
                                Acres
                            </option>

                            <option value="hectares"<?= $farmSelected('farm_size_unit', 'hectares') ?>>
                                Hectares
                            </option>

                        </select>

                    </div>

                </div>


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">
                        Farming Experience
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        name="farming_experience"
                        value="<?= $farmValue('farming_experience') ?>"
                        placeholder="e.g. 10 years"
                        required
                    >

                </div>


            </div>

        </div>



        <!-- CROPS AND SOIL -->

        <div class="va-profile-card">

            <div class="card-title">

                <h2><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> Crops & Soil</h2>

                <p>
                    This information will help VAYUMA
                    generate farming recommendations.
                </p>

            </div>


            <div class="va-profile-form-grid">


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">
                        Main Crop
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        name="main_crops"
                        value="<?= $farmValue('main_crops') ?>"
                        placeholder="e.g. Maize"
                        required
                    >

                </div>


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">
                        Secondary Crop
                    </label>

                    <input
                        type="text"
                        name="secondary_crop"
                        value="<?= $farmValue('secondary_crop') ?>"
                        placeholder="e.g. Beans"
                    >

                </div>


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">
                        Soil Type
                        <span>*</span>
                    </label>

                    <select
                        name="soil_type"
                        required
                    >
<?php if (!empty($farmProfile['soil_type']) && !in_array($farmProfile['soil_type'], ['Loamy', 'Clay', 'Sandy', 'Silty', 'Black Cotton', 'Volcanic', 'Red Soil', 'Unknown'], true)): ?><option value="<?= $farmValue('soil_type') ?>" selected><?= $farmValue('soil_type') ?> (existing)</option><?php endif; ?>


                        <option value="">
                            Select Soil Type
                        </option>

                        <option<?= $farmSelected('soil_type', 'Loamy') ?>>Loamy</option>
                        <option<?= $farmSelected('soil_type', 'Clay') ?>>Clay</option>
                        <option<?= $farmSelected('soil_type', 'Sandy') ?>>Sandy</option>
                        <option<?= $farmSelected('soil_type', 'Silty') ?>>Silty</option>
                        <option<?= $farmSelected('soil_type', 'Black Cotton') ?>>Black Cotton</option>
                        <option<?= $farmSelected('soil_type', 'Volcanic') ?>>Volcanic</option>
                        <option<?= $farmSelected('soil_type', 'Red Soil') ?>>Red Soil</option>
                        <option<?= $farmSelected('soil_type', 'Unknown') ?>>Unknown</option>

                    </select>

                </div>


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">
                        Irrigation
                        <span>*</span>
                    </label>

                    <select
                        name="irrigation"
                        required
                    >
<?php if (!empty($farmProfile['irrigation']) && !in_array($farmProfile['irrigation'], ['Rain-fed', 'Irrigated', 'Both'], true)): ?><option value="<?= $farmValue('irrigation') ?>" selected><?= $farmValue('irrigation') ?> (existing)</option><?php endif; ?>


                        <option value="">
                            Select irrigation type
                        </option>

                        <option<?= $farmSelected('irrigation', 'Rain-fed') ?>>Rain-fed</option>
                        <option<?= $farmSelected('irrigation', 'Irrigated') ?>>Irrigated</option>
                        <option<?= $farmSelected('irrigation', 'Both') ?>>Both</option>

                    </select>

                </div>


            </div>

        </div>



        <!-- ACTIONS -->

        <div class="actions">

            <a
                href="farmer_dashboard.php"
                class="cancel"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="save" <?= $profileDuplicate ? 'disabled' : '' ?>
            >
                Save Farm Profile →
            </button>

        </div>


    </form>

</main>


<script src="assets/js/vayuma-language.js" defer></script>
</body>
</html>