<?php
session_start(); require_once __DIR__.'/config/db.php'; require_once __DIR__.'/includes/language.php';
if(!isset($_SESSION['user_id'])||($_SESSION['logged_in']??false)!==true){header('Location: login.php');exit;}
if(strtolower(trim($_SESSION['role']??''))!=='extension_officer'){header('Location: dashboard.php');exit;}
require_once __DIR__.'/includes/extension_access.php';
$uid=(int)$_SESSION['user_id']; function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
if(empty($_SESSION['ext_csrf'])) $_SESSION['ext_csrf']=bin2hex(random_bytes(32));
$msg=''; $err='';
try {
if(($_SERVER['REQUEST_METHOD'] ?? 'GET')==='POST'){
 if(!is_string($_POST['csrf']??null) || !hash_equals($_SESSION['ext_csrf'],$_POST['csrf'])){ http_response_code(403); $err='Your session token expired. Refresh and try again.'; }
 else {
  $action=$_POST['action']??'create';
  if($action==='create'){
   $farmerId=(int)($_POST['farmer_id']??0); $date=trim($_POST['visit_date']??''); $county=trim($_POST['county']??''); $location=trim($_POST['location']??''); $purpose=trim($_POST['purpose']??''); $notes=trim($_POST['notes']??'');
   $farmerName='';
   if($farmerId>0) $farmerName=vayuma_extension_farmer_name($conn,$uid,$farmerId) ?? '';
   if($date===''||$purpose===''||($farmerId>0&&$farmerName==='')){http_response_code(400);$err='Choose a valid visit date and purpose, and an eligible farmer if applicable.';}
   else{$dt=str_replace('T',' ',$date);if(strlen($dt)===16)$dt.=':00';$farmerValue=$farmerId>0?$farmerId:null;
    $st=$conn->prepare("INSERT INTO extension_field_visits (extension_officer_id,farmer_id,farmer_name_snapshot,county,visit_location,visit_date,purpose,notes) VALUES (?,?,?,?,?,?,?,?)");
    if(!$st) throw new RuntimeException('Visit insert prepare failed'); $st->bind_param('iissssss',$uid,$farmerValue,$farmerName,$county,$location,$dt,$purpose,$notes);
    if(!$st->execute()) throw new RuntimeException('Visit insert failed'); $msg='Farm visit scheduled successfully.';$st->close();
   }
  } elseif($action==='status'){
   $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);$status=$_POST['status']??'';
   if($id===false||$id===null||!in_array($status,['planned','completed','cancelled'],true)){$err='Choose a valid visit and status.';}
   else{
    try{
     $st=$conn->prepare("SELECT status FROM extension_field_visits WHERE id=? AND extension_officer_id=? LIMIT 1");
     if(!$st)throw new mysqli_sql_exception('Unable to prepare visit lookup.');
     $st->bind_param('ii',$id,$uid);if(!$st->execute() || !($result=$st->get_result())) throw new RuntimeException('Visit lookup failed');$current=$result->fetch_assoc();$st->close();
     if(!$current){$err='This visit could not be found in your schedule.';}
     elseif($current['status']===$status){$msg='This visit already has that status.';}
     elseif($current['status']!=='planned'){$err='Completed or cancelled visits cannot be changed.';}
     else{
      $st=$conn->prepare("UPDATE extension_field_visits SET status=? WHERE id=? AND extension_officer_id=? AND status='planned'");
      if(!$st)throw new mysqli_sql_exception('Unable to prepare visit status update.');
      $st->bind_param('sii',$status,$id,$uid);if(!$st->execute()) throw new RuntimeException('Visit update failed');
      if($st->affected_rows===1)$msg='Visit status updated.';
      else{$err='The visit status was not changed. Refresh and try again.';}
      $st->close();
     }
    }catch(mysqli_sql_exception $exception){error_log('Extension visit status update failed: '.$exception->getMessage());$err='The visit status could not be updated. Please try again.';}
   }
  }
 }
}
} catch (Throwable $e) { error_log('Extension save failed: '.$e->getMessage()); http_response_code(503); $err='Unable to save. Please try again later.'; }
$search=is_string($_GET['farmer_q']??null)?trim($_GET['farmer_q']):'';
$page=isset($_GET['farmer_page']) && is_string($_GET['farmer_page']) && ctype_digit($_GET['farmer_page']) ? min(max((int)$_GET['farmer_page'],1),10000) : 1;
$farmers=[]; $farmerMore=false; $farmerAvailable=true;
try { $farmers=vayuma_extension_farmers($conn,$uid,$search,($page-1)*50); $farmerMore=count($farmers)>50; if($farmerMore)array_pop($farmers); }
catch(Throwable $e){error_log('Extension farmer list failed: '.$e->getMessage());$farmerAvailable=false;}
$visits=[];$recordsAvailable=true;
try {

$st=$conn->prepare("SELECT * FROM extension_field_visits WHERE extension_officer_id=? ORDER BY visit_date DESC LIMIT 100");$st->bind_param('i',$uid);if(!$st->execute() || !($result=$st->get_result())) throw new RuntimeException('Visit list failed');$visits=$result->fetch_all(MYSQLI_ASSOC);$st->close();
} catch (Throwable $e) { error_log('Extension records failed: '.$e->getMessage()); $recordsAvailable=false; $err='Records are temporarily unavailable. Please try again later.'; http_response_code(503); }
$old = static function (string $key, string $default = '') use (&$err): string {
    if ($err === '' || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') return $default;
    if (isset($_POST['action']) && $_POST['action'] !== 'create') return $default;
    return is_string($_POST[$key] ?? null) ? $_POST[$key] : $default;
};
$oldFarmer = $old('farmer_id', '0');
$farmerFound = false;
foreach ($farmers as $candidate) if ((string)$candidate['id'] === $oldFarmer) { $farmerFound = true; break; }
?>
<!doctype html><html lang="en"><head><?php require __DIR__ . '/includes/pwa.php'; ?><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Farm Visits | VAYUMA TRIX</title><link rel="stylesheet" href="frontend/css/tokens.css">
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
</style><link rel="stylesheet" href="frontend/css/extension-workspace.css?v=2"></head><body class="va-extension-workspace"><div class="wrap"><?php $workspaceBackLanguage = true; require __DIR__ . '/includes/workspace_back.php'; ?><div class="top"><div><h1>Farm Visits</h1><p class="muted">Schedule and track your field visits.</p></div></div>
<?php if($msg):?><div class="notice" role="status" aria-live="polite"><?=h($msg)?></div><?php endif;?><?php if($err):?><div class="notice error" role="alert" aria-live="assertive"><?=h($err)?></div><?php endif;?>
<section class="va-profile-card"><h2>Find an eligible farmer</h2><p>Only farmers with an accepted, in-progress or completed support request to you are listed. Search by name or farm.</p><form method="get" class="filters"><label for="farmer-search">Farmer search</label><input id="farmer-search" class="va-profile-input" name="farmer_q" value="<?=h($search)?>"><button class="btn">Search</button></form><?php if(!$farmerAvailable):?><p role="alert">Farmer selection is temporarily unavailable.</p><?php else:?><p>Showing <?=count($farmers)?> eligible farmers on page <?=$page?>. Search or change page before filling the form; doing so clears unsaved entries.</p><nav aria-label="Farmer search pages"><?php if($page>1):?><a href="?farmer_q=<?=urlencode($search)?>&amp;farmer_page=<?=$page-1?>" class="page-link">Previous</a><?php endif;?> <?php if($farmerMore):?><a href="?farmer_q=<?=urlencode($search)?>&amp;farmer_page=<?=$page+1?>" class="page-link">Next</a><?php endif;?></nav><?php endif;?></section>
<section class="va-profile-card"><h2>Schedule a visit</h2><form method="post" class="formgrid" style="margin-top:15px"><input class="va-profile-input" type="hidden" name="csrf" value="<?=h($_SESSION['ext_csrf'])?>"><input class="va-profile-input" type="hidden" name="action" value="create">
<div class="field"><label class="va-profile-label-field" for="visit-farmer">Registered farmer (optional)</label><select id="visit-farmer" class="va-profile-input" name="farmer_id" required><option value="0">No farmer linked</option><?php if($oldFarmer !== '0' && $oldFarmer !== '' && !$farmerFound):?><option value="" selected disabled>Previous farmer unavailable — choose again</option><?php endif;?><?php foreach($farmers as $f):?><option value="<?=(int)$f['id']?>" <?=$oldFarmer===(string)$f['id']?'selected':''?>><?=h($f['full_name'])?></option><?php endforeach;?></select></div>
<div class="field"><label class="va-profile-label-field" for="visit-date">Visit date and time *</label><input id="visit-date" class="va-profile-input" type="datetime-local" name="visit_date" value="<?=h($old('visit_date'))?>" required></div><div class="field"><label class="va-profile-label-field" for="visit-county">County</label><input id="visit-county" class="va-profile-input" name="county" value="<?=h($old('county'))?>" maxlength="100"></div><div class="field"><label class="va-profile-label-field" for="visit-location">Farm / visit location</label><input id="visit-location" class="va-profile-input" name="location" value="<?=h($old('location'))?>" maxlength="255"></div><div class="field"><label class="va-profile-label-field" for="visit-purpose">Purpose *</label><input id="visit-purpose" class="va-profile-input" name="purpose" value="<?=h($old('purpose'))?>" maxlength="200" required placeholder="e.g. soil fertility assessment"></div><div class="field full"><label class="va-profile-label-field" for="visit-notes">Notes / preparation</label><textarea id="visit-notes" class="va-profile-input" name="notes" rows="3"><?=h($old('notes'))?></textarea></div><div><button class="btn" type="submit">Save farm visit</button></div></form></section>
<section class="va-profile-card"><h2>My scheduled visits</h2><div class="tablewrap" tabindex="0" role="region" aria-label="Scheduled visits table, scroll horizontally"><table><thead><tr><th>Date</th><th>Farmer</th><th>Location</th><th>Purpose</th><th>Status</th><th>Update</th></tr></thead><tbody><?php foreach($visits as $v):?><tr><td><?=h($v['visit_date'])?></td><td><?=h($v['farmer_name_snapshot']?:'Not linked')?></td><td><?=h(trim(($v['county']??'').' '.($v['visit_location']??'')))?></td><td><?=h($v['purpose'])?></td><td><?=h(ucfirst($v['status']))?></td><td><?php if($v['status']==='planned'):?><form method="post" class="inline"><input class="va-profile-input" type="hidden" name="csrf" value="<?=h($_SESSION['ext_csrf'])?>"><input class="va-profile-input" type="hidden" name="action" value="status"><input class="va-profile-input" type="hidden" name="id" value="<?=$v['id']?>"><label class="sr-only" for="visit-status-<?=$v['id']?>">Status for visit on <?=h($v['visit_date'])?></label><select id="visit-status-<?=$v['id']?>" class="va-profile-input" name="status"><option value="planned" <?=$v['status']==='planned'?'selected':''?>>Planned</option><option value="completed" <?=$v['status']==='completed'?'selected':''?>>Completed</option><option value="cancelled" <?=$v['status']==='cancelled'?'selected':''?>>Cancelled</option></select><button class="btn" type="submit" style="padding:8px 10px">Save</button></form><?php else:?><span class="muted">Final status</span><?php endif;?></td></tr><?php endforeach;?></tbody></table><?php if($recordsAvailable && !$visits):?><p class="muted" style="padding:15px 0">No visits scheduled yet.</p><?php endif;?></div></section></div></body></html>
