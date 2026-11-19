// Run against a local test server: NODE_PATH=/path/to/node_modules
// VAYUMA_CHROMIUM_PATH=/path/to/chromium node tests/auth_layout_browser.cjs
const assert = require('node:assert/strict');
const puppeteer = require('puppeteer-core');
const base = process.env.VAYUMA_BASE_URL || 'http://127.0.0.1:8080';
const executablePath = process.env.VAYUMA_CHROMIUM_PATH;
if (!executablePath) throw new Error('Set VAYUMA_CHROMIUM_PATH');
(async () => {
  const browser = await puppeteer.launch({executablePath, headless: true, args: ['--no-sandbox', '--disable-dev-shm-usage']});
  try {
    const page = await browser.newPage();
    for (const width of [320, 390, 1280]) for (const lang of ['en', 'sw']) {
      await page.setViewport({width, height: 900});
      await page.goto(`${base}/register.php?lang=${lang}`, {waitUntil: 'networkidle0'});
      const data = await page.evaluate(() => ({
        lang: document.documentElement.lang,
        overflow: document.documentElement.scrollWidth > innerWidth,
        fields: [...document.querySelectorAll('.input-wrapper')].map(w => {
          const input = w.querySelector('input'), icon = w.querySelector('.input-icon'), svg = icon.querySelector('svg');
          const i = input.getBoundingClientRect(), s = svg.getBoundingClientRect();
          return {id: input.id, center: Math.abs((i.top + i.height / 2) - (s.top + s.height / 2)),
            gap: i.left + parseFloat(getComputedStyle(input).paddingLeft) - s.right,
            height: i.height, pointer: getComputedStyle(icon).pointerEvents,
            ref: svg.querySelector('use').getAttribute('href').split('#')[1]};
        })
      }));
      assert.equal(data.lang, lang); assert.equal(data.overflow, false, `register overflow ${width}/${lang}`);
      assert.deepEqual(data.fields.map(x => x.ref), ['va-icon-profile','va-icon-phone','va-icon-email','va-icon-lock','va-icon-lock']);
      for (const f of data.fields) { assert.ok(f.center < 1, `${f.id} centered`); assert.ok(f.gap >= 8, `${f.id} gap`); assert.equal(f.height, 52); assert.equal(f.pointer, 'none'); }
      await page.evaluate(() => { document.documentElement.style.scrollBehavior = 'auto'; document.querySelector('#full_name').scrollIntoView({block: 'center'}); });
      const iconBox = await page.$eval('#full_name', e => { const r = e.getBoundingClientRect(); return {x: r.left + 25, y: r.top + r.height / 2}; });
      await page.mouse.click(iconBox.x, iconBox.y);
      assert.equal(await page.evaluate(() => document.activeElement.id), 'full_name');
      await page.type('#full_name', 'Example User');
      assert.equal(await page.$eval('#full_name', e => e.value), 'Example User');
      assert.notEqual(await page.$eval('#full_name', e => getComputedStyle(e).boxShadow), 'none');
      await page.type('#password', 'Strong123!'); await page.type('#confirm_password', 'wrong');
      assert.match(await page.$eval('#matchMessage', e => e.textContent), lang === 'sw' ? /hayalingani/ : /do not match/);
      for (const id of ['password', 'confirm_password']) {
        const button = `#${id} + button`;
        await page.click(button); assert.equal(await page.$eval(`#${id}`, e => e.type), 'text');
        assert.equal(await page.$eval(button, e => e.getAttribute('aria-pressed')), 'true');
        await page.click(button); assert.equal(await page.$eval(`#${id}`, e => e.type), 'password');
      }
      await page.goto(`${base}/login.php?lang=${lang}`, {waitUntil: 'networkidle0'});
      assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false, `login overflow ${width}/${lang}`);
      await page.type('#loginPassword', 'example'); await page.click('.password-toggle');
      assert.equal(await page.$eval('#loginPassword', e => e.type), 'text');
      await page.click('.password-toggle'); assert.equal(await page.$eval('#loginPassword', e => e.type), 'password');
      console.log(`PASS auth ${width}px ${lang}`);
    }
  } finally { await browser.close(); }
})().catch(e => { console.error(e); process.exitCode = 1; });
