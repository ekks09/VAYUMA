// Synthetic, pre-rendered fixture checks only; auth and DB are mocked.
// Set VAYUMA_PORTAL_FIXTURE_URL and VAYUMA_CHROMIUM_PATH.
const assert=require('node:assert/strict');
const puppeteer=require('puppeteer-core');
const base=process.env.VAYUMA_PORTAL_FIXTURE_URL;
if(!base)throw new Error('Set VAYUMA_PORTAL_FIXTURE_URL');
(async()=>{
 const browser=await puppeteer.launch({executablePath:process.env.VAYUMA_CHROMIUM_PATH,headless:true,args:['--no-sandbox','--disable-dev-shm-usage']});
 try {
  const page=await browser.newPage();
  // Stub the clipboard so the synthetic code never touches the host clipboard.
  await page.evaluateOnNewDocument(() => Object.defineProperty(navigator, 'clipboard', {configurable:true, value:{writeText:async value=>{window.__copiedClaimCode=value;}}}));
  for(const width of [320,360,390,768,1024]){
   await page.setViewport({width,height:850});
   for(const fixture of ['onboarding','unlock','claim']){
    const response=await page.goto(`${base}/${fixture}.html`,{waitUntil:'load'});assert.equal(response.status(),200);
    const layout=await page.evaluate(()=>({scroll:document.documentElement.scrollWidth,fields:[...document.querySelectorAll('.onboard input:not([type=checkbox]), .onboard select, .onboard button')].filter(e=>e.getClientRects().length).map(e=>({name:e.name||e.id||e.tagName,h:e.getBoundingClientRect().height,font:parseFloat(getComputedStyle(e).fontSize)}))}));
    assert.ok(layout.scroll<=width+1,`${fixture} ${width} overflow ${layout.scroll}`);
    for(const field of layout.fields){assert.ok(field.h>=44,`${fixture} ${width} ${field.name} height ${field.h}`);assert.ok(field.font>=16,`${fixture} ${width} ${field.name} font ${field.font}`);}
   }
  }
  await page.setViewport({width:320,height:850});await page.goto(`${base}/onboarding.html`,{waitUntil:'load'});
  assert.equal(await page.$$eval('[data-farmer-field]',els=>els.every(e=>!e.hidden && e.querySelector('input,select').required)),true);
  await page.select('#onboarding-role','buyer');assert.equal(await page.$$eval('[data-farmer-field]',els=>els.every(e=>e.hidden && !e.querySelector('input,select').required)),true);
  await page.select('#onboarding-role','farmer');assert.equal(await page.$$eval('[data-farmer-field]',els=>els.every(e=>!e.hidden && e.querySelector('input,select').required)),true);
  assert.equal(await page.$eval('.onboard-search',e=>e.method),'post');
  assert.match(await page.$eval('#claim-code',e=>e.textContent),/Sample Farmer/);
  assert.match(await page.$eval('#claim-code',e=>e.textContent),/does not send SMS/);
  assert.equal(await page.$eval('#copy-claim-code',e=>e.hidden),false);
  await page.click('#copy-claim-code');
  assert.equal(await page.evaluate(()=>window.__copiedClaimCode),'abcdef0123456789abcdef0123456789');
  assert.match(await page.$eval('#copy-claim-status',e=>e.textContent),/Copied/);
  console.log('PASS synthetic staff workspace, unlock and claim layouts at five widths; role fields, private search and copy');
 }finally{await browser.close()}
})().catch(e=>{console.error(e);process.exitCode=1});
