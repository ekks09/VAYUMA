const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs'),os=require('node:os'),path=require('node:path'),{spawnSync}=require('node:child_process');
const root=path.resolve(__dirname,'../vayuma'),php=process.env.VAYUMA_PHP||'php';
test('report POST rejects foreign visits and mismatched farmers, accepts a matching visit, and hides database failures (mock)',()=>{
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-ext-test-'));
 try {
  fs.cpSync(root,dir,{recursive:true});
  fs.writeFileSync(path.join(dir,'config/db.php'),`<?php
class MockResult {private $rows;function __construct($rows){$this->rows=$rows;}function fetch_assoc(){return array_shift($this->rows) ?: null;}function fetch_all($mode=0){return $this->rows;}}
class MockStmt {private $sql; private $v=[];function __construct($sql){$this->sql=$sql;}function bind_param($types,&...$v){$this->v=&$v;}function execute(){if(strpos($this->sql,'INSERT INTO extension_field_reports')!==false){if(getenv('TEST_FAIL')==='exception')throw new mysqli_sql_exception('SECRET_SQL_DETAIL');if(getenv('TEST_FAIL')==='false')return false;file_put_contents(getenv('TEST_CAPTURE'),'inserted');}return true;}function close(){}function get_result(){if(strpos($this->sql,'SELECT u.full_name')!==false)return new MockResult((int)$this->v[0]===4 && (int)$this->v[1]===7?[['full_name'=>'Sample Farmer']]:[]);if(strpos($this->sql,'SELECT farmer_id FROM extension_field_visits')!==false)return new MockResult((int)$this->v[0]===10 && (int)$this->v[1]===7?[['farmer_id'=>4]]:[]);return new MockResult([]);}}
class MockConn {function prepare($q){if(strpos($q,'INSERT INTO extension_field_reports')!==false){if(getenv('TEST_FAIL')==='prepare_exception')throw new mysqli_sql_exception('SECRET_SQL_DETAIL');if(getenv('TEST_FAIL')==='prepare_false')return false;}return new MockStmt($q);}function query($q){return new MockResult([]);}}$conn=new MockConn;`);
  let source=fs.readFileSync(path.join(dir,'extension_reports.php'),'utf8').replace('session_start();',"session_start(); $_SESSION=['user_id'=>7,'logged_in'=>true,'role'=>'extension_officer','full_name'=>'Staff','ext_csrf'=>'test-csrf'];");
  fs.writeFileSync(path.join(dir,'extension_reports.php'),source);
  const capture=path.join(dir,'capture');
  function req(visit,farmer,fail=false,csrf='test-csrf'){
   fs.rmSync(capture,{force:true});
   const code=`ini_set('display_errors','0');$_SERVER['REQUEST_METHOD']='POST';$_SERVER['SCRIPT_NAME']='/extension_reports.php';$_POST=['csrf'=>${JSON.stringify(csrf)},'visit_id'=>${JSON.stringify(String(visit))},'farmer_id'=>${JSON.stringify(String(farmer))},'report_type'=>'Farm assessment','observations'=>'Example <script>','recommendations'=>'Keep & review','county'=>'Nairobi'];require ${JSON.stringify(path.join(dir,'extension_reports.php'))};`;
   const result=spawnSync(php,['-r',code],{encoding:'utf8',env:{...process.env,TEST_CAPTURE:capture,TEST_FAIL:fail||'0'}});assert.equal(result.status,0,result.stderr);return {html:result.stdout,inserted:fs.existsSync(capture)};
  }
  let a=req(11,4);assert.equal(a.inserted,false);assert.match(a.html,/selected visit is unavailable/);assert.match(a.html,/Example &lt;script&gt;/);assert.match(a.html,/Keep &amp; review/);assert.match(a.html,/value="Nairobi"/);assert.match(a.html,/value="Farm assessment" selected/);assert.match(a.html,/Previous visit unavailable/);
  a=req(10,0);assert.equal(a.inserted,false);assert.match(a.html,/does not match the farmer/);
  a=req(10,5);assert.equal(a.inserted,false);assert.match(a.html,/selected farmer is not available/);
  a=req(10,4,false,'wrong');assert.equal(a.inserted,false);assert.match(a.html,/session token expired/i);
  a=req(10,4);assert.equal(a.inserted,true);assert.match(a.html,/saved successfully/);assert.doesNotMatch(a.html,/Example &lt;script&gt;/);
  for (const mode of ['exception','false','prepare_exception','prepare_false']) {a=req(10,4,mode);assert.equal(a.inserted,false);assert.match(a.html,/Unable to save/);assert.doesNotMatch(a.html,/SECRET_SQL_DETAIL|Fatal error/);}
 } finally {fs.rmSync(dir,{recursive:true,force:true});}
});
