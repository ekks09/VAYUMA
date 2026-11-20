<?php
session_start();
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/language.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}
if (strtolower(trim($_SESSION['role'] ?? '')) !== 'transport_provider') {
    header('Location: role.php');
    exit;
}

$uid = (int) $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'Transport Provider';
$profile = [];
$stmt = $conn->prepare("SELECT * FROM transport_profiles WHERE user_id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();
}

$countyGroups = require __DIR__ . '/config/kenya_counties.php';
$counties = [];
foreach ($countyGroups as $region => $items) {
    foreach ($items as $item) {
        if (!empty($item['name'])) $counties[] = $item['name'];
    }
}
$counties[] = 'Marsabit';
$counties = array_values(array_unique($counties));
sort($counties, SORT_NATURAL | SORT_FLAG_CASE);

$error = $_SESSION['transport_profile_error'] ?? '';
unset($_SESSION['transport_profile_error']);
function transport_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head><?php require __DIR__ . '/includes/pwa.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Transport Provider Profile | VAYUMA</title>
<link rel="stylesheet" href="frontend/css/tokens.css?v=20261110">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css?v=20261110">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
<link rel="stylesheet" href="frontend/css/profile_forms.css?v=20261120-1">
<style>
.va-form-page { max-width: 950px; margin: 0 auto; padding: var(--va-space-6); }
.va-form-brand { text-align: center; margin-bottom: var(--va-space-6); }
.va-form-brand h1 { color: var(--va-primary); font-size: var(--va-font-size-3xl); letter-spacing: 0.05em; margin: 0; }
.va-form-brand p { color: var(--va-text-muted); font-size: var(--va-font-size-xs); letter-spacing: 0.05em; margin-top: var(--va-space-1); }
.va-form-card { background: var(--va-surface); border-radius: var(--va-radius-lg); padding: var(--va-space-6); box-shadow: var(--va-shadow-sm); border: 1px solid var(--va-border); }
.va-form-heading { margin-bottom: var(--va-space-5); }
.va-form-label { color: var(--va-primary); font-size: var(--va-font-size-xs); font-weight: var(--va-font-weight-bold); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: var(--va-space-2); }
.va-form-heading h2 { font-size: var(--va-font-size-2xl); margin-bottom: var(--va-space-2); color: var(--va-text-primary); }
.va-form-heading p { color: var(--va-text-secondary); line-height: var(--va-line-height-relaxed); }
.va-form-welcome { background: var(--va-primary-light); border-radius: var(--va-radius-md); padding: var(--va-space-4); margin: var(--va-space-6) 0; color: #17663a; line-height: var(--va-line-height-relaxed); }
.va-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: var(--va-space-4); }
.va-form-group { display: flex; flex-direction: column; }
.va-form-full { grid-column: 1 / -1; }
.va-form-label-field { font-size: var(--va-font-size-sm); font-weight: var(--va-font-weight-bold); margin-bottom: var(--va-space-2); color: var(--va-text-secondary); }
.va-form-input { width: 100%; padding: 0.75rem 1rem; font-size: var(--va-font-size-base); font-family: inherit; border: 1px solid var(--va-border); border-radius: var(--va-radius); background: var(--va-surface); color: var(--va-text-primary); }
.va-form-input:focus { outline: none; border-color: var(--va-border-focus); box-shadow: 0 0 0 3px rgba(10, 138, 73, 0.1); }
.va-form-hint { font-size: var(--va-font-size-xs); color: var(--va-text-muted); margin-top: var(--va-space-1); }
.va-form-error { background: #fde7e7; color: #a32121; padding: var(--va-space-3); border-radius: var(--va-radius-md); margin-bottom: var(--va-space-4); }
.va-form-button-area { margin-top: var(--va-space-6); }
.va-form-btn { width: 100%; border: none; padding: var(--va-space-4); background: var(--va-primary); color: #fff; border-radius: var(--va-radius); font-size: var(--va-font-size-base); font-weight: var(--va-font-weight-bold); cursor: pointer; }
.va-form-btn:hover { background: var(--va-primary-hover); }
.va-form-back { display: inline-block; margin-top: var(--va-space-4); color: var(--va-primary); text-decoration: none; font-weight: var(--va-font-weight-bold); }
@media (max-width: 700px) {
  .va-form-page { padding: var(--va-space-4); }
  .va-form-card { padding: var(--va-space-4); }
  .va-form-grid { grid-template-columns: 1fr; }
  .va-form-full { grid-column: auto; }
}
</style>
</head>
<body class="va-profile-document">
<?php vayuma_language_selector(); require __DIR__ . '/includes/profile_navigation.php'; ?>
<main class="va-form-page">
<div class="va-form-brand">VAYUMA<small>SMART ECOSYSTEM</small></div>
<h1><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-transport"/></svg> Transport Provider Profile</h1>
<p class="intro">Welcome, <?=transport_h($full_name)?>. Tell farmers, buyers and other stakeholders where your transport business is based, what vehicles you operate and the areas you serve.</p>
<?php if ($error): ?><div class="alert"><?=transport_h($error)?></div><?php endif; ?>
<form action="auth/save_transport.php" method="post"><?= vayuma_csrf_field() ?>
<div class="va-form-grid">
<div class="field"><label for="business_name">Business / transport name *</label><input class="va-form-input" id="business_name" name="business_name" required maxlength="150" value="<?=transport_h($profile['business_name'] ?? '')?>" placeholder="e.g. Green Valley Transport"></div>
<div class="field"><label for="phone">Contact phone *</label><input class="va-form-input" id="phone" name="phone" required maxlength="30" value="<?=transport_h($profile['phone'] ?? '')?>" placeholder="07XXXXXXXX"></div>
<div class="field"><label for="county">Registered business county *</label><select class="va-form-input" id="county" name="county" required><option value="">Select county</option><?php foreach($counties as $county): ?><option value="<?=transport_h($county)?>" <?=strcasecmp((string)($profile['county']??''),$county)===0?'selected':''?>><?=transport_h($county)?></option><?php endforeach; ?></select><div class="hint">This is the county where your business is registered or mainly based.</div></div>
<div class="field"><label for="vehicle_type">Vehicle type *</label><select class="va-form-input" id="vehicle_type" name="vehicle_type" required><option value="">Select vehicle</option><?php foreach(['Pickup','Truck','Lorry','Van','Motorbike','Tractor','Refrigerated truck','Other'] as $v): ?><option value="<?=transport_h($v)?>" <?=($profile['vehicle_type']??'')===$v?'selected':''?>><?=transport_h($v)?></option><?php endforeach; ?></select></div>
<div class="field"><label for="registration_number">Vehicle registration number</label><input class="va-form-input" id="registration_number" name="registration_number" maxlength="100" value="<?=transport_h($profile['registration_number'] ?? $profile['vehicle_registration'] ?? '')?>" placeholder="e.g. KDA 123A"></div>
<div class="field"><label for="vehicle_capacity">Maximum load capacity *</label><input class="va-form-input" id="vehicle_capacity" name="vehicle_capacity" required maxlength="100" value="<?=transport_h($profile['vehicle_capacity'] ?? $profile['load_capacity'] ?? '')?>" placeholder="e.g. 5 tonnes or 80 bags"></div>
<div class="field"><label for="service_type">Transport service *</label><select class="va-form-input" id="service_type" name="service_type" required><option value="">Select service</option><?php foreach(['Farm to market','Farm to warehouse','Farm to buyer','Warehouse to buyer','Livestock transport','General agricultural transport','Other'] as $v): ?><option value="<?=transport_h($v)?>" <?=($profile['service_type']??'')===$v?'selected':''?>><?=transport_h($v)?></option><?php endforeach; ?></select></div>
<div class="field"><label for="operating_area">Operating / service coverage *</label><input class="va-form-input" id="operating_area" name="operating_area" required maxlength="255" value="<?=transport_h($profile['operating_area'] ?? '')?>" placeholder="e.g. Kiambu, Nairobi and Nakuru"><div class="hint">List counties, towns or routes you are willing to serve. This is separate from your registered business county.</div></div>
</div>
<button type="submit" class="va-form-btn">Save Transport Profile</button>
</form>
</main>
<script src="assets/js/vayuma-language.js" defer></script>
</body>
</html>
