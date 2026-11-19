const assert = require('node:assert/strict');
const puppeteer = require('puppeteer-core');
const base = process.env.VAYUMA_BASE_URL || 'http://127.0.0.1:8080';
(async () => {
  const browser = await puppeteer.launch({executablePath:process.env.VAYUMA_CHROMIUM_PATH,headless:true,args:['--no-sandbox','--disable-dev-shm-usage']});
  try {
    for (const width of [320,390,1280]) {
      const context=await browser.createBrowserContext(); const page=await context.newPage(); await page.setViewport({width,height:850});
      await page.goto(`${base}/welcome.php`,{waitUntil:'networkidle0'});
      await Promise.all([page.waitForNavigation({waitUntil:'networkidle0'}),page.click('.va-language-selector a[lang=sw]')]);
      assert.equal(await page.$eval('html',e=>e.lang),'sw');
      assert.match(await page.$eval('.welcome-description',e=>e.textContent),/wakulima/);
      await page.goto(`${base}/register.php`,{waitUntil:'networkidle0'});
      assert.equal(await page.$eval('.form-header h2',e=>e.textContent.trim()),'Fungua Akaunti Yako');
      assert.equal(await page.$eval('#full_name',e=>e.placeholder),'Weka jina lako kamili');
      assert.match(await page.$eval('label[for=email]',e=>e.textContent),/Barua Pepe/);
      await page.type('#password','Nenosiri123!'); await page.type('#confirm_password','tofauti');
      assert.match(await page.$eval('#matchMessage',e=>e.textContent),/hayalingani/);
      await page.click('#password + button'); assert.equal(await page.$eval('#password + button',e=>e.getAttribute('aria-label')),'Ficha nenosiri');
      await page.goto(`${base}/login.php`,{waitUntil:'networkidle0'});
      assert.equal(await page.$eval('.login-header h1',e=>e.textContent.trim()),'Karibu Tena');
      assert.equal(await page.$eval('#loginIdentifier',e=>e.placeholder),'Weka barua pepe au nambari ya simu');
      await Promise.all([page.waitForNavigation({waitUntil:'networkidle0'}),page.click('.va-language-selector a[lang=en]')]);
      assert.equal(await page.$eval('html',e=>e.lang),'en'); assert.equal(await page.$eval('.login-header h1',e=>e.textContent.trim()),'Welcome Back');
      assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth > innerWidth),false);
      await context.close(); console.log(`PASS language switching and persistence ${width}px`);
    }
    {
      const context=await browser.createBrowserContext(); const page=await context.newPage();
      await page.setRequestInterception(true);
      page.on('request', request => { const headers={...request.headers()}; delete headers.cookie; request.continue({headers}); });
      const response=await page.goto(`${base}/register.php?lang=sw`,{waitUntil:'networkidle0'});
      assert.equal(response.status(),200); assert.equal(await page.$eval('html',e=>e.lang),'sw');
      assert.equal(await page.$eval('.form-header h2',e=>e.textContent.trim()),'Fungua Akaunti Yako');
      await context.close(); console.log('PASS direct language selection without session cookie');
    }
    const context=await browser.createBrowserContext(); const page=await context.newPage(); await page.setJavaScriptEnabled(false);
    await page.goto(`${base}/register.php?lang=sw`,{waitUntil:'networkidle0'});
    assert.equal(await page.$eval('.form-header h2',e=>e.textContent.trim()),'Fungua Akaunti Yako');
    assert.equal(await page.$eval('#full_name',e=>e.placeholder),'Weka jina lako kamili');
    await context.close(); console.log('PASS translated registration without JavaScript');
  } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1});
