// Requires the read-only synthetic admin.html fixture; never uses a real account or database.
const assert = require('node:assert/strict');
const puppeteer = require('puppeteer-core');
const base = process.env.VAYUMA_DASHBOARD_FIXTURE_URL;
if (!base) throw new Error('Set VAYUMA_DASHBOARD_FIXTURE_URL');
(async () => {
  const browser = await puppeteer.launch({executablePath:process.env.VAYUMA_CHROMIUM_PATH, headless:true, args:['--no-sandbox','--disable-dev-shm-usage']});
  try {
    const page = await browser.newPage();
    for (const width of [320,390,767,768,1024,1280]) {
      await page.setViewport({width,height:844});
      await page.goto(`${base}/admin.html`, {waitUntil:'load'});
      for (const url of ['admin_verification.php','admin_subscriptions.php','admin.php','dashboard.php']) assert.ok(await page.$(`aside a[href="${url}"]`), url);
      assert.ok(await page.$('aside .va-language-selector'));
      assert.ok(await page.$('.vy-user-menu a[href="role.php"]'));
      const layout = await page.evaluate(() => {
        const side=document.querySelector('aside').getBoundingClientRect();
        const main=document.querySelector('.va-generic-main').getBoundingClientRect();
        return {scroll:document.documentElement.scrollWidth, sidebarRight:side.right, mainLeft:main.left};
      });
      assert.ok(layout.scroll <= width+1, `overflow at ${width}: ${JSON.stringify(layout)}`);
      if (width >= 768) assert.ok(layout.sidebarRight <= layout.mainLeft+1, `overlap at ${width}`);
      else {
        await page.click('.va-mobile-menu-btn');
        assert.equal(await page.$eval('.va-mobile-menu-btn', el => el.getAttribute('aria-expanded')), 'true');
        await page.keyboard.press('Escape');
        assert.equal(await page.$eval('.va-mobile-menu-btn', el => el.getAttribute('aria-expanded')), 'false');
      }
      console.log(`PASS synthetic admin dashboard ${width}px`);
    }
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
