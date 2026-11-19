<?php
require_once __DIR__.'/config/db.php';
function gp_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$token=trim($_GET['token']??'');
if($token!==''){
  $stmt=$conn->prepare("SELECT * FROM government_programmes WHERE public_token=? AND status='published' LIMIT 1");
  $stmt->bind_param('s',$token); $stmt->execute(); $programme=$stmt->get_result()->fetch_assoc(); $stmt->close();
  if(!$programme){http_response_code(404); exit('Programme not found or no longer available.');}
  ?><!doctype html><html lang="en"><head><?php require __DIR__ . '/includes/pwa.php'; ?><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=gp_h($programme['title'])?> | VAYUMA</title><link rel="stylesheet" href="frontend/css/tokens.css">
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
</style></head><body><main class="wrap"><h1>VAYUMA | Agricultural Programmes</h1><article class="card"><small><?=gp_h(str_replace('_',' ',ucfirst($programme['visibility'])))?> programme · <?=gp_h($programme['programme_type'])?></small><h2><?=gp_h($programme['title'])?></h2><p><?=nl2br(gp_h($programme['summary']))?></p><p><?=nl2br(gp_h($programme['description']))?></p><p><b>Institution:</b> <?=gp_h($programme['institution_name'])?></p><p><b>Location:</b> <?=gp_h(implode(', ',array_filter([$programme['ward'],$programme['sub_county'],$programme['county']])))?></p><p><b>Venue:</b> <?=gp_h($programme['venue'])?></p><p><b>Starts:</b> <?=gp_h($programme['start_at'])?> &nbsp; <b>Ends:</b> <?=gp_h($programme['end_at'])?></p><?php if($programme['visibility']==='restricted'):?><p><b>Eligibility:</b> <?=nl2br(gp_h($programme['eligibility']))?></p><?php endif;?></article><section class="va-profile-card"><h2>Register interest / attendance</h2><p>You do not need a VAYUMA account to register.</p><?php if(isset($_GET['registered'])):?><p style="color:green">Your registration has been received.</p><?php endif;?><form method="post" action="government_programme_register.php"><input class="va-profile-input" type="hidden" name="token" value="<?=gp_h($token)?>"><label class="va-profile-label-field">Full name *</label><input class="va-profile-input" name="full_name" required maxlength="150"><label class="va-profile-label-field">Phone number *</label><input class="va-profile-input" name="phone" required maxlength="30"><label class="va-profile-label-field">Email (optional)</label><input class="va-profile-input" name="email" type="email" maxlength="150"><label class="va-profile-label-field">County</label><input class="va-profile-input" name="county" maxlength="100"><label class="va-profile-label-field">Additional note (optional)</label><textarea class="va-profile-input" name="notes" maxlength="500"></textarea><button class="btn">Submit registration</button></form></section><p><a href="government_programmes.php">Browse other programmes</a></p></main></body></html><?php
  exit;
}
$sql="SELECT id,title,programme_type,summary,county,sub_county,ward,venue,start_at,end_at,visibility,public_token FROM government_programmes WHERE status='published' AND (visibility IN ('public','location_targeted') OR visibility='restricted') ORDER BY start_at IS NULL,start_at ASC,created_at DESC";
$res=$conn->query($sql);
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Government Programmes | VAYUMA</title><style>body{font-family:Arial;background:#f3f7f4;color:#183b29;margin:0}.wrap{max-width:1050px;margin:30px auto;padding:20px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px}.card{background:#fff;border:1px solid #dce8df;border-radius:14px;padding:20px}.btn{display:inline-block;background:#087d42;color:white;padding:10px 14px;border-radius:8px;text-decoration:none;font-weight:bold}</style></head><body><main class="wrap"><h1>Government Agricultural Programmes & Events</h1><p>Public information and shareable opportunities from participating institutions.</p><div class="va-profile-form-grid"><?php if($res && $res->num_rows): while($p=$res->fetch_assoc()):?><article class="card"><small><?=gp_h($p['programme_type'])?> · <?=gp_h($p['visibility'])?></small><h2><?=gp_h($p['title'])?></h2><p><?=gp_h($p['summary'])?></p><p><?=gp_h(implode(', ',array_filter([$p['ward'],$p['sub_county'],$p['county']])))?></p><p><?=gp_h($p['start_at'])?></p><a class="btn" href="government_programmes.php?token=<?=urlencode($p['public_token'])?>">View details</a></article><?php endwhile;else:?><p>No published programmes are available at the moment.</p><?php endif;?></div></main></body></html>
