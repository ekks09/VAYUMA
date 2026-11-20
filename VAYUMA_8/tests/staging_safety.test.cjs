const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {spawnSync} = require('node:child_process');
const root = path.join(__dirname, '..', 'vayuma');
const php = process.env.VAYUMA_PHP || 'php';
const routes = ['mpesa_stkpush.php','mpesa_stkush.php','subscription_stkpush.php','mpesa_callback.php','paystack_initialize.php','paystack_verify.php','paystack_webhook.php','paystack_subscription_return.php','paystack_subscription_webhook.php'];
for (const route of routes) test(`${route} remains locked even with enable flag`, () => {
  const r=spawnSync(php, [path.join(root, route)], {encoding:'utf8', env:{...process.env, MPESA_SANDBOX_ENABLED:'1', PAYMENTS_ENABLED:'1'}});
  assert.equal(r.status,0,r.stderr); assert.match(r.stdout,/Payment processing is temporarily unavailable/); assert.doesNotMatch(r.stdout,/VAYUMA is temporarily unavailable/);
});
test('saved-item parent IDs use signed integers matching the supplied schema', () => {
  const s=fs.readFileSync(path.join(root,'database/migrations/saved_items.sql'),'utf8');
  assert.match(s,/user_id INT NOT NULL/); assert.match(s,/produce_listing_id INT NOT NULL/);
});
test('AI files have no output before PHP', () => {
  for (const f of ['ai_advisor.php','ai_chat_api.php']) assert.ok(fs.readFileSync(path.join(root,f),'utf8').startsWith('<?php'));
});
test('receipt pages request private no-store caching', () => {
  for (const f of ['mpesa_receipt.php','subscription_receipt.php']) assert.match(fs.readFileSync(path.join(root,f),'utf8'),/Cache-Control: private, no-store/);
});
test('database configuration failures log only a safe stage, not credentials', () => {
  const r=spawnSync(php, [path.join(root, 'config/db.php')], {
    encoding:'utf8', env:{...process.env, APP_ENV:'invalid', DB_USER:'synthetic-user', DB_PASSWORD:'synthetic-secret'}
  });
  assert.equal(r.status, 0, r.stderr);
  const output = r.stdout + r.stderr;
  assert.match(output, /Stage: configuration/);
  assert.match(output, /VAYUMA is temporarily unavailable/);
  assert.doesNotMatch(output, /synthetic-user|synthetic-secret/);
});
test('Render Blueprint targets the free staging Docker service', () => {
  const blueprint = fs.readFileSync(path.join(__dirname, '..', '..', 'render.yaml'), 'utf8');
  assert.match(blueprint, /^\s+plan:\s*free\s*$/m);
  assert.match(blueprint, /^\s+dockerfilePath:\s*\.\/VAYUMA_8\/Dockerfile\s*$/m);
  assert.match(blueprint, /^\s+dockerContext:\s*\.\/VAYUMA_8\s*$/m);
  assert.match(blueprint, /^\s+healthCheckPath:\s*\/health\.php\s*$/m);
  assert.match(blueprint, /key:\s*APP_ENV\s*\n\s*value:\s*staging/);
});
