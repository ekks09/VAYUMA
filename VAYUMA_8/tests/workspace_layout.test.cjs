const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs'),path=require('node:path'),os=require('node:os'),{spawnSync}=require('node:child_process');
const root=path.resolve(__dirname,'../vayuma'),php=process.env.VAYUMA_PHP||'php';
const read=f=>fs.readFileSync(path.join(root,f),'utf8');
const roles={farmer:'farmer_dashboard.php',buyer:'buyer_dashboard.php',consumer:'consumer_dashboard.php',transport_provider:'transport_dashboard.php',input_supplier:'input_supplier_dashboard.php',manufacturer:'manufacturer_dashboard.php',warehouse_provider:'warehouse_provider_dashboard.php',retailer:'retailer_dashboard.php',agribusiness:'agribusiness_dashboard.php',cooperative:'cooperative_dashboard.php',researcher:'researcher_dashboard.php',extension_officer:'extension_dashboard.php',veterinary_officer:'veterinary_officer_dashboard.php',financial_institution:'finance_dashboard.php',insurance_provider:'insurance_provider_dashboard.php',government_officer:'government_officer_dashboard.php',ngo_partner:'ngo_partner_dashboard.php',agricultural_service_provider:'agricultural_service_provider_dashboard.php',strategic_partner:'stakeholder_dashboard.php'};
test('all 19 role return links are server-rendered and ignore unsafe URLs',()=>{
 const helper=path.join(root,'includes/workspace_back.php');
 for(const [role,target] of Object.entries(roles)){
  const code=`$_SESSION=['user_id'=>1,'logged_in'=>true,'role'=>${JSON.stringify(role)}];$_GET['from']='https://evil.invalid';require ${JSON.stringify(helper)};`;
  const r=spawnSync(php,['-r',code],{encoding:'utf8'});assert.equal(r.status,0,r.stderr);assert.match(r.stdout,new RegExp(`href="${target.replace('.','\\.')}"`));assert.doesNotMatch(r.stdout,/evil\.invalid/);
 }
 const r=spawnSync(php,['-r',`$_SESSION=[];require ${JSON.stringify(helper)};`],{encoding:'utf8'});assert.equal(r.status,0,r.stderr);assert.doesNotMatch(r.stdout,/workspace-back/);
});
test('workspace foundations cover role templates and principal destinations',()=>{
 assert.match(read('includes/pwa.php'),/assets\/css\/vayuma-ui\.css/);
 assert.match(read('includes/ui_simple.php'),/workspace_back\.php/);
 for(const f of ['market_connect.php','produce_details.php','stakeholder_offers.php','stakeholder_requests.php','stakeholder_directory.php','government_programmes.php','government_programme_manage.php','role.php']) assert.match(read(f),/workspace_back\.php/,f);
 const core=read('stakeholder_dashboard_core.php');assert.match(core,/use href="frontend\/images\/icons\.svg#<\?=hd\(\$config\[1\]\)/);assert.match(core,/vy-status-badge/);assert.doesNotMatch(core,/<nav class="va-dash-sidebar--nav">/);
 const css=read('assets/css/vayuma-ui.css');assert.match(css,/padding-bottom:max\(96px/);assert.match(css,/\.va-dash-stat strong/);assert.match(read('sw.js'),/assets\/css\/vayuma-ui\.css/);
});

test('return_to accepts only allowlisted local destinations and rejects unsafe input',()=>{
 const file=path.join(root,'includes/role_routes.php');
 const code=`$_SESSION=['user_id'=>1,'logged_in'=>true,'role'=>'farmer'];require ${JSON.stringify(file)};echo json_encode(array_map('vayuma_workspace_return_navigation',['market_connect.php','subscription.php','https://evil.invalid','//evil.invalid','../market_connect.php','market_connect.php?next=evil', ['market_connect.php'], 'government_officer_dashboard.php']));`;
 const r=spawnSync(php,['-r',code],{encoding:'utf8'});assert.equal(r.status,0,r.stderr);const values=JSON.parse(r.stdout);assert.equal(values[0][0],'market_connect.php');assert.equal(values[1][0],'subscription.php');for(const row of values.slice(2))assert.equal(row[0],'farmer_dashboard.php');
});
test('secondary routes and role profile form have shared navigation and accessible fields',()=>{
 for(const f of ['extension_farmers.php','extension_visits.php','extension_reports.php','extension_resources.php','climate.php','soil_analysis.php','farming_score.php','category.php','buy_product.php','admin_categories.php','payment.php','paystack_display.php','ai_advisor.php'])assert.match(read(f),/workspace_back\.php/,f);
 const css=read('assets/css/vayuma-ui.css');for(const name of ['--bg-canvas','--bg-surface','--bg-subtle','--text-primary','--text-muted','--border-subtle','--brand-primary','--status-warning','--status-success'])assert.ok(css.includes(name));
 const form=read('stakeholder_profile_form.php');assert.match(form,/for="vy-field-organization_name"/);assert.match(form,/id="vy-field-organization_name"/);assert.match(form,/Save profile & continue/);assert.match(read('includes/workspace_back.php'),/header_user_menu/);
});

test('receipt error page renders the back component instead of literal PHP',()=>{
 const source=read('mpesa_receipt.php');assert.doesNotMatch(source,/echo '[^\n]*<\?php require/);assert.match(source,/require __DIR__ \. '\/includes\/workspace_back\.php'/);
});
