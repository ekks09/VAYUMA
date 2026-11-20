// Run with jsdom available in NODE_PATH and VAYUMA_PHP set; synthetic DOM, mocked API.
const assert=require('node:assert/strict'), {spawnSync}=require('node:child_process'), path=require('node:path');
const {JSDOM}=require('jsdom');
const fs=require('node:fs'), os=require('node:os');
const root=path.resolve(__dirname,'../vayuma'), php=process.env.VAYUMA_PHP||'php';
(async()=>{
 const fixture=fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-ai-dom-')); fs.cpSync(root,fixture,{recursive:true});
 fs.writeFileSync(path.join(fixture,'config/db.php'), '<?php $conn=new stdClass();');
 fs.writeFileSync(path.join(fixture,'includes/feature_access_policy.php'), "<?php function vayuma_feature_access_decision($c,$s){return 'allow';}");
 const code=`ini_set('display_errors','0'); session_start(); $_SESSION=['user_id'=>1,'logged_in'=>true,'role'=>'farmer']; $_SERVER['SCRIPT_NAME']='/ai_advisor.php'; require ${JSON.stringify(path.join(fixture,'ai_advisor.php'))};`;
 const r=spawnSync(php,['-r',code],{encoding:'utf8'}); assert.equal(r.status,0,r.stderr); assert.match(r.stdout,/<!DOCTYPE html>/);
 const dom=new JSDOM(r.stdout,{url:'https://example.test/ai_advisor.php',runScripts:'outside-only'});
 const w=dom.window, doc=w.document, requests=[];
 w.fetch=async (url,options)=>{const data=JSON.parse(options.body);requests.push(data);return {ok:true,json:async()=>({success:true,answer:'Example response',mode:data.external_consent?'ai_support':'rule_based'})};};
 const script=[...doc.querySelectorAll('script')].find(s=>s.textContent.includes('const languageSelect'));assert.ok(script);w.eval(script.textContent);
 const consent=doc.getElementById('externalConsent'), choice=doc.getElementById('responseChoice'), form=doc.getElementById('chatForm');
 assert.equal(choice.value,'ai'); assert.equal(consent.checked,false); assert.match(doc.getElementById('responseMode').textContent,/permission required/i); assert.equal(consent.closest('details'),null);
 const send=async()=>{doc.getElementById('question').value='What can I do?';form.dispatchEvent(new w.Event('submit',{bubbles:true,cancelable:true}));await new Promise(resolve=>setTimeout(resolve,10));};
 await send(); assert.equal(requests.length,0); assert.equal(doc.getElementById('consentError').hidden,false); assert.equal(doc.getElementById('question').value,'What can I do?');
 consent.checked=true;consent.dispatchEvent(new w.Event('change',{bubbles:true}));assert.match(doc.getElementById('responseMode').textContent,/VAYUMA AI Support selected/);
 await send(); assert.equal(requests[0].external_consent,true);assert.match(doc.querySelector('#messages .message:last-child .bubble').textContent,/VAYUMA AI Support/);
 choice.value='rules';choice.dispatchEvent(new w.Event('change',{bubbles:true}));await send();assert.equal(requests[1].external_consent,false);assert.match(doc.querySelector('#messages .message:last-child .bubble').textContent,/Rule-based helper/);
 choice.value='ai';choice.dispatchEvent(new w.Event('change',{bubbles:true}));
 doc.getElementById('language').value='sw';doc.getElementById('language').dispatchEvent(new w.Event('change'));assert.match(doc.getElementById('responseMode').textContent,/imechaguliwa/);
 doc.getElementById('clearBtn').click();assert.ok(doc.getElementById('welcomeTitle'));dom.window.close(); fs.rmSync(fixture,{recursive:true,force:true}); console.log('PASS synthetic AI consent, response labels, language and reset');
})().catch(e=>{console.error(e);process.exitCode=1;});
