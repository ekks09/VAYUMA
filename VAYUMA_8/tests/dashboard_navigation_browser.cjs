const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const puppeteer = require('puppeteer-core');
const root = path.join(__dirname, '../vayuma');
(async () => {
  const source = fs.readFileSync(path.join(root, 'extension_dashboard.php'), 'utf8');
  const aside = source.match(/<aside class="va-dash-sidebar" id="vaDashSidebar"[^>]*>[\s\S]*?<\/aside>/)[0].replace(/<\?php[\s\S]*?\?>/g,'').replace(/<\?=[\s\S]*?\?>/g,'');
  assert.match(source, /class="va-mobile-menu-btn" aria-controls="vaDashSidebar"/);
  assert.match(source, /frontend\/js\/vayuma-ui\.js/);
  const browser = await puppeteer.launch({executablePath:process.env.VAYUMA_CHROMIUM_PATH,headless:true,args:['--no-sandbox','--disable-dev-shm-usage']});
  try {
    const page=await browser.newPage();
    for (const width of [390,1280]) {
      await page.setViewport({width,height:850});
      await page.setContent(`<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"></head><body>${aside}<main class="va-dash-main"><button type="button" class="va-mobile-menu-btn" aria-controls="vaDashSidebar" aria-expanded="false" aria-label="Open navigation">Menu</button></main></body></html>`);
      for (const file of ['tokens.css','components.css','components_addition.css','layout.css','pages.css','dashboard-shell.css']) await page.addStyleTag({content:fs.readFileSync(path.join(root,'frontend/css',file),'utf8')});
      await page.addScriptTag({content:fs.readFileSync(path.join(root,'frontend/js/vayuma-ui.js'),'utf8')});
      const display=await page.$eval('.va-mobile-menu-btn',e=>getComputedStyle(e).display);
      if(width===390){
        assert.notEqual(display,'none'); assert.equal(await page.$eval('#vaDashSidebar',e=>e.getAttribute('aria-hidden')),'true');
        await page.click('.va-mobile-menu-btn'); assert.equal(await page.$eval('.va-mobile-menu-btn',e=>e.getAttribute('aria-expanded')),'true');
        assert.equal(await page.$eval('#vaDashSidebar',e=>e.classList.contains('va-dash-sidebar--open')),true);
        await page.keyboard.press('Escape'); assert.equal(await page.$eval('.va-mobile-menu-btn',e=>e.getAttribute('aria-expanded')),'false');
      } else { assert.equal(display,'none'); assert.equal(await page.$eval('#vaDashSidebar',e=>e.inert),false); }
      console.log(`PASS extension sidebar fixture ${width}px`);
    }
  } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1});
