<?php
session_start(); require_once __DIR__.'/config/db.php';
if(!isset($_SESSION['user_id'])||($_SESSION['logged_in']??false)!==true){header('Location: login.php');exit;}
if(strtolower(trim($_SESSION['role']??''))!=='government_officer'){http_response_code(403);exit('Government officer access only.');}
$uid=(int)$_SESSION['user_id'];
$stmt=$conn->prepare("SELECT organization_name,verification_status FROM stakeholder_profiles WHERE user_id=? AND role='government_officer' LIMIT 1");$stmt->bind_param('i',$uid);$stmt->execute();$profile=$stmt->get_result()->fetch_assoc();$stmt->close();
if(!$profile){http_response_code(403);exit('Complete your government officer profile first.');}
if(($profile['verification_status']??'pending')!=='verified'){http_response_code(403);exit('Only verified government officers can publish official programmes. Your profile verification is currently pending or not approved.');}
function gm_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$stmt=$conn->prepare("SELECT p.*, (SELECT COUNT(*) FROM government_programme_registrations r WHERE r.programme_id=p.id) registrations FROM government_programmes p WHERE created_by=? ORDER BY created_at DESC");$stmt->bind_param('i',$uid);$stmt->execute();$rows=$stmt->get_result();
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Manage Programmes | VAYUMA</title><link rel="stylesheet" href="frontend/css/tokens.css">
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
</style></head><body><main class="wrap"><h1>Government Programmes Workspace</h1><p>Institution: <?=gm_h($profile['organization_name'])?> · Verified publisher</p><p><a class="btn" href="government_programme_create.php">＋ Create programme / event</a> <a href="government_officer_dashboard.php">Back to dashboard</a></p><section class="va-form-card"><h2>Your published and draft programmes</h2><table><tr><th>Programme</th><th>Visibility</th><th>Status</th><th>Registrations</th><th>Public link</th></tr><?php while($r=$rows->fetch_assoc()):?><tr><td><?=gm_h($r['title'])?></td><td><?=gm_h($r['visibility'])?></td><td><?=gm_h($r['status'])?></td><td><?=gm_h($r['registrations'])?></td><td><?php if($r['status']==='published'):?><a target="_blank" href="government_programmes.php?token=<?=urlencode($r['public_token'])?>">Open / Share</a><?php endif;?></td></tr><?php endwhile;?></table></section></main></body></html>
