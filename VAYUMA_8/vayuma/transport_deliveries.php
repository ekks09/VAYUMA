<?php
session_start();
require_once __DIR__ . '/includes/language.php';require_once __DIR__.'/includes/csrf.php';require_once __DIR__.'/config/db.php';if(!isset($_SESSION['user_id'])||($_SESSION['logged_in']??false)!==true){header('Location: login.php');exit;}if(strtolower(trim($_SESSION['role']??''))!=='transport_provider'){header('Location: dashboard.php');exit;}$uid=(int)$_SESSION['user_id'];$stmt=$conn->prepare("SELECT d.*,o.quantity,o.unit,o.total_amount,pl.produce_name,pl.location AS produce_location,u.full_name AS requester_name,u.phone AS requester_phone FROM delivery_requests d INNER JOIN orders o ON o.id=d.order_id LEFT JOIN produce_listings pl ON pl.id=o.produce_listing_id INNER JOIN users u ON u.id=d.requester_id WHERE d.status IN ('requested','accepted','in_transit') AND (d.transport_provider_id IS NULL OR d.transport_provider_id=?) ORDER BY d.created_at DESC");$stmt->bind_param('i',$uid);$stmt->execute();$rows=$stmt->get_result();$deliveryActionNotice=(string)($_SESSION['delivery_action_notice']??'');unset($_SESSION['delivery_action_notice']);
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?><!doctype html><html><head><?php require __DIR__ . '/includes/pwa.php'; ?><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Delivery Requests | VAYUMA TRIX</title><link rel="stylesheet" href="frontend/css/tokens.css">
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
</style></head><body>
<?php vayuma_language_selector(); ?><main class="va-form-page"><?php require __DIR__ . '/includes/workspace_back.php'; ?><header class="top"><strong style="color:#087d42;font-size:24px;letter-spacing:2px">VAYUMA TRIX</strong></header><section class="hero"><h1><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-transport"/></svg> Delivery Network</h1><p>Review available and assigned delivery requests and update their status. The buyer order page does not yet provide live tracking.</p></section><?php if($deliveryActionNotice!==''):?><p class="notice" role="status"><?=h($deliveryActionNotice)?></p><?php endif;?><?php if($rows->num_rows):?><section class="grid"><?php while($r=$rows->fetch_assoc()):?><article class="card"><span class="status"><?=h(ucwords(str_replace('_',' ',$r['status'])))?></span><h2><?=h($r['produce_name']??'Produce')?> · <?=h($r['quantity'])?> <?=h($r['unit'])?></h2><div class="meta"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Pickup: <?=h($r['pickup_location'])?><br><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Delivery: <?=h($r['delivery_location'])?><br><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-profile"/></svg> Requester: <?=h($r['requester_name'])?> · <?=h($r['requester_phone'])?><?php if($r['requested_date']):?><br><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Requested: <?=h($r['requested_date'])?><?php endif;?><?php if($r['notes']):?><br><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> <?=h($r['notes'])?><?php endif;?></div><div style="margin-top:15px"><?php if($r['status']==='requested'):?><form action="auth/update_delivery.php" method="post" style="display:inline"><?=vayuma_csrf_field()?><input class="va-form-input" type="hidden" name="delivery_id" value="<?=h($r['id'])?>"><button class="btn" name="status" value="accepted">Accept job</button></form><?php elseif($r['status']==='accepted'):?><form action="auth/update_delivery.php" method="post" style="display:inline"><?=vayuma_csrf_field()?><input class="va-form-input" type="hidden" name="delivery_id" value="<?=h($r['id'])?>"><button class="btn" name="status" value="in_transit">Start delivery</button></form><?php elseif($r['status']==='in_transit'):?><form action="auth/update_delivery.php" method="post" style="display:inline"><?=vayuma_csrf_field()?><input class="va-form-input" type="hidden" name="delivery_id" value="<?=h($r['id'])?>"><button class="btn" name="status" value="delivered">Mark delivered</button></form><?php endif;?></div></article><?php endwhile;?></section><?php else:?><div class="empty"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-transport"/></svg> No delivery requests are available right now.</div><?php endif;?></main><script src="assets/js/vayuma-language.js" defer></script>
</body></html>
