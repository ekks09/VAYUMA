const {test} = require('node:test');
const assert = require('node:assert/strict');
const {spawnSync} = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const php = process.env.VAYUMA_PHP || 'php';
const health = path.resolve(__dirname, '../vayuma/health.php');
function run(file, query, extraEnv = {}) {
  const code = `$_GET['check'] = ${JSON.stringify(query)}; require ${JSON.stringify(file)};`;
  return spawnSync(php, ['-r', code], {encoding:'utf8', env:{...process.env, ...extraEnv}});
}
test('liveness does not require a database connection', () => {
  const r = run(health, '', {APP_ENV:'invalid'});
  assert.equal(r.status, 0, r.stderr);
  assert.deepEqual(JSON.parse(r.stdout), {status:'ok', database:'not_checked'});
  assert.doesNotMatch(r.stderr, /database connection or configuration failed/);
});
test('database readiness hides connection errors and credentials', () => {
  const r = run(health, 'db', {APP_ENV:'invalid', DB_PASSWORD:'synthetic-secret'});
  assert.equal(r.status, 0, r.stderr);
  assert.deepEqual(JSON.parse(r.stdout), {status:'unavailable', database:'unavailable'});
  assert.match(r.stderr, /Stage: configuration/);
  assert.doesNotMatch(r.stdout + r.stderr, /synthetic-secret/);
});
test('database readiness succeeds only after a query', () => {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'vayuma-health-test-'));
  try {
    fs.mkdirSync(path.join(dir, 'config'));
    fs.copyFileSync(health, path.join(dir, 'health.php'));
    fs.writeFileSync(path.join(dir, 'config/db.php'), `<?php
class FakeResult { function fetch_row() { return [1]; } function free() {} }
class FakeConnection { function query($sql) { return $sql === 'SELECT 1' ? new FakeResult() : false; } function close() {} }
$conn = new FakeConnection();
`);
    const r = run(path.join(dir, 'health.php'), 'db');
    assert.equal(r.status, 0, r.stderr);
    assert.deepEqual(JSON.parse(r.stdout), {status:'ok', database:'connected'});
  } finally { fs.rmSync(dir, {recursive:true, force:true}); }
});
