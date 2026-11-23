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
  assert.match(await page.$eval('#add-person',e=>e.textContent),/separate signed-out browser/i);
  assert.ok(await page.$('input[name="action"][value="reset_claim"]'));
  await page.goto(`${base}/claim.html`,{waitUntil:'load'});
  assert.ok(await page.$('input[name="farmer_id"]'));
  assert.equal(await page.$('input[name="code"]'),null);
  console.log('PASS synthetic staff workspace, unlock and claim layouts at five widths; role fields, private search and ID claiming');
 }finally{await browser.close()}
})().catch(e=>{console.error(e);process.exitCode=1});
