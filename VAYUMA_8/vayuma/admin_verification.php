<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/admin.php';
require_once __DIR__ . '/includes/csrf.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true || !is_vayuma_admin((int)$_SESSION['user_id'])) {
    http_response_code(403); exit('Administrator access is not enabled for this account.');
}
function av_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$allowedRoles=['all','government_officer','extension_officer','veterinary_officer','financial_institution','transport_provider','cooperative','ngo_partner','insurance_provider','manufacturer','buyer','input_supplier','warehouse_provider','agricultural_service_provider','strategic_partner','researcher'];
$allowedStatuses=['all','pending','verified','rejected'];
$role=$_GET['role']??'all'; if(!in_array($role,$allowedRoles,true))$role='all';
$status=$_GET['status']??'pending'; if(!in_array($status,$allowedStatuses,true))$status='pending';
$q=trim((string)($_GET['q']??''));
$where=[];$types='';$params=[];
if($role!=='all'){$where[]='sp.role=?';$types.='s';$params[]=$role;}
if($status!=='all'){$where[]='sp.verification_status=?';$types.='s';$params[]=$status;}
if($q!==''){$where[]='(sp.organization_name LIKE ? OR sp.registration_number LIKE ? OR sp.county LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)';$types.='sssss';$like='%'.$q.'%';array_push($params,$like,$like,$like,$like,$like);}
$sql="SELECT sp.id AS profile_id,sp.user_id,sp.role,sp.organization_name,sp.organization_type,sp.county,sp.location,sp.contact_phone,sp.service_focus,sp.description,sp.license_number,sp.registration_number,sp.verification_status,sp.created_at,sp.updated_at,u.full_name,u.email FROM stakeholder_profiles sp INNER JOIN users u ON u.id=sp.user_id";
if($where)$sql.=' WHERE '.implode(' AND ',$where);
$sql.=" ORDER BY CASE sp.verification_status WHEN 'pending' THEN 0 WHEN 'rejected' THEN 1 ELSE 2 END, sp.updated_at DESC";
$stmt=$conn->prepare($sql); if(!$stmt){http_response_code(500);exit('Unable to load verification records.');}
if($types!==''){$stmt->bind_param($types,...$params);} $stmt->execute();$rows=$stmt->get_result();
$roleLabels=['government_officer'=>'Government Officer','extension_officer'=>'Extension Officer','veterinary_officer'=>'Veterinary Officer','financial_institution'=>'Financial Institution','transport_provider'=>'Transport Provider','cooperative'=>'Cooperative','ngo_partner'=>'NGO Partner','insurance_provider'=>'Insurance Provider','manufacturer'=>'Manufacturer','buyer'=>'Buyer','input_supplier'=>'Input Supplier','warehouse_provider'=>'Warehouse Provider','agricultural_service_provider'=>'Agricultural Service Provider','strategic_partner'=>'Strategic Partner','researcher'=>'Researcher'];
?><!doctype html><html lang="en"><head><?php require __DIR__ . '/includes/pwa.php'; ?><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Stakeholder Verification | VAYUMA</title><link rel="stylesheet" href="frontend/css/tokens.css">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
<style>
.va-dash-sidebar { width: 260px; background: var(--va-surface); border-right: 1px solid var(--va-border); position: fixed; left: 0; top: 0; bottom: 0; padding: var(--va-space-5); overflow-y: auto; z-index: 100; }
.va-dash-sidebar--logo { padding: 0 var(--va-space-4) var(--va-space-6); }
.va-dash-sidebar--logo h1 { color: var(--va-primary); font-size: var(--va-font-size-xl); font-weight: var(--va-font-weight-bold); letter-spacing: 0.05em; margin: 0; }
.va-dash-sidebar--logo p { color: var(--va-text-muted); font-size: var(--va-font-size-xs); letter-spacing: 0.05em; margin-top: var(--va-space-1); }
.va-dash-sidebar--section { font-size: var(--va-font-size-xs); font-weight: var(--va-font-weight-bold); text-transform: uppercase; letter-spacing: 0.1em; color: var(--va-text-muted); margin: var(--va-space-6) var(--va-space-4) var(--va-space-2); }
.va-dash-sidebar--nav-item { display: flex; align-items: center; gap: var(--va-space-3); padding: var(--va-space-3) var(--va-space-4); margin: var(--va-space-1) 0; border-radius: var(--va-radius-md); text-decoration: none; color: var(--va-text-secondary); font-size: var(--va-font-size-sm); transition: all var(--va-transition); }
.va-dash-sidebar--nav-item:hover, .va-dash-sidebar--nav-item--active { background: var(--va-primary-light); color: var(--va-primary); }
.va-dash-sidebar--nav-item--active { font-weight: var(--va-font-weight-semibold); }
.va-dash-sidebar--icon { width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: var(--va-font-size-sm); flex-shrink: 0; }
.va-dash-main { margin-left: 260px; padding: var(--va-space-6); }
.va-dash-topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--va-space-6); }
.va-dash-search { width: 340px; background: var(--va-surface); border: 1px solid var(--va-border); border-radius: var(--va-radius-md); padding: var(--va-space-3) var(--va-space-4); color: var(--va-text-muted); font-size: var(--va-font-size-sm); }
.va-dash-profile { display: flex; align-items: center; gap: var(--va-space-3); }
.va-dash-profile-info { text-align: right; }
.va-dash-profile-info strong { display: block; font-size: var(--va-font-size-sm); color: var(--va-text-primary); }
.va-dash-profile-info small { color: var(--va-text-muted); font-size: var(--va-font-size-xs); }
.va-dash-avatar { width: 44px; height: 44px; border-radius: 50%; background: var(--va-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: var(--va-font-weight-bold); font-size: var(--va-font-size-sm); }
.va-dash-role-label { color: var(--va-primary); font-size: var(--va-font-size-xs); font-weight: var(--va-font-weight-bold); letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: var(--va-space-2); }
.va-dash-page-title { font-size: var(--va-font-size-3xl); margin-bottom: var(--va-space-2); color: var(--va-text-primary); }
.va-dash-subtitle { color: var(--va-text-secondary); font-size: var(--va-font-size-base); line-height: var(--va-line-height-relaxed); margin-bottom: var(--va-space-6); }
.va-dash-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--va-space-4); margin-bottom: var(--va-space-6); }
.va-dash-stat { background: var(--va-surface); border: 1px solid var(--va-border); border-radius: var(--va-radius-lg); padding: var(--va-space-5); }
.va-dash-stat-icon { font-size: var(--va-font-size-xl); margin-bottom: var(--va-space-3); }
.va-dash-stat h3 { color: var(--va-text-muted); font-size: var(--va-font-size-xs); margin-bottom: var(--va-space-2); font-weight: var(--va-font-weight-semibold); text-transform: uppercase; letter-spacing: 0.05em; }
.va-dash-number { color: var(--va-primary); font-size: var(--va-font-size-2xl); font-weight: var(--va-font-weight-bold); }
.va-dash-description { color: var(--va-text-muted); font-size: var(--va-font-size-xs); margin-top: var(--va-space-1); }
.va-dash-content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: var(--va-space-6); }
.va-dash-panel { background: var(--va-surface); border: 1px solid var(--va-border); border-radius: var(--va-radius-lg); padding: var(--va-space-5); }
.va-dash-panel h2 { font-size: var(--va-font-size-lg); margin-bottom: var(--va-space-4); color: var(--va-text-primary); }
.va-dash-feature { display: flex; align-items: center; gap: var(--va-space-3); padding: var(--va-space-3) 0; border-bottom: 1px solid var(--va-border-light); }
.va-dash-feature:last-child { border-bottom: none; }
.va-dash-feature-icon { width: 40px; height: 40px; border-radius: var(--va-radius-md); background: var(--va-primary-light); color: var(--va-primary); display: flex; align-items: center; justify-content: center; font-size: var(--va-font-size-sm); }
.va-dash-feature strong { display: block; margin-bottom: var(--va-space-1); color: var(--va-text-primary); font-size: var(--va-font-size-sm); }
.va-dash-feature span { color: var(--va-text-secondary); font-size: var(--va-font-size-xs); line-height: var(--va-line-height-relaxed); }
.va-dash-info { margin-top: var(--va-space-5); padding: var(--va-space-4); background: var(--va-primary-light); border-radius: var(--va-radius-md); }
.va-dash-info h3 { color: var(--va-primary); margin-bottom: var(--va-space-2); font-size: var(--va-font-size-sm); }
.va-dash-info p { color: var(--va-text-secondary); font-size: var(--va-font-size-xs); line-height: var(--va-line-height-relaxed); }
.va-dash-action { display: flex; align-items: center; gap: var(--va-space-3); padding: var(--va-space-3); margin-bottom: var(--va-space-2); border: 1px solid var(--va-border); border-radius: var(--va-radius-md); text-decoration: none; color: var(--va-text-secondary); font-size: var(--va-font-size-sm); transition: all var(--va-transition); }
.va-dash-action:hover { background: var(--va-primary-light); color: var(--va-primary); border-color: var(--va-primary); }
@media (max-width: 800px) { .va-dash-sidebar { width: 220px; } .va-dash-main { margin-left: 220px; } }
@media (max-width: 650px) { .va-dash-sidebar { position: fixed; left: -260px; transition: left 0.3s; box-shadow: var(--va-shadow-lg); z-index: 200; } .va-dash-sidebar--open { left: 0; } .va-dash-main { margin-left: 0; width: 100%; padding: var(--va-space-4); } .va-dash-stats { grid-template-columns: 1fr; } }
</style></head><body><main class="wrap"><?php require __DIR__ . '/includes/workspace_back.php'; ?><header class="top"><h2 style="color:#087d42">VAYUMA · Verification Centre</h2><nav><a class="va-dash-sidebar--nav-item" href="admin_dashboard.php">Admin dashboard</a> &nbsp;·&nbsp; <a class="va-dash-sidebar--nav-item" href="admin.php">Legacy admin / payments</a></nav></header><section class="hero"><h1>Stakeholder Verification</h1><p>Check organisation and contact details before approving a stakeholder. Government officers must be verified before they can publish official programmes.</p></section><section class="filters"><form method="get"><select name="role"><option value="all">All stakeholder roles</option><?php foreach($roleLabels as $key=>$label):?><option value="<?=av_h($key)?>" <?=$role===$key?'selected':''?>><?=av_h($label)?></option><?php endforeach;?></select><select name="status"><option value="pending" <?=$status==='pending'?'selected':''?>>Pending</option><option value="verified" <?=$status==='verified'?'selected':''?>>Verified</option><option value="rejected" <?=$status==='rejected'?'selected':''?>>Rejected</option><option value="all" <?=$status==='all'?'selected':''?>>All statuses</option></select><input type="search" name="q" value="<?=av_h($q)?>" placeholder="Search organisation, officer, county..."><button class="btn">Filter records</button><a class="va-dash-sidebar--nav-item" href="admin_verification.php" style="align-self:center;color:#087d42">Clear</a></form></section>
<?php if(!$rows->num_rows):?><div class="empty">No stakeholder profiles match these filters.</div><?php endif;?>
<?php while($p=$rows->fetch_assoc()):?><article class="item"><div class="itemhead"><div><h2 style="margin:0 0 6px"><?=av_h($p['organization_name'])?></h2><div style="color:#68786e"><?=av_h($p['full_name'])?> · <?=av_h($p['email'])?></div></div><span class="badge <?=av_h($p['verification_status'])?>"><?=av_h(ucfirst($p['verification_status']))?></span></div><div class="details"><div class="detail"><b>Stakeholder role</b><?=av_h($roleLabels[$p['role']]??ucwords(str_replace('_',' ',$p['role'])))?></div><div class="detail"><b>County / location</b><?=av_h($p['county'])?><?=!empty($p['location'])?' · '.av_h($p['location']):''?></div><div class="detail"><b>Contact phone</b><?=av_h($p['contact_phone']?:'Not supplied')?></div><div class="detail"><b>Registration number</b><?=av_h($p['registration_number']?:'Not supplied')?></div><div class="detail"><b>License number</b><?=av_h($p['license_number']?:'Not supplied')?></div><div class="detail"><b>Organisation type / focus</b><?=av_h(trim(($p['organization_type']??'').' '.($p['service_focus']??''))?:'Not supplied')?></div></div><?php if(!empty($p['description'])):?><p style="color:#56685d;line-height:1.5"><?=nl2br(av_h($p['description']))?></p><?php endif;?>
<form method="post" action="auth/admin_update_verification.php"><?=vayuma_csrf_field()?><input type="hidden" name="profile_id" value="<?=av_h($p['profile_id'])?>"><input type="text" name="decision_note" maxlength="500" placeholder="Decision note (optional)" style="flex:1;min-width:210px;padding:10px;border:1px solid #cbd9cf;border-radius:8px"><button class="btn" name="status" value="verified" <?=$p['verification_status']==='verified'?'disabled':''?>>Approve / Verify</button><button class="btn reject" name="status" value="rejected" <?=$p['verification_status']==='rejected'?'disabled':''?>>Reject</button><button class="btn" style="background:#6b7b71" name="status" value="pending" <?=$p['verification_status']==='pending'?'disabled':''?>>Return to pending</button></form></article><?php endwhile;$stmt->close();?>
</main></body></html>
