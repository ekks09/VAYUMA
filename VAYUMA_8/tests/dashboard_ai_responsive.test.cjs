const {test}=require('node:test'), assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path');
const root=path.resolve(__dirname,'../vayuma'), read=f=>fs.readFileSync(path.join(root,f),'utf8');
test('free banner stacks with full-width copy and wrapping actions on mobile',()=>{
 const s=read('includes/subscription_banner.php');
 assert.match(s,/@media \(max-width: 767px\)/);assert.match(s,/flex-direction: column; align-items: stretch; gap: 12px/);assert.match(s,/flex-wrap: wrap/);
});
test('responsive navigation retains staff entry in profile menu and compact workspace actions',()=>{
 const css=read('frontend/css/user-menu.css'), menu=read('includes/header_user_menu.php'), layout=read('assets/css/dashboard-layout.css'),page=read('extension_dashboard.php');
 assert.doesNotMatch(css,/\.vy-staff-entry\s*\{\s*display\s*:\s*none/);assert.match(css,/\.vy-staff-entry\{display:inline-flex/);assert.match(menu,/vy-user-menu__link" href="onboarding\.php"/);
 assert.match(layout,/padding-bottom:max\(96px/);assert.match(layout,/grid-template-columns:repeat\(2,minmax\(0,1fr\)\)/);assert.match(page,/va-ext-actions[\s\S]*va-ext-profile/);
});
test('AI capacity is raised while retaining atomic daily and session limits',()=>{
 const api=read('ai_chat_api.php'), helper=read('includes/nvidia_advisor.php'), js=read('frontend/js/ai-chat.js');
 assert.match(api,/count\(\$_SESSION\['ai_requests'\]\) >= 1000/);assert.match(helper,/request_count<10000/);assert.match(api,/mb_strlen\(\$question\) > 8000/);assert.match(js,/maxlength="8000"/);assert.match(helper,/'max_tokens'=>2048/);
 assert.ok(js.indexOf('class="vai-prompts"')<js.indexOf('class="vai-form"'));assert.doesNotMatch(js,/class="vai-context"/);
});
