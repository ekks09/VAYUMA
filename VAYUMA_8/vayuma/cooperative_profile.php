<?php

session_start();
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/language.php';


/*
|--------------------------------------------------------------------------
| REQUIRE LOGIN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true
) {
    header("Location: login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| REQUIRE COOPERATIVE ROLE
|--------------------------------------------------------------------------
*/

$role = strtolower(trim($_SESSION['role'] ?? ''));

if ($role !== 'cooperative') {
    header("Location: role.php");
    exit;
}


require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/profile_prefill.php';
try {
    $profile = vayuma_existing_profile($conn, 'cooperative_profiles', (int)$_SESSION['user_id']);
} catch (Throwable $e) {
    error_log('VAYUMA cooperative profile lookup failed.');
    http_response_code(503);
    exit('Your profile could not be loaded. Please try again or contact support.');
}

$full_name = $_SESSION['full_name'] ?? 'Cooperative User';

?>

<!DOCTYPE html>
<html lang="en">

<head><?php require __DIR__ . '/includes/pwa.php'; ?>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Cooperative Profile | VAYUMA</title>


<link rel="stylesheet" href="frontend/css/tokens.css?v=20261110">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css?v=20261110">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
<link rel="stylesheet" href="frontend/css/profile_forms.css?v=20261120-1">
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
<?php require __DIR__ . '/includes/profile_navigation.php'; ?>


<div class="va-profile-page">


    <div class="va-profile-brand">

        <h1>VAYUMA</h1>

        <p>SMART ECOSYSTEM</p>

    </div>


    <div class="va-profile-card">


        <div class="va-profile-heading">

            <div class="va-profile-label">
                COOPERATIVE PROFILE
            </div>

            <h2>
                Register your cooperative
            </h2>

            <p>
                Tell VAYUMA about your cooperative so you
                can connect farmers, buyers, markets and
                agricultural services.
            </p>

        </div>


        <div class="va-profile-welcome">

            Welcome,
            <strong>
                <?php echo htmlspecialchars($full_name); ?>
            </strong>
            

            <br>

            Complete your cooperative information to
            continue to your dashboard.

        </div>


        <form
            action="auth/save_cooperative.php"
            method="POST"
        ><?= vayuma_csrf_field() ?>


            <div class="va-profile-form-grid">


                <!-- COOPERATIVE NAME -->

                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">
                        Cooperative Name
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="cooperative_name"
                        placeholder="e.g. Green Harvest Farmers Cooperative"
                        required
                     value="<?= vayuma_profile_value($profile['cooperative_name'] ?? '') ?>">

                </div>


                <!-- REGISTRATION NUMBER -->

                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">
                        Registration Number
                    </label>

                    <input
                        type="text"
                        name="registration_number"
                        placeholder="e.g. COOP/12345"
                     value="<?= vayuma_profile_value($profile['registration_number'] ?? '') ?>">

                </div>


                <!-- COOPERATIVE TYPE -->

                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">
                        Cooperative Type
                        <span class="required">*</span>
                    </label>

                    <select
                        name="cooperative_type"
                        required
                    >
                        <?php if (!empty($profile['cooperative_type']) && !in_array((string)$profile['cooperative_type'], ['Agricultural', 'Dairy', 'Coffee', 'Tea', 'Horticulture', 'Livestock', 'Multi-purpose', 'Other'], true)): ?>
                        <option value="<?= vayuma_profile_value($profile['cooperative_type']) ?>" selected><?= vayuma_profile_value($profile['cooperative_type']) ?></option>
                        <?php endif; ?>

                        <option value="">
                            Select cooperative type
                        </option>

                        <option value="Agricultural" <?= ($profile['cooperative_type'] ?? '') === 'Agricultural' ? 'selected' : '' ?>>
                            Agricultural Cooperative
                        </option>

                        <option value="Dairy" <?= ($profile['cooperative_type'] ?? '') === 'Dairy' ? 'selected' : '' ?>>
                            Dairy Cooperative
                        </option>

                        <option value="Coffee" <?= ($profile['cooperative_type'] ?? '') === 'Coffee' ? 'selected' : '' ?>>
                            Coffee Cooperative
                        </option>

                        <option value="Tea" <?= ($profile['cooperative_type'] ?? '') === 'Tea' ? 'selected' : '' ?>>
                            Tea Cooperative
                        </option>

                        <option value="Horticulture" <?= ($profile['cooperative_type'] ?? '') === 'Horticulture' ? 'selected' : '' ?>>
                            Horticulture Cooperative
                        </option>

                        <option value="Livestock" <?= ($profile['cooperative_type'] ?? '') === 'Livestock' ? 'selected' : '' ?>>
                            Livestock Cooperative
                        </option>

                        <option value="Multi-purpose" <?= ($profile['cooperative_type'] ?? '') === 'Multi-purpose' ? 'selected' : '' ?>>
                            Multi-purpose Cooperative
                        </option>

                        <option value="Other" <?= ($profile['cooperative_type'] ?? '') === 'Other' ? 'selected' : '' ?>>
                            Other
                        </option>

                    </select>

                </div>


                <!-- LOCATION -->

                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">
                        Location
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="location"
                        placeholder="e.g. Nakuru, Kenya"
                        required
                     value="<?= vayuma_profile_value($profile['location'] ?? '') ?>">

                </div>


                <!-- MEMBER COUNT -->

                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">
                        Number of Members
                    </label>

                    <input
                        type="number"
                        name="member_count"
                        min="0"
                     value="<?= vayuma_profile_value($profile['member_count'] ?? '0') ?>">

                </div>


                <!-- CONTACT PERSON -->

                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">
                        Contact Person
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="contact_person"
                        placeholder="Cooperative manager/chairperson"
                        required
                     value="<?= vayuma_profile_value($profile['contact_person'] ?? '') ?>">

                </div>


                <!-- DESCRIPTION -->

                <div class="form-group full">

                    <label class="va-profile-label-field">
                        Cooperative Description
                    </label>

                    <textarea
                        name="description"
                        placeholder="Describe your cooperative, the farmers you represent, crops produced and the services you provide..."
                    ><?= vayuma_profile_value($profile['description'] ?? '') ?></textarea>

                    <span class="va-profile-hint">
                        This information will help VAYUMA connect your cooperative with relevant opportunities.
                    </span>

                </div>


            </div>


            <div class="va-profile-button-area">

                <button type="submit" class="va-profile-btn">
                    Save Cooperative Profile &rarr;
                </button>

            </div>


        </form>


    </div>


</div>


<script src="assets/js/vayuma-language.js" defer></script>
</body>

</html>