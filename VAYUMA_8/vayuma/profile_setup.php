<?php
session_start();
require_once __DIR__ . '/includes/language.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

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


    <form action="auth/save_farmer.php"
          method="POST">


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

                        <option value="">
                            Select County
                        </option>

                        <option>Baringo</option>
                        <option>Bomet</option>
                        <option>Bungoma</option>
                        <option>Busia</option>
                        <option>Elgeyo-Marakwet</option>
                        <option>Embu</option>
                        <option>Garissa</option>
                        <option>Homa Bay</option>
                        <option>Isiolo</option>
                        <option>Kajiado</option>
                        <option>Kakamega</option>
                        <option>Kericho</option>
                        <option>Kiambu</option>
                        <option>Kilifi</option>
                        <option>Kirinyaga</option>
                        <option>Kisii</option>
                        <option>Kisumu</option>
                        <option>Kitui</option>
                        <option>Kwale</option>
                        <option>Laikipia</option>
                        <option>Lamu</option>
                        <option>Machakos</option>
                        <option>Makueni</option>
                        <option>Mandera</option>
                        <option>Marsabit</option>
                        <option>Meru</option>
                        <option>Migori</option>
                        <option>Mombasa</option>
                        <option>Murang'a</option>
                        <option>Nairobi</option>
                        <option>Nakuru</option>
                        <option>Nandi</option>
                        <option>Narok</option>
                        <option>Nyamira</option>
                        <option>Nyandarua</option>
                        <option>Nyeri</option>
                        <option>Samburu</option>
                        <option>Siaya</option>
                        <option>Taita-Taveta</option>
                        <option>Tana River</option>
                        <option>Tharaka-Nithi</option>
                        <option>Trans Nzoia</option>
                        <option>Turkana</option>
                        <option>Uasin Gishu</option>
                        <option>Vihiga</option>
                        <option>Wajir</option>
                        <option>West Pokot</option>

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
                            placeholder="e.g. 5"
                            required
                        >

                        <select class="va-profile-input" name="farm_size_unit">

                            <option value="acres">
                                Acres
                            </option>

                            <option value="hectares">
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

                        <option value="">
                            Select Soil Type
                        </option>

                        <option>Loamy</option>
                        <option>Clay</option>
                        <option>Sandy</option>
                        <option>Silty</option>
                        <option>Black Cotton</option>
                        <option>Volcanic</option>
                        <option>Red Soil</option>
                        <option>Unknown</option>

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

                        <option value="">
                            Select irrigation type
                        </option>

                        <option>Rain-fed</option>
                        <option>Irrigated</option>
                        <option>Both</option>

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
                class="save"
            >
                Save Farm Profile →
            </button>

        </div>


    </form>

</main>


<script src="assets/js/vayuma-language.js" defer></script>
</body>
</html>