// Disposable in-memory MariaDB test; pass VAYUMA_MARIADB_MODULE as an absolute
// path to lite4mariadb's dist/index.mjs. No network, credentials or data files.
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {fileURLToPath, pathToFileURL} from 'node:url';
import path from 'node:path';
const modulePath = process.env.VAYUMA_MARIADB_MODULE;
if (!modulePath || !path.isAbsolute(modulePath)) throw new Error('Set VAYUMA_MARIADB_MODULE to the installed lite4mariadb dist/index.mjs');
const {Lite4MariaDB} = await import(pathToFileURL(modulePath).href);
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const db = await Lite4MariaDB.create();
try {
  const schema = readFileSync(path.join(root, 'staging/sql/01_attached_schema_REFERENCE.sql'), 'utf8');
  const match = schema.match(/CREATE TABLE `users` \([\s\S]*?\) ENGINE=InnoDB[^;]*;/);
  assert.ok(match, 'users table must exist in reference schema');
  db.exec(match[0]);
  db.execMulti(readFileSync(path.join(root, 'staging/sql/03_avatar_column_MANUAL.sql'), 'utf8'));
  const cols = db.query("SHOW COLUMNS FROM users LIKE 'avatar_url'");
  assert.equal(cols.length, 1); assert.equal(cols[0].Type.toLowerCase(), 'varchar(255)'); assert.equal(cols[0].Null, 'YES');
  db.exec("INSERT INTO users (full_name,phone,avatar_url) VALUES ('Test User','0712345678','avatar_1.jpg')");
  assert.equal(db.query('SELECT avatar_url FROM users WHERE id=1')[0].avatar_url, 'avatar_1.jpg');
  console.log('PASS avatar migration and filename storage on disposable in-memory MariaDB');
} finally { await db.close(); }
