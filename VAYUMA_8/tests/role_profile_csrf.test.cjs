const {test} = require('node:test');
const assert = require('node:assert/strict');
const {spawnSync} = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname,'../vayuma');
const php = process.env.VAYUMA_PHP || 'php';
const forms = {'profile_setup.php':'save_farmer.php','buyer_profile.php':'save_buyer.php','consumer_profile.php':'save_consumer.php','transport_profile.php':'save_transport.php','cooperative_profile.php':'save_cooperative.php','extension_profile.php':'save_extension.php','researcher_profile.php':'save_researcher.php','finance_profile.php':'save_finance.php','veterinary_officer_profile.php':'save_veterinary_profile.php','stakeholder_profile.php':'save_stakeholder_profile.php','stakeholder_profile_form.php':'save_stakeholder_profile.php'};
test('role profile forms include a CSRF field in their save form', () => {
  for (const [file, action] of Object.entries(forms)) {
    const text = fs.readFileSync(path.join(root,file),'utf8');
    assert.match(text, /require_once __DIR__\s*\.\s*['"]\/includes\/csrf\.php['"]/);
    const form = text.match(new RegExp(`<form\\b(?=[^>]*action="auth/${action.replace('.', '\\.')}")[^>]*>\\s*<\\?=\\s*vayuma_csrf_field\\(\\)\\s*\\?>`, 'i'));
    assert.ok(form, file);
  }
});
for (const [action, role] of [['save_buyer.php', 'buyer'], ['save_consumer.php', 'consumer']]) test(`${action} requires a fully authenticated session and the right role`, () => {
  for (const [logged, sessionRole] of [[false, role], [true, role === 'buyer' ? 'consumer' : 'buyer']]) {
    const code = `session_start(); $_SESSION=['user_id'=>7,'logged_in'=>${logged ? 'true' : 'false'},'role'=>${JSON.stringify(sessionRole)},'vayuma_csrf_token'=>'known']; $_SERVER['REQUEST_METHOD']='POST'; $_POST=['csrf_token'=>'known']; require ${JSON.stringify(path.join(root,'auth',action))};`;
    const r = spawnSync(php, ['-r',code], {encoding:'utf8'});
    assert.equal(r.status, 0, r.stderr);
    assert.doesNotMatch(r.stderr, /database connection or configuration failed/);
  }
});
for (const action of [...new Set([...Object.values(forms), 'save_profile.php'])]) test(`${action} rejects requests without a CSRF token before database access`, () => {
  const code = `$_SERVER['REQUEST_METHOD']='POST'; $_POST=[]; require ${JSON.stringify(path.join(root,'auth',action))};`;
  const r=spawnSync(php,['-r',code],{encoding:'utf8'});
  assert.equal(r.status,0,r.stderr);
  assert.match(r.stdout,/Your session expired/);
  assert.doesNotMatch(r.stderr,/database connection or configuration failed/);
});
