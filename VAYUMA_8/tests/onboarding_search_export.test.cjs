const {test}=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path'),os=require('node:os'),{spawnSync}=require('node:child_process');
const root=path.resolve(__dirname,'../vayuma'),php=process.env.VAYUMA_PHP||'php';
test('multi-term name, location and normalized phone search',()=>{
 const helper=path.join(root,'includes/onboarding_search.php');const code=`require ${JSON.stringify(helper)}; $r=['full_name'=>'Mary Wanjiku','phone'=>'0712345678','county'=>'Kiambu','location'=>'Githunguri','activity'=>'Maize']; foreach (['Mary +254 712 345 678','WANJIKU maize 0712','mary kiambu','mary 999','xyz'] as $q) echo (vayuma_onboarding_matches($r,vayuma_onboarding_search_tokens($q))?'1':'0');`;
 const r=spawnSync(php,['-r',code],{encoding:'utf8'});assert.equal(r.status,0,r.stderr);assert.equal(r.stdout,'11100');
});
test('CSV export enforces staff and CSRF, filters, clears buffers and escapes spreadsheet formulas',()=>{
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-export-test-'));try{
 fs.mkdirSync(path.join(dir,'includes'));fs.mkdirSync(path.join(dir,'config'));
 for(const f of ['csrf.php','onboarding_search.php'])fs.copyFileSync(path.join(root,'includes',f),path.join(dir,'includes',f));
 fs.writeFileSync(path.join(dir,'includes/farmer_identity.php'),"<?php function vayuma_farmer_id_decrypt($s){return $s ?: '';}");
 fs.copyFileSync(path.join(root,'farmer_export.php'),path.join(dir,'farmer_export.php'));
 fs.writeFileSync(path.join(dir,'config/db.php'),`<?php class R {public $i=0;function fetch_assoc(){if($this->i++) return null;return ['record_type'=>'Staff record not claimed','account_id'=>null,'full_name'=>'=Harm','phone'=>'0712345678','email'=>null,'created_at'=>null,'county'=>'Kiambu','sub_county'=>null,'location'=>'Village','id_cipher'=>'0012345','farm_name'=>null,'activity'=>'Maize','farm_size'=>1,'farm_size_unit'=>'acres','preferred_language'=>'en','onboarded_at'=>null,'onboarded_by'=>null,'farming_experience'=>null,'soil_type'=>null,'irrigation'=>null,'secondary_crop'=>null,'current_role'=>null];}}class C {function query($q){return new R;}} $conn=new C;`);
 function run(query='',session=true,csrf='ok'){const code=`session_start();$_SESSION=['user_id'=>7,'logged_in'=>true,${session?"'onboarding_unlocked_user_id'=>7,":""}'onboarding_key_fingerprint'=>hash('sha256','key'),'vayuma_csrf_token'=>'ok'];$_SERVER['REQUEST_METHOD']='POST';$_POST=['csrf_token'=>${JSON.stringify(csrf)},'search_query'=>${JSON.stringify(query)}];ob_start();echo 'stray output';require ${JSON.stringify(path.join(dir,'farmer_export.php'))};`;return spawnSync(php,['-r',code],{encoding:'utf8',env:{...process.env,VAYUMA_STAFF_ONBOARD_KEY:'key'}});}
 let r=run('Kiambu 0712');assert.equal(r.status,0,r.stderr);assert.ok(r.stdout.startsWith('\ufeff'));assert.doesNotMatch(r.stdout,/stray output/);assert.match(r.stdout,/'=Harm/);assert.match(r.stdout,/'0712345678/);assert.match(r.stdout,/'0012345/);
 r=run('no-match');assert.match(r.stdout,/Full name/);assert.doesNotMatch(r.stdout,/Harm/);
 r=run('',false);assert.match(r.stdout,/Unlock Staff Access/);assert.doesNotMatch(r.stdout,/=Harm/);
 r=run('',true,'bad');assert.match(r.stdout,/could not be verified/);assert.doesNotMatch(r.stdout,/=Harm/);
 }finally{fs.rmSync(dir,{recursive:true,force:true});}
});
