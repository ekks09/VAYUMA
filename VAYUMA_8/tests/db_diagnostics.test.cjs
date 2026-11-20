const {test} = require('node:test');
const assert = require('node:assert/strict');
const {spawnSync} = require('node:child_process');
const path = require('node:path');
const php = process.env.VAYUMA_PHP || 'php';
const root = path.resolve(__dirname, '../vayuma');
function run(code, env) {
  return spawnSync(php, ['-r', code], {encoding: 'utf8', env: {...process.env, ...env}});
}
const base = {DB_HOST:'synthetic-host', DB_PORT:'4000', DB_NAME:'', DB_USER:'synthetic-user', DB_PASSWORD:'synthetic-secret', DB_SSL_CA:'/synthetic/ca', DB_DIAGNOSTICS:'1'};
test('opt-in staging failure logs presence, not values, and response stays generic', () => {
  const r = run(`$_GET['check']='db'; require ${JSON.stringify(path.join(root,'health.php'))};`, {...base, APP_ENV:'staging'});
  assert.equal(r.status, 0, r.stderr);
  assert.deepEqual(JSON.parse(r.stdout), {status:'unavailable', database:'unavailable'});
  assert.match(r.stderr, /Stage: configuration/);
  assert.match(r.stderr, /DB_HOST=set DB_PORT=set DB_NAME=unset DB_USER=set DB_PASSWORD=set DB_SSL_CA=set/);
  assert.match(r.stderr, /CA_source=custom CA_readable=no/);
  assert.doesNotMatch(r.stdout + r.stderr, /synthetic-host|synthetic-user|synthetic-secret|\/synthetic\/ca/);
});
test('diagnostics are disabled outside staging and without opt-in', () => {
  for (const env of [{...base, APP_ENV:'production'}, {...base, APP_ENV:'staging', DB_DIAGNOSTICS:''}]) {
    const r = run(`$_GET['check']='db'; require ${JSON.stringify(path.join(root,'health.php'))};`, env);
    assert.equal(r.status, 0, r.stderr);
    assert.doesNotMatch(r.stderr, /staging DB environment|driver error/);
  }
});
test('connection error codes identify likely failure stages without driver text', () => {
  const helper = path.join(root, 'config/db_diagnostics.php');
  const r = run(`require ${JSON.stringify(helper)}; echo json_encode(array_map('vayuma_db_connection_stage', [1045,2026,2003,9999]));`, base);
  assert.equal(r.status, 0, r.stderr);
  assert.deepEqual(JSON.parse(r.stdout), ['authentication_or_database_access','tls_handshake','tcp_or_connection','connection_or_handshake']);
});
test('driver diagnostics exclude driver messages and credentials', () => {
  const helper = path.join(root, 'config/db_diagnostics.php');
  const code = `require ${JSON.stringify(helper)}; vayuma_db_log_driver_error(1045, '28000', "Access denied for synthetic-user on synthetic-host using synthetic-secret at /synthetic/ca\\nnext line");`;
  const r = run(code, {...base, APP_ENV:'staging'});
  assert.equal(r.status, 0, r.stderr);
  assert.match(r.stderr, /code=1045 SQLSTATE=28000/);
  assert.doesNotMatch(r.stderr, /Access denied|synthetic-user|synthetic-host|synthetic-secret|\/synthetic\/ca/);
  assert.equal(r.stderr.trim().split('\n').length, 1);
});
