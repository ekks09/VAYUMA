<?php
session_start();
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/language.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if (($_SESSION['role'] ?? '') !== 'buyer') { header("Location: role.php"); exit; }

$userId=(int)$_SESSION['user_id'];
$fullName=$_SESSION['full_name'] ?? 'Buyer';
$profile=[];
$stmt=$conn->prepare("SELECT * FROM buyer_profiles WHERE user_id=? LIMIT 1");
if($stmt){$stmt->bind_param('i',$userId);$stmt->execute();$profile=$stmt->get_result()->fetch_assoc() ?: []; $stmt->close();}
$formError=$_SESSION['buyer_profile_error']??'';
$formOld=$_SESSION['buyer_profile_old']??[];
unset($_SESSION['buyer_profile_error'],$_SESSION['buyer_profile_old']);
$formOld=is_array($formOld)?$formOld:[];
$profile=array_merge($profile,$formOld);
$countyId=isset($formOld['county_id'])?(int)$formOld['county_id']:0;
if(!empty($profile['county'])){
 if($countyId<1){
  $c=$conn->prepare("SELECT id FROM administrative_units WHERE unit_type='county' AND name=? LIMIT 1");
  if($c){$c->bind_param('s',$profile['county']);$c->execute();$countyId=(int)($c->get_result()->fetch_assoc()['id']??0);$c->close();}
 }
}
$countySearch=(string)($formOld['county_search']??$profile['county']??'');
function oldv($profile,$key,$fallback=''){return htmlspecialchars((string)($profile[$key]??$fallback),ENT_QUOTES,'UTF-8');}
?>
<!DOCTYPE html><html lang="en"><head><?php require __DIR__ . '/includes/pwa.php'; ?>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Buyer Profile | VAYUMA TRIX</title>
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
</style></head><body class="va-profile-document">
<?php require __DIR__ . '/includes/profile_navigation.php'; ?>
<div class="page"><div class="va-profile-page">
<div class="header"><h1>Buyer Profile</h1><p>Tell farmers and suppliers what your business or organisation needs.</p></div>
<div class="va-profile-card"><form action="auth/save_buyer.php" method="POST"><?= vayuma_csrf_field() ?>
<?php if($formError!==''): ?><div class="va-profile-error" role="alert"><?= htmlspecialchars($formError,ENT_QUOTES,'UTF-8') ?></div><?php endif; ?>
<div class="profile-extra"><h3>Personal / Business details</h3>
<div class="va-profile-form-grid">
<div class="va-profile-form-group"><label class="va-profile-label-field">Contact person's full name</label><input class="va-profile-input" name="full_name" maxlength="150" value="<?= oldv($profile,'full_name',$fullName) ?>" required></div>
<div class="va-profile-form-group"><label class="va-profile-label-field">Buyer type</label><select class="va-profile-input" name="buyer_type" required>
<option value="">Select buyer type</option>
<?php foreach(['Individual','Business','Retailer','Wholesaler','Institution','Processor','Exporter','Hotel / Restaurant'] as $v): ?><option value="<?= htmlspecialchars($v) ?>" <?= (($profile['buyer_type']??'')===$v?'selected':'') ?>><?= htmlspecialchars($v) ?></option><?php endforeach; ?>
</select></div>
<div class="va-profile-form-group"><label class="va-profile-label-field">Business / organisation name (optional)</label><input class="va-profile-input" name="business_name" maxlength="150" value="<?= oldv($profile,'business_name') ?>" placeholder="Leave blank for individual buyers"></div>
<div class="va-profile-form-group"><label class="va-profile-label-field">Business type (optional)</label><input class="va-profile-input" name="business_type" maxlength="100" value="<?= oldv($profile,'business_type') ?>" placeholder="e.g. grocery, school, hotel"></div>
<div class="va-profile-form-group"><label class="va-profile-label-field" for="county_search">County</label><input class="va-profile-input" type="search" name="county_search" id="county_search" list="kenyan_counties" autocomplete="off" placeholder="Type to search Kenyan counties" value="<?= htmlspecialchars($countySearch,ENT_QUOTES,'UTF-8') ?>" required data-selected-id="<?= $countyId ?>" aria-describedby="county_status"><datalist id="kenyan_counties"></datalist><input type="hidden" name="county_id" id="county_id" value="<?= $countyId ?>"><small class="va-profile-hint" id="county_status" role="status">Start typing a county name to search.</small></div>
<div class="va-profile-form-group"><label class="va-profile-label-field">Town / area / estate</label><input class="va-profile-input" name="location" maxlength="255" value="<?= oldv($profile,'location') ?>" placeholder="e.g. Westlands, Nairobi" required></div>
</div></div>
<div class="profile-extra"><h3>Purchasing needs</h3>
<div class="va-profile-form-grid">
<div class="va-profile-form-group"><label class="va-profile-label-field">Main preferred product</label><select class="va-profile-input" name="preferred_product" required>
<option value="">Select product</option>
<?php foreach(['Maize','Beans','Rice','Vegetables','Fruits','Potatoes','Dairy','Meat','Poultry','Fish','Honey','Other / Multiple products'] as $v): ?><option value="<?= htmlspecialchars($v) ?>" <?= (($profile['preferred_product']??'')===$v?'selected':'') ?>><?= htmlspecialchars($v) ?></option><?php endforeach; ?>
</select></div>
<div class="va-profile-form-group"><label class="va-profile-label-field">Expected purchasing volume (optional)</label><input class="va-profile-input" name="expected_volume" maxlength="100" value="<?= oldv($profile,'expected_volume') ?>" placeholder="e.g. 2 tonnes per month"></div>
</div>
<div class="va-profile-form-group"><label class="va-profile-label-field">Other products interested in (optional)</label><textarea class="va-profile-input" name="products_interested" maxlength="3000" placeholder="List other products, varieties or quality requirements"><?= oldv($profile,'products_interested') ?></textarea></div>
<div class="va-profile-form-grid">
<div class="va-profile-form-group"><label class="va-profile-label-field">Preferred payment method</label><select class="va-profile-input" name="payment_method" required>
<option value="">Select payment method</option>
<?php foreach(['M-Pesa','Cash','Bank','Negotiable'] as $v): ?><option value="<?= htmlspecialchars($v) ?>" <?= (($profile['payment_method']??'')===$v?'selected':'') ?>><?= htmlspecialchars($v) ?></option><?php endforeach; ?>
</select></div>
<div class="va-profile-form-group"><label class="va-profile-label-field">Delivery preference (optional)</label><select class="va-profile-input" name="delivery_preference">
<option value="">Select preference</option>
<?php foreach(['Farm / seller delivery','Buyer collection','Transport arranged separately','Negotiable'] as $v): ?><option value="<?= htmlspecialchars($v) ?>" <?= (($profile['delivery_preference']??'')===$v?'selected':'') ?>><?= htmlspecialchars($v) ?></option><?php endforeach; ?>
</select></div>
</div></div>
<button class="btn" type="submit">Save Buyer Profile</button></form>
</div></div></div>
<script>
(async()=>{const input=document.getElementById('county_search'),idField=document.getElementById('county_id'),list=document.getElementById('kenyan_counties'),status=document.getElementById('county_status'),form=input.form,selectedId=Number(input.dataset.selectedId||0);let counties=[];const matchCounty=()=>counties.find(item=>item.name.localeCompare(input.value.trim(),undefined,{sensitivity:'base'})===0);const syncCounty=()=>{const match=matchCounty();idField.value=match?String(match.id):'';if(input.value.trim()!==''&&!match)status.textContent='Choose a county from the suggestions.';else status.textContent='Start typing a county name to search.';return Boolean(match);};input.addEventListener('input',syncCounty);input.addEventListener('change',syncCounty);form.addEventListener('submit',event=>{if(!syncCounty()){event.preventDefault();status.textContent='Select a valid county from the suggestions before saving.';input.focus();}});try{const response=await fetch('api/location_options.php?type=county');const data=await response.json();if(!response.ok||!data.success||!Array.isArray(data.items)||data.items.length===0)throw new Error(data.message||'No counties were returned.');counties=data.items;for(const county of counties){const option=document.createElement('option');option.value=county.name;list.append(option);}const selected=counties.find(county=>county.id===selectedId);if(selected){input.value=selected.name;idField.value=String(selected.id);}else if(input.value){syncCounty();}status.textContent='Type a county name and choose a suggestion.';}catch(error){status.textContent='County options could not be loaded. Refresh the page and try again.';console.error('Buyer county lookup failed:',error);}})();
</script><script src="assets/js/vayuma-language.js" defer></script></body></html>
