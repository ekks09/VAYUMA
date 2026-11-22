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
        if (role==='farmer') {
          const sprite=await page.evaluate(async()=>{const r=await fetch('frontend/images/icons.svg');const d=new DOMParser().parseFromString(await r.text(),'image/svg+xml');return {error:!!d.querySelector('parsererror'),weather:!!d.getElementById('va-icon-weather'),soil:!!d.getElementById('va-icon-soil'),ai:!!d.getElementById('va-icon-ai')}});
          assert.deepEqual(sprite,{error:false,weather:true,soil:true,ai:true},'broken shared icon sprite');
        }
        assert.ok(await page.$('.vy-user-menu__trigger'), 'missing account menu');
        assert.equal(await page.$eval('.vy-user-menu a[href="subscription.php"]',e=>e.getAttribute('href')), 'subscription.php');
        assert.ok(await page.$('.vy-user-menu a[href="role.php"]'), 'missing category link');
        assert.ok(await page.$('.vy-user-menu a[href="onboarding.php"]'), 'missing staff access');
        assert.ok(await page.$('.vy-user-menu a[href="feature_access.php?feature=ai"]'), 'missing AI access');
        assert.match(await page.$eval('.vy-user-menu a[href^="profile.php"]',e=>e.textContent), /Profile & Settings/);
        const logo=await page.$eval('aside img[src="assets/images/brand/app-icon-192.png"]', e=>({width:e.getBoundingClientRect().width,natural:e.naturalWidth}));
        assert.ok(logo.natural>0 && logo.width<=48, 'sidebar brand missing or oversized');
        if (role==='buyer' || role==='consumer') {
          assert.ok(await page.$('.dashboard-quick-links a[href^="profile.php"]'), 'missing profile shortcut');
          assert.ok(await page.$('.dashboard-quick-links a[href="feature_access.php?feature=ai"]'), 'missing AI shortcut');
          assert.ok(await page.$('aside a[href="feature_access.php?feature=ai"] use[href$="va-icon-ai"]'), 'incorrect AI icon');
        }
        if (role==='buyer') assert.ok(await page.$('.va-main--content h2#buyer-recent-orders'), 'recent orders outside content');
        if (role==='farmer') for(const href of ['climate.php','farming_score.php','crop_opportunities.php']) assert.ok(await page.$(`section[aria-labelledby="farm-insights-title"] a[href="${href}"]`),`missing ${href} insight`);
        assert.ok(await page.$('aside .va-language-selector'), 'language selector is not in the sidebar');
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
        for (const viewport of [320, 390, 767, 768, 1024, 1280]) {
          await page.setViewport({width:viewport,height:844});
          const layout = await page.evaluate(()=>{
            const sidebar = document.querySelector('aside.va-sidebar, aside.va-dash-sidebar');
            const main = document.querySelector('.va-main--content, .va-dash-main, .va-generic-main');
            const toggle = document.querySelector('.va-mobile-menu-btn');
            return {viewport:innerWidth, document:document.documentElement.scrollWidth,
              sidebarRight:sidebar.getBoundingClientRect().right, mainLeft:main.getBoundingClientRect().left,
              toggleVisible:getComputedStyle(toggle).display !== 'none'};
          });
          assert.ok(layout.document <= layout.viewport + 1, `horizontal overflow ${JSON.stringify(layout)}`);
          if (['farmer','buyer','consumer'].includes(role) && viewport < 768) {
            const bad=await page.$$eval('.va-main--content a.va-btn, .va-main--content button.va-btn, .farmer-quick-links nav a, .dashboard-quick-links nav a',links=>links.filter(e=>e.getBoundingClientRect().height>0&&e.getBoundingClientRect().height<43.5).map(e=>e.textContent.trim()));
            assert.deepEqual(bad,[],`small dashboard tap targets: ${bad.join(', ')}`);
            assert.ok(await page.$('.vayuma-ai-fab'), 'floating AI launcher is missing');
          }
          if (viewport < 768) { const target=await page.$eval('.va-mobile-menu-btn',e=>{const r=e.getBoundingClientRect();return {width:r.width,height:r.height};}); assert.ok(target.width >= 44 && target.height >= 44, `menu touch target ${JSON.stringify(target)}`); }
          assert.equal(layout.toggleVisible, viewport < 768, `incorrect menu breakpoint ${JSON.stringify(layout)}`);
          if (viewport >= 768) assert.ok(layout.sidebarRight <= layout.mainLeft + 1, `sidebar overlaps content ${JSON.stringify(layout)}`);
        }
        console.log(`PASS ${role}: install guidance, account menu, navigation and 320–1280px layouts`);
      } catch (e) { failures.push(`${role}: ${e.message}`); console.error(`FAIL ${role}: ${e.message}`); }
    }
  } finally { await browser.close(); }
  assert.deepEqual(failures,[]);
})().catch(e=>{console.error(e);process.exitCode=1});
