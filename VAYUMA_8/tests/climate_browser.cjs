// Synthetic DOM and mocked provider responses; does not contact a weather service.
const assert=require('node:assert/strict'), fs=require('node:fs'), path=require('node:path'), puppeteer=require('puppeteer-core');
(async()=>{
 const browser=await puppeteer.launch({executablePath:process.env.VAYUMA_CHROMIUM_PATH,headless:true,args:['--no-sandbox','--disable-dev-shm-usage']});
 try {const page=await browser.newPage();
 await page.setContent(`<form id="climateForm"><input id="location"><input id="county"><button>Search</button></form><input type="checkbox" id="keepForecast"><button id="useGps">GPS</button><div id="matchArea" hidden><select id="matches"></select><button id="showForecast">Forecast</button></div><p id="status"></p><div id="temperature"></div><div id="humidity"></div><div id="rainfall"></div><div id="wind"></div><div id="condition"></div><p id="farmingAdvice"></p><ul id="climateWarnings"></ul><div id="forecast"></div>`);
 await page.evaluate(()=>{ window.failForecast=false; window.fetch=async url=>{ const query=new URL(url,'https://example.test').searchParams; if(query.get('mode')==='search') return {ok:true,json:async()=>({success:true,matches:[{name:'Naivasha',county:'Nakuru',country:'Kenya',latitude:-0.7,longitude:36.4}]})}; if(window.failForecast) return {ok:false,json:async()=>({success:false,message:'Provider unavailable'})}; return {ok:true,json:async()=>({success:true,source:'Open-Meteo model estimate',retrieved_at:'2026-01-01T12:00:00Z',forecast_time:'2026-01-01T15:00',timezone:'Africa/Nairobi',current:{temperature:24,humidity:60,rainfall:0.2,wind:10,condition:'Rain'},forecast:[{date:'2026-01-01',max:33,min:18,rainfall:30,rain_probability:80,max_wind:42,condition:'Rain'}]})}; };});
 await page.addScriptTag({content:fs.readFileSync(path.join(__dirname,'../vayuma/frontend/js/climate-guidance.js'),'utf8')});
 await page.type('#location','Naivasha'); await page.click('#climateForm button'); await page.waitForFunction(()=>!document.querySelector('#matchArea').hidden);
 assert.match(await page.$eval('#matches',e=>e.textContent),/Naivasha, Nakuru, Kenya/); assert.equal(await page.$eval('#temperature',e=>e.textContent),'—');
 await page.click('#showForecast'); await page.waitForFunction(()=>document.querySelector('#temperature').textContent.includes('24'));
 assert.match(await page.$eval('#rainfall',e=>e.textContent),/0.2 mm/); assert.match(await page.$eval('#forecast',e=>e.textContent),/forecast rain 30 mm\/day; chance 80%/); assert.equal(await page.$$eval('#climateWarnings li',els=>els.length),3);
 await page.evaluate(()=>window.failForecast=true); await page.click('#showForecast'); await page.waitForFunction(()=>document.querySelector('#status').textContent.includes('Provider unavailable')); assert.equal(await page.$eval('#temperature',e=>e.textContent),'—'); assert.equal(await page.$eval('#forecast',e=>e.textContent),'');
 console.log('PASS mocked search, location confirmation, weather units/warnings and stale-value clearing');
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1});
