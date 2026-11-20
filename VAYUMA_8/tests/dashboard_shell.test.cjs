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
  assert.match(read('includes/profile_navigation.php'), /href="role\.php"/);
  assert.match(read('role.php'), /vayuma_dashboard_return_route\(\)/);
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
