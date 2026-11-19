// Public navigation simulation only: not a real installed app or authenticated session.
const assert=require('node:assert/strict'); const puppeteer=require('puppeteer-core');
const base=process.env.VAYUMA_BASE_URL || 'http://127.0.0.1:8080';
(async()=>{const browser=await puppeteer.launch({executablePath:process.env.VAYUMA_CHROMIUM_PATH,headless:true,args:['--no-sandbox','--disable-dev-shm-usage']});try{
 const page=await browser.newPage(); await page.setViewport({width:390,height:844,isMobile:true,hasTouch:true});
 await page.evaluateOnNewDocument(()=>Object.defineProperty(navigator,'standalone',{value:true,configurable:true}));
 await page.goto(`${base}/welcome.php`,{waitUntil:'networkidle0'}); assert.equal(await page.$('.va-install-notice'),null);
 await Promise.all([page.waitForNavigation({waitUntil:'networkidle0'}),page.click('a[href="login.php"]')]); assert.equal(new URL(page.url()).pathname,'/login.php');
 await Promise.all([page.waitForNavigation({waitUntil:'networkidle0'}),page.click('a[href="register.php"]')]); assert.equal(new URL(page.url()).pathname,'/register.php');
 await page.goBack({waitUntil:'networkidle0'}); assert.equal(new URL(page.url()).pathname,'/login.php');
 await page.goBack({waitUntil:'networkidle0'}); assert.equal(new URL(page.url()).pathname,'/welcome.php');
 assert.equal(await page.$('.va-install-notice'),null); console.log('PASS simulated standalone public navigation and browser back');
 } finally {await browser.close();}})().catch(e=>{console.error(e);process.exitCode=1});
