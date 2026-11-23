<?php

session_start();
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/language.php';

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true
) {
    header("Location: login.php");
    exit;
}

$role = strtolower(trim($_SESSION['role'] ?? ''));

if ($role !== 'financial_institution') {
    header("Location: role.php");
    exit;
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/profile_prefill.php';
try {
    $profile = vayuma_existing_profile($conn, 'financial_profiles', (int)$_SESSION['user_id']);
} catch (Throwable $e) {
    error_log('VAYUMA finance profile lookup failed.');
    http_response_code(503);
    exit('Your profile could not be loaded. Please try again or contact support.');
}

$full_name = $_SESSION['full_name'] ?? 'Financial Institution User';

?>

<!DOCTYPE html>
<html lang="en">

<head><?php require __DIR__ . '/includes/pwa.php'; ?>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0, interactive-widget=resizes-content"
>

<title>Financial Institution Profile | VAYUMA TRIX</title>

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

        <h1>VAYUMA TRIX</h1>

        <p>AGRITECH INNOVATIONS KENYA</p>

    </div>


    <div class="va-profile-card">

        <div class="va-profile-heading">

            <div class="va-profile-label">
                FINANCIAL INSTITUTION
            </div>

            <h2>
                Complete your institution profile
            </h2>

            <p>
                Provide your institution's information so
                VAYUMA can connect agricultural finance with
                farmers, cooperatives and other ecosystem
                participants.
            </p>

        </div>


        <div class="va-profile-welcome">

            Welcome,
            <strong>
                <?php echo htmlspecialchars($full_name); ?>
            </strong>
            

            <br>

            Complete your institution information to
            continue to your dashboard.

        </div>


        <form
            action="auth/save_finance.php"
            method="POST"
        ><?= vayuma_csrf_field() ?>

            <div class="va-profile-form-grid">


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">
                        Institution Name
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="institution_name"
                        placeholder="e.g. VAYUMA Agricultural Finance"
                        required
                     value="<?= vayuma_profile_value($profile['institution_name'] ?? '') ?>">

                </div>


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">
                        Institution Type
                        <span class="required">*</span>
                    </label>

                    <select
                        name="institution_type"
                        required
                    >
                        <?php if (!empty($profile['institution_type']) && !in_array((string)$profile['institution_type'], ['Bank', 'Microfinance', 'SACCO', 'Insurance', 'Fintech', 'Development Finance', 'Other'], true)): ?>
                        <option value="<?= vayuma_profile_value($profile['institution_type']) ?>" selected><?= vayuma_profile_value($profile['institution_type']) ?></option>
                        <?php endif; ?>

                        <option value="">
                            Select institution type
                        </option>

                        <option value="Bank" <?= ($profile['institution_type'] ?? '') === 'Bank' ? 'selected' : '' ?>>
                            Bank
                        </option>

                        <option value="Microfinance" <?= ($profile['institution_type'] ?? '') === 'Microfinance' ? 'selected' : '' ?>>
                            Microfinance Institution
                        </option>

                        <option value="SACCO" <?= ($profile['institution_type'] ?? '') === 'SACCO' ? 'selected' : '' ?>>
                            SACCO
                        </option>

                        <option value="Insurance" <?= ($profile['institution_type'] ?? '') === 'Insurance' ? 'selected' : '' ?>>
                            Insurance Company
                        </option>

                        <option value="Fintech" <?= ($profile['institution_type'] ?? '') === 'Fintech' ? 'selected' : '' ?>>
                            Fintech
                        </option>

                        <option value="Development Finance" <?= ($profile['institution_type'] ?? '') === 'Development Finance' ? 'selected' : '' ?>>
                            Development Finance Institution
                        </option>

                        <option value="Other" <?= ($profile['institution_type'] ?? '') === 'Other' ? 'selected' : '' ?>>
                            Other
                        </option>

                    </select>

                </div>


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">
                        Registration Number
                    </label>

                    <input
                        type="text"
                        name="registration_number"
                        placeholder="Institution registration number"
                     value="<?= vayuma_profile_value($profile['registration_number'] ?? '') ?>">

                </div>


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">
                        Location
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="location"
                        placeholder="e.g. Nairobi, Kenya"
                        required
                     value="<?= vayuma_profile_value($profile['location'] ?? '') ?>">

                </div>


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">
                        Contact Person
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="contact_person"
                        placeholder="Manager or representative"
                        required
                     value="<?= vayuma_profile_value($profile['contact_person'] ?? '') ?>">

                </div>


                <div class="va-profile-form-group">

                    <label class="va-profile-label-field">
                        Financial Services
                    </label>

                    <input
                        type="text"
                        name="services"
                        placeholder="e.g. Agricultural loans, insurance"
                     value="<?= vayuma_profile_value($profile['services'] ?? '') ?>">

                </div>


                <div class="form-group full">

                    <label class="va-profile-label-field">
                        Institution Description
                    </label>

                    <textarea
                        name="description"
                        placeholder="Describe the financial services your institution provides to farmers and agricultural businesses..."
                    ><?= vayuma_profile_value($profile['description'] ?? '') ?></textarea>

                    <span class="va-profile-hint">
                        This helps VAYUMA understand the services available within its agricultural finance ecosystem.
                    </span>

                </div>


            </div>


            <div class="va-profile-button-area">

                <button type="submit" class="va-profile-btn">
                    Save Institution Profile &rarr;
                </button>

            </div>

        </form>

    </div>

</div>

<script src="assets/js/vayuma-language.js" defer></script>
</body>

</html>