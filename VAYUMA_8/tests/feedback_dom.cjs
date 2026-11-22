// Synthetic DOM coverage; no server or credentials are used.
const assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path'),{JSDOM}=require('jsdom');
const root=path.join(__dirname,'../vayuma');
(async()=>{
 const d=new JSDOM('<!doctype html><body><div class="form-alert">Please retry</div><div class="login-success">Saved</div><div class="va-alert--error" role="status">Custom role</div><input id="staff-passphrase" type="password"><button id="onboard-show-key" hidden></button></body>',{runScripts:'outside-only'});
 d.window.eval(fs.readFileSync(path.join(root,'frontend/js/feedback.js'),'utf8'));d.window.document.dispatchEvent(new d.window.Event('DOMContentLoaded'));
 assert.equal(d.window.document.querySelector('.form-alert').getAttribute('role'),'alert');assert.equal(d.window.document.querySelector('.login-success').getAttribute('role'),'status');assert.equal(d.window.document.querySelector('.va-alert--error').getAttribute('role'),'status');
 let n=d.window.document.createElement('p');n.className='onboard-error';n.textContent='Try again';d.window.document.body.appendChild(n);await new Promise(resolve=>setTimeout(resolve,0));assert.equal(n.getAttribute('role'),'alert');
 d.window.eval(fs.readFileSync(path.join(root,'assets/js/onboarding-workspace.js'),'utf8'));const b=d.window.document.querySelector('#onboard-show-key'),i=d.window.document.querySelector('#staff-passphrase');assert.equal(b.hidden,false);b.click();assert.equal(i.type,'text');assert.equal(b.getAttribute('aria-pressed'),'true');b.click();assert.equal(i.type,'password');assert.equal(b.getAttribute('aria-pressed'),'false');
 d.window.close();console.log('PASS feedback semantics and passphrase visibility on synthetic DOM');
})().catch(e=>{console.error(e);process.exitCode=1});
