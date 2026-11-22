const {test}=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path');
const root=path.resolve(__dirname,'../vayuma'),read=f=>fs.readFileSync(path.join(root,f),'utf8');
test('extension dashboard keeps its role guard and existing navigation destinations',()=>{
 const s=read('extension_dashboard.php');assert.match(s,/subscription_guard\.php/);assert.match(s,/\$role !== 'extension_officer'/);assert.match(s,/htmlspecialchars\(\$full_name, ENT_QUOTES/);
 for(const href of ['extension_profile.php?from=extension_dashboard.php','agricultural_support_requests.php?view=received','agricultural_support_requests.php?view=sent','extension_farmers.php','extension_visits.php','extension_reports.php','feature_access.php?feature=ai','disease_helper.php','climate.php','extension_resources.php','subscription.php','auth/logout.php']) assert.ok(s.includes(`href="${href}"`),href);
 assert.match(s,/aria-current="page"/);assert.match(s,/va-ext-sidebar-footer[\s\S]*?vayuma_language_selector\(\)[\s\S]*?va-sidebar--bottom/);assert.match(read('frontend/js/pwa.js'),/querySelector\('\.va-sidebar--bottom'\)/);
});
test('dashboard uses honest unavailable metrics and useful links without fabricated zeroes',()=>{
 const s=read('extension_dashboard.php');for(const label of ['Farmers Supported','Farm Visits','Farm Reports','Farmer Advisories']) assert.ok(s.includes(label));assert.equal((s.match(/Count not available/g)||[]).length,4);assert.doesNotMatch(s,/stat-number[^>]*>\s*0/);assert.match(s,/Search VAYUMA: not available yet/);
 for(const label of ['View visits','View reports','Browse farmers','Open crop advisory','Extension Activities','Quick Actions','VAYUMA TRIX Mission']) assert.ok(s.includes(label),label);
});
test('scoped styling adds responsive clearance, compact cards, icons and actions',()=>{
 const s=read('extension_dashboard.php'),css=read('assets/css/dashboard-layout.css'),icons=read('frontend/images/icons.svg');
 assert.match(css,/va-extension-dashboard[^\n]*padding-bottom:max\(96px/);assert.match(css,/va-ext-content-grid[^\n]*grid-template-columns:minmax/);assert.match(css,/va-ext-actions a:hover/);assert.match(css,/va-ext-stat[^\n]*padding:16px/);assert.match(css,/@media\(max-width:767px\)/);
 for(const id of ['search','farmers','visit','report','advice','crop','pest','weather','resources','ai']) {assert.ok(s.includes(`#va-ext-${id}`),id);assert.ok(icons.includes(`id="va-ext-${id}"`),id);}
 assert.match(read('sw.js'),/assets\/css\/dashboard-layout\.css/);assert.match(read('sw.js'),/frontend\/images\/icons\.svg/);assert.doesNotMatch(s,/class="va-dash-panel h2"|class="quick-action"/);
});
