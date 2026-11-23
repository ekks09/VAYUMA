// Synthetic DOM; content is never interpreted as HTML.
const assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path');
const {JSDOM}=require('jsdom');
(async()=>{
 const dom=new JSDOM('<!doctype html><html lang="en"><head><meta name="vayuma-ai-csrf" content="x"></head><body><div data-ai-page></div></body></html>',{url:'https://example.test/',runScripts:'outside-only'});
 const w=dom.window; w.HTMLElement.prototype.scrollIntoView=function(){}; w.fetch=async()=>({ok:true,json:async()=>({success:true,answer:'## Steps\n- **First** action\n- Next\n\n1. One\n2. Two\n<img src=x onerror=alert(1)>'})});
 w.eval(fs.readFileSync(path.join(__dirname,'../vayuma/frontend/js/ai-chat.js'),'utf8'));
 const d=w.document;d.querySelector('textarea').value='Explain';d.querySelector('.vai-form').dispatchEvent(new w.Event('submit',{bubbles:true,cancelable:true}));await new Promise(r=>setTimeout(r,10));
 const msg=d.querySelector('.vai-message--assistant'); assert.equal(msg.querySelectorAll('ul li').length,2);assert.equal(msg.querySelectorAll('ol li').length,2);assert.equal(msg.querySelector('ul strong').textContent,'First');assert.equal(msg.querySelector('img'),null);assert.match(msg.textContent,/<img/);
 assert.equal(d.querySelector('.vai-prompts').parentElement,d.querySelector('.vai-compose')); assert.equal(d.querySelector('textarea').maxLength,8000);dom.window.close();console.log('PASS safe markdown lists and prompt flow');
})().catch(e=>{console.error(e);process.exitCode=1});
