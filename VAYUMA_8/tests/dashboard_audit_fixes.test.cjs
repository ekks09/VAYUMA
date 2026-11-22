const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs'),path=require('node:path'),{spawnSync}=require('node:child_process');
const root=path.resolve(__dirname,'../vayuma'), php=process.env.VAYUMA_PHP||'php';
const read=f=>fs.readFileSync(path.join(root,f),'utf8');
test('extension access checks visit owner and exact farmer context (mock database)',()=>{
 const code=`require ${JSON.stringify(path.join(root,'includes/extension_access.php'))};
 class R {private $rows;function __construct($r){$this->rows=$r;}function fetch_assoc(){return array_shift($this->rows) ?: null;}}
 class S {private $q;private $v; function __construct($q){$this->q=$q;}function bind_param($t,&...$v){$this->v=&$v;}function execute(){return true;}function close(){}function get_result(){if(strpos($this->q,'extension_field_visits')!==false){return new R($this->v[0]===10 && $this->v[1]===7 ? [['farmer_id'=>4]] : []);}return new R($this->v[0]===4 && $this->v[1]===7 ? [['full_name'=>'Allowed']] : []);}}
 class C {function prepare($q){return new S($q);}} $c=new C;
 echo json_encode([vayuma_extension_visit_matches($c,7,10,4),vayuma_extension_visit_matches($c,8,10,4),vayuma_extension_visit_matches($c,7,10,5),vayuma_extension_visit_matches($c,7,10,0),vayuma_extension_farmer_name($c,7,4),vayuma_extension_farmer_name($c,8,4)]);`;
 const r=spawnSync(php,['-r',code],{encoding:'utf8'});assert.equal(r.status,0,r.stderr);assert.deepEqual(JSON.parse(r.stdout),[true,false,false,false,'Allowed',null]);
});
test('extension forms enforce shared scope and do not disclose SQL errors',()=>{
 const scope=read('includes/extension_access.php');assert.match(scope,/provider_id=\?/);assert.match(scope,/accepted.*in_progress.*completed/);assert.match(scope,/LIMIT 51 OFFSET/);
 for(const f of ['extension_visits.php','extension_reports.php']){const s=read(f);assert.match(s,/vayuma_extension_farmer_name/);assert.match(s,/vayuma_extension_farmers/);assert.doesNotMatch(s,/\$st->error/);assert.match(s,/ext_csrf/);}
 const report=read('extension_reports.php');assert.match(report,/vayuma_extension_visit_matches/);for(const n of ['farmer_id','visit_id','report_date','report_type','county','location','observations','recommendations','follow_up_date'])assert.match(report,new RegExp(`for="report-${n}"`));
});
test('staff entry and honest metric/error states are present',()=>{
 assert.match(read('role.php'),/href="onboarding.php"/);assert.doesNotMatch(read('onboarding.php'),/subscription_guard\.php/);assert.match(read('onboarding.php'),/Registered farmer accounts/);assert.match(read('onboarding.php'),/Staff onboarding farmer records/);
 assert.match(read('includes/onboarding_lock.php'),/!is_string\(\$configured\)/);const core=read('stakeholder_dashboard_core.php');assert.match(core,/'offers'=>null/);assert.match(core,/Unavailable/);assert.match(core,/Accepted buyer orders/);
 assert.match(read('farmer_dashboard.php'),/\$totalListings === null/);const buyer=read('buyer_dashboard.php');assert.match(buyer,/\$savedCount === null/);assert.match(buyer,/!\$ordersAvailable/);assert.doesNotMatch(buyer,/onclick="window.location.href/);
});
