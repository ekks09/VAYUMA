// Source-level regression checks only; they do not replace database or browser tests.
const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.join(__dirname, '../vayuma');
const read = name => fs.readFileSync(path.join(root, name), 'utf8');
test('trial claim is authenticated, CSRF protected and one-time', () => {
  const s = read('claim_trial.php');
  assert.match(s, /logged_in/); assert.match(s, /vayuma_csrf_valid\(\)/);
  assert.match(s, /has_used_trial=0/); assert.match(s, /INTERVAL 30 DAY/);
  assert.match(s, /p\.plan_type='paid'/);
});
test('payment stays disabled and members are not asked to send money', () => {
  assert.match(read('includes/payment_gate.php'), /return false/);
  assert.match(read('subscription.php'), /Please do not send payment/);
});
test('all dashboard templates render the shared banner', () => {
  for (const name of ['farmer','buyer','consumer','transport','cooperative','extension','finance','researcher','stakeholder'])
    assert.match(read(name + '_dashboard.php'), /render_subscription_banner\(/, name);
  assert.match(read('stakeholder_dashboard_core.php'), /render_subscription_banner\(/);
});
test('guard requires unexpired trial or paid periods, preserving free and approved negotiated plans', () => {
 const s = read('lib/subscription_lib.php');
 assert.match(s, /trial_ends_at.*> time\(\)/);
 assert.match(s, /expires_at.*> time\(\)/);
 assert.match(s, /negotiated_approved/);
 assert.match(s, /plan_type.*free/);
});
