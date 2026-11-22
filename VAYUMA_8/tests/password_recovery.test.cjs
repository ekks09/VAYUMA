const {test}=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),os=require('node:os'),path=require('node:path'),{spawnSync}=require('node:child_process');
const root=path.resolve(__dirname,'../vayuma'),php=process.env.VAYUMA_PHP||'php';
function run(code,env={}){const r=spawnSync(php,['-d','display_errors=0','-r',code],{encoding:'utf8',env:{...process.env,...env}});assert.equal(r.status,0,r.stderr);return r.stdout;}
test('recovery configuration fails closed, and never trusts request Host',()=>{
 const helper=path.join(root,'includes/password_recovery.php'),base={VAYUMA_PASSWORD_RESET_ENABLED:'1',BREVO_API_KEY:'test-key-not-real',BREVO_SENDER_EMAIL:'sender@example.test',BREVO_SENDER_NAME:'Example Sender',BREVO_RESET_TEMPLATE_ID:'2',RENDER_EXTERNAL_URL:'https://instance.onrender.com'};
 const code=`require ${JSON.stringify(helper)}; $_SERVER['HTTP_HOST']='evil.invalid'; echo json_encode(vayuma_recovery_config());`;
 assert.equal(JSON.parse(run(code,base)),null,'must require auto-prepend guard');
 const guarded=`define('VAYUMA_SESSION_GUARD_ACTIVE',true); ${code}`;
 assert.equal(JSON.parse(run(guarded,{...base,VAYUMA_PASSWORD_RESET_ENABLED:'0'})),null);
 assert.equal(JSON.parse(run(guarded,{...base,BREVO_API_KEY:''})),null);
 assert.equal(JSON.parse(run(guarded,{...base,BREVO_RESET_TEMPLATE_ID:'0'})),null);
 assert.equal(JSON.parse(run(guarded,{...base,BREVO_RESET_TEMPLATE_ID:'2oops'})),null);
 assert.equal(JSON.parse(run(guarded,{...base,BREVO_SENDER_EMAIL:'',BREVO_FROM_EMAIL:''})).template_id,2);
 assert.equal(JSON.parse(run(guarded,base)).url,'https://instance.onrender.com');
 assert.equal(JSON.parse(run(guarded,{...base,RENDER_EXTERNAL_URL:'http://instance.onrender.com'})),null);
 assert.equal(JSON.parse(run(guarded,{...base,VAYUMA_PUBLIC_URL:'https://evil.invalid/?x=y'})),null);
});
test('Brevo payload uses the configured template and a token-bearing fragment',()=>{
 const d=fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-recovery-send-'));try{
  let src=fs.readFileSync(path.join(root,'includes/password_recovery.php'),'utf8').replace(/\bcurl_init\(/g,'mock_init(').replace(/\bcurl_setopt_array\(/g,'mock_opts(').replace(/\bcurl_exec\(/g,'mock_exec(').replace(/\bcurl_getinfo\(/g,'mock_info(').replace(/\bcurl_close\(/g,'mock_close(');let file=path.join(d,'helper.php');fs.writeFileSync(file,src);
  const code=`function mock_init($url){$GLOBALS['url']=$url;return true;}function mock_opts($c,$opts){$GLOBALS['data']=json_decode($opts[CURLOPT_POSTFIELDS],true);return true;}function mock_exec($c){return '{}';}function mock_info($c,$i){return 201;}function mock_close($c){} require ${JSON.stringify(file)}; $token=str_repeat('a',64); $ok=vayuma_recovery_send(['url'=>'https://app.example.test','sender'=>'sender@example.test','key'=>'fake-test-key','template_id'=>2],'user@example.test',$token);echo json_encode([$ok,$GLOBALS['url'],$GLOBALS['data']]);`;
  const [ok,url,body]=JSON.parse(run(code));assert.equal(ok,true);assert.equal(url,'https://api.brevo.com/v3/smtp/email');assert.equal(body.templateId,2);assert.equal(body.params.reset_url,'https://app.example.test/reset_password.php#token='+ 'a'.repeat(64));assert.deepEqual(body.to,[{email:'user@example.test'}]);assert.equal(body.htmlContent,undefined);assert.equal(body.sender,undefined);
 }finally{fs.rmSync(d,{recursive:true,force:true});}
});
test('session guard checks password fingerprints and rejects missing or outdated sessions',()=>{
 const file=path.join(root,'includes/auth_session_guard.php');const code=`require ${JSON.stringify(file)}; class R {function fetch_assoc(){return ['password'=>'stored-hash'];}} class S {function bind_param($t,&...$v){} function execute(){return true;}function get_result(){return new R;} function close(){}} class C {function prepare($q){return new S;}} $c=new C; echo json_encode([vayuma_session_matches($c,3,vayuma_session_fingerprint('stored-hash')),vayuma_session_matches($c,3,vayuma_session_fingerprint('old-hash')),vayuma_session_matches($c,3,null)]);`;
 assert.deepEqual(JSON.parse(run(code)),[true,false,false]);assert.match(fs.readFileSync(path.join(root,'auth/login.php'),'utf8'),/vayuma_auth_fingerprint/);
});
test('reset tokens, limits, transactions and deployment have explicit safeguards',()=>{
 const read=f=>fs.readFileSync(path.join(root,f),'utf8'),reset=read('reset_password.php'),forgot=read('forgot_password.php'),sql=fs.readFileSync(path.join(root,'../staging/sql/14_password_recovery_REVIEW.sql'),'utf8');
 assert.match(forgot,/random_bytes\(32\)/);assert.match(forgot,/hash\('sha256',\$token\)/);assert.match(forgot,/LIMIT 2/);assert.match(forgot,/If an eligible account exists/);assert.match(forgot,/vayuma_recovery_limit/);
 assert.match(reset,/FOR UPDATE/);assert.match(reset,/expires_at>UTC_TIMESTAMP\(\)/);assert.match(reset,/u\.email_identity=t\.email_identity/);assert.match(reset,/password_verify\(\$password/);assert.match(reset,/DELETE FROM password_reset_tokens/);assert.match(reset,/\$conn->commit\(\)/);assert.match(reset,/session_destroy\(\)/);assert.match(sql,/USE vayuma_db;/);assert.match(sql,/ON DELETE CASCADE/);
 assert.match(fs.readFileSync(path.join(root,'../ops/php-production.ini'),'utf8'),/auto_prepend_file.*auth_session_guard\.php/);
});
test('mocked recovery sends a one-use code, changes a password and rejects replay or changed email',()=>{
 const d=fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-recovery-flow-'));try{
  fs.cpSync(root,d,{recursive:true});
  const file=path.join(d,'includes/password_recovery.php');let helper=fs.readFileSync(file,'utf8').replace('return vayuma_recovery_brevo_send($config, $body);',"file_put_contents(getenv('RECOVERY_SENT'), json_encode($body)); return true;").replace('function vayuma_recovery_notice(array $config, string $email): bool {','function vayuma_recovery_notice(array $config, string $email): bool { return true;');fs.writeFileSync(file,helper);
  const forgot=path.join(d,'forgot_password.php');fs.writeFileSync(forgot,fs.readFileSync(forgot,'utf8').replace('if ($remaining > 0) usleep((int)($remaining * 1000000));',''));
  fs.writeFileSync(path.join(d,'config/db.php'),`<?php
function state(){return json_decode(file_get_contents(getenv('RECOVERY_STATE')),true);} function save($s){file_put_contents(getenv('RECOVERY_STATE'),json_encode($s));}
class R{private $rows;function __construct($rows){$this->rows=$rows;}function fetch_assoc(){return array_shift($this->rows)?:null;}}
class S{private $q,$v=[];public $affected_rows=1;function __construct($q){$this->q=$q;}function bind_param($t,&...$v){$this->v=&$v;}function execute(){ $s=state();if(strpos($this->q,'INSERT INTO password_reset_limits')===0){$k=$this->v[0].$this->v[1];$s['limits'][$k]=($s['limits'][$k]??0)+1;}
 elseif(strpos($this->q,'INSERT INTO password_reset_tokens')===0){$s['token']=['id'=>$this->v[0],'hash'=>$this->v[1],'email'=>$this->v[2]];}
 elseif(strpos($this->q,'UPDATE users SET password=')===0){$s['password']=$this->v[0];}
 elseif(strpos($this->q,'DELETE FROM password_reset_tokens')===0){if(($s['token']['hash']??'')===$this->v[1])$s['token']=null;else $this->affected_rows=0;}
 save($s);return true;}function get_result(){$s=state();if(strpos($this->q,'SELECT attempts FROM')===0)return new R([['attempts'=>$s['limits'][$this->v[0].$this->v[1]]??0]]);
 if(strpos($this->q,'SELECT id,email FROM users')===0)return new R($this->v[0]===$s['email']?[['id'=>7,'email'=>$s['email']]]:[]);
 if(strpos($this->q,'SELECT t.user_id')===0)return new R(($s['token']['hash']??'')===$this->v[0] && $s['email']===($s['token']['email']??'') && !$s['expired']?[['user_id'=>7,'email'=>$s['email']]]:[]);
 if(strpos($this->q,'SELECT password FROM users')===0)return new R([['password'=>$s['password']]]);return new R([]);}function close(){}}
class C{function prepare($q){return new S($q);}function query($q){return true;}function begin_transaction(){$GLOBALS['snapshot']=state();return true;}function commit(){return true;}function rollback(){save($GLOBALS['snapshot']);return true;}} $conn=new C;`);
  const state=path.join(d,'state.json'),sent=path.join(d,'mail.txt');fs.writeFileSync(state,JSON.stringify({email:'farmer@example.test',password:'old-hash',token:null,expired:false,limits:{}}));
  const env={VAYUMA_PASSWORD_RESET_ENABLED:'1',BREVO_API_KEY:'fake-key-not-real',BREVO_SENDER_EMAIL:'sender@example.test',BREVO_SENDER_NAME:'Example Sender',BREVO_RESET_TEMPLATE_ID:'2',RENDER_EXTERNAL_URL:'https://app.example.test',RECOVERY_STATE:state,RECOVERY_SENT:sent};
  function request(file,post){const code=`define('VAYUMA_SESSION_GUARD_ACTIVE',true);session_start();$_SESSION=['vayuma_csrf_token'=>'test-token'];$_SERVER['REQUEST_METHOD']='POST';$_SERVER['REMOTE_ADDR']='127.0.0.1';$_POST=json_decode(${JSON.stringify(JSON.stringify(post))},true);require ${JSON.stringify(path.join(d,file))};`;return run(code,env);}
  request('forgot_password.php',{csrf_token:'test-token',email:'farmer@example.test'});const payload=JSON.parse(fs.readFileSync(sent,'utf8'));assert.equal(payload.templateId,2);const link=new URL(payload.params.reset_url);assert.equal(link.origin,'https://app.example.test');assert.equal(link.pathname,'/reset_password.php');const input={value:''};let cleaned='';require('node:vm').runInNewContext(fs.readFileSync(path.join(root,'frontend/js/password-reset.js'),'utf8'),{window:{location:{hash:link.hash,pathname:link.pathname,search:link.search}},document:{getElementById(){return input;}},history:{replaceState(a,b,url){cleaned=url;}}});const token=input.value;assert.match(token,/^[a-f0-9]{64}$/);assert.equal(cleaned,'/reset_password.php');let st=JSON.parse(fs.readFileSync(state,'utf8'));assert.notEqual(st.token.hash,token);assert.equal(st.token.hash,require('node:crypto').createHash('sha256').update(token).digest('hex'));
  let out=request('reset_password.php',{csrf_token:'test-token',token,password:'new-password-1',confirm_password:'different'});assert.match(out,/Passwords do not match/);assert.equal(JSON.parse(fs.readFileSync(state,'utf8')).password,'old-hash');
  st=JSON.parse(fs.readFileSync(state,'utf8'));st.expired=true;fs.writeFileSync(state,JSON.stringify(st));out=request('reset_password.php',{csrf_token:'test-token',token,password:'new-password-1',confirm_password:'new-password-1'});assert.match(out,/invalid or has expired/);st=JSON.parse(fs.readFileSync(state,'utf8'));assert.equal(st.password,'old-hash');st.expired=false;fs.writeFileSync(state,JSON.stringify(st));
  st=JSON.parse(fs.readFileSync(state,'utf8'));st.email='changed@example.test';fs.writeFileSync(state,JSON.stringify(st));out=request('reset_password.php',{csrf_token:'test-token',token,password:'new-password-1',confirm_password:'new-password-1'});assert.match(out,/invalid or has expired/);st=JSON.parse(fs.readFileSync(state,'utf8'));assert.equal(st.password,'old-hash');st.email='farmer@example.test';fs.writeFileSync(state,JSON.stringify(st));
  request('reset_password.php',{csrf_token:'test-token',token,password:'new-password-1',confirm_password:'new-password-1'});st=JSON.parse(fs.readFileSync(state,'utf8'));assert.equal(st.token,null);assert.notEqual(st.password,'old-hash');
  const helperCheck=`echo password_verify('new-password-1',base64_decode('${Buffer.from(st.password).toString('base64')}'))?'yes':'no';`;assert.equal(run(helperCheck),'yes');out=request('reset_password.php',{csrf_token:'test-token',token,password:'new-password-2',confirm_password:'new-password-2'});assert.match(out,/invalid or has expired/);
 }finally{fs.rmSync(d,{recursive:true,force:true});}
});

test('reset fragment is put in the form and removed from browser history',()=>{
 const vm=require('node:vm'),script=fs.readFileSync(path.join(root,'frontend/js/password-reset.js'),'utf8'),input={value:''};let clean='';
 const ctx={window:{location:{hash:'#token='+ 'a'.repeat(64),pathname:'/reset_password.php',search:''}},document:{getElementById(id){return id==='resetToken'?input:null;}},history:{replaceState(a,b,url){clean=url;}}};
 vm.runInNewContext(script,ctx);assert.equal(input.value,'a'.repeat(64));assert.equal(clean,'/reset_password.php');
 input.value='';clean='';ctx.window.location.hash='#token=bad';vm.runInNewContext(script,ctx);assert.equal(input.value,'');assert.equal(clean,'');
});
