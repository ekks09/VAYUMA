<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/language.php';
if(!isset($_SESSION['user_id'])){header("Location: login.php");exit;}
if(($_SESSION['role']??'')!=='consumer'){header("Location: role.php");exit;}
$userId=(int)$_SESSION['user_id'];$fullName=$_SESSION['full_name']??'Consumer';$profile=[];
$stmt=$conn->prepare("SELECT * FROM consumer_profiles WHERE user_id=? LIMIT 1");
if($stmt){$stmt->bind_param('i',$userId);$stmt->execute();$profile=$stmt->get_result()->fetch_assoc()?:[];$stmt->close();}
$countyId=0;
if(!empty($profile['county'])){$c=$conn->prepare("SELECT id FROM administrative_units WHERE unit_type='county' AND name=? LIMIT 1");if($c){$c->bind_param('s',$profile['county']);$c->execute();$countyId=(int)($c->get_result()->fetch_assoc()['id']??0);$c->close();}}
function oldv($profile,$key,$fallback=''){return htmlspecialchars((string)($profile[$key]??$fallback),ENT_QUOTES,'UTF-8');}
?>
<!DOCTYPE html><html lang="en"><head><?php require __DIR__ . '/includes/pwa.php'; ?><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Consumer Profile | VAYUMA</title>
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
</style></head><body class="va-profile-document"><?php vayuma_language_selector(); require __DIR__ . '/includes/profile_navigation.php'; ?>
<div class="va-profile-page"><div class="header"><h1>Consumer Profile</h1><p>Set your food shopping interests and delivery preferences.</p></div>
<div class="va-profile-card"><form action="auth/save_consumer.php" method="POST">
<div class="profile-extra"><h3>Your details</h3><div class="va-profile-form-grid">
<div class="va-profile-form-group"><label class="va-profile-label-field">Full name</label><input class="va-profile-input" name="full_name" maxlength="150" value="<?= oldv($profile,'full_name',$fullName) ?>" required></div>
<div class="va-profile-form-group"><label class="va-profile-label-field">County</label><select class="va-profile-input" name="county_id" id="county_id" data-selected="<?= $countyId ?>" required><option value="">Loading counties...</option></select></div>
<div class="va-profile-form-group"><label class="va-profile-label-field">Town / area / estate</label><input class="va-profile-input" name="location" maxlength="255" value="<?= oldv($profile,'location') ?>" placeholder="e.g. Kasarani, Nairobi" required></div>
<div class="va-profile-form-group"><label class="va-profile-label-field">Shopping frequency (optional)</label><select class="va-profile-input" name="shopping_frequency">
<option value="">Select frequency</option>
<?php foreach(['Daily','Several times a week','Weekly','Monthly','Occasionally'] as $v): ?><option value="<?= htmlspecialchars($v) ?>" <?= (($profile['shopping_frequency']??'')===$v?'selected':'') ?>><?= htmlspecialchars($v) ?></option><?php endforeach; ?>
</select></div></div></div>
<div class="profile-extra"><h3>Shopping preferences</h3>
<div class="va-profile-form-grid"><div class="va-profile-form-group"><label class="va-profile-label-field">Main preferred product</label><select class="va-profile-input" name="preferred_product" required>
<option value="">Select product</option>
<?php foreach(['Vegetables','Fruits','Cereals','Maize','Beans','Dairy','Meat','Poultry','Fish','Honey','Other / Multiple products'] as $v): ?><option value="<?= htmlspecialchars($v) ?>" <?= (($profile['preferred_product']??'')===$v?'selected':'') ?>><?= htmlspecialchars($v) ?></option><?php endforeach; ?>
</select></div>
<div class="va-profile-form-group"><label class="va-profile-label-field">Shopping source</label><select class="va-profile-input" name="shopping_preference" required>
<option value="">Select preference</option>
<?php foreach(['Local','Marketplace','Cooperatives','Any trusted seller'] as $v): ?><option value="<?= htmlspecialchars($v) ?>" <?= (($profile['shopping_preference']??'')===$v?'selected':'') ?>><?= htmlspecialchars($v) ?></option><?php endforeach; ?>
</select></div>
<div class="va-profile-form-group"><label class="va-profile-label-field">Preferred delivery (optional)</label><select class="va-profile-input" name="preferred_delivery">
<option value="">Select delivery preference</option>
<?php foreach(['Home delivery','Pickup point','Collect from seller','No preference'] as $v): ?><option value="<?= htmlspecialchars($v) ?>" <?= (($profile['preferred_delivery']??'')===$v?'selected':'') ?>><?= htmlspecialchars($v) ?></option><?php endforeach; ?>
</select></div></div>
<div class="va-profile-form-group"><label class="va-profile-label-field">Other favourite products (optional)</label><textarea class="va-profile-input" name="favorite_products" maxlength="3000" placeholder="List other products you regularly buy"><?= oldv($profile,'favorite_products') ?></textarea></div>
</div><button class="btn" type="submit">Save Consumer Profile</button></form>
</div></div>
<script>
(async()=>{const s=document.getElementById('county_id'),selected=Number(s.dataset.selected||0);try{const r=await fetch('api/location_options.php?type=county');const d=await r.json();if(!r.ok||!d.success)throw Error();s.innerHTML='<option value="">Select county</option>';d.items.forEach(x=>{const o=new Option(x.name,x.id);if(Number(x.id)===selected)o.selected=true;s.add(o);});}catch(e){s.innerHTML='<option value="">County list unavailable &mdash; refresh</option>';s.disabled=true;console.error(e);}})();
</script><script src="assets/js/vayuma-language.js" defer></script></body></html>
