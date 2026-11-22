const test=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs'),path=require('node:path');
const root=path.join(__dirname,'../vayuma'),read=f=>fs.readFileSync(path.join(root,f),'utf8');
test('AI page and global launcher share the compact chat component',()=>{
 const page=read('ai_advisor.php'),script=read('frontend/js/ai-chat.js'),css=read('frontend/css/ai-chat.css'),pwa=read('includes/pwa.php');
 assert.match(page,/data-ai-page/);assert.match(page,/ai-chat\.js/);assert.match(page,/ai-chat\.css/);assert.doesNotMatch(page,/YOUR TOOLKIT|Guidance you can check|externalConsent/);
 assert.match(pwa,/\$_SESSION\['logged_in'\]/);assert.match(pwa,/ai-chat\.js/);assert.match(pwa,/vayuma-ai-csrf/);
 for(const pattern of [/role="log"/,/role','dialog'/,/AbortController/,/requestId/,/share_soil/,/share_weather/,/Shift|shiftKey/,/external AI service/])assert.match(script,pattern);
 assert.match(css,/z-index:9999/);assert.match(css,/width:min\(400px,100%\)/);assert.match(css,/prefers-reduced-motion/);
 assert.doesNotMatch(script,/externalConsent|rule_based/);
});
test('NVIDIA gets curated guidance, not live account access',()=>{const guide=read('includes/ai_platform_guide.php');assert.match(guide,/role-specific dashboard/);assert.match(guide,/cannot see current listings/);assert.match(guide,/Payment processing is currently unavailable/);assert.match(read('includes/nvidia_advisor.php'),/vayuma_ai_guide\(\$question, \$language, true\)/);});
test('farmer dashboard retains working AI and profile destinations',()=>{const dashboard=read('farmer_dashboard.php');assert.match(dashboard,/'VAYUMA TRIX AI' => 'feature_access\.php\?feature=ai'/);assert.match(dashboard,/'Profile & Settings' => 'profile\.php\?return_to=farmer_dashboard\.php'/);assert.match(dashboard,/Voice support and SMS \/ USSD are not available yet/);});
