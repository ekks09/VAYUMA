const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const {spawnSync} = require('node:child_process');
const root = path.resolve(__dirname, '../vayuma');
const file = name => fs.readFileSync(path.join(root,name),'utf8');

test('install entries persist after notice dismissal and hide on installation', async () => {
 const listeners = {};
 function element() { return {children:[],hidden:false, className:'', append(...nodes){this.children.push(...nodes)}, prepend(node){this.children.unshift(node)}, querySelector(){return null},setAttribute(){},addEventListener(name,cb){this[name]=cb},remove(){this.removed=true},focus(){}}; }
 const sidebar=element(), bottom=element(), account=element(), body=element(); sidebar.querySelector=()=>bottom;
 const doc={currentScript:{src:'https://example.test/frontend/js/pwa.js'},readyState:'complete',body,documentElement:{lang:'en'},querySelector(s){return s.startsWith('aside.')?sidebar:s==='.vy-user-menu__popover'?account:null},createElement:element,addEventListener(){}};
 const storage={getItem(){return null},setItem(){}};
 const win={addEventListener(n,fn){listeners[n]=fn},matchMedia(){return {matches:false}}};
 vm.runInNewContext(file('frontend/js/pwa.js'),{window:win,document:doc,navigator:{userAgent:'Android'},localStorage:storage,URL,Date,setTimeout});
 assert.equal(bottom.children.length,1); assert.equal(account.children.length,1);
 const notice=body.children[0], actions=notice.children[2]; actions.children[1].click();
 assert.equal(notice.removed,true); assert.equal(bottom.children[0].hidden,false); assert.equal(account.children[0].hidden,false);
 listeners.appinstalled(); assert.equal(bottom.children[0].hidden,true); assert.equal(account.children[0].hidden,true);
});

test('navigation is generated from trusted session and rendered without model HTML', () => {
 const api=file('ai_chat_api.php'), js=file('frontend/js/ai-chat.js'), guide=file('includes/nvidia_advisor.php');
 assert.match(api,/\$_SESSION\['role'\]/); assert.match(api,/'navigation_links'=>\$navigationLinks/);
 assert.match(guide,/do not ask for it again/); assert.match(guide,/cannot perform changes or see private account data/);
 assert.match(js,/link\.textContent = item\.label/); assert.match(js,/!allowed\.test\(item\.href\)/);
});

const php=process.env.VAYUMA_PHP || 'php';
const hasPhp=spawnSync(php,['-v']).status===0;
test('navigation matches role and existing pages, with unknown role fallback', {skip:!hasPhp}, () => {
 const helper=path.join(root,'includes/ai_navigation.php');
 const code=`require ${JSON.stringify(helper)}; echo json_encode([vayuma_ai_navigation_links('How do I edit my profile?', 'farmer', 'en'), vayuma_ai_navigation_links('profile', 'buyer', 'en'), vayuma_ai_navigation_links('profile', 'unknown', 'sw'), vayuma_ai_navigation_catalog('farmer','en')]);`;
 const result=spawnSync(php,['-r',code],{encoding:'utf8'}); assert.equal(result.status,0,result.stderr);
 const [farmer,buyer,unknown,catalog]=JSON.parse(result.stdout);
 assert.equal(farmer[1].href,'profile_setup.php'); assert.equal(buyer[1].href,'buyer_profile.php'); assert.equal(unknown[1].href,'role.php');
 for (const item of catalog) assert.ok(fs.existsSync(path.join(root,item.href.split('?')[0])),item.href);
});
