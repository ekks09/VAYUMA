<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/language.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) { header('Location: login.php'); exit; }
if (strtolower(trim($_SESSION['role'] ?? '')) !== 'extension_officer') { header('Location: role.php'); exit; }
$userId=(int)$_SESSION['user_id']; $profile=[];
$stmt=$conn->prepare('SELECT * FROM extension_profiles WHERE user_id=? LIMIT 1');
if($stmt){$stmt->bind_param('i',$userId);$stmt->execute();$profile=$stmt->get_result()->fetch_assoc()?:[];$stmt->close();}
$counties=require __DIR__.'/config/kenya_counties.php'; $countyOptions=[];
foreach($counties as $group){foreach($group as $item){if(!empty($item['name']))$countyOptions[]=$item['name'];}}
$countyOptions[]='Marsabit';$countyOptions=array_values(array_unique($countyOptions));sort($countyOptions,SORT_NATURAL|SORT_FLAG_CASE);
$error=$_SESSION['profile_error']??'';unset($_SESSION['profile_error']);
function eh($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Extension Officer Profile | VAYUMA</title><link rel="stylesheet" href="frontend/css/tokens.css?v=20261110">
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
</style></head><body class="va-profile-document">
<?php vayuma_language_selector(); require __DIR__ . '/includes/profile_navigation.php'; ?><main class="va-profile-page"><div class="va-profile-brand">VAYUMA<small>SMART ECOSYSTEM</small></div><section class="hero"><h1>Agricultural Extension Officer</h1><p>Share your professional background, technical focus and the areas where you can support farmers.</p></section><section class="va-profile-card"><?php if($error):?><div class="va-profile-error"><?=eh($error)?></div><?php endif;?><form method="post" action="auth/save_extension.php"><div class="va-profile-form-grid">
<div><label class="va-profile-label-field">Organisation / employer *</label><input class="va-profile-input" name="organization" required maxlength="200" value="<?=eh($profile['organization']??'')?>" placeholder="County Department of Agriculture"></div>
<div><label class="va-profile-label-field">County of work base *</label><select class="va-profile-input" name="county" required><option value="">Select county</option><?php foreach($countyOptions as $c):?><option value="<?=eh($c)?>" <?=strcasecmp((string)($profile['county']??''),$c)===0?'selected':''?>><?=eh($c)?></option><?php endforeach;?></select></div>
<div><label class="va-profile-label-field">Sub-county / local office</label><input class="va-profile-input" name="sub_county" maxlength="100" value="<?=eh($profile['sub_county']??'')?>" placeholder="Optional"></div>
<div><label class="va-profile-label-field">Town / office location</label><input class="va-profile-input" name="location" maxlength="255" value="<?=eh($profile['location']??'')?>" placeholder="Town or office location"></div>
<div><label class="va-profile-label-field">Technical specialization *</label><input class="va-profile-input" name="specialization" required maxlength="200" value="<?=eh($profile['specialization']??'')?>" placeholder="Crop production, livestock, soil health..."></div>
<div><label class="va-profile-label-field">Qualification *</label><input class="va-profile-input" name="qualification" required maxlength="200" value="<?=eh($profile['qualification']??'')?>" placeholder="Diploma / degree / certificate"></div>
<div><label class="va-profile-label-field">Years of experience</label><input class="va-profile-input" type="number" name="experience_years" min="0" max="60" value="<?=eh($profile['years_experience']??'')?>"></div>
<div><label class="va-profile-label-field">Service coverage / areas served</label><input class="va-profile-input" name="coverage_area" maxlength="255" value="<?=eh($profile['coverage_area']??'')?>" placeholder="Counties, sub-counties or wards served"></div>
<div class="field full"><label class="va-profile-label-field">Professional bio and farmer support offered</label><textarea class="va-profile-input" name="bio" maxlength="5000" placeholder="Describe your experience, advisory topics, field support and preferred contact approach."><?=eh($profile['bio']??'')?></textarea><p class="hint">Farmers can request extension support separately from veterinary services. Farming activity is optional.</p></div>
</div><button class="btn" type="submit">Save extension profile &rarr;</button></form></section></main><script src="assets/js/vayuma-language.js" defer></script></body></html>
