const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.join(__dirname, '../vayuma');
const read = file => fs.readFileSync(path.join(root,file), 'utf8');

test('dashboard shell and category navigation use the shared components', () => {
  for (const role of ['farmer','buyer','consumer']) {
    const source = read(`${role}_dashboard.php`);
    assert.match(source, /dashboard_sidebar\.php/);
    assert.match(source, /va-simple-dashboard-grid/);
  }
  for (const role of ['transport','extension','researcher','finance','cooperative']) {
    const source = read(`${role}_dashboard.php`);
    assert.match(source, /dashboard-layout\.css/);
    assert.match(source, /va-dash-layout/);
    assert.match(source, /<aside[\s\S]*?vayuma_language_selector\(\)[\s\S]*?<\/aside>/);
  }
  for (const file of ['stakeholder_dashboard.php','stakeholder_dashboard_core.php','admin_dashboard.php']) {
    assert.match(read(file), /dashboard_generic_sidebar\.php/);
  }
  assert.match(read('includes/header_user_menu.php'), /href="role\.php"/);
  assert.match(read('includes/profile_navigation.php'), /header_user_menu\.php/);
  assert.match(read('role.php'), /workspace_back\.php/);
  assert.match(read('includes/workspace_back.php'), /vayuma_workspace_return_navigation/);
});

test('category change remains authenticated, CSRF protected and restricted during active paid access', () => {
  const save = read('auth/save_role.php');
  assert.match(save, /\$_SESSION\['logged_in'\]/);
  assert.match(save, /vayuma_csrf_valid\(\)/);
  assert.match(save, /status = 'active' AND expires_at > NOW\(\)/);
  assert.match(save, /Change your stakeholder type after it expires/);
  assert.match(read('auth/register_process.php'), /session_regenerate_id\(true\)/);
});

test('service worker allows the public dashboard stylesheet but not avatar or PHP caching', () => {
  const worker = read('sw.js');
  assert.match(worker, /assets\/css\/dashboard-layout\.css/);
  assert.doesNotMatch(worker, /\['avatar\.php'/);
  assert.match(worker, /request\.mode === 'navigate'[\s\S]*?fetch\(request\)/);
});

test('dashboard insights, buyer order layout and the existing icon sprite stay usable', () => {
  const farmer=read('farmer_dashboard.php'), buyer=read('buyer_dashboard.php'), consumer=read('consumer_dashboard.php');
  for (const href of ['climate.php','farming_score.php','crop_opportunities.php']) assert.match(farmer,new RegExp(`href="${href.replace('.','\\.')}"`));
  assert.match(farmer,/profile, not a field measurement/);
  for(const source of [buyer,consumer]) { assert.match(source,/dashboard-quick-links/);assert.match(source,/profile\.php\?return_to=/);assert.match(source,/feature_access\.php\?feature=ai/); }
  assert.match(buyer,/id="buyer-recent-orders"/);assert.match(buyer,/Online payment processing is paused/);assert.match(buyer,/Recent saved listings shown \(up to 6\)/);
  const sprite=read('frontend/images/icons.svg');assert.equal((sprite.match(/<\/svg>/g)||[]).length,1);
  const ids=[...sprite.matchAll(/<symbol id="([^"]+)"/g)].map(m=>m[1]);assert.equal(new Set(ids).size,ids.length);
  for(const id of ['va-icon-weather','va-icon-soil','va-icon-ai']) assert.ok(ids.includes(id));
  for(const tag of sprite.match(/<[^>]+>/g)) assert.ok((tag.match(/\sfill=/g)||[]).length<=1,'invalid repeated fill attribute');
});
