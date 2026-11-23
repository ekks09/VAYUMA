// Synthetic DOM checks; requires jsdom on NODE_PATH. No network or provider access.
const assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path');
const {JSDOM}=require('jsdom');
const source=fs.readFileSync(path.join(__dirname,'../vayuma/frontend/js/ai-chat.js'),'utf8');
async function run(page){
 const dom=new JSDOM(`<!doctype html><html lang="en"><head><meta name="vayuma-ai-csrf" content="test-csrf"></head><body>${page?'<div data-ai-page></div>':''}</body></html>`,{url:'https://example.test/',runScripts:'outside-only',pretendToBeVisual:true});
 const w=dom.window,doc=w.document,requests=[];
 let scrolled=null, copied='', spoken=''; Object.defineProperty(w.navigator,'clipboard',{value:{writeText:async value=>{copied=value;}}}); w.speechSynthesis={speak:u=>{spoken=u.text;},cancel:()=>{spoken='';}}; w.SpeechSynthesisUtterance=function(value){this.text=value;}; w.HTMLElement.prototype.scrollIntoView=function(options){scrolled={element:this, options};}; w.HTMLElement.prototype.scrollTo=function(options){this.__scrollTo=options;};
 w.fetch=async(url,opts)=>{const data=JSON.parse(opts.body);if(data.action)return {ok:true,json:async()=>({success:true,conversations:[]})};requests.push({url,opts,data});return {ok:true,json:async()=>({success:true,answer:'<img src=x onerror=alert(1)> Example answer\n\n'+('Long paragraph. '.repeat(100))})};};
 w.eval(source);
 if(!page){assert.ok(doc.querySelector('.vayuma-ai-fab'));doc.querySelector('.vayuma-ai-fab').click();assert.equal(doc.querySelector('.vai-overlay').hidden,false);}
 assert.match(doc.querySelector('.vai-caption').textContent,/external AI service/);assert.equal(doc.querySelector('.vai-context'),null);
 doc.querySelector('textarea').value='What next?';doc.querySelector('.vai-form').dispatchEvent(new w.Event('submit',{bubbles:true,cancelable:true}));await new Promise(r=>setTimeout(r,0));
 assert.equal(requests.length,1);assert.equal(requests[0].opts.headers['X-CSRF-Token'],'test-csrf');assert.deepEqual(requests[0].data,{question:'What next?',language:'en',mode:'general',history:[]});
 assert.equal(scrolled.element,doc.querySelector('.vai-message--assistant')); assert.equal(scrolled.options.block,'start'); assert.equal(scrolled.options.behavior,'smooth');
 doc.querySelector('.vai-bottom').click(); assert.equal(doc.querySelector('.vai-messages').__scrollTo.behavior,'smooth');
 const actions=doc.querySelectorAll('.vai-response-actions button'); actions[0].click(); await new Promise(r=>setTimeout(r,0)); assert.match(copied,/Long paragraph/); assert.equal(actions[0].textContent,'Copied'); actions[1].click(); assert.match(spoken,/Long paragraph/); assert.equal(actions[1].textContent,'Stop'); actions[1].click(); assert.equal(spoken,'');
 assert.equal(doc.querySelector('.vai-message--assistant img'),null);assert.match(doc.querySelector('.vai-message--assistant').textContent,/<img/);
 doc.querySelector('textarea').value='Tell me more';doc.querySelector('.vai-form').dispatchEvent(new w.Event('submit',{bubbles:true,cancelable:true}));await new Promise(r=>setTimeout(r,0));assert.equal(requests[1].data.history.length,2);assert.equal(requests[1].data.history[0].content,'What next?');assert.equal(requests[1].data.history[1].role,'assistant');
 doc.querySelector('[data-lang=sw]').click();assert.match(doc.querySelector('.vai-welcome').textContent,/Habari/);
 doc.querySelector('.vai-clear').click();assert.equal(doc.querySelectorAll('.vai-message').length,0);assert.equal(doc.querySelector('.vai-prompts').hidden,false);
 if(!page){doc.querySelector('.vai-close').click();assert.equal(doc.querySelector('.vai-overlay').hidden,true);}
 dom.window.close();
}
(async()=>{await run(false);await run(true);console.log('PASS synthetic shared drawer/page, external disclosure, text-only input, safe output, language and reset');})().catch(e=>{console.error(e);process.exitCode=1});
