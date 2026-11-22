<?php
session_start();
require_once __DIR__ . '/includes/language.php'; require_once __DIR__.'/config/db.php';
if(!isset($_SESSION['user_id'])||($_SESSION['logged_in']??false)!==true){header('Location: login.php');exit;}
$role=trim($_GET['role']??''); $county=trim($_GET['county']??'');
$allowed=['input_supplier','manufacturer','warehouse_provider','retailer','agribusiness','veterinary_officer','insurance_provider','government_officer','ngo_partner','agricultural_service_provider','researcher','extension_officer','cooperative','financial_institution','transport_provider'];
$sql="SELECT sp.*,u.full_name,u.phone FROM stakeholder_profiles sp INNER JOIN users u ON u.id=sp.user_id WHERE sp.profile_completed=1"; $types='';$params=[];
if(in_array($role,$allowed,true)){$sql.=' AND sp.role=?';$types.='s';$params[]=$role;} if($county!==''){$sql.=' AND sp.county=?';$types.='s';$params[]=$county;} $sql.=" ORDER BY CASE sp.verification_status WHEN 'verified' THEN 0 ELSE 1 END, sp.organization_name ASC";
$stmt=$conn->prepare($sql); if($types)$stmt->bind_param($types,...$params); $stmt->execute();$rows=$stmt->get_result();
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><head><?php require __DIR__ . '/includes/pwa.php'; ?><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Stakeholder Directory | VAYUMA</title><link rel="stylesheet" href="frontend/css/tokens.css">
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
</style></head><body>
<?php vayuma_language_selector(); ?><main class="va-profile-page"><?php require __DIR__ . '/includes/workspace_back.php'; ?><header class="top"><div class="va-profile-brand">VAYUMA<small>SMART ECOSYSTEM</small></div><nav><a href="stakeholder_offers.php">Exchange</a><a href="auth/logout.php">Logout</a></nav></header><section class="hero"><h1><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-globe"/></svg> Stakeholder Directory</h1><p>Find organisations and service providers across the VAYUMA ecosystem.</p></section><form class="filters"><select class="va-profile-input" name="role" aria-label="Filter by stakeholder role"><option value="">All stakeholder roles</option><?php foreach($allowed as $r):?><option value="<?=h($r)?>" <?=$role===$r?'selected':''?>><?=h(ucwords(str_replace('_',' ',$r)))?></option><?php endforeach;?></select><input class="va-profile-input" name="county" value="<?=h($county)?>" aria-label="Filter by county" placeholder="Filter by county"><button>Search</button></form><?php if($rows->num_rows):?><section class="grid"><?php while($r=$rows->fetch_assoc()):?><article class="card"><div class="role"><?=h(ucwords(str_replace('_',' ',$r['role'])))?></div><h2><?=h($r['organization_name'])?></h2><div class="meta"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> <?=h($r['county'])?><?=!empty($r['location'])?' · '.h($r['location']):''?><br><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-target"/></svg> <?=h($r['service_focus']?:'Agricultural services and support')?><?php if($r['verification_status']==='verified' && ($r['contact_phone']?:$r['phone'])): ?><br>📞 <?=h($r['contact_phone']?:$r['phone'])?><?php else: ?><br>📞 Contact details available after verification<?php endif; ?></div><a class="directory-link" href="stakeholder_offers.php?provider=<?= (int)$r['user_id'] ?>">View published offers →</a><?php if(in_array($r['role'],['veterinary_officer','extension_officer'],true) && (int)$r['user_id']!==(int)$_SESSION['user_id']): ?><br><a class="directory-link" href="request_agricultural_support.php?type=<?=h($r['role']==='veterinary_officer'?'veterinary':'extension')?>&provider=<?= (int)$r['user_id'] ?>">Request <?=h($r['role']==='veterinary_officer'?'animal-health service':'extension support')?> →</a><?php endif; ?><span class="badge <?=$r['verification_status']==='verified'?'verified':''?>"><?=h(ucfirst($r['verification_status']))?></span></article><?php endwhile;?></section><?php else:?><div class="empty">No stakeholder profiles matched your search.</div><?php endif;?></main><script src="assets/js/vayuma-language.js" defer></script>
</body></html>
