<?php
session_start();
require_once __DIR__ . '/includes/language.php'; require_once __DIR__ . '/includes/csrf.php'; require_once __DIR__ . "/config/db.php";
if (!isset($_SESSION["user_id"]) || ($_SESSION["logged_in"] ?? false) !== true) { header("Location: login.php"); exit; }
$user_id = (int) $_SESSION["user_id"]; $request_success = $_SESSION["request_success"] ?? ""; $request_error = $_SESSION["request_error"] ?? ""; unset($_SESSION["request_success"], $_SESSION["request_error"]);
$incoming_stmt = $conn->prepare("SELECT r.id, r.message, r.contact_phone, r.status, r.created_at, o.title, u.full_name, u.email FROM stakeholder_offer_requests r INNER JOIN stakeholder_offers o ON o.id = r.offer_id INNER JOIN users u ON u.id = r.requester_id WHERE o.provider_id = ? ORDER BY r.created_at DESC"); $incoming_stmt->bind_param("i", $user_id); $incoming_stmt->execute(); $incoming = $incoming_stmt->get_result(); $incoming_stmt->close();
$outgoing_stmt = $conn->prepare("SELECT r.status, r.created_at, o.title, o.offer_type, u.full_name, sp.organization_name FROM stakeholder_offer_requests r INNER JOIN stakeholder_offers o ON o.id = r.offer_id INNER JOIN users u ON u.id = o.provider_id LEFT JOIN stakeholder_profiles sp ON sp.user_id = o.provider_id WHERE r.requester_id = ? ORDER BY r.created_at DESC"); $outgoing_stmt->bind_param("i", $user_id); $outgoing_stmt->execute(); $outgoing = $outgoing_stmt->get_result(); $outgoing_stmt->close();
function request_status(string $status): string { return ucfirst($status); }
?><!doctype html><html lang="en"><head><?php require __DIR__ . '/includes/pwa.php'; ?><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Requests | VAYUMA</title><link rel="stylesheet" href="frontend/css/tokens.css">
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
<?php vayuma_language_selector(); ?><main class="va-form-page"><header class="top"><div class="va-form-brand">VAYUMA<small>SMART ECOSYSTEM</small></div><nav><a href="dashboard.php">Dashboard</a><a href="stakeholder_offers.php">Exchange</a><a href="auth/logout.php">Logout</a></nav></header><section class="hero"><h1>Requests & connections</h1><p>Manage incoming interest in your offers and track the opportunities you have requested.</p></section><?php if ($request_success): ?><div class="notice"><?= htmlspecialchars($request_success, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?><?php if ($request_error): ?><div class="notice" style="background:#fff0ef;color:#9a332b"><?= htmlspecialchars($request_error, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?><section class="section"><h2>Incoming requests</h2><?php if ($incoming->num_rows): ?><?php while ($item = $incoming->fetch_assoc()): ?><article class="request"><span class="status"><?= htmlspecialchars(request_status($item["status"]), ENT_QUOTES, "UTF-8") ?></span><h3><?= htmlspecialchars($item["title"], ENT_QUOTES, "UTF-8") ?></h3><p><strong><?= htmlspecialchars($item["full_name"], ENT_QUOTES, "UTF-8") ?></strong> — <?= htmlspecialchars($item["message"], ENT_QUOTES, "UTF-8") ?></p><div class="meta">Contact: <?= htmlspecialchars($item["contact_phone"] ?: $item["email"], ENT_QUOTES, "UTF-8") ?> · <?= htmlspecialchars($item["created_at"], ENT_QUOTES, "UTF-8") ?></div><?php if ($item["status"] === "pending"): ?><div class="actions"><form action="auth/update_stakeholder_request.php" method="POST"><?= vayuma_csrf_field() ?><input class="va-form-input" type="hidden" name="request_id" value="<?= (int) $item["id"] ?>"><input class="va-form-input" type="hidden" name="status" value="accepted"><button class="btn">Accept</button></form><form action="auth/update_stakeholder_request.php" method="POST"><?= vayuma_csrf_field() ?><input class="va-form-input" type="hidden" name="request_id" value="<?= (int) $item["id"] ?>"><input class="va-form-input" type="hidden" name="status" value="declined"><button class="btn decline">Decline</button></form></div><?php elseif ($item["status"] === "accepted"): ?><form class="actions" action="auth/update_stakeholder_request.php" method="POST"><?= vayuma_csrf_field() ?><input class="va-form-input" type="hidden" name="request_id" value="<?= (int) $item["id"] ?>"><input class="va-form-input" type="hidden" name="status" value="completed"><button class="btn">Mark completed</button></form><?php endif; ?></article><?php endwhile; ?><?php else: ?><div class="empty">No incoming requests yet. Publish an offer in the VAYUMA Exchange to start connecting.</div><?php endif; ?></section><section class="section"><h2>Requests you sent</h2><?php if ($outgoing->num_rows): ?><?php while ($item = $outgoing->fetch_assoc()): ?><article class="request"><span class="status"><?= htmlspecialchars(request_status($item["status"]), ENT_QUOTES, "UTF-8") ?></span><h3><?= htmlspecialchars($item["title"], ENT_QUOTES, "UTF-8") ?></h3><p><?= htmlspecialchars($item["organization_name"] ?: $item["full_name"], ENT_QUOTES, "UTF-8") ?> · <?= htmlspecialchars($item["offer_type"], ENT_QUOTES, "UTF-8") ?></p><div class="meta"><?= htmlspecialchars($item["created_at"], ENT_QUOTES, "UTF-8") ?></div><?php if ($item["status"] === "pending"): ?><form class="actions" action="auth/cancel_stakeholder_request.php" method="POST" onsubmit="return confirm('Withdraw this request?')"><?= vayuma_csrf_field() ?><input class="va-form-input" type="hidden" name="request_id" value="<?= (int)$item["id"] ?>"><button class="btn decline">Withdraw request</button></form><?php endif; ?></article><?php endwhile; ?><?php else: ?><div class="empty">You have not sent any requests. Browse the VAYUMA Exchange to find opportunities.</div><?php endif; ?></section></main><script src="assets/js/vayuma-language.js" defer></script>
</body></html>
