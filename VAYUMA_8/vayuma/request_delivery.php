<?php
session_start();
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';
if(!isset($_SESSION['user_id'])||($_SESSION['logged_in']??false)!==true){header('Location: login.php');exit;}
$uid=(int)$_SESSION['user_id'];$orderId=(int)($_GET['order_id']??0);if($orderId<1){$_SESSION['delivery_error']='Choose a valid order for delivery.';header('Location: my_orders.php');exit;}
$stmt=$conn->prepare("SELECT o.*,pl.produce_name,pl.location FROM orders o LEFT JOIN produce_listings pl ON pl.id=o.produce_listing_id WHERE o.id=? AND o.buyer_id=? AND o.status IN ('accepted','paid') LIMIT 1");$stmt->bind_param('ii',$orderId,$uid);$stmt->execute();$order=$stmt->get_result()->fetch_assoc();$stmt->close();if(!$order){$_SESSION['delivery_error']='This order was not found or is not ready for delivery.';header('Location: my_orders.php');exit;}
$stmt=$conn->prepare('SELECT * FROM delivery_requests WHERE order_id=? LIMIT 1');$stmt->bind_param('i',$orderId);$stmt->execute();$delivery=$stmt->get_result()->fetch_assoc();$stmt->close();
$deliveryNotice=(string)($_SESSION['delivery_notice']??'');$deliveryError=(string)($_SESSION['delivery_error']??'');unset($_SESSION['delivery_notice'],$_SESSION['delivery_error']);
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?><!doctype html><html lang="<?=h($current_language)?>"><head><?php require __DIR__ . '/includes/pwa.php'; ?><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Request Delivery | VAYUMA</title><link rel="stylesheet" href="frontend/css/tokens.css">
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
<?php vayuma_language_selector(); ?><main class="va-form-page"><header class="top"><strong style="color:#087d42;font-size:24px">VAYUMA</strong><a href="my_orders.php">← My Orders</a></header><section class="va-form-card"><h1><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-transport"/></svg> Request delivery</h1><p><?=h($order['produce_name']??'Produce')?> · <?=h($order['quantity'])?> <?=h($order['unit'])?></p><?php if($deliveryError!==''):?><div class="va-form-error" role="alert"><?=h($deliveryError)?></div><?php endif;?><?php if($deliveryNotice!==''):?><div class="notice" role="status"><?=h($deliveryNotice)?></div><?php endif;?><?php if($delivery):?><div class="notice">Delivery status: <strong><?=h(ucwords(str_replace('_',' ',$delivery['status'])))?></strong><?php if($delivery['transport_provider_id']):?> · Transport provider assigned<?php endif;?></div><?php else:?><form action="auth/create_delivery_request.php" method="post"><?=vayuma_csrf_field()?><input class="va-form-input" type="hidden" name="order_id" value="<?=h($orderId)?>"><div class="field"><label class="va-form-label-field" for="pickup-location">Pickup location *</label><input id="pickup-location" class="va-form-input" name="pickup_location" required maxlength="255" value="<?=h($order['location']??'')?>"></div><div class="field"><label class="va-form-label-field" for="delivery-location">Delivery location *</label><input id="delivery-location" class="va-form-input" name="delivery_location" required maxlength="255" placeholder="Town, estate, market or address"></div><div class="field"><label class="va-form-label-field" for="requested-date">Requested date</label><input id="requested-date" class="va-form-input" type="date" name="requested_date"></div><div class="field"><label class="va-form-label-field" for="delivery-notes">Notes</label><textarea id="delivery-notes" class="va-form-input" name="notes" maxlength="5000" placeholder="Fragile produce, loading instructions, preferred vehicle, etc."></textarea></div><button class="btn" type="submit">Send delivery request →</button></form><?php endif;?></section></main><script src="assets/js/vayuma-language.js" defer></script>
</body></html>
