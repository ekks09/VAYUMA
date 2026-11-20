const {test}=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path'),vm=require('node:vm');
const root=path.join(__dirname,'../vayuma'), read=f=>fs.readFileSync(path.join(root,f),'utf8');
test('marketplace and pricing use the dashboard grid and scrollable pricing tables',()=>{for(const f of ['market_connect.php','admin_subscriptions.php']){const s=read(f);assert.match(s,/dashboardShell/);assert.match(s,/va-simple-dashboard-grid/);assert.match(s,/dashboard_sidebar\.php/);}const s=read('admin_subscriptions.php');assert.equal((s.match(/class="va-table-scroll"/g)||[]).length,2);assert.match(read('assets/css/dashboard-layout.css'),/\.va-table-scroll.*overflow-x:auto/);});
test('profile navigation wraps on small screens and wide admin tables are accessible',()=>{const s=read('frontend/css/profile_forms.css');assert.match(s,/\.va-profile-navigation\s*\{[\s\S]*?flex-wrap:\s*wrap/);const a=read('admin.php');assert.equal((a.match(/class="va-admin-table-scroll"/g)||[]).length,2);assert.match(a,/tabindex="0" role="region"/);});
test('extension sidebar destinations share responsive styles and budget script parses',()=>{for(const f of ['extension_farmers.php','extension_visits.php','extension_reports.php','extension_resources.php'])assert.match(read(f),/extension-workspace\.css/);const s=read('budget_planner.php');for(const match of s.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/g))if(match[1].trim())assert.doesNotThrow(()=>new vm.Script(match[1]));});
test('subscription uses one accessible navigation group instead of duplicated header links',()=>{
  const s=read('subscription.php');
  assert.match(s,/\$showNav\s*=\s*false/);
  assert.match(s,/class="va-subscription-navigation va-mb-4"/);
  assert.match(s,/if \(\$dashboardAccessible && \$dashboardUrl !== null\)/);
  assert.match(s,/Back to your profile/);
  assert.match(s,/Choose stakeholder type/);
  assert.match(read('frontend/css/components_addition.css'),/\.va-subscription-navigation \.va-btn\s*\{[^}]*min-height:\s*44px/s);
});
