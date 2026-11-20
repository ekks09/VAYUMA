// Disposable MariaDB compatibility check; this does not test TiDB or PHP integration.
import {readFileSync} from 'node:fs';
import {pathToFileURL} from 'node:url';
import assert from 'node:assert/strict';
import path from 'node:path';
const modulePath = process.env.VAYUMA_MARIADB_MODULE;
if (!modulePath || !path.isAbsolute(modulePath)) throw new Error('Set VAYUMA_MARIADB_MODULE');
const {Lite4MariaDB} = await import(pathToFileURL(modulePath).href);
const db = await Lite4MariaDB.create();
try {
  const sql = new URL('../staging/sql/', import.meta.url);
  db.execMulti(readFileSync(new URL('01_attached_schema_REFERENCE.sql', sql), 'utf8'));
  db.execMulti(readFileSync(new URL('09_avatar_storage_REVIEW.sql', sql), 'utf8'));
  db.exec("INSERT INTO users (full_name, role) VALUES ('Sample', 'farmer')");
  db.exec("INSERT INTO user_avatars (user_id, jpeg_bytes) VALUES (1, 'sample') ON DUPLICATE KEY UPDATE jpeg_bytes=VALUES(jpeg_bytes)");
  db.exec("INSERT INTO user_avatars (user_id, jpeg_bytes) VALUES (1, 'updated') ON DUPLICATE KEY UPDATE jpeg_bytes=VALUES(jpeg_bytes)");
  assert.equal(Number(db.query('SELECT OCTET_LENGTH(jpeg_bytes) AS n FROM user_avatars WHERE user_id=1')[0].n), 7);
  db.exec('START TRANSACTION');
  db.exec("UPDATE user_avatars SET jpeg_bytes='rolled back' WHERE user_id=1");
  db.exec('ROLLBACK');
  assert.equal(Number(db.query('SELECT OCTET_LENGTH(jpeg_bytes) AS n FROM user_avatars WHERE user_id=1')[0].n), 7);
  db.exec('DELETE FROM users WHERE id=1');
  assert.equal(Number(db.query('SELECT COUNT(*) AS n FROM user_avatars')[0].n), 0);
  console.log('PASS avatar schema and cascade (disposable MariaDB only)');
} finally { await db.close(); }
