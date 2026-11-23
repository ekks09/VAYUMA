// Synthetic sidebar enhancement tests; no real account or browser layout.
const assert=require('node:assert/strict');
const fs=require('node:fs'),path=require('node:path');
const {JSDOM}=require('jsdom');
const js=fs.readFileSync(path.join(__dirname,'../vayuma/frontend/js/dashboard-sidebar-icons.js'),'utf8');
for(const type of ['va-sidebar','va-dash-sidebar']){
 const dom=new JSDOM(`<!doctype html><body><aside class="${type}"><nav>
 <a class="va-nav-item va-category-link" href="role.php"><svg aria-hidden="true"></svg><span>Select account category</span></a>
 <a class="va-nav-item va-personal-details-link" href="profile.php?return_to=farmer_dashboard.php"><span class="va-nav-icon"></span><span>Edit personal details</span></a>
 <a class="va-dash-sidebar--nav-item" href="market_connect.php"><svg></svg><span>Marketplace</span></a>
 <span class="va-dash-sidebar--nav-item" aria-disabled="true" title="not available"><span class="va-dash-sidebar--icon"></span>Loan requests <small>Planned</small></span>
 </nav><div class="va-sidebar--bottom"><a href="subscription.php">Subscription</a><a class="va-logout-item" href="auth/logout.php"><span aria-hidden="true">⇥</span>Logout</a><button class="va-install-entry"><span class="va-install-entry__icon">↓</span><span>Install app</span></button></div></aside></body>`,{runScripts:'outside-only'});
 const {document}=dom.window;dom.window.eval(js);document.dispatchEvent(new dom.window.Event('DOMContentLoaded'));
 const sidebar=document.querySelector('aside');assert.ok(sidebar.classList.contains('va-sidebar--visual'));
 for(const el of sidebar.querySelectorAll('a, [aria-disabled],button')){assert.equal(el.querySelectorAll(':scope > .va-action-symbol').length,1);assert.equal(el.querySelector(':scope > .va-action-symbol').getAttribute('aria-hidden'),'true');}
 assert.ok(document.querySelector('.va-personal-details-link .va-action-symbol--profile'));assert.equal(document.querySelector('.va-personal-details-link').getAttribute('href'),'profile.php?return_to=farmer_dashboard.php');
 assert.ok(document.querySelector('[href="market_connect.php"] .va-action-symbol--market'));
 const disabled=document.querySelector('[aria-disabled]');assert.equal(disabled.tagName,'SPAN');assert.equal(disabled.getAttribute('aria-disabled'),'true');assert.equal(disabled.getAttribute('title'),'not available');
 assert.ok(document.querySelector('.va-install-entry .va-action-symbol--download'));assert.ok(document.querySelector('.va-logout-item .va-action-symbol--exit'));
 dom.window.close();
}
console.log('PASS synthetic simple and stakeholder sidebar icons, account links and disabled-state preservation');
