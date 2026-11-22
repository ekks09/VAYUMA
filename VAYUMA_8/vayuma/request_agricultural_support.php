<?php
session_start();require_once __DIR__.'/config/db.php';require_once __DIR__.'/includes/language.php';
if(!isset($_SESSION['user_id'])||($_SESSION['logged_in']??false)!==true){header('Location: login.php');exit;}
$type=($_GET['type']??'')==='veterinary'?'veterinary':(($_GET['type']??'')==='extension'?'extension':'');$provider=(int)($_GET['provider']??0);
if(!$type||$provider<1){http_response_code(400);exit('Invalid service provider.');}
$expected=$type==='veterinary'?'veterinary_officer':'extension_officer';
$stmt=$conn->prepare("SELECT sp.user_id,sp.organization_name,sp.organization_type,sp.county,sp.location,sp.service_focus,sp.service_area,sp.verification_status FROM stakeholder_profiles sp WHERE sp.user_id=? AND sp.role=? AND sp.profile_completed=1 LIMIT 1");
$stmt->bind_param('is',$provider,$expected);$stmt->execute();$providerRow=$stmt->get_result()->fetch_assoc();$stmt->close();
if(!$providerRow){http_response_code(404);exit('This service provider profile is not available.');}
$error=$_SESSION['support_error']??'';unset($_SESSION['support_error']);require_once __DIR__.'/includes/csrf.php';function sh($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><head><?php require __DIR__ . '/includes/pwa.php'; ?><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Request Agricultural Support | VAYUMA TRIX</title><link rel="stylesheet" href="frontend/css/tokens.css">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
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
</style></head><body><?php vayuma_language_selector();?><main class="va-form-page"><?php require __DIR__ . '/includes/workspace_back.php'; ?><div class="va-form-brand">VAYUMA TRIX</div><h1><?= $type==='veterinary'?'Request animal-health service':'Request agricultural extension support' ?></h1><section class="va-form-card"><div class="provider"><strong><?=sh($providerRow['organization_name'])?></strong><br><?=sh($providerRow['organization_type']??'')?> · <?=sh($providerRow['county'])?><?=!empty($providerRow['location'])?' · '.sh($providerRow['location']):''?><br><?=sh($providerRow['service_focus']??'')?> <br>Verification: <?=sh(ucfirst($providerRow['verification_status']))?></div><?php if($error):?><div class="va-form-error"><?=sh($error)?></div><?php endif;?><form method="post" action="auth/create_agricultural_support_request.php"><input class="va-form-input" type="hidden" name="type" value="<?=sh($type)?>"><input class="va-form-input" type="hidden" name="provider_id" value="<?=$provider?>"><?=vayuma_csrf_field()?>
<div class="va-form-grid">
<?php if($type==='veterinary'):?>
<div><label class="va-form-label-field">Animal species *</label><input class="va-form-input" name="animal_species" required maxlength="120" placeholder="Cattle, goats, poultry, pets..."></div><div><label class="va-form-label-field">Urgency *</label><select class="va-form-input" name="urgency"><option value="routine">Routine</option><option value="soon">Needs attention soon</option><option value="urgent">Urgent</option><option value="emergency">Emergency</option></select></div>
<div class="va-form-full"><label class="va-form-label-field">Animal-health concern / symptoms *</label><textarea class="va-form-input" name="details" required maxlength="5000" placeholder="Describe what you have observed, when it started and how many animals are affected."></textarea><p class="hint">For a severe or rapidly worsening animal-health emergency, contact a qualified local veterinary professional immediately. Do not wait for an online response.</p></div>
<?php else:?>
<div><label class="va-form-label-field">Support topic *</label><select class="va-form-input" name="topic" required><option value="">Choose topic</option><?php foreach(['Crop production','Livestock management','Soil fertility','Pest and disease management','Climate-smart agriculture','Irrigation and water management','Post-harvest handling','Farm business and records','Training / demonstration','Other'] as $t):?><option value="<?=sh($t)?>"><?=sh($t)?></option><?php endforeach;?></select></div><div><label class="va-form-label-field">Preferred support mode</label><select class="va-form-input" name="support_mode"><option value="remote">Phone / online guidance</option><option value="farm_visit">Farm visit</option><option value="office_visit">Office visit</option><option value="group_training">Group training</option></select></div>
<div class="va-form-full"><label class="va-form-label-field">Farm activity (optional)</label><input class="va-form-input" name="farm_activity" maxlength="180" placeholder="Optional — e.g. dairy, maize, fish, beekeeping, agroforestry"></div><div class="va-form-full"><label class="va-form-label-field">Describe the support needed *</label><textarea class="va-form-input" name="details" required maxlength="5000" placeholder="Explain the challenge, what support you need and any relevant context."></textarea></div>
<?php endif;?>
<div><label class="va-form-label-field">County where support is needed *</label><input class="va-form-input" name="county" required maxlength="100" placeholder="County"></div><div><label class="va-form-label-field">Sub-county / ward / village</label><input class="va-form-input" name="locality" maxlength="180" placeholder="Optional local area"></div><div><label class="va-form-label-field">Preferred date</label><input class="va-form-input" type="date" name="preferred_date" min="<?=date('Y-m-d')?>"></div><div><label class="va-form-label-field">Contact phone</label><input class="va-form-input" name="contact_phone" maxlength="30" value="<?=sh($_SESSION['phone']??'')?>"></div>
</div><button class="btn" type="submit">Send support request →</button></form></section><p><a href="stakeholder_directory.php">← Back to directory</a> · <a href="agricultural_support_requests.php">My support requests</a></p></main><script src="assets/js/vayuma-language.js" defer></script></body></html>
