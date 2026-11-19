// Browser tests for the install UI; the injected sidebar is a fixture, not a signed-in dashboard.
const assert = require('node:assert/strict');
const puppeteer = require('puppeteer-core');
const base = process.env.VAYUMA_BASE_URL || 'http://127.0.0.1:8080';
const ua = {
  ios:'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
  android:'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36'
};
(async () => {
  const browser=await puppeteer.launch({executablePath:process.env.VAYUMA_CHROMIUM_PATH,headless:true,args:['--no-sandbox','--disable-dev-shm-usage']});
  try {
    for (const device of ['ios','android']) for (const width of [320,390,568,768,1280]) {
      const context=await browser.createBrowserContext(); const page=await context.newPage(); await page.setViewport({width,height:800,isMobile:width<600,hasTouch:width<600}); await page.setUserAgent(ua[device]);
      await page.evaluateOnNewDocument(() => document.addEventListener('readystatechange', () => {
        if (document.readyState === 'interactive' && !document.querySelector('.va-sidebar')) {
          const sidebar=document.createElement('aside'); sidebar.className='va-sidebar'; sidebar.style.cssText='position:relative;width:220px'; sidebar.innerHTML='<div class="va-sidebar--bottom"></div>'; document.body.prepend(sidebar);
        }
      }));
      await page.goto(`${base}/welcome.php`,{waitUntil:'networkidle0'});
      assert.ok(await page.$('.va-install-entry')); assert.ok(await page.$('.va-install-notice'));
      if (device === 'ios') assert.match(await page.$eval('.va-install-notice p',e=>e.textContent), /Safari.*Share.*Add to Home Screen/);
      const bounds=await page.$eval('.va-install-notice',e=>{const r=e.getBoundingClientRect();return {left:r.left,right:r.right,bottom:r.bottom};});
      assert.ok(bounds.left>=0 && bounds.right<=width && bounds.bottom<=800,JSON.stringify(bounds));
      await page.click('.va-install-notice button');
      assert.match(await page.$eval('.va-install-dialog p',e=>e.textContent),device==='ios'?/Safari.*Share.*Add to Home Screen/i:/browser menu.*Install app/i);
      await page.keyboard.press('Escape'); assert.equal(await page.$('.va-install-dialog'),null);
      await page.click('.va-install-later'); assert.equal(await page.$('.va-install-notice'),null);
      await page.reload({waitUntil:'networkidle0'}); assert.equal(await page.$('.va-install-notice'),null);
      assert.ok(await page.$('.va-install-entry')); await page.click('.va-install-entry'); assert.ok(await page.$('.va-install-dialog'));
      await context.close(); console.log(`PASS ${device} ${width}px install guidance and dismissal`);
    }
    {
      const context=await browser.createBrowserContext(); const page=await context.newPage();
      await page.setUserAgent(ua.ios); await page.setViewport({width:568,height:320,isMobile:true,hasTouch:true});
      await page.goto(`${base}/welcome.php?lang=sw`,{waitUntil:'networkidle0'});
      assert.match(await page.$eval('.va-install-notice',e=>e.textContent),/Sakinisha programu/);
      await page.click('.va-install-notice button');
      assert.match(await page.$eval('.va-install-dialog',e=>e.textContent),/Safari/);
      const box=await page.$eval('.va-install-dialog',e=>{const r=e.getBoundingClientRect();return {top:r.top,bottom:r.bottom,left:r.left,right:r.right}});
      assert.ok(box.top>=0 && box.bottom<=320 && box.left>=0 && box.right<=568,JSON.stringify(box));
      await context.close(); console.log('PASS Kiswahili landscape install dialog');
    }
    {
      const context=await browser.createBrowserContext(); const page=await context.newPage();
      await page.setUserAgent(ua.ios);
      await page.evaluateOnNewDocument(() => Object.defineProperty(navigator, 'standalone', {value:true}));
      await page.goto(`${base}/welcome.php`,{waitUntil:'networkidle0'});
      assert.equal(await page.$('.va-install-notice'),null);
      await context.close(); console.log('PASS installed iOS suppresses notice');
    }
    const page=await browser.newPage(); await page.setUserAgent(ua.android); await page.setViewport({width:390,height:800});
    await page.goto(`${base}/welcome.php`,{waitUntil:'networkidle0'}); await page.evaluate(()=>{localStorage.removeItem('vayuma-install-dismissed-until'); const e=new Event('beforeinstallprompt',{cancelable:true}); e.prompt=()=>{window.__promptCalled=true;return Promise.resolve()}; e.userChoice=Promise.resolve({outcome:'accepted'}); window.dispatchEvent(e);});
    await page.click('.va-install-notice button'); assert.equal(await page.evaluate(()=>window.__promptCalled),true);
    await page.evaluate(()=>window.dispatchEvent(new Event('appinstalled'))); assert.equal(await page.$('.va-install-notice'),null);
    await page.close(); console.log('PASS Android native prompt and installed state');
  } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1});
