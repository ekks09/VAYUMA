<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/admin.php';
require_once __DIR__ . '/includes/language.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true || !is_vayuma_admin((int)$_SESSION['user_id'])) {
    http_response_code(403); exit('Administrator access is not enabled for this account. Configure config/admin.local.php first.');
}
function ad_h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$counts = ['pending'=>0,'verified'=>0,'rejected'=>0,'government'=>0];
$res = $conn->query("SELECT verification_status, COUNT(*) AS total FROM stakeholder_profiles GROUP BY verification_status");
if ($res) while ($r=$res->fetch_assoc()) { $key=$r['verification_status']; if(isset($counts[$key])) $counts[$key]=(int)$r['total']; }
$res = $conn->query("SELECT COUNT(*) AS total FROM stakeholder_profiles WHERE role='government_officer' AND verification_status='pending'");
if($res) $counts['government']=(int)$res->fetch_assoc()['total'];
$adminName = 'Administrator';
$stmt=$conn->prepare('SELECT full_name FROM users WHERE id=? LIMIT 1');
$adminId=(int)$_SESSION['user_id']; $stmt->bind_param('i',$adminId); $stmt->execute(); $adminName=$stmt->get_result()->fetch_assoc()['full_name'] ?? $adminName; $stmt->close();
?><!doctype html><html lang="en"><head><?php require __DIR__ . '/includes/pwa.php'; ?><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title>VAYUMA Administrator Dashboard</title><link rel="stylesheet" href="frontend/css/tokens.css">
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
</style><link rel="stylesheet" href="assets/css/dashboard-layout.css?v=2"></head><body><div class="va-dash-layout"><?php
$dashboardLinks = ['Admin dashboard' => 'admin_dashboard.php', 'Verification centre' => 'admin_verification.php', 'Subscription and pricing' => 'admin_subscriptions.php', 'Legacy admin centre' => 'admin.php', 'Main website dashboard' => 'dashboard.php'];
require __DIR__ . '/includes/dashboard_generic_sidebar.php'; ?><main class="wrap va-generic-main"><header class="top vy-user-menu__stakeholder-header"><button type="button" class="va-mobile-menu-btn" aria-controls="vaDashSidebar" aria-expanded="false" aria-label="Open navigation">Menu</button><div class="brand">VAYUMA <small style="display:block;font-size:9px;color:#7a8a80;letter-spacing:1px">ADMINISTRATION</small></div><nav><a class="va-dash-sidebar--nav-item" href="dashboard.php">Main website dashboard</a> &nbsp;·&nbsp; <a class="va-dash-sidebar--nav-item" href="auth/logout.php">Logout</a></nav><?php require __DIR__ . "/includes/header_user_menu.php"; ?></header><section class="hero"><h1>Administrator Dashboard</h1><p>Welcome, <?=ad_h($adminName)?>. Manage stakeholder trust and official government access across VAYUMA.</p></section><section class="stats"><div class="va-dash-stat"><strong><?=$counts['pending']?></strong><span>Pending verifications</span></div><div class="va-dash-stat"><strong><?=$counts['verified']?></strong><span>Verified profiles</span></div><div class="va-dash-stat"><strong><?=$counts['rejected']?></strong><span>Rejected profiles</span></div><div class="va-dash-stat"><strong><?=$counts['government']?></strong><span>Government officers awaiting review</span></div></section><section class="grid"><article class="card"><h2><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Government Officer Verification</h2><p>Review government officer organisations, registration details and contact information. Approve only after checking the submitted credentials.</p><a class="va-dash-sidebar--nav-item button" href="admin_verification.php?role=government_officer&status=pending">Review government officers</a></article><article class="card"><h2><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> All Stakeholder Verification</h2><p>Review and manage verification for other stakeholder categories, including extension, veterinary, finance, transport and partner organisations.</p><a class="va-dash-sidebar--nav-item button" href="admin_verification.php">Open verification centre</a></article><article class="card"><h2><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Bank Transfer Reviews</h2><p>Continue to the existing administration page to review submitted bank transfer proof and payment status.</p><a class="va-dash-sidebar--nav-item button" href="admin.php">Open payment and legacy admin centre</a></article><article class="card"><h2>📜 Verification Audit Trail</h2><p>Every verification decision made through the new dashboard is recorded with the administrator, previous status, new status and decision note.</p><span class="muted">Available after Phase 7 migration is installed.</span></article></section></main></div><script src="frontend/js/vayuma-ui.js" defer></script></body></html>
