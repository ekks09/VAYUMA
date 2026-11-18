<?php
session_start();
require_once __DIR__ . '/includes/language.php'; require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . "/config/db.php";
if (!isset($_SESSION["user_id"]) || ($_SESSION["logged_in"] ?? false) !== true) { header("Location: login.php"); exit; }
$user_id = (int) $_SESSION["user_id"]; $view = $_GET["view"] ?? "browse"; $view = $view === "manage" ? "manage" : "browse";
$offer_error = $_SESSION["offer_error"] ?? ""; $offer_success = $_SESSION["offer_success"] ?? ""; unset($_SESSION["offer_error"], $_SESSION["offer_success"]);
$county = trim($_GET["county"] ?? ""); $county = substr($county, 0, 100); $provider_filter = max(0, (int)($_GET["provider"] ?? 0));
if ($view === "manage") {
    $stmt = $conn->prepare("SELECT id, title, offer_type, county, price_note, availability, status, created_at FROM stakeholder_offers WHERE provider_id = ? ORDER BY created_at DESC"); $stmt->bind_param("i", $user_id); $stmt->execute(); $offers = $stmt->get_result(); $stmt->close();
} else {
    $sql = "SELECT o.id, o.title, o.offer_type, o.description, o.county, o.price_note, o.availability, o.created_at, u.full_name, sp.organization_name FROM stakeholder_offers o INNER JOIN users u ON u.id = o.provider_id LEFT JOIN stakeholder_profiles sp ON sp.user_id = o.provider_id WHERE o.status = 'active'";
    if ($county !== "") { $sql .= " AND o.county = ?"; }
    if ($provider_filter > 0) { $sql .= " AND o.provider_id = ?"; }
    $sql .= " ORDER BY o.created_at DESC";
    $stmt = $conn->prepare($sql); if ($county !== "" && $provider_filter > 0) { $stmt->bind_param("si", $county, $provider_filter); } elseif ($county !== "") { $stmt->bind_param("s", $county); } elseif ($provider_filter > 0) { $stmt->bind_param("i", $provider_filter); } $stmt->execute(); $offers = $stmt->get_result(); $stmt->close();
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>VAYUMA Exchange</title><link rel="stylesheet" href="frontend/css/tokens.css">
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
<?php vayuma_language_selector(); ?><main class="va-form-page"><header class="top"><div class="va-form-brand">VAYUMA<small>SMART ECOSYSTEM</small></div><nav class="nav"><a href="dashboard.php">Dashboard</a><a href="stakeholder_requests.php">Requests</a><a href="auth/logout.php">Logout</a></nav></header><section class="hero"><h1><?= $view === "manage" ? "Manage your offers" : ($provider_filter > 0 ? "Published offers from this stakeholder" : "VAYUMA Exchange") ?></h1><p><?= $view === "manage" ? "Publish inputs, services, programmes, storage or buying opportunities for the VAYUMA ecosystem." : "Discover agricultural offers, services and opportunities from verified VAYUMA stakeholders." ?></p></section><nav class="tabs"><a class="<?= $view === "browse" ? "active" : "" ?>" href="stakeholder_offers.php">Browse offers</a><a class="<?= $view === "manage" ? "active" : "" ?>" href="stakeholder_offers.php?view=manage">My offers</a></nav><?php if ($offer_success): ?><div class="notice success"><?= htmlspecialchars($offer_success, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?><?php if ($offer_error): ?><div class="notice error"><?= htmlspecialchars($offer_error, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>
<?php if ($view === "manage"): ?><section class="form"><h2>Publish an offer</h2><form action="auth/save_stakeholder_offer.php" method="POST"><?= vayuma_csrf_field() ?><div class="formgrid"><div class="field"><label class="va-form-label-field">Offer title *</label><input class="va-form-input" name="title" required placeholder="e.g. Certified maize seed available"></div><div class="field"><label class="va-form-label-field">Offer type *</label><input class="va-form-input" name="offer_type" required placeholder="e.g. Input supply, storage, programme"></div><div class="field"><label class="va-form-label-field">County *</label><input class="va-form-input" name="county" required placeholder="e.g. Nakuru"></div><div class="field"><label class="va-form-label-field">Price / terms</label><input class="va-form-input" name="price_note" placeholder="e.g. KSh 3,500 per bag or Contact us"></div><div class="field full"><label class="va-form-label-field">Availability</label><input class="va-form-input" name="availability" placeholder="e.g. Available now, Monday–Friday, 200 tonnes"></div><div class="field full"><label class="va-form-label-field">Description *</label><textarea class="va-form-input" name="description" required placeholder="Describe the offer, requirements and how VAYUMA members can benefit."></textarea></div></div><p><button class="button" type="submit">Publish offer →</button></p></form></section><h2>Your published offers</h2><?php if ($offers->num_rows): ?><table><thead><tr><th>Offer</th><th>County</th><th>Terms</th><th>Status</th><th>Manage</th></tr></thead><tbody><?php while ($offer = $offers->fetch_assoc()): ?><tr><td><strong><?= htmlspecialchars($offer["title"], ENT_QUOTES, "UTF-8") ?></strong><br><small><?= htmlspecialchars($offer["offer_type"], ENT_QUOTES, "UTF-8") ?></small></td><td><?= htmlspecialchars($offer["county"], ENT_QUOTES, "UTF-8") ?></td><td><?= htmlspecialchars($offer["price_note"] ?: "—", ENT_QUOTES, "UTF-8") ?></td><td><?= htmlspecialchars(ucfirst($offer["status"]), ENT_QUOTES, "UTF-8") ?></td><td><form action="auth/update_stakeholder_offer.php" method="POST" style="display:flex;gap:6px;flex-wrap:wrap"><?= vayuma_csrf_field() ?><input class="va-form-input" type="hidden" name="offer_id" value="<?= (int)$offer["id"] ?>"><select class="va-form-input" name="status" aria-label="Offer status"><option value="active" <?=$offer["status"]==="active"?"selected":""?>>Active</option><option value="paused" <?=$offer["status"]==="paused"?"selected":""?>>Paused</option><option value="closed" <?=$offer["status"]==="closed"?"selected":""?>>Closed</option></select><button class="button" type="submit">Save</button></form></td></tr><?php endwhile; ?></tbody></table><?php else: ?><div class="empty">No offers published yet. Add your first one above.</div><?php endif; ?>
<?php else: ?><section class="grid"><?php if ($offers->num_rows): ?><?php while ($offer = $offers->fetch_assoc()): ?><article class="offer"><div class="tag"><?= htmlspecialchars(strtoupper($offer["offer_type"]), ENT_QUOTES, "UTF-8") ?></div><h2><?= htmlspecialchars($offer["title"], ENT_QUOTES, "UTF-8") ?></h2><p><?= htmlspecialchars(mb_strimwidth($offer["description"], 0, 165, "…"), ENT_QUOTES, "UTF-8") ?></p><div class="meta"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> <?= htmlspecialchars($offer["county"], ENT_QUOTES, "UTF-8") ?><br><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> <?= htmlspecialchars($offer["organization_name"] ?: $offer["full_name"], ENT_QUOTES, "UTF-8") ?><?= $offer["price_note"] ? "<br>💬 " . htmlspecialchars($offer["price_note"], ENT_QUOTES, "UTF-8") : "" ?></div><a class="button" href="stakeholder_offer.php?id=<?= (int) $offer["id"] ?>">View & request →</a></article><?php endwhile; ?><?php else: ?><div class="empty">No active offers yet. Be the first to publish an opportunity.</div><?php endif; ?></section><?php endif; ?></main><script src="assets/js/vayuma-language.js" defer></script>
</body></html>
