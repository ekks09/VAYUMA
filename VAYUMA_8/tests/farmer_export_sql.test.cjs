// SQLite checks query shape and record coverage; this is not TiDB integration.
const {test}=require('node:test'),assert=require('node:assert/strict');
const {spawnSync}=require('node:child_process'),path=require('node:path');
test('static report queries return registered and unclaimed farmers',()=>{
 const script=String.raw`
import re, sqlite3, sys
source=open(sys.argv[1]).read()
block=source.split('    $queries = [',1)[1].split("    $stage = 'prepare_report';",1)[0]
queries=re.findall(r'"(SELECT[\s\S]*?)"',block)
assert len(queries)==2
assert all(('AS '+chr(96)+'current_role'+chr(96)) in q for q in queries)
db=sqlite3.connect(':memory:')
db.row_factory=sqlite3.Row
db.executescript('''
CREATE TABLE users (id INTEGER, full_name TEXT, phone TEXT, email TEXT, created_at TEXT, role TEXT);
CREATE TABLE farmer_profiles (id INTEGER, user_id INTEGER, county TEXT, sub_county TEXT, farm_location TEXT, farmer_id_ciphertext TEXT, farm_name TEXT, main_crops TEXT, farm_size TEXT, farm_size_unit TEXT, farming_experience TEXT, soil_type TEXT, irrigation TEXT, secondary_crop TEXT);
CREATE TABLE onboarding_records (id INTEGER, full_name TEXT, phone_identity TEXT, county TEXT, farmer_location TEXT, farmer_id_ciphertext TEXT, activity TEXT, farm_size TEXT, farm_size_unit TEXT, preferred_language TEXT, created_at TEXT, onboarded_by_user_id INTEGER, claimed_by_user_id INTEGER, stakeholder_role TEXT);
INSERT INTO users(id,full_name,role) VALUES (1,'Registered','farmer'),(2,'Staff','extension_officer');
INSERT INTO farmer_profiles(id,user_id,county) VALUES (1,1,'Nairobi');
INSERT INTO onboarding_records(id,full_name,county,claimed_by_user_id,onboarded_by_user_id,stakeholder_role) VALUES (1,'Claimed','Kiambu',1,2,'farmer'),(2,'Unclaimed','Kiambu',NULL,2,'farmer'),(3,'Buyer','Kiambu',NULL,2,'buyer');
''')
registered=[dict(r) for r in db.execute(queries[0])]
unclaimed=[dict(r) for r in db.execute(queries[1])]
assert len(registered)==1 and registered[0]['full_name']=='Registered' and registered[0]['current_role']=='farmer' and registered[0]['onboarded_by']=='Staff'
assert len(unclaimed)==1 and unclaimed[0]['full_name']=='Unclaimed' and unclaimed[0]['current_role'] is None
`;
 const r=spawnSync('python3',['-c',script,path.join(__dirname,'../vayuma/farmer_export.php')],{encoding:'utf8'});
 assert.equal(r.status,0,r.stderr);
});
