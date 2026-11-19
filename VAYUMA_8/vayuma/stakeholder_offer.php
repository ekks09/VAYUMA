<?php
session_start();
require_once __DIR__ . '/includes/language.php'; require_once __DIR__ . '/includes/csrf.php'; require_once __DIR__ . "/config/db.php";
if (!isset($_SESSION["user_id"]) || ($_SESSION["logged_in"] ?? false) !== true) { header("Location: login.php"); exit; }
$id = (int) ($_GET["id"] ?? 0); if ($id < 1) { header("Location: stakeholder_offers.php"); exit; }
$stmt = $conn->prepare("SELECT o.*, u.full_name, u.phone, sp.organization_name FROM stakeholder_offers o INNER JOIN users u ON u.id = o.provider_id LEFT JOIN stakeholder_profiles sp ON sp.user_id = o.provider_id WHERE o.id = ? AND o.status = 'active' LIMIT 1"); $stmt->bind_param("i", $id); $stmt->execute(); $offer = $stmt->get_result()->fetch_assoc(); $stmt->close(); if (!$offer) { header("Location: stakeholder_offers.php"); exit; }
$requester_id = (int) $_SESSION["user_id"]; $request_error = $_SESSION["request_error"] ?? ""; unset($_SESSION["request_error"]);
?><!doctype html><html lang="en"><head><?php require __DIR__ . '/includes/pwa.php'; ?><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= htmlspecialchars($offer["title"], ENT_QUOTES, "UTF-8") ?> | VAYUMA</title><link rel="stylesheet" href="frontend/css/tokens.css">
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
<?php vayuma_language_selector(); ?><main class="va-form-page"><a class="va-form-back" href="stakeholder_offers.php">← Back to offers</a><section class="va-form-card"><div class="tag"><?= htmlspecialchars(strtoupper($offer["offer_type"]), ENT_QUOTES, "UTF-8") ?></div><h1><?= htmlspecialchars($offer["title"], ENT_QUOTES, "UTF-8") ?></h1><div class="meta"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> <?= htmlspecialchars($offer["county"], ENT_QUOTES, "UTF-8") ?> · <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> <?= htmlspecialchars($offer["organization_name"] ?: $offer["full_name"], ENT_QUOTES, "UTF-8") ?><?= $offer["availability"] ? "<br>Availability: " . htmlspecialchars($offer["availability"], ENT_QUOTES, "UTF-8") : "" ?><?= $offer["price_note"] ? "<br>Terms: " . htmlspecialchars($offer["price_note"], ENT_QUOTES, "UTF-8") : "" ?></div><div class="description"><?= htmlspecialchars($offer["description"], ENT_QUOTES, "UTF-8") ?></div><?php if ((int) $offer["provider_id"] !== $requester_id): ?><div class="request"><h2>Send a request</h2><?php if ($request_error): ?><p class="error"><?= htmlspecialchars($request_error, ENT_QUOTES, "UTF-8") ?></p><?php endif; ?><form action="auth/save_stakeholder_request.php" method="POST"><?= vayuma_csrf_field() ?><input class="va-form-input" type="hidden" name="offer_id" value="<?= (int) $offer["id"] ?>"><label class="va-form-label-field">Your contact phone</label><input class="va-form-input" name="contact_phone" value="<?= htmlspecialchars($_SESSION["phone"] ?? "", ENT_QUOTES, "UTF-8") ?>"><label class="va-form-label-field">Message *</label><textarea class="va-form-input" name="message" required placeholder="Tell the provider what you need."></textarea><button class="button" type="submit">Send request →</button></form></div><?php else: ?><div class="request">This is your offer. <a href="stakeholder_requests.php">View incoming requests</a>.</div><?php endif; ?></section></main><script src="assets/js/vayuma-language.js" defer></script>
</body></html>
