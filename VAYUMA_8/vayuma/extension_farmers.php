<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/language.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) { header('Location: login.php'); exit; }
if (strtolower(trim($_SESSION['role'] ?? '')) !== 'extension_officer') { header('Location: dashboard.php'); exit; }

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
require_once __DIR__.'/includes/extension_access.php';
$search=is_string($_GET['q']??null)?trim($_GET['q']):'';
$page=isset($_GET['page']) && is_string($_GET['page']) && ctype_digit($_GET['page']) ? min(max((int)$_GET['page'],1),10000) : 1;
$farmers=[];$more=false;$available=true;
try {$farmers=vayuma_extension_farmers($conn,(int)$_SESSION['user_id'],$search,($page-1)*50);$more=count($farmers)>50;if($more)array_pop($farmers);}
catch(Throwable $e){error_log('Extension farmer directory failed: '.$e->getMessage());$available=false;http_response_code(503);}
?>
<!doctype html><html lang="en"><head><?php require __DIR__ . '/includes/pwa.php'; ?><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1, interactive-widget=resizes-content"><title>Farmers | VAYUMA Extension</title>
<link rel="stylesheet" href="frontend/css/tokens.css">
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
</style><link rel="stylesheet" href="frontend/css/extension-workspace.css?v=2"></head><body class="va-extension-workspace"><div class="wrap"><?php $workspaceBackLanguage = true; require __DIR__ . '/includes/workspace_back.php'; ?>
<div class="top"><div><div class="va-profile-brand">VAYUMA · Farmers</div><p class="muted">Browse farmers who have an accepted support relationship with you.</p></div></div>
<div class="va-profile-card"><form method="get" class="filters"><label for="farmer-q">Search farmers, farms, crops or locations</label><input id="farmer-q" class="va-profile-input" name="q" value="<?=h($search)?>"><button class="btn">Search</button><a class="btn" href="extension_farmers.php">Clear</a></form>
<p class="muted">Only farmers with an accepted, in-progress or completed extension support request to you are shown. This page does not create accounts.</p><?php if(!$available):?><p role="alert">Farmer records are temporarily unavailable.</p><?php else:?><p>Showing <?=count($farmers)?> records on page <?=$page?>.</p><?php endif;?>
<div class="va-profile-form-grid"><?php foreach($farmers as $f):?><article class="farmer"><h3><?=h($f['full_name'] ?: 'Farmer')?></h3><?php if($f['farm_name']):?><div class="tag"><?=h($f['farm_name'])?></div><?php endif;?><?php if($f['county']):?><div class="tag"><?=h($f['county'])?></div><?php endif;?><p class="muted"><?=h(trim(($f['sub_county']??'').' '.($f['farm_location']??'')))?></p><p><strong>Main crops:</strong> <?=h($f['main_crops'] ?: 'Not provided')?></p></article><?php endforeach;?></div>
<?php if($available && !$farmers):?><div class="empty">No eligible farmers matched your search. Clear the search or check your received support requests.</div><?php endif;?><p><a href="agricultural_support_requests.php?view=received&amp;type=extension">View received extension requests</a></p><nav aria-label="Farmer pages"><?php if($page>1):?><a href="?q=<?=urlencode($search)?>&amp;page=<?=$page-1?>" class="page-link">Previous</a><?php endif;?> <?php if($more):?><a href="?q=<?=urlencode($search)?>&amp;page=<?=$page+1?>" class="page-link">Next</a><?php endif;?></nav></div></div></body></html>
