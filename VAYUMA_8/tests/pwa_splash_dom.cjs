// Simulate installed and browser modes; this is not an operating-system splash test.
const assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path'),{JSDOM}=require('jsdom');
const pwa=fs.readFileSync(path.join(__dirname,'../vayuma/includes/pwa.php'),'utf8');
const code=pwa.match(/<script>\s*([\s\S]*?)<\/script>/)[1];
for(const installed of [false,true]){
 const dom=new JSDOM('<!doctype html><html><head></head><body></body></html>',{runScripts:'outside-only'});
 const w=dom.window;w.matchMedia=()=>({matches:installed});let fallback;w.setTimeout=fn=>{fallback=fn};w.eval(code);
 assert.equal(w.document.documentElement.classList.contains('va-pwa-loading'),installed);
 if(installed){w.dispatchEvent(new w.Event('load'));assert.equal(w.document.documentElement.classList.contains('va-pwa-loading'),false);w.document.documentElement.classList.add('va-pwa-loading');fallback();assert.equal(w.document.documentElement.classList.contains('va-pwa-loading'),false);}
 dom.window.close();
}
console.log('PASS installed-only PWA loading surface and cleanup');
