// Synthetic browser layout test; no real authentication or provider calls.
const assert = require('node:assert/strict');
const fs = require('node:fs'), path = require('node:path');
const puppeteer = require('puppeteer-core');
const root = path.resolve(__dirname, '../vayuma');
(async () => {
 const browser = await puppeteer.launch({executablePath:process.env.VAYUMA_CHROMIUM_PATH, args:['--no-sandbox','--disable-dev-shm-usage']});
 try {
  const page = await browser.newPage();
  for (const [width,height] of [[320,568],[390,844],[768,768],[1280,850]]) {
   await page.setViewport({width,height});
   await page.setContent('<!doctype html><html lang="en"><head><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="vayuma-ai-csrf" content="fixture"></head><body></body></html>');
   for (const f of ['frontend/css/tokens.css','frontend/css/components.css','frontend/css/extension-workspace.css','frontend/css/ai-chat.css']) await page.addStyleTag({content:fs.readFileSync(path.join(root,f),'utf8')});
   // Simulate an inherited full-width button rule from a host page.
   await page.addStyleTag({content:'button { width: 100%; }'});
   await page.addScriptTag({content:fs.readFileSync(path.join(root,'frontend/js/ai-chat.js'),'utf8')});
   await page.click('.vayuma-ai-fab');
   const metrics = await page.evaluate(() => {
    const rect=s=>document.querySelector(s).getBoundingClientRect().toJSON();
    const input=rect('.vai-form textarea'),send=rect('.vai-form button'),form=rect('.vai-form'),drawer=rect('.vayuma-chat--drawer'),prompts=document.querySelector('.vai-prompts');
    return {input,send,form,drawer,prompts:rect('.vai-prompts'),scroll:prompts.scrollWidth,client:prompts.clientWidth,disabled:document.querySelector('.vai-form button').disabled,placeholder:document.querySelector('textarea').placeholder};
   });
   assert.ok(metrics.input.width>=140,`${width} input: ${JSON.stringify(metrics)}`);
   assert.ok(metrics.send.width<120 && metrics.input.right<=metrics.send.left && metrics.send.right<=metrics.form.right,`${width} send: ${JSON.stringify(metrics)}`);
   assert.ok(metrics.scroll<=metrics.client+1,`${width} prompts overflow`);
   assert.ok(metrics.drawer.height<=height && (height<700 || metrics.drawer.height<height*.95),`${width} drawer size: ${JSON.stringify(metrics)}`);
   assert.ok(!metrics.disabled && metrics.placeholder);
   await page.click('.vai-context summary');
   assert.equal(await page.$eval('.vai-context',e=>e.open),true);
   assert.ok(await page.$eval('.vai-compose',e=>e.scrollHeight>=e.clientHeight));
  }
  console.log('PASS synthetic AI drawer layout at 320–1280px');
 } finally { await browser.close(); }
})().catch(e=>{console.error(e);process.exitCode=1});
