// PHP-WASM synthetic failure test; no database or real accounts.
const {test}=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),os=require('node:os'),path=require('node:path'),{spawnSync}=require('node:child_process');
const root=path.resolve(__dirname,'../vayuma'),php=process.env.VAYUMA_PHP||'php';
test('visit validation preserves escaped draft fields and refuses an unassigned farmer',()=>{
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'vy-visit-ux-'));try{
 fs.cpSync(root,dir,{recursive:true});fs.writeFileSync(path.join(dir,'config/db.php'),`<?php
class R {private $rows;function __construct($rows=[]){$this->rows=$rows;}function fetch_assoc(){return array_shift($this->rows)?:null;}function fetch_all($mode=0){return $this->rows;}}
class S {private $q;function __construct($q){$this->q=$q;}function bind_param($t,&...$v){}function execute(){return true;}function close(){}function get_result(){if(strpos($this->q,'SELECT * FROM extension_field_visits')!==false)return new R([['id'=>1,'visit_date'=>'2026-12-01','farmer_name_snapshot'=>'Farmer','county'=>'','visit_location'=>'','purpose'=>'Example','status'=>'completed'],['id'=>2,'visit_date'=>'2026-12-02','farmer_name_snapshot'=>'Farmer','county'=>'','visit_location'=>'','purpose'=>'Example','status'=>'planned']]);return new R;}}
class C {function prepare($q){return new S($q);}function query($q){return new R;}}$conn=new C;`);
 const p=path.join(dir,'extension_visits.php');fs.writeFileSync(p,fs.readFileSync(p,'utf8').replace('session_start();',"session_start(); $_SESSION=['user_id'=>7,'logged_in'=>true,'role'=>'extension_officer','ext_csrf'=>'test'];"));
 const code=`ini_set('display_errors','0');$_SERVER['REQUEST_METHOD']='POST';$_SERVER['SCRIPT_NAME']='/extension_visits.php';$_POST=['csrf'=>'test','action'=>'create','farmer_id'=>'8','visit_date'=>'2026-12-01T10:15','county'=>'Nairobi','location'=>'North & West','purpose'=>'Visit <test>','notes'=>'Keep <notes>'];require ${JSON.stringify(p)};`;
 const r=spawnSync(php,['-r',code],{encoding:'utf8'});assert.equal(r.status,0,r.stderr);assert.match(r.stdout,/eligible farmer/);assert.match(r.stdout,/value="2026-12-01T10:15"/);assert.match(r.stdout,/value="North &amp; West"/);assert.match(r.stdout,/value="Visit &lt;test&gt;"/);assert.match(r.stdout,/Keep &lt;notes&gt;/);assert.match(r.stdout,/Previous farmer unavailable/);assert.doesNotMatch(r.stdout,/Farm visit scheduled successfully/);assert.match(r.stdout,/Final status/);assert.doesNotMatch(r.stdout,/id="visit-status-1"/);assert.match(r.stdout,/id="visit-status-2"/);
 }finally{fs.rmSync(dir,{recursive:true,force:true});}
});
