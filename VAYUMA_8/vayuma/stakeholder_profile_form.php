<?php
require_once __DIR__ . '/includes/language.php';

if (!isset($expected_role)) { $expected_role = strtolower(trim($_SESSION['role'] ?? '')); }
require_once __DIR__.'/stakeholder_role_config.php';
$config=vayuma_role_config($expected_role);
if (!$config) { header('Location: role.php'); exit; }
$profile=$profile??[];
$isWarehouse = ($expected_role === 'warehouse_provider');
$organizationLabel = $isWarehouse ? 'Warehouse / storage business name *' : 'Business / organisation name *';
$organizationPlaceholder = $isWarehouse ? 'e.g. Green Valley Grain Stores' : 'e.g. Green Valley Agro Ltd';
$typeLabel = $isWarehouse ? 'Facility type' : 'Organisation type';
$typePlaceholder = $isWarehouse ? 'Grain store, cold room, silo, warehouse...' : 'Company, cooperative, NGO, individual...';
$countyLabel = $isWarehouse ? 'Registered facility county *' : 'County *';
$locationLabel = $isWarehouse ? 'Town / facility location' : 'Town / location';
$focusLabel = $isWarehouse ? 'Storage services and produce handled' : 'Service focus';
$focusPlaceholder = $isWarehouse ? 'e.g. grain, potatoes, cold storage, drying' : 'What do you mainly provide?';
$capacityLabel = $isWarehouse ? 'Available storage capacity' : 'Capacity';
$capacityPlaceholder = $isWarehouse ? 'e.g. 500 tonnes, 2,000 bags, cold-room volume' : 'e.g. 500 bags, 10 trucks, 100 tonnes';
$areaLabel = $isWarehouse ? 'Operating / service coverage' : 'Service area';
$areaPlaceholder = $isWarehouse ? 'Counties, towns or routes served' : 'Counties / towns served';
$descriptionPlaceholder = $isWarehouse ? 'Describe storage conditions, produce accepted, handling services, security, charges and how farmers can request space.' : 'Describe your products, services, experience or programme.';
$countyGroups = require __DIR__ . '/config/kenya_counties.php';
$countyOptions = [];
foreach ($countyGroups as $regionItems) {
    foreach ($regionItems as $countyItem) {
        if (!empty($countyItem['name'])) $countyOptions[] = $countyItem['name'];
    }
}
$countyOptions[] = 'Marsabit';
$countyOptions = array_values(array_unique($countyOptions));
sort($countyOptions, SORT_NATURAL | SORT_FLAG_CASE);
$currentCounty = trim((string)($profile['county'] ?? ''));
$err=$_SESSION['profile_error']??''; unset($_SESSION['profile_error']);
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=h($config[0])?> Profile | VAYUMA</title><link rel="stylesheet" href="frontend/css/tokens.css">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
<link rel="stylesheet" href="frontend/css/profile_forms.css?v=20261110-3">
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
</style></head><body class="va-profile-document">
<?php vayuma_language_selector(); require __DIR__ . '/includes/profile_navigation.php'; ?>
<main class="va-form-page"><header class="top"><div class="va-form-brand">VAYUMA<small>SMART ECOSYSTEM</small></div></header><section class="hero"><div class="icon"><svg class="va-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg#<?=h($config[1])?>"></use></svg></div><h1><?=h($config[0])?> profile</h1><p><?=h($config[2])?> Complete your profile so other VAYUMA members know who you are and what you provide.</p></section><section class="va-form-card"><?php if($err):?><div class="notice"><?=h($err)?></div><?php endif;?><form action="auth/save_stakeholder_profile.php" method="post"><input class="va-form-input" type="hidden" name="role" value="<?=h($expected_role)?>"><div class="va-form-grid"><div class="field"><label class="va-form-label-field"><?=h($organizationLabel)?></label><input class="va-form-input" name="organization_name" required value="<?=h($profile['organization_name']??'')?>" placeholder="<?=h($organizationPlaceholder)?>"></div><div class="field"><label class="va-form-label-field"><?=h($typeLabel)?></label><input class="va-form-input" name="organization_type" value="<?=h($profile['organization_type']??'')?>" placeholder="<?=h($typePlaceholder)?>" ></div><div class="field"><label class="va-form-label-field"><?=h($countyLabel)?></label><select class="va-form-input" name="county" required><option value="">Select county</option><?php if($currentCounty !== '' && !in_array($currentCounty,$countyOptions,true)): ?><option value="<?=h($currentCounty)?>" selected><?=h($currentCounty)?> (existing)</option><?php endif; ?><?php foreach($countyOptions as $countyOption): ?><option value="<?=h($countyOption)?>" <?=strcasecmp($currentCounty,$countyOption)===0?'selected':''?>><?=h($countyOption)?></option><?php endforeach; ?></select></div><div class="field"><label class="va-form-label-field"><?=h($locationLabel)?></label><input class="va-form-input" name="location" value="<?=h($profile['location']??'')?>" placeholder="e.g. Naivasha"></div><div class="field"><label class="va-form-label-field">Contact phone</label><input class="va-form-input" name="contact_phone" value="<?=h($profile['contact_phone']??($_SESSION['phone']??''))?>" placeholder="07XXXXXXXX"></div><div class="field"><label class="va-form-label-field"><?=h($focusLabel)?></label><input class="va-form-input" name="service_focus" value="<?=h($profile['service_focus']??'')?>" placeholder="<?=h($focusPlaceholder)?>"></div><div class="field"><label class="va-form-label-field">Licence / permit number</label><input class="va-form-input" name="license_number" value="<?=h($profile['license_number']??'')?>" placeholder="Optional"></div><div class="field"><label class="va-form-label-field">Business / registration number</label><input class="va-form-input" name="registration_number" value="<?=h($profile['registration_number']??'')?>" placeholder="Optional"></div><div class="field"><label class="va-form-label-field"><?=h($capacityLabel)?></label><input class="va-form-input" name="capacity" value="<?=h($profile['capacity']??'')?>" placeholder="<?=h($capacityPlaceholder)?>"></div><div class="field"><label class="va-form-label-field"><?=h($areaLabel)?></label><input class="va-form-input" name="service_area" value="<?=h($profile['service_area']??'')?>" placeholder="<?=h($areaPlaceholder)?>"></div><div class="field full"><label class="va-form-label-field">About your organisation</label><textarea class="va-form-input" name="description" placeholder="<?=h($descriptionPlaceholder)?>"><?=h($profile['description']??'')?></textarea><div class="hint"><?= $isWarehouse ? 'Describe storage conditions and charges here. Storage requests and inventory tracking will be added in a later workflow.' : 'Verification can be completed later by the VAYUMA administration team.' ?></div></div></div><button class="button" type="submit">Save profile & continue →</button></form></section></main><script src="assets/js/vayuma-language.js" defer></script>
</body></html>
