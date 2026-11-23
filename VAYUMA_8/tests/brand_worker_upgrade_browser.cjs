// Synthetic local service-worker upgrade; does not test installed mobile PWAs.
const http=require('node:http'),fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const puppeteer=require('puppeteer-core'),root=path.resolve(__dirname,'../vayuma');
const current=fs.readFileSync(path.join(root,'sw.js'),'utf8');let old=true;
const oldWorker="self.addEventListener('install',e=>e.waitUntil(caches.open('vayuma-public-v16').then(c=>c.put('/old-logo',new Response('old'))))); self.addEventListener('activate',e=>e.waitUntil(self.clients.claim()));";
const server=http.createServer((req,res)=>{
 const pathname=new URL(req.url,'http://fixture').pathname;
 if(pathname==='/sw.js'){res.setHeader('Content-Type','application/javascript');res.setHeader('Cache-Control','no-store');return res.end(old?oldWorker:current);}
 if(pathname==='/offline.html'){res.setHeader('Content-Type','text/html');return res.end('Offline');}
 if(pathname==='/'){res.setHeader('Content-Type','text/html');return res.end('<!doctype html><title>worker fixture</title>');}
 res.statusCode=404;res.end('Not found');
});
(async()=>{
 await new Promise(resolve=>server.listen(0,'0.0.0.0',resolve));
 const browser=await puppeteer.launch({executablePath:process.env.VAYUMA_CHROMIUM_PATH,args:['--no-sandbox','--disable-dev-shm-usage']});
 try{
  const url='http://127.0.0.1:'+server.address().port+'/';let page=await browser.newPage();await page.goto(url);
  await page.evaluate(async()=>{await navigator.serviceWorker.register('/sw.js');await navigator.serviceWorker.ready;});
  assert.ok((await page.evaluate(()=>caches.keys())).includes('vayuma-public-v16'));
  old=false;await page.evaluate(async()=>{await (await navigator.serviceWorker.getRegistration()).update();});
  await page.waitForFunction(async()=>!!(await navigator.serviceWorker.getRegistration()).waiting,{timeout:10000});
  await page.close();page=await browser.newPage();await page.goto(url);
  await page.waitForFunction(async()=>{const keys=await caches.keys();return keys.includes('vayuma-public-v19')&&!keys.includes('vayuma-public-v16');},{timeout:15000});
  console.log('PASS synthetic browser service-worker upgrade and old cache cleanup');
 }finally{await browser.close();server.close();}
})().catch(e=>{console.error(e);server.close();process.exitCode=1});
