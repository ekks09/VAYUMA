// Synthetic populated fixtures only; no real marketplace, orders or accounts.
const assert=require('node:assert/strict'),puppeteer=require('puppeteer-core');
const base=process.env.VAYUMA_DASHBOARD_FIXTURE_URL;if(!base)throw Error('Set VAYUMA_DASHBOARD_FIXTURE_URL');
(async()=>{const b=await puppeteer.launch({executablePath:process.env.VAYUMA_CHROMIUM_PATH,args:['--no-sandbox','--disable-dev-shm-usage']});try{for(const role of ['buyer','consumer']){const page=await b.newPage();await page.setViewport({width:390,height:844});await page.goto(`${base}/${role}-filled.html`,{waitUntil:'load'});
 const links=await page.$$eval('a[aria-label="View Fresh Mangoes details"]',es=>es.map(e=>({tag:e.tagName,href:e.getAttribute('href'),parentClickable:!!e.closest('[onclick]')})));assert.ok(links.length>=1);for(const item of links){assert.equal(item.tag,'A');assert.equal(item.href,'produce_details.php?id=11');assert.equal(item.parentClickable,false)}
 await page.evaluate(()=>document.querySelector('a[aria-label="View Fresh Mangoes details"]').focus());assert.equal(await page.evaluate(()=>document.activeElement?.getAttribute('aria-label')),'View Fresh Mangoes details');
 if(role==='buyer'){const remove=await page.$('button[aria-label="Remove Fresh Mangoes from saved items"]');assert.ok(remove);}
 console.log(`PASS ${role}: populated listing links have distinct keyboard-accessible names`);await page.close();}}finally{await b.close()}})().catch(e=>{console.error(e);process.exitCode=1});
