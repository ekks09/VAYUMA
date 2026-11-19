<?php
session_start();
require_once __DIR__ . '/includes/language.php';require_once __DIR__.'/config/db.php';if(!isset($_SESSION['user_id'])||($_SESSION['logged_in']??false)!==true){header('Location: login.php');exit;}$role=strtolower(trim($_SESSION['role']??''));$dashboard=$role==='farmer'?'farmer_dashboard.php':'buyer_dashboard.php';$items=$conn->query("SELECT pl.id,pl.produce_name,pl.category,pl.quantity,pl.unit,pl.price_per_unit,pl.location,pl.image FROM produce_listings pl WHERE pl.status='available' ORDER BY pl.created_at DESC LIMIT 8");?>
<!doctype html><html><head><?php require __DIR__ . '/includes/pwa.php'; ?><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Recommended Produce | VAYUMA</title><link rel="stylesheet" href="frontend/css/tokens.css">
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
</style><style>.product-img{width:100%;height:170px;object-fit:cover;border-radius:14px;background:#edf4ed}.price{color:#218c45;font-weight:800}</style></head><body>
<?php vayuma_language_selector(); ?><header class="top"><div class="va-profile-brand">VAYUMA<small>SMART ECOSYSTEM</small></div><a class="va-profile-back" href="<?= $dashboard ?>">← Dashboard</a></header><main class="container"><div class="eyebrow">SMART SHOPPING</div><h1 class="title"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> Recommended Produce</h1><p class="muted">Fresh available listings from farmers.</p><div class="va-profile-form-grid"><?php while($p=$items->fetch_assoc()): ?><div class="tile"><?php if(!empty($p['image'])):?><img class="product-img" src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['produce_name']) ?>"><?php else:?><div class="product-img" style="display:grid;place-items:center;font-size:50px"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg></div><?php endif;?><p class="eyebrow" style="margin-top:15px"><?= htmlspecialchars($p['category']) ?></p><h3><?= htmlspecialchars($p['produce_name']) ?></h3><p class="muted"><?= htmlspecialchars($p['quantity']) ?> <?= htmlspecialchars($p['unit']) ?> · <span class="price">KSh <?= number_format((float)$p['price_per_unit'],2) ?>/<?= htmlspecialchars($p['unit']) ?></span></p><a href="produce_details.php?id=<?= (int)$p['id'] ?>">View produce →</a></div><?php endwhile;?></div></main><script src="assets/js/vayuma-language.js" defer></script>
</body></html>