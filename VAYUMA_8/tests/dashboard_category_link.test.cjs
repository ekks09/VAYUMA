const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {spawnSync} = require('node:child_process');
const root=path.resolve(__dirname,'../vayuma');
const read=f=>fs.readFileSync(path.join(root,f),'utf8');
test('every role dashboard renders a direct, shared category selection link',()=>{
 const routes=read('includes/role_routes.php');
 const dashboards=[...routes.matchAll(/'dashboard'\s*=>\s*'([^']+)'/g)].map(m=>m[1]);
 assert.equal(dashboards.length,19);
 for(const dashboard of dashboards){
  let source=read(dashboard);
  if(source.includes("'/stakeholder_dashboard_core.php'")) source+=read('stakeholder_dashboard_core.php');
  if(source.includes("'/includes/dashboard_sidebar.php'")) source+=read('includes/dashboard_sidebar.php');
  if(source.includes("'/includes/dashboard_generic_sidebar.php'")) source+=read('includes/dashboard_generic_sidebar.php');
  assert.match(source,/dashboard_category_link\.php/,dashboard);
 }
 const link=read('includes/dashboard_category_link.php');assert.match(link,/href="role\.php"/);assert.match(link,/Select account category/);assert.match(link,/Chagua aina ya akaunti/);assert.match(link,/profile\.php/);assert.match(link,/Edit personal details/);
 assert.match(read('role.php'),/auth\/save_role\.php/);assert.match(read('auth/save_role.php'),/vayuma_csrf_valid/);
});
const php=process.env.VAYUMA_PHP||'php';
const hasPhp=spawnSync(php,['-v']).status===0;
test('category link renders for both sidebar styles and languages', {skip:!hasPhp},()=>{
 const helper=path.join(root,'includes/dashboard_category_link.php');
 for(const [lang,style,label,css] of [['en','simple','Select account category','va-nav-item'],['sw','dash','Chagua aina ya akaunti','va-dash-sidebar--nav-item']]){
  const r=spawnSync(php,['-r',`$_SESSION=['role'=>'farmer']; $current_language=${JSON.stringify(lang)}; $categoryLinkStyle=${JSON.stringify(style)}; require ${JSON.stringify(helper)};`],{encoding:'utf8'});
  assert.equal(r.status,0,r.stderr);assert.ok(r.stdout.includes(label));assert.ok(r.stdout.includes(css));assert.match(r.stdout,/href="role\.php"/);assert.match(r.stdout,/profile\.php\?return_to=farmer_dashboard\.php/);
 }
});
