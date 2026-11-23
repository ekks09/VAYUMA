// Simulated VisualViewport events; real device keyboard behavior still needs testing.
const assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path');
const {JSDOM}=require('jsdom');
const dom=new JSDOM('<!doctype html><html lang="en"><head><meta name="vayuma-ai-csrf" content="token"></head><body></body></html>',{url:'https://example.test/',runScripts:'outside-only',pretendToBeVisual:true});
const w=dom.window,d=w.document;Object.defineProperty(w,'innerWidth',{value:390,writable:true});
const events={};const viewport={width:390,height:800,offsetTop:0,offsetLeft:0,addEventListener(n,fn){events[n]=fn;}};
Object.defineProperty(w,'visualViewport',{value:viewport});
w.eval(fs.readFileSync(path.join(__dirname,'../vayuma/frontend/js/ai-chat.js'),'utf8'));
d.querySelector('.vayuma-ai-fab').click();const overlay=d.querySelector('.vai-overlay'),messages=d.querySelector('.vai-messages');
assert.equal(d.body.classList.contains('vai-dialog-open'),true);assert.equal(overlay.style.getPropertyValue('--vai-vv-height'),'800px');
Object.defineProperty(messages,'scrollHeight',{value:1200,configurable:true});messages.scrollTop=100; const compose=d.querySelector('.vai-compose');Object.defineProperty(compose,'scrollHeight',{value:500,configurable:true});
d.querySelector('textarea').focus();viewport.height=480;viewport.offsetTop=16;events.resize();
assert.equal(overlay.style.getPropertyValue('--vai-vv-height'),'480px');assert.equal(overlay.style.getPropertyValue('--vai-vv-top'),'16px');assert.equal(messages.scrollTop,1200);assert.equal(compose.scrollTop,500);
viewport.offsetTop=5;events.scroll();assert.equal(overlay.style.getPropertyValue('--vai-vv-top'),'5px');
w.innerWidth=1000;w.dispatchEvent(new w.Event('resize'));assert.equal(overlay.style.getPropertyValue('--vai-vv-height'),'');
d.querySelector('.vai-close').click();assert.equal(overlay.hidden,true);assert.equal(d.body.classList.contains('vai-dialog-open'),false);dom.window.close();
console.log('PASS synthetic keyboard resize, scrolling, desktop cleanup and close');
