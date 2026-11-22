<?php
session_start();
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . "/config/db.php";
if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit; }

$role = strtolower(trim($_SESSION["role"] ?? ""));
$roles = [
    "input_supplier" => ["Input Supplier", "va-icon-crops", "Products or inputs you supply", "Bring reliable inputs closer to farmers."],
    "manufacturer" => ["Manufacturer", "va-icon-tag", "Products manufactured or raw materials required", "Turn agricultural potential into valuable products."],
    "warehouse_provider" => ["Warehouse Provider", "va-icon-warehouse", "Storage capacity and services offered", "Help produce stay safe, available and market-ready."],
    "retailer" => ["Retailer", "va-icon-marketplace", "Products you retail", "Connect quality agricultural products with customers."],
    "agribusiness" => ["Agribusiness / Trader", "va-icon-crops", "Produce categories and trading focus", "Grow reliable agricultural supply chains."],
    "veterinary_officer" => ["Veterinary Officer", "va-icon-veterinary", "Veterinary services and livestock specialisation", "Improve animal health for stronger farm livelihoods."],
    "insurance_provider" => ["Insurance Provider", "va-icon-checkmark", "Insurance products and agricultural coverage", "Protect agricultural livelihoods from risk."],
    "government_officer" => ["Government Officer", "va-icon-checkmark", "Department, programme, or public service area", "Strengthen agricultural services and public programmes."],
    "ngo_partner" => ["NGO / Development Partner", "va-icon-ngo", "Programmes, grants, or development focus", "Create measurable impact across agricultural communities."],
    "agricultural_service_provider" => ["Agricultural Service Provider", "va-icon-agricultural-services", "Services such as machinery, irrigation, spraying, or soil testing", "Make dependable farm services easy to find and book."],
    "strategic_partner" => ["Strategic Partner", "va-icon-cooperative", "Your organisation and partnership focus", "Collaborate with VAYUMA through a tailored strategic partnership."],
];
if (!isset($roles[$role])) { header("Location: role.php"); exit; }
[$role_name, $icon, $focus_label, $tagline] = $roles[$role];
$full_name = $_SESSION["full_name"] ?? ""; $phone = $_SESSION["phone"] ?? ""; $email = $_SESSION["email"] ?? "";
$profile_error = $_SESSION["profile_error"] ?? ""; unset($_SESSION["profile_error"]);
$existing = [];
$stmt = $conn->prepare("SELECT organization_name, organization_type, county, location, contact_phone, service_focus, description FROM stakeholder_profiles WHERE user_id = ? LIMIT 1");
if ($stmt) { $user_id = (int) $_SESSION["user_id"]; $stmt->bind_param("i", $user_id); $stmt->execute(); $existing = $stmt->get_result()->fetch_assoc() ?: []; $stmt->close(); }
function profile_value(array $profile, string $field, string $fallback = ""): string { return htmlspecialchars($profile[$field] ?? $fallback, ENT_QUOTES, "UTF-8"); }
?>
<!doctype html>
<html lang="en"><head><?php require __DIR__ . '/includes/pwa.php'; ?><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= htmlspecialchars($role_name, ENT_QUOTES, "UTF-8") ?> Profile | VAYUMA TRIX</title>
<link rel="stylesheet" href="frontend/css/tokens.css?v=20261110">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css?v=20261110">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
<link rel="stylesheet" href="frontend/css/profile_forms.css?v=20261120-1">
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
</style></head><body class="va-profile-document">
<?php require __DIR__ . '/includes/profile_navigation.php'; ?><main class="va-form-page">
<header class="topbar"><div class="va-form-brand">VAYUMA TRIX<small>AGRITECH INNOVATIONS KENYA</small></div></header>
<section class="hero"><div class="role-icon"><svg class="va-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg#<?= htmlspecialchars($icon, ENT_QUOTES, "UTF-8") ?>"></use></svg></div><div><div class="eyebrow">STEP 3 OF 3 · PROFILE SETUP</div><h1><?= htmlspecialchars($role_name, ENT_QUOTES, "UTF-8") ?></h1><p><?= htmlspecialchars($tagline, ENT_QUOTES, "UTF-8") ?></p></div></section>
<div class="layout"><aside class="steps"><h2>Your VAYUMA journey</h2><div class="step"><div class="number"><svg class="va-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg#va-icon-checkmark"></use></svg></div><div><strong>Create account</strong><span>Your VAYUMA account is ready.</span></div></div><div class="step"><div class="number"><svg class="va-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg#va-icon-checkmark"></use></svg></div><div><strong>Choose role</strong><span><?= htmlspecialchars($role_name, ENT_QUOTES, "UTF-8") ?></span></div></div><div class="step active"><div class="number">3</div><div><strong>Complete profile</strong><span>Tell partners how to connect with you.</span></div></div><div class="privacy">Your details support trusted VAYUMA connections.</div></aside>
<section class="form-card"><h2>Build your professional presence</h2><p>A complete profile helps the right farmers, buyers and partners discover your services.</p><?php if ($profile_error !== ""): ?><div class="va-form-error"><?= htmlspecialchars($profile_error, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>
<form action="auth/save_stakeholder_profile.php" method="POST"><?= vayuma_csrf_field() ?><div class="section"><div class="va-form-heading"><i aria-hidden="true"><svg class="va-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg#va-icon-profile"></use></svg></i><div><h3>Account contact</h3><span>Details linked to your VAYUMA account.</span></div></div><div class="va-form-grid"><div class="field"><label class="va-form-label-field">Full name</label><input class="va-form-input" value="<?= htmlspecialchars($full_name, ENT_QUOTES, "UTF-8") ?>" readonly></div><div class="field"><label class="va-form-label-field">Email address</label><input class="va-form-input" value="<?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?>" readonly></div></div></div>
<div class="section"><div class="va-form-heading"><i aria-hidden="true"><svg class="va-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg#va-icon-checkmark"></use></svg></i><div><h3>Organisation details</h3><span>These details appear on your VAYUMA profile.</span></div></div><div class="va-form-grid"><div class="field"><label class="va-form-label-field">Organisation / business name <b class="required">*</b></label><input class="va-form-input" name="organization_name" required placeholder="Enter name" value="<?= profile_value($existing,"organization_name") ?>"></div><div class="field"><label class="va-form-label-field">Organisation type</label><input class="va-form-input" name="organization_type" placeholder="e.g. Company, NGO, County Department" value="<?= profile_value($existing,"organization_type") ?>"></div><div class="field"><label class="va-form-label-field">County <b class="required">*</b></label><input class="va-form-input" name="county" required placeholder="e.g. Nakuru" value="<?= profile_value($existing,"county") ?>"></div><div class="field"><label class="va-form-label-field">Town / location</label><input class="va-form-input" name="location" placeholder="e.g. Nakuru Town" value="<?= profile_value($existing,"location") ?>"></div><div class="field full"><label class="va-form-label-field">Contact phone</label><input class="va-form-input" name="contact_phone" value="<?= profile_value($existing,"contact_phone",$phone) ?>"><span class="va-form-hint">Use a number partners can reach for business or service requests.</span></div></div></div>
<div class="section"><div class="va-form-heading"><i aria-hidden="true"><svg class="va-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg#<?= htmlspecialchars($icon, ENT_QUOTES, "UTF-8") ?>"></use></svg></i><div><h3>Your role on VAYUMA</h3><span>Help the ecosystem understand what you offer.</span></div></div><div class="va-form-grid"><div class="field full"><label class="va-form-label-field"><?= htmlspecialchars($focus_label, ENT_QUOTES, "UTF-8") ?></label><input class="va-form-input" name="service_focus" placeholder="Briefly describe your focus" value="<?= profile_value($existing,"service_focus") ?>"></div><div class="field full"><label class="va-form-label-field">About your organisation or service</label><textarea class="va-form-input" name="description" placeholder="Describe how you participate in the VAYUMA ecosystem."><?= profile_value($existing,"description") ?></textarea></div></div></div>
<div class="actions"><small>Fields marked <b class="required">*</b> are required. You can update these details later from your dashboard.</small><button class="submit" type="submit">Save profile & enter dashboard &rarr;</button></div></form></section></div></main><script src="assets/js/vayuma-language.js" defer></script>
</body></html>
