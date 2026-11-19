<?php
session_start();
require_once __DIR__ . '/includes/language.php';require_once __DIR__.'/includes/csrf.php';require_once __DIR__.'/config/db.php';if(!isset($_SESSION['user_id'])||($_SESSION['logged_in']??false)!==true){header('Location: login.php');exit;}if(strtolower(trim($_SESSION['role']??''))!=='farmer'){header('Location: dashboard.php');exit;}$userId=(int)$_SESSION['user_id'];$stmt=$conn->prepare("SELECT o.*,pl.produce_name,pl.location,u.full_name AS buyer_name,u.phone AS buyer_phone FROM orders o LEFT JOIN produce_listings pl ON o.produce_listing_id=pl.id LEFT JOIN users u ON o.buyer_id=u.id WHERE o.seller_id=? ORDER BY o.created_at DESC");$stmt->bind_param('i',$userId);$stmt->execute();$orders=$stmt->get_result();$sellerOrderNotice=(string)($_SESSION['seller_order_notice']??'');$sellerOrderNoticeType=(string)($_SESSION['seller_order_notice_type']??'error');unset($_SESSION['seller_order_notice'],$_SESSION['seller_order_notice_type']);?>
<!doctype html><html><head><?php require __DIR__ . '/includes/pwa.php'; ?><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Produce Requests | VAYUMA</title><link rel="stylesheet" href="frontend/css/tokens.css">
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
<?php vayuma_language_selector(); ?><header class="top"><div class="va-profile-brand">VAYUMA<small>SMART ECOSYSTEM</small></div><a class="va-profile-back" href="farmer_dashboard.php">← Dashboard</a></header><main class="container"><div class="eyebrow">FARM BUSINESS</div><h1 class="title"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Produce Requests</h1><p class="muted">Requests from buyers and other farmers interested in your produce.</p><?php if($sellerOrderNotice!==''):?><p class="<?= $sellerOrderNoticeType==='success'?'va-profile-welcome':'va-profile-error' ?>" role="<?= $sellerOrderNoticeType==='success'?'status':'alert' ?>"><?=htmlspecialchars($sellerOrderNotice,ENT_QUOTES,'UTF-8')?></p><?php endif;?><div class="va-profile-card"><div class="table-wrap"><?php if($orders->num_rows): ?><table class="orders"><thead><tr><th>Order</th><th>Produce</th><th>Buyer</th><th>Quantity</th><th>Total</th><th>Status</th><th>Action</th></tr></thead><tbody><?php while($o=$orders->fetch_assoc()): ?><tr><td>#<?= (int)$o['id'] ?></td><td><strong><?= htmlspecialchars($o['produce_name']??'Produce') ?></strong><br><?= htmlspecialchars($o['location']??'') ?></td><td><?= htmlspecialchars($o['buyer_name']??'Buyer') ?><br><small><?= htmlspecialchars($o['buyer_phone']??'') ?></small></td><td><?= htmlspecialchars($o['quantity']) ?> <?= htmlspecialchars($o['unit']) ?></td><td>KSh <?= number_format((float)$o['total_amount'],2) ?></td><td><span class="badge"><?= htmlspecialchars(ucfirst($o['status'])) ?></span></td><td><?php if($o['status']==='pending'): ?><form method="post" action="update_order_status.php"><?=vayuma_csrf_field()?><input class="va-profile-input" type="hidden" name="order_id" value="<?= (int)$o['id'] ?>"><button class="btn" name="status" value="accepted">Accept</button> <button class="btn danger" name="status" value="rejected">Reject</button></form><?php else:?>—<?php endif;?></td></tr><?php endwhile;?></tbody></table><?php else:?><div class="empty"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg><h3>No incoming requests</h3><p>When buyers or other farmers request your produce, they will appear here.</p></div><?php endif;?></div></div></main><script src="assets/js/vayuma-language.js" defer></script>
</body></html>