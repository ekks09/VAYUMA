// Isolated in-memory MariaDB only; never connects to an external database.
import assert from 'node:assert/strict'; import {readFileSync} from 'node:fs'; import path from 'node:path'; import {fileURLToPath,pathToFileURL} from 'node:url';
const mod=process.env.VAYUMA_MARIADB_MODULE; if (!mod || !path.isAbsolute(mod)) throw new Error('Set VAYUMA_MARIADB_MODULE');
const {Lite4MariaDB}=await import(pathToFileURL(mod).href); const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
const schema=readFileSync(path.join(root,'staging/sql/01_attached_schema_REFERENCE.sql'),'utf8').match(/CREATE TABLE `users` \([\s\S]*?\) ENGINE=InnoDB[^;]*;/)[0];
const audit=readFileSync(path.join(root,'staging/sql/04_identity_audit_READ_ONLY.sql'),'utf8'); const migration=readFileSync(path.join(root,'staging/sql/05_identity_unique_MANUAL.sql'),'utf8');
const db=await Lite4MariaDB.create();
try {
 db.exec(schema);
 db.exec("INSERT INTO users (full_name,phone,email) VALUES ('One','0712345678','Test@example.com'),('Two','+254 712-345-678',' test@EXAMPLE.com '),('Three',NULL,NULL),('Four','', '')");
 const auditResults=db.execMulti(audit); const collisions=auditResults.filter(x=>x.rows && x.rows.some(r=>Array.isArray(r) ? r.includes('1,2') : Object.values(r).includes('1,2')));
 assert.ok(collisions.length>=2,'audit must identify duplicate email and phone groups');
 assert.throws(()=>db.execMulti(migration),/duplicate/i,'DDL must refuse dirty data');
 // Reset only the disposable fixture after the intentionally failed migration.
 db.exec('DROP TABLE users'); db.exec(schema);
 db.exec("INSERT INTO users (full_name,phone,email) VALUES ('One','0712345678','Test@example.com'),('Three',NULL,NULL),('Four','', '')");
 db.execMulti(migration);
 assert.throws(()=>db.exec("INSERT INTO users (phone,email) VALUES ('+254 712-345-678','other@example.com')"),/duplicate/i);
 assert.throws(()=>db.exec("INSERT INTO users (phone,email) VALUES ('0799999999',' test@EXAMPLE.com ')"),/duplicate/i);
 assert.throws(()=>db.exec("UPDATE users SET phone='+254712345678' WHERE full_name='Three'"),/duplicate/i);
 assert.throws(()=>db.exec("UPDATE users SET email=' TEST@example.COM ' WHERE full_name='Three'"),/duplicate/i);
 db.exec("INSERT INTO users (phone,email) VALUES (NULL,NULL)");
 db.exec("INSERT INTO users (phone,email) VALUES ('',' ')");
 assert.equal(db.query('SELECT COUNT(*) AS n FROM users')[0].n,5);
 console.log('PASS identity audit, dirty-data refusal, formatting-equivalent uniqueness and nullable values in disposable MariaDB');
} finally {await db.close();}
