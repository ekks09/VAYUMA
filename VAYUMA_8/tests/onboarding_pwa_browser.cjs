// Synthetic local fixture and service worker; does not test installed Android/iOS PWAs.
const assert=require('node:assert/strict'),puppeteer=require('puppeteer-core');
const base=process.env.VAYUMA_PORTAL_FIXTURE_URL;if(!base)throw new Error('Set VAYUMA_PORTAL_FIXTURE_URL');
(async()=>{const browser=await puppeteer.launch({executablePath:process.env.VAYUMA_CHROMIUM_PATH,headless:true,args:['--no-sandbox','--disable-dev-shm-usage']});try{
 const page=await browser.newPage();await page.setViewport({width:360,height:780});await page.goto(`${base}/onboarding.html`,{waitUntil:'load'});
 await page.evaluate(async()=>{await navigator.serviceWorker.ready;});await page.reload({waitUntil:'load'});
 assert.equal(await page.evaluate(()=>!!navigator.serviceWorker.controller),true);
 const cached=await page.evaluate(async()=>{const keys=await caches.keys();const urls=[];for(const k of keys)for(const req of await (await caches.open(k)).keys())urls.push(req.url);return urls;});
 assert.ok(cached.some(u=>u.endsWith('/offline.html')));assert.ok(!cached.some(u=>/onboarding\.html|farmer_export\.php|claim_account\.php/.test(u)));
 // Fixture server drops this request, simulating a worker network failure.
 await page.goto(`${base}/onboarding.html?simulate-drop=1`,{waitUntil:'load'});
 assert.match(await page.$eval('body',e=>e.textContent),/You are offline/);assert.doesNotMatch(await page.$eval('body',e=>e.textContent),/Sample Farmer/);
 console.log('PASS synthetic PWA offline fallback; private page and export are not cached');
 }finally{await browser.close()}})().catch(e=>{console.error(e);process.exitCode=1});
