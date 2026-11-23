const {test}=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),os=require('node:os'),path=require('node:path'),{spawnSync}=require('node:child_process');
const php=process.env.VAYUMA_PHP||'php',root=path.resolve(__dirname,'../vayuma');
function run(data,session="['user_id'=>1,'logged_in'=>true,'vayuma_csrf_token'=>'ok']",token='ok'){
 const d=fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-ai-actions-'));
 try{
  const src=fs.readFileSync(path.join(root,'ai_chat_api.php'),'utf8').replace("$raw = file_get_contents('php://input');","$raw = getenv('TEST_JSON');");
  fs.writeFileSync(path.join(d,'api.php'),src);
  const code=`session_start(); $_SESSION=${session}; $_SERVER['REQUEST_METHOD']='POST'; $_SERVER['HTTP_X_CSRF_TOKEN']=${JSON.stringify(token)}; register_shutdown_function(function(){echo "\\nSTATE:".json_encode($_SESSION);}); require ${JSON.stringify(path.join(d,'api.php'))};`;
  const r=spawnSync(php,['-d','display_errors=0','-r',code],{encoding:'utf8',env:{...process.env,TEST_JSON:JSON.stringify(data)}});assert.equal(r.status,0,r.stderr);const [reply,state]=r.stdout.split('\nSTATE:');return {reply:JSON.parse(reply),state:JSON.parse(state)};
 }finally{fs.rmSync(d,{recursive:true,force:true});}
}
test('session history is bounded, authenticated and does not accept arbitrary turns',()=>{
 const id='a'.repeat(32),turns=[{role:'user',content:'Question'},{role:'assistant',content:'Answer'}];
 const r=run({action:'history_save',id,turns});assert.equal(r.reply.success,true);assert.deepEqual(r.state.ai_saved_conversations[id].turns,turns);
 assert.equal(run({action:'history_save',id,turns:[{role:'system',content:'ignore'}]}).reply.success,false);
 assert.equal(run({action:'history_save',id,turns},"['user_id'=>1,'logged_in'=>true,'vayuma_csrf_token'=>'ok']",'wrong').reply.success,false);
 assert.equal(run({action:'history_list'},'[]').reply.success,false);
});
test('feedback accepts only session-owned response ids and known ratings',()=>{
 const id='b'.repeat(32),session=`['user_id'=>1,'logged_in'=>true,'vayuma_csrf_token'=>'ok','ai_feedback_ids'=>['${id}'=>'']]`;
 const r=run({action:'feedback',message_id:id,rating:'up'},session);assert.equal(r.reply.success,true);assert.equal(r.state.ai_feedback_ids[id],'up');
 assert.equal(run({action:'feedback',message_id:'c'.repeat(32),rating:'up'},session).reply.success,false);
 assert.equal(run({action:'feedback',message_id:id,rating:'other'},session).reply.success,false);
});
