// Synthetic DOM integration test; no live provider, microphone or browser layout.
const assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path'),{JSDOM}=require('jsdom');
(async()=>{
 const dom=new JSDOM('<!doctype html><html lang="en"><head><meta name="vayuma-ai-csrf" content="csrf"></head><body><div data-ai-page></div></body></html>',{url:'https://example.test/',runScripts:'outside-only',pretendToBeVisual:true});
 const w=dom.window,d=w.document,requests=[],saved={},scrolls=[];
 w.HTMLElement.prototype.scrollIntoView=function(options){scrolls.push({node:this,options});};
 w.SpeechRecognition=function(){this.start=()=>{this.onresult({results:[[{transcript:'maize in Kiambu'}]]});this.onend();};this.stop=()=>this.onend();};
 w.fetch=async(_,opts)=>{const data=JSON.parse(opts.body);requests.push(data);if(data.action==='history_save'){saved[data.id]={id:data.id,turns:data.turns};return {ok:true,json:async()=>({success:true})};}if(data.action==='history_list')return {ok:true,json:async()=>({success:true,conversations:Object.values(saved)})};if(data.action==='feedback')return {ok:true,json:async()=>({success:true})};return {ok:true,json:async()=>({success:true,answer:'**Practical** advice\n- First',message_id:'abc'})};};
 w.eval(fs.readFileSync(path.join(__dirname,'../vayuma/frontend/js/ai-chat.js'),'utf8'));
 const text=d.querySelector('textarea');d.querySelector('.vai-voice').click();assert.match(text.value,/maize in Kiambu/);d.querySelector('.vai-mode').value='agri-pro';text.focus();d.querySelector('.vai-form').dispatchEvent(new w.Event('submit',{bubbles:true,cancelable:true}));
 assert.equal(text.value,'');assert.notEqual(d.activeElement,text);assert.ok(d.querySelector('.vayuma-chat').classList.contains('vai-conversation-started'));
 await new Promise(r=>setTimeout(r,10));assert.equal(requests[0].mode,'agri-pro');assert.equal(scrolls[0].options.block,'end');assert.equal(scrolls[1].options.block,'start');assert.equal(Object.keys(saved).length,1);
 d.querySelector('[data-rating=up]').click();await new Promise(r=>setTimeout(r,0));assert.equal(d.querySelector('[data-rating=up]').getAttribute('aria-pressed'),'true');assert.ok(requests.some(x=>x.action==='feedback'&&x.rating==='up'));
 d.querySelector('.vai-new').click();assert.equal(d.querySelectorAll('.vai-message').length,0);d.querySelector('.vai-history-toggle').click();await new Promise(r=>setTimeout(r,0));assert.ok(d.querySelector('.vai-history-list button'));d.querySelector('.vai-history-list button').click();assert.equal(d.querySelectorAll('.vai-message').length,2);assert.equal(d.querySelector('.vai-history').hidden,true);
 assert.equal(d.querySelector('.vai-context'),null);assert.ok(d.querySelector('.vai-prompts button'));assert.equal('crop' in requests[0],false);
 dom.window.close();console.log('PASS synthetic send, scroll, mode, voice, feedback, session history and text-only chat');
})().catch(e=>{console.error(e);process.exitCode=1});
