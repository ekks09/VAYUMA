const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs'); const os = require('node:os'); const path = require('node:path');
const {spawnSync} = require('node:child_process');
const php=process.env.VAYUMA_PHP||'php'; const root=path.resolve(__dirname,'../vayuma');
function run(code){let r=spawnSync(php,['-d','display_errors=0','-r',code],{encoding:'utf8'});assert.equal(r.status,0,r.stderr);return JSON.parse(r.stdout);}
test('plan type transitions update each membership, audit changes and refuse active paid to negotiated',()=>{
 const lib=path.join(root,'lib/plan_reconciliation.php');
 const code=`require ${JSON.stringify(lib)};
 class R {private $rows;function __construct($rows){$this->rows=$rows;}function fetch_assoc(){return array_shift($this->rows)?:null;}}
 class S {private $sql;private $args=[];function __construct($s){$this->sql=$s;}function bind_param($t,&...$a){$this->args=&$a;}function execute(){if(strpos($this->sql,'UPDATE user_subscriptions')===0)$GLOBALS['updates'][]=array_values($this->args);if(strpos($this->sql,'INSERT INTO subscription_status_history')===0)$GLOBALS['history'][]=array_values($this->args);return true;}function get_result(){return new R($GLOBALS['members']);}function close(){}}
 class D {function prepare($s){return new S($s);}} $out=[];
 foreach([['free','paid','free_account'],['paid','free','active'],['free','negotiated','free_account'],['paid','negotiated','active'],['paid','paid','active']] as [$old,$new,$status]){ $GLOBALS['members']=[['id'=>5,'status'=>$status]];$GLOBALS['updates']=[];$GLOBALS['history']=[];$error=''; try{vayuma_reconcile_plan_memberships(new D(),2,$old,$new,'monthly');}catch(DomainException $e){$error='blocked';}$out[]=[$GLOBALS['updates'],$GLOBALS['history'],$error];} echo json_encode($out);`;
 const rows=run(code);assert.equal(rows[0][0][0][0],'payment_due');assert.equal(rows[1][0][0][0],'free_account');assert.equal(rows[2][0][0][0],'negotiated_plan');assert.equal(rows[3][2],'blocked');assert.deepEqual(rows[4][0],[]);for(let i=0;i<3;i++)assert.equal(rows[i][1].length,1);
});
test('expiry locks and rechecks renewal; expiry and history roll back together on failure',()=>{
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-expiry-'));
 try {const source=fs.readFileSync(path.join(root,'lib/subscription_lib.php'),'utf8').replaceAll('mysqli $conn','$conn'); const lib=path.join(dir,'lib.php');fs.writeFileSync(lib,source);
 const code=`require ${JSON.stringify(lib)};
 class R {private $r;function __construct($r){$this->r=$r;}function fetch_assoc(){return $this->r;}}
 class S {public $affected_rows=1;private $sql;function __construct($s){$this->sql=$s;}function bind_param($t,&...$a){}function execute(){if(strpos($this->sql,'UPDATE user_subscriptions')===0)$GLOBALS['writes']++;if(strpos($this->sql,'INSERT INTO subscription_status_history')===0){$GLOBALS['hist']++;if($GLOBALS['mode']==='fail')throw new RuntimeException('failed');}return true;}function get_result(){if(strpos($this->sql,'FOR UPDATE')!==false)return new R(['status'=>'active','expires_at'=>$GLOBALS['mode']==='renew'?'2999-01-01 00:00:00':'2000-01-01 00:00:00','grace_period_ends_at'=>null]);return new R(['id'=>3,'status'=>'active','expires_at'=>'2000-01-01 00:00:00','grace_period_ends_at'=>null,'billing_interval'=>'monthly','plan_billing_interval'=>'monthly']);}function close(){}}
 class D {function prepare($s){return new S($s);}function begin_transaction(){$GLOBALS['begun']++;return true;}function commit(){$GLOBALS['commits']++;return true;}function rollback(){$GLOBALS['rollbacks']++;}}
 $out=[];foreach(['renew','expire','fail'] as $mode){$GLOBALS['mode']=$mode;$GLOBALS['writes']=$GLOBALS['hist']=$GLOBALS['begun']=$GLOBALS['commits']=$GLOBALS['rollbacks']=0;$status='exception';try{$status=subscription_for_user(new D(),7)['status'];}catch(Throwable $e){}$out[]=[$status,$GLOBALS['writes'],$GLOBALS['hist'],$GLOBALS['begun'],$GLOBALS['commits'],$GLOBALS['rollbacks']];}echo json_encode($out);`;
 const rows=run(code);assert.deepEqual(rows[0],['active',0,0,1,1,0]);assert.deepEqual(rows[1],['expired',1,1,1,1,0]);assert.deepEqual(rows[2],['exception',1,1,1,0,1]);
 }finally{fs.rmSync(dir,{recursive:true,force:true});}
});
