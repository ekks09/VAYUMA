// Synthetic pre-rendered fixture only; not a signed-in TiDB or Brevo test.
const assert=require('node:assert/strict'),puppeteer=require('puppeteer-core');
const base=process.env.VAYUMA_EXTENSION_FIXTURE_URL;if(!base)throw Error('Set VAYUMA_EXTENSION_FIXTURE_URL');
(async()=>{const browser=await puppeteer.launch({executablePath:process.env.VAYUMA_CHROMIUM_PATH,args:['--no-sandbox','--disable-dev-shm-usage']});try{
 const page=await browser.newPage();
 for(const width of [320,390,767,768,1024,1280]){
  await page.setViewport({width,height:844});await page.goto(base,{waitUntil:'load'});
  const info=await page.evaluate(()=>{const rect=s=>{let r=document.querySelector(s).getBoundingClientRect();return {top:r.top,bottom:r.bottom,left:r.left,right:r.right,height:r.height,width:r.width}};let cards=[...document.querySelectorAll('.va-ext-stat')].map(e=>e.getBoundingClientRect().height);return {width:document.documentElement.scrollWidth,top:rect('.va-dash-topbar'),search:rect('.va-ext-search'),account:rect('.vy-account-controls'),padding:parseFloat(getComputedStyle(document.querySelector('main')).paddingBottom),cards,actions:[...document.querySelectorAll('.va-ext-actions a,.va-ext-action,.va-ext-stat a')].map(e=>e.getBoundingClientRect().height),icons:[...document.querySelectorAll('.va-ext-icon')].map(e=>e.getBoundingClientRect().width),columns:getComputedStyle(document.querySelector('.va-ext-content-grid')).gridTemplateColumns};});
  assert.ok(info.width<=width+1,`${width} horizontal overflow ${info.width}`);assert.ok(info.padding>=96,`${width} insufficient clearance`);assert.ok(Math.abs(info.search.top-info.account.top)<25,`${width} header not aligned ${JSON.stringify(info)}`);assert.ok(info.account.right<=width+1,`${width} header overflow`);assert.ok(info.cards.every(h=>h<=220),`${width} oversized KPI cards ${info.cards}`);assert.ok(info.icons.every(w=>w>=19&&w<=21),`${width} unexpected icon width`);assert.ok(info.actions.every(h=>h>=43),`${width} small action target`);
  // The reserved rail keeps the fixed launcher clear of lower cards at any scroll position.
  for(const selector of ['.va-ext-activities','.va-ext-quick','.va-ext-mission']) {
   await page.$eval(selector,e=>e.scrollIntoView({block:'center'}));
   const clash=await page.evaluate((s)=>{const r=document.querySelector(s).getBoundingClientRect(),f=document.querySelector('.vayuma-ai-fab')?.getBoundingClientRect();return f && r.left<f.right && r.right>f.left && r.top<f.bottom && r.bottom>f.top;},selector);assert.ok(!clash,`${width} floating launcher overlaps ${selector}`);
  }
  await page.evaluate(()=>window.scrollTo(0,document.body.scrollHeight));
  const bottom=await page.evaluate(()=>{const last=document.querySelector('.va-ext-mission').getBoundingClientRect(),fab=document.querySelector('.vayuma-ai-fab')?.getBoundingClientRect();return {lastBottom:last.bottom,fabTop:fab?.top};});if(bottom.fabTop!==undefined)assert.ok(bottom.lastBottom<=bottom.fabTop,`${width} bottom clearance ${JSON.stringify(bottom)}`);
  if(width<768){await page.evaluate(()=>window.scrollTo(0,0));await page.click('.va-mobile-menu-btn');assert.equal(await page.$eval('.va-mobile-menu-btn',e=>e.getAttribute('aria-expanded')),'true');assert.ok(await page.$('.va-sidebar--bottom .va-install-entry'));await page.keyboard.press('Escape');assert.equal(await page.$eval('.va-mobile-menu-btn',e=>e.getAttribute('aria-expanded')),'false');}
  console.log(`PASS synthetic extension dashboard ${width}px`);
 }
}finally{await browser.close();}})().catch(e=>{console.error(e);process.exitCode=1});
