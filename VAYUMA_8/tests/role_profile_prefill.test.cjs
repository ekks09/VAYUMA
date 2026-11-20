const {test}=require('node:test');const assert=require('node:assert/strict');const fs=require('node:fs');const os=require('node:os');const path=require('node:path');const {spawnSync}=require('node:child_process');
const php=process.env.VAYUMA_PHP||'php',root=path.resolve(__dirname,'../vayuma');
test('cooperative, researcher and finance forms escape existing values and their save handlers create and owner-scope edits',()=>{
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-role-profile-'));
 try{fs.cpSync(root,dir,{recursive:true});fs.writeFileSync(path.join(dir,'config/db.php'),`<?php
class R {private $rows;public $num_rows; function __construct($r){$this->rows=$r;$this->num_rows=count($r);}function fetch_assoc(){return array_shift($this->rows)?:null;}}
class S {private $sql;private $args=[];function __construct($s){$this->sql=$s;}function bind_param($t,&...$a){$this->args=&$a;}function execute(){if(strpos(ltrim($this->sql),'UPDATE ')===0||strpos(ltrim($this->sql),'INSERT ')===0){if(getenv('MODE')==='fail')throw new RuntimeException('secret raw driver text');$GLOBALS['write']=['sql'=>$this->sql,'args'=>array_values($this->args)];}return true;}function get_result(){if(getenv('MODE')==='new')return new R([]);$r=json_decode(getenv('PROFILE'),true);return new R([$r]);}function close(){}}
class D {function prepare($s){return new S($s);}}$conn=new D();`);
 const cases=[
 ['researcher','researcher','researcher',{institution:'<Institution>',research_area:'Area',qualification:'Degree',location:'Town',bio:'<Bio>'}],
 ['cooperative','cooperative','cooperative',{cooperative_name:'<Coop>',registration_number:'R',cooperative_type:'Custom <type>',location:'Town',member_count:'7',contact_person:'Person',description:'<Description>'}],
 ['finance','financial_institution','finance',{institution_name:'<Finance>',institution_type:'Custom <type>',registration_number:'R',location:'Town',contact_person:'Person',services:'Services',description:'<Description>'}]
 ];
 for(const [form,role,save,fields] of cases){
  const profile={id:9,...fields};const base={...process.env,PROFILE:JSON.stringify(profile)};
  const render=`session_start();$_SESSION=['user_id'=>7,'logged_in'=>true,'role'=>${JSON.stringify(role)}];$_SERVER['REQUEST_METHOD']='GET';require ${JSON.stringify(path.join(dir,form+'_profile.php'))};`;
  const r=spawnSync(php,['-d','display_errors=0','-r',render],{encoding:'utf8',env:{...base,MODE:'existing'}});assert.equal(r.status,0,r.stderr);assert.ok(r.stdout.includes('&lt;'));assert.doesNotMatch(r.stdout,/<(?:Institution|Coop|Finance|Description|Bio)>/);if(form!=='researcher')assert.match(r.stdout,/Custom &lt;type&gt;/);
  for(const mode of ['new','existing','fail']){const post={csrf_token:'token',...fields};const code=`session_start();$_SESSION=['user_id'=>7,'logged_in'=>true,'role'=>${JSON.stringify(role)},'vayuma_csrf_token'=>'token'];$_SERVER['REQUEST_METHOD']='POST';$_POST=json_decode(getenv('POST_DATA'),true);register_shutdown_function(function(){fwrite(STDERR,"\\nRESULT=".json_encode(['write'=>$GLOBALS['write']??null,'status'=>http_response_code()]));});require ${JSON.stringify(path.join(dir,'auth/save_'+save+'.php'))};`;
   const res=spawnSync(php,['-d','display_errors=0','-r',code],{encoding:'utf8',env:{...base,MODE:mode,POST_DATA:JSON.stringify(post)}});assert.equal(res.status,0,res.stderr);let data=JSON.parse(res.stderr.slice(res.stderr.lastIndexOf('RESULT=')+7));if(mode==='fail'){assert.equal(data.status,500);assert.doesNotMatch(res.stdout+res.stderr,/secret raw driver text/);}else{assert.match(data.write.sql,mode==='new'?/INSERT INTO/:/UPDATE /);assert.ok(data.write.args.includes(7));if(mode==='existing')assert.match(data.write.sql,/user_id\s*=\s*\?/);}
  }
 }
 }finally{fs.rmSync(dir,{recursive:true,force:true});}
});
