// Disposable MariaDB check only; not a hosted TiDB or mysqli integration test.
import {readFileSync} from 'node:fs';
import {pathToFileURL} from 'node:url';
import path from 'node:path';
import assert from 'node:assert/strict';
const mod=process.env.VAYUMA_MARIADB_MODULE;
if (!mod || !path.isAbsolute(mod)) throw new Error('Set VAYUMA_MARIADB_MODULE');
const {Lite4MariaDB}=await import(pathToFileURL(mod).href);
const db=await Lite4MariaDB.create();
try {
 db.execMulti(readFileSync(new URL('../staging/sql/01_attached_schema_REFERENCE.sql',import.meta.url),'utf8'));
 db.exec("INSERT INTO users (full_name,role) VALUES ('One','farmer'),('Two','farmer')");
 db.exec('START TRANSACTION');
 assert.equal(db.query('SELECT id FROM users WHERE id=1 FOR UPDATE').length,1);
 db.exec("INSERT INTO farmer_profiles (user_id,farm_name,county,sub_county,farm_location,farm_size,farm_size_unit,farming_experience,main_crops,soil_type,irrigation,secondary_crop) VALUES (1,'First','Nairobi','Westlands','Sample','2','acres','5','Maize','Loamy','Rain-fed','Beans')");
 db.exec('COMMIT');
 db.exec("INSERT INTO farmer_profiles (user_id,farm_name) VALUES (2,'Other')");
 db.exec('START TRANSACTION'); db.query('SELECT id FROM users WHERE id=1 FOR UPDATE');
 const rows=db.query('SELECT id FROM farmer_profiles WHERE user_id=1 ORDER BY id LIMIT 2'); assert.equal(rows.length,1);
 db.exec(`UPDATE farmer_profiles SET farm_name='Updated' WHERE id=${Number(rows[0].id)} AND user_id=1`); db.exec('COMMIT');
 assert.equal(db.query('SELECT farm_name FROM farmer_profiles WHERE user_id=1')[0].farm_name,'Updated');
 assert.equal(db.query('SELECT farm_name FROM farmer_profiles WHERE user_id=2')[0].farm_name,'Other');
 db.exec("INSERT INTO farmer_profiles (user_id,farm_name) VALUES (1,'Duplicate')");
 assert.equal(db.query('SELECT id FROM farmer_profiles WHERE user_id=1 ORDER BY id LIMIT 2').length,2);
 console.log('PASS farmer SQL insert, owner-scoped update and duplicate detection (disposable MariaDB)');
} finally {await db.close();}
