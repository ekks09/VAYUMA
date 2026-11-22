<?php
session_start(); require_once __DIR__.'/config/db.php'; require_once __DIR__.'/includes/workspace_notice.php';
if(!isset($_SESSION['user_id'])||($_SESSION['logged_in']??false)!==true){header('Location: login.php');exit;}
if(strtolower(trim($_SESSION['role']??''))!=='government_officer'){vayuma_workspace_denied('Government officer access only.');}
$uid=(int)$_SESSION['user_id'];$stmt=$conn->prepare("SELECT organization_name,verification_status FROM stakeholder_profiles WHERE user_id=? AND role='government_officer' LIMIT 1");$stmt->bind_param('i',$uid);$stmt->execute();$profile=$stmt->get_result()->fetch_assoc();$stmt->close();
if(!$profile||($profile['verification_status']??'pending')!=='verified'){vayuma_workspace_denied('A verified government officer profile is required to publish official programmes.');}
function gc_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
$title=trim($_POST['title']??'');$type=trim($_POST['programme_type']??'');$summary=trim($_POST['summary']??'');$desc=trim($_POST['description']??'');$elig=trim($_POST['eligibility']??'');$county=trim($_POST['county']??'');$sub=trim($_POST['sub_county']??'');$ward=trim($_POST['ward']??'');$venue=trim($_POST['venue']??'');$start=trim($_POST['start_at']??'');$end=trim($_POST['end_at']??'');$deadline=trim($_POST['registration_deadline']??'');$visibility=$_POST['visibility']??'public';$status=isset($_POST['publish'])?'published':'draft';
if($title===''||$type===''||$summary===''||$desc===''||!in_array($visibility,['public','location_targeted','restricted'],true))$error='Complete all required fields and choose a valid visibility.';
elseif($visibility==='restricted'&&$elig==='')$error='Restricted programmes must explain eligibility.';
else{$token=bin2hex(random_bytes(24));$institution=$profile['organization_name'];$start=$start!==''?str_replace('T',' ',$start).(strlen($start)===16?':00':''):null;$end=$end!==''?str_replace('T',' ',$end).(strlen($end)===16?':00':''):null;$deadline=$deadline!==''?str_replace('T',' ',$deadline).(strlen($deadline)===16?':00':''):null;
$stmt=$conn->prepare("INSERT INTO government_programmes(created_by,institution_name,title,programme_type,summary,description,eligibility,county,sub_county,ward,venue,start_at,end_at,registration_deadline,visibility,status,public_token) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
$stmt->bind_param('issssssssssssssss',$uid,$institution,$title,$type,$summary,$desc,$elig,$county,$sub,$ward,$venue,$start,$end,$deadline,$visibility,$status,$token);
if($stmt->execute()){$stmt->close();header('Location: government_programme_manage.php');exit;}else{$error='Could not save the programme. Please try again later.'; error_log('VAYUMA programme save failed');$stmt->close();}
}}
?><!doctype html><html lang="en"><head><?php require __DIR__ . '/includes/pwa.php'; ?><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Create Programme | VAYUMA</title><link rel="stylesheet" href="frontend/css/tokens.css">
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
</style></head><body><main class="wrap"><?php require __DIR__ . '/includes/workspace_back.php'; ?><h1>Create agricultural programme or event</h1><p>Publishing is restricted to verified government officers. Location-targeted links remain publicly shareable; targeting controls discovery, not who can open a shared public link.</p><section class="va-form-card"><?php if($error):?><p style="color:#b00"><?=gc_h($error)?></p><?php endif;?><form method="post"><label class="va-form-label-field">Programme / event title *</label><input class="va-form-input" name="title" required maxlength="180"><label class="va-form-label-field">Category *</label><select class="va-form-input" name="programme_type" required><option value="">Choose</option><?php foreach(['Seeds and fertilizer','Livestock distribution','Training / seminar','Vaccination campaign','Grant / subsidy','Farm equipment','Irrigation support','Field day','Market information','Agricultural alert','Other'] as $v):?><option><?=gc_h($v)?></option><?php endforeach;?></select><label class="va-form-label-field">Short summary *</label><input class="va-form-input" name="summary" required maxlength="500"><label class="va-form-label-field">Full description *</label><textarea class="va-form-input" name="description" rows="5" required></textarea><label class="va-form-label-field">Visibility *</label><select class="va-form-input" name="visibility"><option value="public">Public</option><option value="location_targeted">Location-targeted (publicly shareable)</option><option value="restricted">Restricted eligibility</option></select><label class="va-form-label-field">Eligibility (required for restricted)</label><textarea class="va-form-input" name="eligibility" rows="3"></textarea><label class="va-form-label-field">County</label><input class="va-form-input" name="county" maxlength="100"><label class="va-form-label-field">Sub-county</label><input class="va-form-input" name="sub_county" maxlength="120"><label class="va-form-label-field">Ward</label><input class="va-form-input" name="ward" maxlength="120"><label class="va-form-label-field">Venue</label><input class="va-form-input" name="venue" maxlength="220"><label class="va-form-label-field">Start date/time</label><input class="va-form-input" type="datetime-local" name="start_at"><label class="va-form-label-field">End date/time</label><input class="va-form-input" type="datetime-local" name="end_at"><label class="va-form-label-field">Registration deadline</label><input class="va-form-input" type="datetime-local" name="registration_deadline"><p><button class="btn" name="publish" value="1">Publish programme</button> <button class="btn" type="submit" style="background:#64756a">Save draft</button></p></form></section><p><a href="government_programme_manage.php">Back to programme workspace</a></p></main></body></html>
