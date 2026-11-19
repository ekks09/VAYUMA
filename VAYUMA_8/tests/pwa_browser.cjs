// Run with puppeteer-core installed, VAYUMA_CHROMIUM_PATH set and a local test server.
const assert = require('node:assert/strict');
const puppeteer = require('puppeteer-core');
const base = process.env.VAYUMA_BASE_URL || 'http://127.0.0.1:8080';
(async () => {
  if (!process.env.VAYUMA_CHROMIUM_PATH) throw new Error('Set VAYUMA_CHROMIUM_PATH');
  const browser = await puppeteer.launch({executablePath: process.env.VAYUMA_CHROMIUM_PATH, headless: true, args: ['--no-sandbox', '--disable-dev-shm-usage']});
  try {
    const page = await browser.newPage();
    await page.goto(`${base}/welcome.php`, {waitUntil: 'networkidle0'});
    await page.evaluate(() => navigator.serviceWorker.ready);
    await page.reload({waitUntil: 'networkidle0'});
    assert.ok(await page.evaluate(() => !!navigator.serviceWorker.controller));
    const client = await page.createCDPSession();
    const manifest = await client.send('Page.getAppManifest');
    assert.equal(manifest.errors.length, 0, JSON.stringify(manifest.errors));
    const installability = await client.send('Page.getInstallabilityErrors');
    assert.equal(installability.installabilityErrors.length, 0, JSON.stringify(installability.installabilityErrors));
    const parsed = JSON.parse(manifest.data);
    assert.equal(parsed.start_url, './welcome.php');
    assert.deepEqual(parsed.icons.map(x => [x.sizes,x.purpose]), [['192x192','any'], ['512x512','any'], ['192x192','maskable'], ['512x512','maskable']]);
    assert.equal(parsed.display, 'standalone');
    assert.equal(new URL(parsed.scope, manifest.url).pathname, '/');
    assert.equal(await page.evaluate(async () => (await navigator.serviceWorker.ready).scope), `${base}/`);
    for (const icon of parsed.icons) {
      const response = await page.goto(new URL(icon.src, `${base}/manifest.json`).href);
      assert.equal(response.status(), 200); assert.match(response.headers()['content-type'], /^image\/png/);
    }
    await page.goto(`${base}/welcome.php`, {waitUntil: 'networkidle0'});
    await page.evaluate(async () => {
      await fetch('assets/css/style.css');
      await fetch('api/subscription_status.php');
      await fetch('mpesa_receipt.php');
      await fetch('auth/register_process.php', {method:'POST'});
    });
    const cached = await page.evaluate(async () => (await Promise.all((await caches.keys()).map(async key => (await (await caches.open(key)).keys()).map(r => new URL(r.url).pathname)))).flat());
    assert.ok(cached.includes('/offline.html'));
    assert.ok(cached.includes('/assets/css/style.css'));
    assert.ok(!cached.some(path => /\.php$|\/api\/|\/auth\//.test(path)), JSON.stringify(cached));
    const worker = browser.targets().find(t => t.type() === 'service_worker' && t.url().endsWith('/sw.js'));
    assert.ok(worker, 'worker target');
    const workerClient = await worker.createCDPSession();
    await workerClient.send('Network.enable');
    await workerClient.send('Network.emulateNetworkConditions', {offline: true, latency: 0, downloadThroughput: 0, uploadThroughput: 0});
    await page.setOfflineMode(true);
    const response = await page.goto(`${base}/login.php`, {waitUntil:'domcontentloaded'});
    assert.equal(response.status(), 200);
    assert.match(await page.$eval('h1', el => el.textContent), /offline/i);
    assert.equal(await page.evaluate(async () => {try { await fetch('auth/register_process.php', {method:'POST'}); return true; } catch (_) { return false; }}), false);
    console.log('PASS manifest, worker, public cache, private/POST exclusion and offline fallback');
  } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exitCode = 1;});
