<?php
session_start();
require_once __DIR__ . '/includes/language.php';require_once __DIR__.'/config/db.php';require_once __DIR__.'/config/admin.php';require_once __DIR__.'/includes/csrf.php';
if(!isset($_SESSION['user_id'])||($_SESSION['logged_in']??false)!==true||!is_vayuma_admin((int)$_SESSION['user_id'])){http_response_code(403);die('VAYUMA admin access is not enabled for this account. Set VAYUMA_ADMIN_USER_IDS in your server environment for the designated administrator.');}
$profiles=$conn->query("SELECT sp.*,u.full_name,u.email FROM stakeholder_profiles sp INNER JOIN users u ON u.id=sp.user_id ORDER BY sp.verification_status='pending' DESC,sp.updated_at DESC");
$bankNotice=(string)($_SESSION['bank_admin_notice']??'');unset($_SESSION['bank_admin_notice']);
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>VAYUMA Admin</title><link rel="stylesheet" href="frontend/css/tokens.css">
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
</style></head><body>
<?php vayuma_language_selector(); ?><main class="page"><header class="top"><strong style="color:#087d42;font-size:24px">VAYUMA ADMIN</strong><a class="va-dash-sidebar--nav-item" href="admin_dashboard.php">Admin Dashboard</a> &nbsp; <a class="va-dash-sidebar--nav-item" href="admin_verification.php">Verification Dashboard</a> &nbsp; <a class="va-dash-sidebar--nav-item" href="dashboard.php">Website Dashboard</a></header><section class="hero"><h1>Verification Centre</h1><p>Review stakeholder profiles and update trust status. Use the enhanced verification dashboard for search, filtering and decision history.</p><p><a class="va-dash-sidebar--nav-item" href="admin_verification.php" style="display:inline-block;background:#fff;color:#087d42;padding:10px 14px;border-radius:8px;text-decoration:none;font-weight:800">Open enhanced verification dashboard →</a></p></section><section class="card"><table class="table"><tr><th>Organisation</th><th>Role</th><th>County</th><th>Registration</th><th>Status</th><th>Action</th></tr><?php while($p=$profiles->fetch_assoc()):?><tr><td><strong><?=h($p['organization_name'])?></strong><br><small><?=h($p['full_name'])?> · <?=h($p['email'])?></small></td><td><?=h(ucwords(str_replace('_',' ',$p['role'])))?></td><td><?=h($p['county'])?></td><td><?=h($p['registration_number']?:'—')?></td><td><span class="badge <?=$p['verification_status']==='verified'?'verified':''?>"><?=h(ucfirst($p['verification_status']))?></span></td><td><a class="va-dash-sidebar--nav-item" href="admin_verification.php?role=<?=urlencode($p['role'])?>&status=all" style="color:#087d42;font-weight:700">Review in secure dashboard →</a></td></tr><?php endwhile;?></table></section><section class="card" style="margin-top:25px"><h2>Bank Transfer Payments</h2><?php if($bankNotice!==''): ?><p role="status"><?=h($bankNotice)?></p><?php endif; ?><p>Review submitted bank transfers before marking orders as paid.</p><table class="table"><tr><th>Order</th><th>Buyer</th><th>Amount</th><th>Bank Reference</th><th>Proof</th><th>Status</th><th>Action</th></tr><?php $bankPayments=$conn->query("SELECT p.*,u.full_name FROM payments p INNER JOIN users u ON u.id=p.payer_id WHERE p.payment_method='bank' ORDER BY p.created_at DESC"); while($b=$bankPayments->fetch_assoc()): ?><tr><td>#<?=h($b['order_id'])?></td><td><?=h($b['full_name'])?></td><td>KSh <?=number_format((float)$b['amount'],2)?></td><td><?=h($b['bank_reference']?:'—')?></td><td><?php if(!empty($b['proof_path'])): ?><a class="va-dash-sidebar--nav-item" href="auth/bank_proof.php?id=<?= (int)$b['id'] ?>">View proof</a><?php else: ?>—<?php endif; ?></td><td><span class="badge <?=$b['status']==='paid'?'verified':''?>"><?=h(ucfirst($b['status']))?></span></td><td><?php if($b['status']==='pending'): ?><form action="auth/admin_bank_verify.php" method="post"><?=vayuma_csrf_field()?><input type="hidden" name="payment_id" value="<?=h($b['id'])?>"><button class="btn" name="status" value="paid">Approve</button> <button class="btn reject" name="status" value="failed">Reject</button></form><?php else: ?>—<?php endif; ?></td></tr><?php endwhile;?></table></section></section></main><script src="assets/js/vayuma-language.js" defer></script>
</body></html>
