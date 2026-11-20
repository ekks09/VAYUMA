// Browser checks against synthetic pre-rendered PHP fixtures; not a live login test.
// VAYUMA_MOBILE_FIXTURE_URL serves subscription.php.html, profile_setup.php.html,
// choose_stakeholder.php.html and farmer_dashboard.php.html plus their static assets.
const assert = require('node:assert/strict');
const puppeteer = require('puppeteer-core');
const base = process.env.VAYUMA_MOBILE_FIXTURE_URL;
if (!base) throw new Error('Set VAYUMA_MOBILE_FIXTURE_URL');
(async () => {
  const browser = await puppeteer.launch({executablePath: process.env.VAYUMA_CHROMIUM_PATH, headless:true, args:['--no-sandbox','--disable-dev-shm-usage']});
  try {
    const page = await browser.newPage();
    await page.setCacheEnabled(false);
    for (const name of ['subscription.php','profile_setup.php','choose_stakeholder.php','farmer_dashboard.php']) {
      for (const width of [320, 360, 390, 768, 1024]) {
        await page.setViewport({width, height: 850, isMobile: width < 768, hasTouch: width < 768});
        assert.equal((await page.goto(`${base}/${name}.html`, {waitUntil:'load'})).status(), 200);
        const state = await page.evaluate(() => {
          const rect = el => { const r=el.getBoundingClientRect(); return {left:r.left,right:r.right,top:r.top,bottom:r.bottom,width:r.width,height:r.height}; };
          const top = document.querySelector('.va-topbar');
          const brand = document.querySelector('.va-topbar--brand');
          const user = document.querySelector('.va-topbar--user');
          const nav = document.querySelector('.va-topbar--nav');
          const logo = document.querySelector('.logo-area img');
          return {scroll:document.documentElement.scrollWidth, brand:brand&&rect(brand), user:user&&rect(user), nav:nav&&rect(nav), header:top&&rect(top), logo:logo&&{...rect(logo),naturalWidth:logo.naturalWidth}, links:[...document.querySelectorAll('.va-subscription-navigation a')].map(rect)};
        });
        assert.ok(state.scroll <= width+1, `${name} ${width}: horizontal overflow`);
        if (state.brand && state.user) assert.ok(state.brand.right <= state.user.left, `${name} ${width}: brand/user overlap`);
        if (state.nav && width < 768) assert.ok(state.nav.top >= state.brand.bottom, `${name} ${width}: mobile nav overlaps brand`);
        for (const r of state.links) assert.ok(r.left >= -1 && r.right <= width+1 && r.height >= 44, `${name} ${width}: nav link bounds`);
        if (state.logo) assert.ok(state.logo.naturalWidth > 0 && state.logo.width <= 48 && state.logo.left >= 0 && state.logo.right <= width, `${name} ${width}: logo missing or oversized`);
        const staffLink = await page.$('.vy-user-menu__link[href="onboarding.php"]');
        assert.ok(staffLink, `${name} ${width}: authorized staff access missing`);
        await page.click('.vy-user-menu__trigger');
        const staffRect = await staffLink.evaluate(e => {const r=e.getBoundingClientRect();return {left:r.left,right:r.right,height:r.height};});
        assert.ok(staffRect.left >= -1 && staffRect.right <= width+1 && staffRect.height >= 44, `${name} ${width}: staff link bounds`);
        await page.click('.vy-user-menu__trigger');
        if (name === 'subscription.php') assert.equal(state.nav, null, 'duplicated header links');
        if (name === 'farmer_dashboard.php' && width < 768) {
          await page.click('.va-mobile-menu-btn');
          assert.equal(await page.$eval('.va-mobile-menu-btn', e=>e.getAttribute('aria-expanded')), 'true');
          await page.keyboard.press('Escape');
          assert.equal(await page.$eval('.va-mobile-menu-btn', e=>e.getAttribute('aria-expanded')), 'false');
        }
      }
    }
    for (const width of [320,360,390,768,1024]) {
      await page.setViewport({width,height:850});
      await page.goto(`${base}/onboarding.php.html`, {waitUntil:'load'});
      const unlock=await page.evaluate(()=>{const input=document.querySelector('#staff-passphrase'),button=document.querySelector('button[type=submit]');return {scroll:document.documentElement.scrollWidth, font:parseFloat(getComputedStyle(input).fontSize),height:input.getBoundingClientRect().height,button:button.getBoundingClientRect().height,autocomplete:input.autocomplete}});
      assert.ok(unlock.scroll<=width+1 && unlock.font>=16 && unlock.height>=44 && unlock.button>=44 && unlock.autocomplete==='off', `unlock ${width}: ${JSON.stringify(unlock)}`);
    }
    await page.setViewport({width:320,height:850});
    for (const [source, id] of [['register.php','phone'],['login.php','loginIdentifier']]) {
      await page.goto(`${base}/${source}.html`, {waitUntil:'load'});
      const link=await page.$('[data-claim-source]'); assert.ok(link, `${source}: claim link missing`);
      const rect=await link.evaluate(e=>({height:e.getBoundingClientRect().height,scroll:document.documentElement.scrollWidth}));
      assert.ok(rect.height>=44 && rect.scroll<=321, `${source}: claim link layout`);
      await page.$eval(`#${id}`,e=>{e.value='+254 712 345 678'});
      await link.evaluate(e=>{e.addEventListener('click', event=>event.preventDefault(), {once:true});e.click();});
      await page.goto(`${base}/claim_account.php.html`, {waitUntil:'load'});
      assert.equal(await page.$eval('#claim-phone',e=>e.value),'+254 712 345 678');
    }
    console.log('PASS 20 synthetic header layouts, staff menu, farmer drawer, 5 unlock layouts and claim phone transfer');
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
