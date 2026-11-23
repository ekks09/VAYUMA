// Synthetic browser fixture: fake PHP/DB and fake CSV response, not a live deployment.
const assert=require('node:assert/strict'),fs=require('node:fs'),os=require('node:os'),path=require('node:path');
const puppeteer=require('puppeteer-core');
const base=process.env.VAYUMA_PORTAL_FIXTURE_URL;
if(!base)throw new Error('Set VAYUMA_PORTAL_FIXTURE_URL');
(async()=>{
 const browser=await puppeteer.launch({executablePath:process.env.VAYUMA_CHROMIUM_PATH,headless:true,args:['--no-sandbox','--disable-dev-shm-usage']});
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'vayuma-download-'));
 try {
  const page=await browser.newPage(); await page.setViewport({width:360,height:780,isMobile:true,hasTouch:true,deviceScaleFactor:1});
  await page.setRequestInterception(true);
  page.on('request',req=>{
   if(req.url().endsWith('/farmer_export.php') && req.method()==='POST') req.respond({status:200,headers:{'Content-Type':'text/csv','Content-Disposition':'attachment; filename="vayuma-farmers.csv"','Cache-Control':'private, no-store'},body:'Full name,Phone\nSample Farmer,0712345678\n'});
   else req.continue();
  });
  await page.goto(`${base}/onboarding.html`,{waitUntil:'load'});
  const style=await page.$eval('.onboard-actions a',e=>({height:e.getBoundingClientRect().height,border:getComputedStyle(e).borderRadius}));
  assert.ok(style.height>=44); assert.notEqual(style.border,'0px');
  assert.match(await page.$eval('.onboard-note',e=>e.textContent),/internet connection is required/i);
  assert.match(await page.$eval('#add-person',e=>e.textContent),/separate signed-out browser/i);
  assert.ok(await page.$('form[action="farmer_export.php"] button[type="submit"]'));
  assert.ok(await page.$('input[name="action"][value="lock_staff"]'));
  await page.setOfflineMode(true);
  const blocked=await page.$eval('#onboarding-form',form=>{const e=new Event('submit',{bubbles:true,cancelable:true});form.dispatchEvent(e);return e.defaultPrevented;});
  assert.equal(blocked,true);assert.equal(await page.$eval('#onboard-offline',e=>e.hidden),false);
  assert.equal(await page.$eval('form[action="farmer_export.php"]',form=>{const e=new Event('submit',{bubbles:true,cancelable:true});form.dispatchEvent(e);return e.defaultPrevented;}),true);
  await page.setOfflineMode(false); await page.evaluate(()=>window.dispatchEvent(new Event('online')));
  assert.equal(await page.$eval('#onboard-offline',e=>e.hidden),true);
  const session=await page.createCDPSession();await session.send('Page.setDownloadBehavior',{behavior:'allow',downloadPath:dir});
  await page.click('form[action="farmer_export.php"] button[type="submit"]');
  for(let i=0;i<50 && !fs.existsSync(path.join(dir,'vayuma-farmers.csv'));i++) await new Promise(resolve=>setTimeout(resolve,100));
  assert.match(fs.readFileSync(path.join(dir,'vayuma-farmers.csv'),'utf8'),/Sample Farmer/);
  console.log('PASS synthetic mobile offline handling, action sizing, claim guidance and CSV browser download');
 } finally {await browser.close();fs.rmSync(dir,{recursive:true,force:true});}
})().catch(e=>{console.error(e);process.exitCode=1});
