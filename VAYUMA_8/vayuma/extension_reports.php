<?php
session_start(); require_once __DIR__.'/config/db.php'; require_once __DIR__.'/includes/language.php';
if(!isset($_SESSION['user_id'])||($_SESSION['logged_in']??false)!==true){header('Location: login.php');exit;}
if(strtolower(trim($_SESSION['role']??''))!=='extension_officer'){header('Location: dashboard.php');exit;}
$uid=(int)$_SESSION['user_id'];function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
if(empty($_SESSION['ext_csrf']))$_SESSION['ext_csrf']=bin2hex(random_bytes(32));$msg='';$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($_SESSION['ext_csrf'],$_POST['csrf']??''))$err='Your session token expired. Refresh and try again.';
 else{
  $farmerId=(int)($_POST['farmer_id']??0);$visitId=(int)($_POST['visit_id']??0);$date=$_POST['report_date']??date('Y-m-d');$type=trim($_POST['report_type']??'');$county=trim($_POST['county']??'');$location=trim($_POST['location']??'');$obs=trim($_POST['observations']??'');$rec=trim($_POST['recommendations']??'');$follow=trim($_POST['follow_up_date']??'');
  $farmerName='';if($farmerId>0){$q=$conn->prepare("SELECT full_name FROM users WHERE id=? AND LOWER(TRIM(role))='farmer' LIMIT 1");$q->bind_param('i',$farmerId);$q->execute();$farmerName=(string)($q->get_result()->fetch_assoc()['full_name']??'');$q->close();}
  if($type===''||$obs==='')$err='Report type and observations are required.';
  elseif($farmerId>0&&$farmerName==='')$err='The selected farmer is not valid.';
  else{$fv=$farmerId>0?$farmerId:null;$vv=$visitId>0?$visitId:null;$followValue=$follow!==''?$follow:null;
   $st=$conn->prepare("INSERT INTO extension_field_reports (extension_officer_id,farmer_id,visit_id,farmer_name_snapshot,county,report_location,report_date,report_type,observations,recommendations,follow_up_date) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
   $st->bind_param('iiissssssss',$uid,$fv,$vv,$farmerName,$county,$location,$date,$type,$obs,$rec,$followValue);
   if($st->execute())$msg='Farm report saved successfully.';else$err='Could not save report: '.$st->error;$st->close();
  }
 }
}
$farmers=[];$r=$conn->query("SELECT id,full_name FROM users WHERE LOWER(TRIM(role))='farmer' ORDER BY full_name");if($r)$farmers=$r->fetch_all(MYSQLI_ASSOC);
$visits=[];$st=$conn->prepare("SELECT id,visit_date,purpose FROM extension_field_visits WHERE extension_officer_id=? ORDER BY visit_date DESC LIMIT 100");$st->bind_param('i',$uid);$st->execute();$visits=$st->get_result()->fetch_all(MYSQLI_ASSOC);$st->close();
$st=$conn->prepare("SELECT * FROM extension_field_reports WHERE extension_officer_id=? ORDER BY report_date DESC, id DESC LIMIT 100");$st->bind_param('i',$uid);$st->execute();$reports=$st->get_result()->fetch_all(MYSQLI_ASSOC);$st->close();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Farm Reports | VAYUMA</title><link rel="stylesheet" href="frontend/css/tokens.css">
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
</style></head><body><div class="wrap"><div class="top"><div><h1>Farm Reports</h1><p class="muted">Record field observations, recommendations and follow-up needs.</p></div><a class="btn" href="extension_dashboard.php">← Dashboard</a></div>
<?php if($msg):?><div class="notice"><?=h($msg)?></div><?php endif;?><?php if($err):?><div class="notice error"><?=h($err)?></div><?php endif;?>
<section class="va-profile-card"><h2>Create a farm report</h2><form method="post" class="formgrid" style="margin-top:15px"><input class="va-profile-input" type="hidden" name="csrf" value="<?=h($_SESSION['ext_csrf'])?>">
<div class="field"><label class="va-profile-label-field">Farmer (optional)</label><select class="va-profile-input" name="farmer_id"><option value="0">Select farmer</option><?php foreach($farmers as $f):?><option value="<?=$f['id']?>"><?=h($f['full_name'])?></option><?php endforeach;?></select></div>
<div class="field"><label class="va-profile-label-field">Related visit (optional)</label><select class="va-profile-input" name="visit_id"><option value="0">No linked visit</option><?php foreach($visits as $v):?><option value="<?=$v['id']?>"><?=h($v['visit_date'].' — '.$v['purpose'])?></option><?php endforeach;?></select></div>
<div class="field"><label class="va-profile-label-field">Report date</label><input class="va-profile-input" type="date" name="report_date" value="<?=date('Y-m-d')?>" required></div><div class="field"><label class="va-profile-label-field">Report type *</label><select class="va-profile-input" name="report_type" required><option value="">Choose type</option><option>Farm assessment</option><option>Crop progress</option><option>Soil and water</option><option>Pest and disease observation</option><option>Livestock support</option><option>Training / demonstration</option><option>Other</option></select></div>
<div class="field"><label class="va-profile-label-field">County</label><input class="va-profile-input" name="county" maxlength="100"></div><div class="field"><label class="va-profile-label-field">Farm / location</label><input class="va-profile-input" name="location" maxlength="255"></div>
<div class="field full"><label class="va-profile-label-field">Observations *</label><textarea class="va-profile-input" name="observations" rows="4" required></textarea></div><div class="field full"><label class="va-profile-label-field">Recommendations</label><textarea class="va-profile-input" name="recommendations" rows="4"></textarea></div><div class="field"><label class="va-profile-label-field">Suggested follow-up date</label><input class="va-profile-input" type="date" name="follow_up_date"></div><div><button class="btn">Save report</button></div></form></section>
<section class="va-profile-card"><h2>My reports</h2><?php foreach($reports as $r):?><article class="report"><h3><?=h($r['report_type'])?> · <?=h($r['report_date'])?></h3><p class="muted"><?=h($r['farmer_name_snapshot']?:'Farmer not linked')?> · <?=h(trim(($r['county']??'').' '.($r['report_location']??'')))?></p><p><strong>Observations:</strong><br><?=nl2br(h($r['observations']))?></p><?php if($r['recommendations']):?><p><strong>Recommendations:</strong><br><?=nl2br(h($r['recommendations']))?></p><?php endif;?><?php if($r['follow_up_date']):?><p><strong>Follow-up:</strong> <?=h($r['follow_up_date'])?></p><?php endif;?></article><?php endforeach;?><?php if(!$reports):?><p class="muted" style="margin-top:14px">No reports recorded yet.</p><?php endif;?></section></div></body></html>
