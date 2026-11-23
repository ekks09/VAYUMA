<?php
session_start(); require_once __DIR__.'/config/db.php'; require_once __DIR__.'/includes/language.php';
if(!isset($_SESSION['user_id'])||($_SESSION['logged_in']??false)!==true){header('Location: login.php');exit;}
if(strtolower(trim($_SESSION['role']??''))!=='extension_officer'){header('Location: dashboard.php');exit;}
require_once __DIR__.'/includes/extension_access.php';
$uid=(int)$_SESSION['user_id'];function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
if(empty($_SESSION['ext_csrf']))$_SESSION['ext_csrf']=bin2hex(random_bytes(32));$msg='';$err='';
try {
if(($_SERVER['REQUEST_METHOD'] ?? 'GET')==='POST'){
 if(!is_string($_POST['csrf']??null) || !hash_equals($_SESSION['ext_csrf'],$_POST['csrf'])){http_response_code(403);$err='Your session token expired. Refresh and try again.';}
 else{
  $farmerRaw=$_POST['farmer_id']??'0'; $visitRaw=$_POST['visit_id']??'0';
  if(!is_string($farmerRaw)||!ctype_digit($farmerRaw)||!is_string($visitRaw)||!ctype_digit($visitRaw)) throw new InvalidArgumentException('Invalid selection');
  $farmerId=(int)$farmerRaw;$visitId=(int)$visitRaw;$date=$_POST['report_date']??date('Y-m-d');$type=trim($_POST['report_type']??'');$county=trim($_POST['county']??'');$location=trim($_POST['location']??'');$obs=trim($_POST['observations']??'');$rec=trim($_POST['recommendations']??'');$follow=trim($_POST['follow_up_date']??'');
  $farmerName='';if($farmerId>0) $farmerName=vayuma_extension_farmer_name($conn,$uid,$farmerId) ?? '';
  if($type===''||$obs==='')$err='Report type and observations are required.';
  elseif($farmerId>0&&$farmerName===''){http_response_code(403);$err='The selected farmer is not available to you.';}
  elseif($visitId>0&&!vayuma_extension_visit_matches($conn,$uid,$visitId,$farmerId)){http_response_code(403);$err='The selected visit is unavailable or does not match the farmer.';}
  else{$fv=$farmerId>0?$farmerId:null;$vv=$visitId>0?$visitId:null;$followValue=$follow!==''?$follow:null;
   $st=$conn->prepare("INSERT INTO extension_field_reports (extension_officer_id,farmer_id,visit_id,farmer_name_snapshot,county,report_location,report_date,report_type,observations,recommendations,follow_up_date) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
   if(!$st) throw new RuntimeException('Report insert prepare failed'); $st->bind_param('iiissssssss',$uid,$fv,$vv,$farmerName,$county,$location,$date,$type,$obs,$rec,$followValue);
   if(!$st->execute()) throw new RuntimeException('Report insert failed'); $msg='Farm report saved successfully.';$st->close();
  }
 }
}
} catch (InvalidArgumentException $e) { http_response_code(400); $err='Choose a valid farmer and visit.'; } catch (Throwable $e) { error_log('Extension save failed: '.$e->getMessage()); http_response_code(503); $err='Unable to save. Please try again later.'; }
$search=is_string($_GET['farmer_q']??null)?trim($_GET['farmer_q']):'';
$page=isset($_GET['farmer_page']) && is_string($_GET['farmer_page']) && ctype_digit($_GET['farmer_page']) ? min(max((int)$_GET['farmer_page'],1),10000) : 1;
$farmers=[]; $farmerMore=false; $farmerAvailable=true;
try { $farmers=vayuma_extension_farmers($conn,$uid,$search,($page-1)*50); $farmerMore=count($farmers)>50; if($farmerMore)array_pop($farmers); }
catch(Throwable $e){error_log('Extension farmer list failed: '.$e->getMessage());$farmerAvailable=false;}
$visits=[];$reports=[];$recordsAvailable=true;
try {

$visits=[];$st=$conn->prepare("SELECT id,visit_date,purpose,farmer_name_snapshot FROM extension_field_visits WHERE extension_officer_id=? ORDER BY visit_date DESC LIMIT 100");$st->bind_param('i',$uid);if(!$st->execute() || !($result=$st->get_result())) throw new RuntimeException('Visit list failed');$visits=$result->fetch_all(MYSQLI_ASSOC);$st->close();
$st=$conn->prepare("SELECT * FROM extension_field_reports WHERE extension_officer_id=? ORDER BY report_date DESC, id DESC LIMIT 100");$st->bind_param('i',$uid);if(!$st->execute() || !($result=$st->get_result())) throw new RuntimeException('Report list failed');$reports=$result->fetch_all(MYSQLI_ASSOC);$st->close();
} catch (Throwable $e) { error_log('Extension records failed: '.$e->getMessage()); $recordsAvailable=false; $err='Records are temporarily unavailable. Please try again later.'; http_response_code(503); }
$old = static function (string $key, string $default = '') use (&$err): string {
    if ($err === '' || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') return $default;
    if (isset($_POST['action']) && $_POST['action'] !== 'create') return $default;
    return is_string($_POST[$key] ?? null) ? $_POST[$key] : $default;
};
$oldFarmer = $old('farmer_id', '0');
$oldVisit = $old('visit_id', '0');
$visitFound = false;
foreach ($visits as $candidate) if ((string)$candidate['id'] === $oldVisit) { $visitFound = true; break; }
$farmerFound = false;
foreach ($farmers as $candidate) if ((string)$candidate['id'] === $oldFarmer) { $farmerFound = true; break; }
?>
<!doctype html><html lang="en"><head><?php require __DIR__ . '/includes/pwa.php'; ?><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1, interactive-widget=resizes-content"><title>Farm Reports | VAYUMA TRIX</title><link rel="stylesheet" href="frontend/css/tokens.css">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
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
</style><link rel="stylesheet" href="frontend/css/extension-workspace.css?v=2"></head><body class="va-extension-workspace"><div class="wrap"><?php $workspaceBackLanguage = true; require __DIR__ . '/includes/workspace_back.php'; ?><div class="top"><div><h1>Farm Reports</h1><p class="muted">Record field observations, recommendations and follow-up needs.</p></div></div>
<?php if($msg):?><div class="notice" role="status"><?=h($msg)?></div><?php endif;?><?php if($err):?><div class="notice error" role="alert"><?=h($err)?></div><?php endif;?>
<section class="va-profile-card"><h2>Find an eligible farmer</h2><p>Only farmers with an accepted, in-progress or completed support request to you are listed. Search by name or farm.</p><form method="get" class="filters"><label for="farmer-search">Farmer search</label><input id="farmer-search" class="va-profile-input" name="farmer_q" value="<?=h($search)?>"><button class="btn">Search</button></form><?php if(!$farmerAvailable):?><p role="alert">Farmer selection is temporarily unavailable.</p><?php else:?><p>Showing <?=count($farmers)?> eligible farmers on page <?=$page?>. Search or change page before filling the form; doing so clears unsaved entries.</p><nav aria-label="Farmer search pages"><?php if($page>1):?><a href="?farmer_q=<?=urlencode($search)?>&amp;farmer_page=<?=$page-1?>" class="page-link">Previous</a><?php endif;?> <?php if($farmerMore):?><a href="?farmer_q=<?=urlencode($search)?>&amp;farmer_page=<?=$page+1?>" class="page-link">Next</a><?php endif;?></nav><?php endif;?></section>
<section class="va-profile-card"><h2>Create a farm report</h2><p class="muted">If you link a visit, select the same farmer as that visit. For a visit without a farmer, leave the farmer unlinked.</p><form method="post" class="formgrid" style="margin-top:15px"><input class="va-profile-input" type="hidden" name="csrf" value="<?=h($_SESSION['ext_csrf'])?>">
<div class="field"><label class="va-profile-label-field" for="report-farmer_id">Farmer (optional)</label><select id="report-farmer_id" class="va-profile-input" name="farmer_id" required><option value="0">No farmer linked</option><?php if($oldFarmer !== '0' && $oldFarmer !== '' && !$farmerFound):?><option value="" selected disabled>Previous farmer unavailable — choose again</option><?php endif;?><?php foreach($farmers as $f):?><option value="<?=(int)$f['id']?>" <?=$oldFarmer===(string)$f['id']?'selected':''?>><?=h($f['full_name'])?></option><?php endforeach;?></select></div>
<div class="field"><label class="va-profile-label-field" for="report-visit_id">Related visit (optional; choose its matching farmer)</label><select id="report-visit_id" class="va-profile-input" name="visit_id" required><option value="0">No linked visit</option><?php if($oldVisit !== '0' && $oldVisit !== '' && !$visitFound):?><option value="" selected disabled>Previous visit unavailable — choose again</option><?php endif;?><?php foreach($visits as $v):?><option value="<?=(int)$v['id']?>" <?=$oldVisit===(string)$v['id']?'selected':''?>><?=h($v['visit_date'].' — '.$v['purpose'].' — '.($v['farmer_name_snapshot'] ?: 'No farmer linked'))?></option><?php endforeach;?></select></div>
<div class="field"><label class="va-profile-label-field" for="report-report_date">Report date</label><input id="report-report_date" class="va-profile-input" type="date" name="report_date" value="<?=h($old('report_date',date('Y-m-d')))?>" required></div><div class="field"><label class="va-profile-label-field" for="report-report_type">Report type *</label><select id="report-report_type" class="va-profile-input" name="report_type" required><option value="">Choose type</option><option value="Farm assessment" <?=$old('report_type')==='Farm assessment'?'selected':''?>>Farm assessment</option><option value="Crop progress" <?=$old('report_type')==='Crop progress'?'selected':''?>>Crop progress</option><option value="Soil and water" <?=$old('report_type')==='Soil and water'?'selected':''?>>Soil and water</option><option value="Pest and disease observation" <?=$old('report_type')==='Pest and disease observation'?'selected':''?>>Pest and disease observation</option><option value="Livestock support" <?=$old('report_type')==='Livestock support'?'selected':''?>>Livestock support</option><option value="Training / demonstration" <?=$old('report_type')==='Training / demonstration'?'selected':''?>>Training / demonstration</option><option value="Other" <?=$old('report_type')==='Other'?'selected':''?>>Other</option></select></div>
<div class="field"><label class="va-profile-label-field" for="report-county">County</label><input id="report-county" class="va-profile-input" name="county" value="<?=h($old('county'))?>" maxlength="100"></div><div class="field"><label class="va-profile-label-field" for="report-location">Farm / location</label><input id="report-location" class="va-profile-input" name="location" value="<?=h($old('location'))?>" maxlength="255"></div>
<div class="field full"><label class="va-profile-label-field" for="report-observations">Observations *</label><textarea id="report-observations" class="va-profile-input" name="observations" rows="4" required><?=h($old('observations'))?></textarea></div><div class="field full"><label class="va-profile-label-field" for="report-recommendations">Recommendations</label><textarea id="report-recommendations" class="va-profile-input" name="recommendations" rows="4"><?=h($old('recommendations'))?></textarea></div><div class="field"><label class="va-profile-label-field" for="report-follow_up_date">Suggested follow-up date</label><input id="report-follow_up_date" class="va-profile-input" type="date" name="follow_up_date" value="<?=h($old('follow_up_date'))?>"></div><div><button class="btn">Save report</button></div></form></section>
<section class="va-profile-card"><h2>My reports</h2><?php foreach($reports as $r):?><article class="report"><h3><?=h($r['report_type'])?> · <?=h($r['report_date'])?></h3><p class="muted"><?=h($r['farmer_name_snapshot']?:'Farmer not linked')?> · <?=h(trim(($r['county']??'').' '.($r['report_location']??'')))?></p><p><strong>Observations:</strong><br><?=nl2br(h($r['observations']))?></p><?php if($r['recommendations']):?><p><strong>Recommendations:</strong><br><?=nl2br(h($r['recommendations']))?></p><?php endif;?><?php if($r['follow_up_date']):?><p><strong>Follow-up:</strong> <?=h($r['follow_up_date'])?></p><?php endif;?></article><?php endforeach;?><?php if($recordsAvailable && !$reports):?><p class="muted" style="margin-top:14px">No reports recorded yet.</p><?php endif;?></section></div></body></html>
