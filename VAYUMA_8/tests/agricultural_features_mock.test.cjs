// Disposable copies and in-memory mocks only. No TiDB or external AI requests.
const {test}=require('node:test'), assert=require('node:assert/strict');
const fs=require('node:fs'), os=require('node:os'), path=require('node:path'), {spawnSync}=require('node:child_process');
const root=path.join(__dirname,'../vayuma'), php=process.env.VAYUMA_PHP||'php';
function prepare(){const dir=fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-agri-'));fs.cpSync(root,dir,{recursive:true});return dir;}
function run(code,env={}){return spawnSync(php,['-d','display_errors=0','-r',code],{encoding:'utf8',env:{...process.env,...env}});}
test('AI requires CSRF, defaults to free local guidance and keeps external service gated',()=>{
 const dir=prepare(); try {
  const file=path.join(dir,'ai_chat_api.php'); let s=fs.readFileSync(file,'utf8'); s=s.replace("$raw = file_get_contents('php://input');", "$raw = getenv('TEST_JSON');"); fs.writeFileSync(file,s);
  function ask(payload,csrf='ok',session=true){ const code=`session_start(); $_SESSION=${session?"['user_id'=>1,'logged_in'=>true,'vayuma_csrf_token'=>'ok']":"[]"}; $_SERVER['REQUEST_METHOD']='POST'; $_SERVER['HTTP_X_CSRF_TOKEN']=${JSON.stringify(csrf)}; require ${JSON.stringify(file)};`;const out=run(code,{TEST_JSON:JSON.stringify(payload),VAYUMA_AI_EXTERNAL_ENABLED:'0',OPENAI_API_KEY:''});assert.equal(out.status,0,out.stderr);return JSON.parse(out.stdout); }
  assert.match(ask({question:'soil'},'wrong').error,/session expired/i);
  assert.match(ask({question:'soil'},'ok',false).error,/log in/i);
  const local=ask({question:'soil',crop:'maize'});assert.equal(local.mode,'rule_based');assert.match(local.answer,/not AI/);assert.match(local.context_used.join(' '),/maize.*user supplied/);
  assert.match(ask({question:'soil',external_consent:true}).error,/not enabled/i);
  assert.match(ask({question:' '}).error,/Enter a question/);
 } finally {fs.rmSync(dir,{recursive:true,force:true});}
});
test('soil history scopes queries and saves to session user with CSRF and validates units',()=>{
 const dir=prepare();try{
 fs.writeFileSync(path.join(dir,'config/db.php'),`<?php
class Result {function free(){} function fetch_assoc(){return null;}} class Stmt {private $sql; function __construct($sql){$this->sql=$sql;} function bind_param($types,&...$args){$GLOBALS['bound_id']=$args[0];} function execute(){return true;} function get_result(){return new Result();} function close(){} } class DB {function query($sql){return new Result();} function prepare($sql){return new Stmt($sql);} } $conn=new DB();`);
 const file=path.join(dir,'soil_records.php');
 function submit(post,method='POST'){const data=JSON.stringify(post); const code=`session_start(); $_SESSION=['user_id'=>42,'logged_in'=>true,'vayuma_csrf_token'=>'ok']; $_SERVER['REQUEST_METHOD']='${method}'; $_POST=json_decode(${JSON.stringify(data)},true); register_shutdown_function(function(){echo "\\nMOCK_BOUND=".($GLOBALS['bound_id']??'none');}); require ${JSON.stringify(file)};`;const out=run(code);assert.equal(out.status,0,out.stderr);return out.stdout;}
 const base={csrf_token:'ok',sample_date:'2025-01-01',location:'Nakuru',depth_cm:'20',laboratory:'Lab',method:'Method',nitrogen:'5',nitrogen_unit:'mg/kg'};
 assert.match(submit(base),/MOCK_BOUND=42/); assert.match(submit({...base,nitrogen_unit:''}),/at least one result with its unit/); assert.match(submit({...base,csrf_token:'wrong'}),/session expired/i); assert.match(submit({},'GET'),/MOCK_BOUND=42/);
 }finally{fs.rmSync(dir,{recursive:true,force:true});}
});
