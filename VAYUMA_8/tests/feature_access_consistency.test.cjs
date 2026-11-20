const {test}=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),os=require('node:os'),path=require('node:path'),{spawnSync}=require('node:child_process');
const root=path.join(__dirname,'../vayuma'),php=process.env.VAYUMA_PHP||'php';
test('shared feature policy checks all 19 roles, profiles and subscriptions (mock database)',()=>{
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-access-'));try{
  fs.mkdirSync(path.join(dir,'includes'));fs.mkdirSync(path.join(dir,'lib'));
  for(const f of ['feature_access_policy.php','role_routes.php'])fs.copyFileSync(path.join(root,'includes',f),path.join(dir,'includes',f));
  fs.writeFileSync(path.join(dir,'lib/subscription_lib.php'),`<?php function ensure_user_subscription($c,$id,$r){return getenv('ACCESS')==='yes';} function subscription_is_accessible($v){return $v;}`);
  const roles=['farmer','buyer','consumer','transport_provider','researcher','extension_officer','cooperative','financial_institution','input_supplier','manufacturer','warehouse_provider','retailer','agribusiness','veterinary_officer','insurance_provider','government_officer','ngo_partner','agricultural_service_provider','strategic_partner'];
  for(const role of roles) for(const [profile,access,expected] of [['yes','yes','allow'],['no','yes','profile'],['yes','no','subscription']]){
   const code=`class R{function fetch_assoc(){return getenv('PROFILE')==='yes'?['user_id'=>7,'profile_completed'=>1]:null;}}class S{function bind_param($t,&...$a){}function execute(){return true;}function get_result(){return new R;}function close(){}}class D{function prepare($q){return new S;}} require ${JSON.stringify(path.join(dir,'includes/feature_access_policy.php'))}; echo vayuma_feature_access_decision(new D,['user_id'=>7,'logged_in'=>true,'role'=>${JSON.stringify(role)}]);`;
   const r=spawnSync(php,['-r',code],{encoding:'utf8',env:{...process.env,SUBSCRIPTION_ENFORCEMENT:'1',PROFILE:profile,ACCESS:access}});assert.equal(r.status,0,r.stderr);assert.equal(r.stdout,expected,role);
  }
  for(const f of ['feature_access.php','ai_advisor.php','ai_chat_api.php'])assert.match(fs.readFileSync(path.join(root,f),'utf8'),/vayuma_feature_access_decision\(/,f);
 }finally{fs.rmSync(dir,{recursive:true,force:true});}
});
test('strategic partner cards do not masquerade as unrelated exchange actions',()=>{const s=fs.readFileSync(path.join(root,'stakeholder_dashboard.php'),'utf8');assert.match(s,/Partnership activities[\s\S]*?Planned/);assert.match(s,/Impact reporting[\s\S]*?Planned/);assert.match(s,/href="stakeholder_directory.php"/);assert.doesNotMatch(s,/foreach \(\$cards/);});
test('direct AI page and API enforce the same policy (mock database)',()=>{
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-direct-ai-'));try{
  fs.cpSync(root,dir,{recursive:true});
  fs.writeFileSync(path.join(dir,'config/db.php'),`<?php class R{function fetch_assoc(){return getenv('PROFILE')==='yes'?['user_id'=>7,'profile_completed'=>1]:null;}}class S{function bind_param($t,&...$v){} function execute(){return true;}function get_result(){return new R;}function close(){}}class D{function prepare($q){return new S;}}$conn=new D;`);
  fs.writeFileSync(path.join(dir,'lib/subscription_lib.php'),`<?php function ensure_user_subscription($c,$id,$r){return getenv('ACCESS')==='yes';}function subscription_is_accessible($v){return $v;}`);
  let page=fs.readFileSync(path.join(dir,'ai_advisor.php'),'utf8').replace(/\bheader\(/g,'test_header(');fs.writeFileSync(path.join(dir,'ai_advisor.php'),page);
  const apiPath=path.join(dir,'ai_chat_api.php');fs.writeFileSync(apiPath,fs.readFileSync(apiPath,'utf8').replace("$raw = file_get_contents('php://input');","$raw = getenv('TEST_JSON');"));
  function exec(file,profile,access){const code=`function test_header($v){echo $v;} session_start();$_SESSION=['user_id'=>7,'logged_in'=>true,'role'=>'farmer','vayuma_csrf_token'=>'ok'];$_SERVER['REQUEST_METHOD']='POST';$_SERVER['HTTP_X_CSRF_TOKEN']='ok';require ${JSON.stringify(path.join(dir,file))};`;const r=spawnSync(php,['-d','display_errors=0','-r',code],{encoding:'utf8',env:{...process.env,PROFILE:profile,ACCESS:access,SUBSCRIPTION_ENFORCEMENT:'1',TEST_JSON:JSON.stringify({question:'soil'}),VAYUMA_AI_EXTERNAL_ENABLED:'0'}});assert.equal(r.status,0,r.stderr);return r.stdout;}
  assert.match(exec('ai_advisor.php','no','yes'),/Location: profile_setup.php/);assert.match(exec('ai_advisor.php','yes','no'),/Location: subscription.php/);
  assert.match(JSON.parse(exec('ai_chat_api.php','no','yes')).error,/profile and subscription/i);assert.match(JSON.parse(exec('ai_chat_api.php','yes','no')).error,/profile and subscription/i);
  assert.equal(JSON.parse(exec('ai_chat_api.php','yes','yes')).mode,'rule_based');
 }finally{fs.rmSync(dir,{recursive:true,force:true});}
});
