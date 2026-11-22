// Synthetic DOM checks; requires jsdom on NODE_PATH. No network or provider access.
const assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path');
const {JSDOM}=require('jsdom');
const source=fs.readFileSync(path.join(__dirname,'../vayuma/frontend/js/ai-chat.js'),'utf8');
async function run(page){
 const dom=new JSDOM(`<!doctype html><html lang="en"><head><meta name="vayuma-ai-csrf" content="test-csrf"></head><body>${page?'<div data-ai-page></div>':''}</body></html>`,{url:'https://example.test/',runScripts:'outside-only',pretendToBeVisual:true});
 const w=dom.window,doc=w.document,requests=[];
 w.fetch=async(url,opts)=>{requests.push({url,opts,data:JSON.parse(opts.body)});return {ok:true,json:async()=>({success:true,answer:'<img src=x onerror=alert(1)> Example answer'})};};
 w.eval(source);
 if(!page){assert.ok(doc.querySelector('.vayuma-ai-fab'));doc.querySelector('.vayuma-ai-fab').click();assert.equal(doc.querySelector('.vai-overlay').hidden,false);}
 assert.match(doc.querySelector('.vai-caption').textContent,/external AI service/);assert.equal(doc.querySelector('[name=share_soil]').checked,false);assert.equal(doc.querySelector('[name=share_weather]').checked,false);
 doc.querySelector('[name=crop]').value='Maize';doc.querySelector('[name=stage]').value='Seedling';doc.querySelector('[name=location]').value='Nairobi';doc.querySelector('[name=share_soil]').checked=true;
 doc.querySelector('textarea').value='What next?';doc.querySelector('.vai-form').dispatchEvent(new w.Event('submit',{bubbles:true,cancelable:true}));await new Promise(r=>setTimeout(r,0));
 assert.equal(requests.length,1);assert.equal(requests[0].opts.headers['X-CSRF-Token'],'test-csrf');assert.deepEqual(requests[0].data,{question:'What next?',language:'en',history:[],crop:'Maize',stage:'Seedling',location:'Nairobi',share_soil:true,share_weather:false});
 assert.equal(doc.querySelector('.vai-message--assistant img'),null);assert.match(doc.querySelector('.vai-message--assistant').textContent,/<img/);
 doc.querySelector('textarea').value='Tell me more';doc.querySelector('.vai-form').dispatchEvent(new w.Event('submit',{bubbles:true,cancelable:true}));await new Promise(r=>setTimeout(r,0));assert.equal(requests[1].data.history.length,2);assert.equal(requests[1].data.history[0].content,'What next?');assert.equal(requests[1].data.history[1].role,'assistant');
 doc.querySelector('.vai-lang').click();assert.match(doc.querySelector('.vai-welcome').textContent,/Habari/);
 doc.querySelector('.vai-clear').click();assert.equal(doc.querySelectorAll('.vai-message').length,0);assert.equal(doc.querySelector('[name=crop]').value,'');assert.equal(doc.querySelector('[name=share_soil]').checked,false);
 if(!page){doc.querySelector('.vai-close').click();assert.equal(doc.querySelector('.vai-overlay').hidden,true);}
 dom.window.close();
}
(async()=>{await run(false);await run(true);console.log('PASS synthetic shared drawer/page, external disclosure, optional context, safe output, language and reset');})().catch(e=>{console.error(e);process.exitCode=1});
