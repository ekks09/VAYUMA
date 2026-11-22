// Pre-rendered mocked stakeholder dashboards; no real account or database.
const assert=require('node:assert/strict'),puppeteer=require('puppeteer-core');
const base=process.env.VAYUMA_DASHBOARD_FIXTURE_URL;if(!base)throw Error('Set VAYUMA_DASHBOARD_FIXTURE_URL');
const roles=['input_supplier','manufacturer','warehouse_provider','retailer','agribusiness','veterinary_officer','insurance_provider','government_officer','ngo_partner','agricultural_service_provider'];
(async()=>{const browser=await puppeteer.launch({executablePath:process.env.VAYUMA_CHROMIUM_PATH,args:['--no-sandbox','--disable-dev-shm-usage']});try{const page=await browser.newPage();await page.setCacheEnabled(false);
for(const role of roles)for(const width of [320,390,768,1280]){
 await page.setViewport({width,height:850});const res=await page.goto(`${base}/${role}.html`,{waitUntil:'load'});assert.equal(res.status(),200);
 const s=await page.evaluate(()=>{const main=document.querySelector('main'),stats=document.querySelector('.va-dash-stat'),badge=document.querySelector('.vy-status-badge'),icon=document.querySelector('.hero .icon svg use');const a=stats.querySelector('strong').getBoundingClientRect(),b=stats.querySelector('span').getBoundingClientRect();return {scroll:document.documentElement.scrollWidth,padding:parseFloat(getComputedStyle(main).paddingBottom),badge:badge.textContent.trim(),icon:icon.getAttribute('href'),gap:b.top-a.bottom,duplicate:!!document.querySelector('main .top nav')};});
 assert.ok(s.scroll<=width+1,`${role} ${width} overflow ${s.scroll}`);assert.ok(s.padding>=96,`${role} ${width} clearance ${s.padding}`);assert.equal(s.badge,'Verification: Pending');assert.match(s.icon,/icons\.svg#va-icon-/);assert.ok(s.gap>=7,`${role} stat gap ${s.gap}`);assert.equal(s.duplicate,false);
}
for(const file of ['stakeholder_directory.php','stakeholder_offers.php','stakeholder_requests.php','government_programmes.php','role.php'])for(const width of [320,390,768,1280]){
 await page.setViewport({width,height:850});const res=await page.goto(`${base}/link-${file}.html`,{waitUntil:'load'});assert.equal(res.status(),200);
 const s=await page.evaluate(()=>{const a=document.querySelector('.vy-workspace-back a'),main=document.querySelector('main'),r=a.getBoundingClientRect();return {href:a.getAttribute('href'),width:document.documentElement.scrollWidth,padding:parseFloat(getComputedStyle(main).paddingBottom),left:r.left,right:r.right,height:r.height,css:[...document.styleSheets].some(s=>s.href&&s.href.includes('vayuma-ui.css'))};});
 assert.equal(s.href,'government_officer_dashboard.php');assert.ok(s.width<=width+1&&s.left>=0&&s.right<=width&&s.height>=44&&s.padding>=96&&s.css,`${file} ${width} ${JSON.stringify(s)}`);
}
console.log('PASS 10 mocked stakeholder dashboards and 5 linked pages at 320–1280px; back routes, status, icons, spacing and clearance');
}finally{await browser.close()}})().catch(e=>{console.error(e);process.exitCode=1});
