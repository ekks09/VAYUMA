// Browser inspection of pre-rendered, synthetic dashboard fixtures only.
// Set VAYUMA_DASHBOARD_FIXTURE_URL to the read-only static fixture server.
const assert = require('node:assert/strict');
const puppeteer = require('puppeteer-core');
const base = process.env.VAYUMA_DASHBOARD_FIXTURE_URL;
if (!base) throw new Error('Set VAYUMA_DASHBOARD_FIXTURE_URL');
const roles = {farmer:'farmer_dashboard.php',buyer:'buyer_dashboard.php',consumer:'consumer_dashboard.php',transport_provider:'transport_dashboard.php',input_supplier:'input_supplier_dashboard.php',manufacturer:'manufacturer_dashboard.php',warehouse_provider:'warehouse_provider_dashboard.php',retailer:'retailer_dashboard.php',agribusiness:'agribusiness_dashboard.php',cooperative:'cooperative_dashboard.php',researcher:'researcher_dashboard.php',extension_officer:'extension_dashboard.php',veterinary_officer:'veterinary_officer_dashboard.php',financial_institution:'finance_dashboard.php',insurance_provider:'insurance_provider_dashboard.php',government_officer:'government_officer_dashboard.php',ngo_partner:'ngo_partner_dashboard.php',agricultural_service_provider:'agricultural_service_provider_dashboard.php',strategic_partner:'stakeholder_dashboard.php'};
(async () => {
  const browser = await puppeteer.launch({executablePath:process.env.VAYUMA_CHROMIUM_PATH,headless:true,args:['--no-sandbox','--disable-dev-shm-usage']});
  const failures = [];
  try {
    const page = await browser.newPage();
    for (const [role,dashboard] of Object.entries(roles)) {
      try {
        await page.setViewport({width:320,height:844});
        await page.goto(`${base}/${role}.html`,{waitUntil:'load',timeout:10000});
        assert.ok(await page.$('.va-install-entry'), 'missing install entry');
        assert.ok(await page.$('.vy-user-menu__trigger'), 'missing account menu');
        assert.equal(await page.$eval('.vy-user-menu a[href="subscription.php"]',e=>e.getAttribute('href')), 'subscription.php');
        assert.equal(await page.$eval('.vy-user-menu a[href^="profile.php"]',e=>e.getAttribute('href')), `profile.php?return_to=${dashboard}`);
        await page.click('.vy-user-menu__trigger');
        assert.equal(await page.$eval('.vy-user-menu',e=>e.open),true);
        await page.keyboard.press('Escape');
        assert.equal(await page.$eval('.vy-user-menu',e=>e.open),false);
        const nav = await page.$('.va-mobile-menu-btn');
        if (nav && await nav.isIntersectingViewport()) {
          await nav.click();
          assert.equal(await page.$eval('.va-mobile-menu-btn',e=>e.getAttribute('aria-expanded')),'true');
          await page.keyboard.press('Escape');
          assert.equal(await page.$eval('.va-mobile-menu-btn',e=>e.getAttribute('aria-expanded')),'false');
        }
        // Open a mobile sidebar before activating its install button.
        if (nav && await nav.isIntersectingViewport()) {
          await nav.click();
          await page.waitForFunction(() => {
            const sidebar = document.querySelector('aside.va-sidebar, aside.va-dash-sidebar');
            return sidebar && sidebar.getBoundingClientRect().left >= -1;
          }, {timeout: 3000});
        }
        await page.$eval('.va-install-entry',e=>e.scrollIntoView({block:'center'}));
        await page.click('.va-install-entry');
        assert.ok(await page.$('.va-install-dialog'), 'install guidance did not open');
        await page.keyboard.press('Escape');
        assert.equal(await page.$('.va-install-dialog'),null);
        if (nav && await nav.isIntersectingViewport()) await page.keyboard.press('Escape');
        for (const viewport of [320, 390, 768, 1280]) {
          await page.setViewport({width:viewport,height:844});
          const width = await page.evaluate(()=>({viewport:innerWidth,document:document.documentElement.scrollWidth}));
          assert.ok(width.document <= width.viewport + 1, `horizontal overflow ${JSON.stringify(width)}`);
        }
        console.log(`PASS ${role}: install guidance, account menu, navigation and 320–1280px layouts`);
      } catch (e) { failures.push(`${role}: ${e.message}`); console.error(`FAIL ${role}: ${e.message}`); }
    }
  } finally { await browser.close(); }
  assert.deepEqual(failures,[]);
})().catch(e=>{console.error(e);process.exitCode=1});
