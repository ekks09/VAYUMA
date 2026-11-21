const {test} = require('node:test');
const assert = require('node:assert/strict');
const {spawnSync} = require('node:child_process');
const path = require('node:path');
const fs = require('node:fs');
const php = process.env.VAYUMA_PHP || 'php';
const root = path.resolve(__dirname, '../vayuma');
test('verified TLS flags fail closed outside audited PHP 8.3 mysqlnd and include explicit peer verification', () => {
  const code = `require ${JSON.stringify(path.join(root,'config/db_tls.php'))};
  $out=[]; foreach ([['mysqlnd 8.3.20',80320,true],['libmysql 8.3',80320,true],['mysqlnd 8.4',80400,true],['mysqlnd 8.3',80320,false]] as $case) {
      try {$flags=vayuma_verified_mysqlnd_flags(...$case);$out[]=['ssl'=>(bool)($flags & MYSQLI_CLIENT_SSL),'verify'=>(bool)($flags & (1<<30)),'dont_verify'=>(bool)($flags & MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT)];}
      catch (Throwable $e) {$out[]='refused';}
  } echo json_encode($out);`;
  const r=spawnSync(php,['-r',code],{encoding:'utf8'}); assert.equal(r.status,0,r.stderr);
  assert.deepEqual(JSON.parse(r.stdout),[{ssl:true,verify:true,dont_verify:false},'refused','refused','refused']);
});
test('DB bootstrap uses CA plus audited mysqlnd verification flags, not the rejected mysqli option', () => {
  const db=fs.readFileSync(path.join(root,'config/db.php'),'utf8');
  assert.match(db,/is_file\(\$ca\)\s*\|\|\s*!is_readable\(\$ca\)/);
  assert.match(db,/vayuma_verified_mysqlnd_flags\(mysqli_get_client_info\(\), PHP_VERSION_ID, extension_loaded\('openssl'\)\)/);
  assert.match(db,/ssl_set\(null, null, \$ca, null, null\)/);
  assert.match(db,/real_connect\(\$host, \$dbConnectionUser, \$dbConnectionPassword, \$database, \(int\)\$portText, null, \$flags\)/);
  assert.doesNotMatch(db,/options\(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT/);
});
