const assert=require('node:assert/strict');
const fs=require('node:fs'); const path=require('node:path'); const {spawnSync}=require('node:child_process');
const puppeteer=require('puppeteer-core'); const root=path.join(__dirname,'../vayuma');
(async()=>{
 const php=process.env.VAYUMA_PHP || 'php';
 const code=`$_SESSION=['user_id'=>1,'logged_in'=>true,'role'=>'extension_officer','full_name'=>'Mary Wanjiku','email'=>'mary@example.com']; require '${path.join(root,'includes/header_user_menu.php')}';`;
 const result=spawnSync(php,['-r',code],{encoding:'utf8'}); assert.equal(result.status,0,result.stderr);
 const browser=await puppeteer.launch({executablePath:process.env.VAYUMA_CHROMIUM_PATH,headless:true,args:['--no-sandbox','--disable-dev-shm-usage']});
 try {const page=await browser.newPage(); for (const width of [320,390,1280]) {
  await page.setViewport({width,height:800});
  await page.setContent(`<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"></head><body><div style="display:flex;justify-content:flex-end;padding:12px">${result.stdout}</div></body></html>`);
  for(const f of ['tokens.css','user-menu.css']) await page.addStyleTag({content:fs.readFileSync(path.join(root,'frontend/css',f),'utf8')});
  await page.addScriptTag({content:fs.readFileSync(path.join(root,'frontend/js/user-menu.js'),'utf8')});
  assert.equal(await page.$eval('.vy-user-menu__initials',e=>e.textContent),'MW');
  const rect=await page.$eval('.vy-user-menu__trigger',e=>[e.getBoundingClientRect().width,e.getBoundingClientRect().height]); assert.deepEqual(rect,[36,36]);
  await page.click('.vy-user-menu__trigger'); await page.waitForFunction(()=>document.querySelector('summary').getAttribute('aria-expanded')==='true');
  const bounds=await page.$eval('.vy-user-menu__popover',e=>({left:e.getBoundingClientRect().left,right:e.getBoundingClientRect().right})); assert.ok(bounds.left>=0&&bounds.right<=width,JSON.stringify(bounds));
  assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);
  await page.keyboard.press('Escape'); assert.equal(await page.$eval('summary',e=>e.getAttribute('aria-expanded')),'false');
  await page.click('summary'); await page.mouse.click(1,300); assert.equal(await page.$eval('details',e=>e.open),false);
  console.log(`PASS user menu fixture ${width}px`);
 }} finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1});
