// Synthetic static fixtures and intercepted AI responses; never uses real accounts.
const assert=require('node:assert/strict'),puppeteer=require('puppeteer-core');
const base=process.env.VAYUMA_UI_FIXTURE_URL;if(!base)throw new Error('Set VAYUMA_UI_FIXTURE_URL');
(async()=>{const browser=await puppeteer.launch({executablePath:process.env.VAYUMA_CHROMIUM_PATH,args:['--no-sandbox','--disable-dev-shm-usage']});try{
 const page=await browser.newPage(),requests=[];await page.setRequestInterception(true);page.on('request',r=>r.url().endsWith('/ai_chat_api.php')?(requests.push(JSON.parse(r.postData())),r.respond({status:200,contentType:'application/json',body:JSON.stringify({success:true,answer:'**A useful answer**\n- First step\n- Second step'})})):r.continue());
 for(const width of [320,390,768,1024,1280]){
  await page.setViewport({width,height:850});await page.goto(base+'/portal.html',{waitUntil:'load'});
  const portal=await page.evaluate(()=>({width:document.documentElement.scrollWidth,steps:document.querySelectorAll('.onboard-steps a').length,report:!!document.querySelector('#staff-report'),consent:!!document.querySelector('input[name=consent]'),fieldFont:parseFloat(getComputedStyle(document.querySelector('[name=full_name]')).fontSize)}));
  assert.ok(portal.width<=width+1,`portal ${width}: ${JSON.stringify(portal)}`);assert.equal(portal.steps,3);assert.ok(portal.report&&portal.consent&&portal.fieldFont>=16);
  await page.goto(base+'/ai.html',{waitUntil:'load'});
  const chat=await page.evaluate(()=>({width:document.documentElement.scrollWidth,composer:document.querySelector('.vai-form').getBoundingClientRect().width,font:parseFloat(getComputedStyle(document.querySelector('textarea')).fontSize),message:document.querySelector('.vai-caption').textContent}));
  assert.ok(chat.width<=width+1&&chat.composer<=width&&chat.font>=16,`chat ${width}: ${JSON.stringify(chat)}`);assert.match(chat.message,/recent messages/);
 }
 await page.type('textarea','How do I plan a farm?');await page.keyboard.press('Enter');await page.waitForSelector('.vai-message--assistant');assert.equal(await page.$$eval('.vai-message--assistant li',nodes=>nodes.length),2);
 await page.type('textarea','Explain the first step');await page.keyboard.press('Enter');await page.waitForFunction(()=>document.querySelectorAll('.vai-message--assistant').length===2);assert.equal(requests.length,2);assert.equal(requests[1].history.length,2);assert.equal(requests[1].history[0].content,'How do I plan a farm?');
 await page.click('.vai-clear');await page.type('textarea','New question');await page.keyboard.press('Enter');await page.waitForFunction(()=>document.querySelectorAll('.vai-message--assistant').length===1);assert.deepEqual(requests[2].history,[]);
 console.log('PASS synthetic portal and AI at 320–1280px; safe formatting, follow-ups and reset');
 }finally{await browser.close()}})().catch(e=>{console.error(e);process.exitCode=1});
