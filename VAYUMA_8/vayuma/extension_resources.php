<?php
session_start();
require_once __DIR__.'/includes/language.php';
if(!isset($_SESSION['user_id'])||($_SESSION['logged_in']??false)!==true){header('Location: login.php');exit;}
if(strtolower(trim($_SESSION['role']??''))!=='extension_officer'){header('Location: dashboard.php');exit;}
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><head><?php require __DIR__ . '/includes/pwa.php'; ?><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Farming Resources | VAYUMA TRIX</title><link rel="stylesheet" href="frontend/css/tokens.css">
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
</style><link rel="stylesheet" href="frontend/css/extension-workspace.css?v=2"></head><body class="va-extension-workspace"><div class="wrap"><?php $workspaceBackLanguage = true; require __DIR__ . '/includes/workspace_back.php'; ?><div class="top"><div><h1>Farming Resources</h1><p class="muted">Quick access to VAYUMA's agricultural guidance tools.</p></div></div><div class="va-profile-form-grid">
<div class="va-profile-card"><h2>Crop guidance</h2><p class="muted">Use the AI assistant to explore crop selection, planting practices, nutrient management and other farm questions.</p><a href="ai_advisor.php">Open VAYUMA TRIX AI →</a></div>
<div class="va-profile-card"><h2>Climate guidance</h2><p class="muted">Review climate and weather guidance available through the platform.</p><a href="climate.php">Open climate guidance →</a></div>
<div class="va-profile-card"><h2>Disease and pest support</h2><p class="muted">Open the disease helper for crop and animal health information and precautions.</p><a href="disease_helper.php">Open disease helper →</a></div>
<div class="va-profile-card"><h2>Farmer support requests</h2><p class="muted">Review requests submitted by farmers seeking extension assistance.</p><a href="agricultural_support_requests.php?view=received&type=extension">Manage support requests →</a></div>
<div class="va-profile-card"><h2>Stakeholder network</h2><p class="muted">Find other agricultural service providers and partners registered on VAYUMA.</p><a href="stakeholder_directory.php">Browse stakeholder directory →</a></div>
<div class="va-profile-card"><h2>Field records</h2><p class="muted">Plan field visits and document observations, recommendations and follow-up.</p><a href="extension_visits.php">Open farm visits →</a></div>
</div></div></body></html>
