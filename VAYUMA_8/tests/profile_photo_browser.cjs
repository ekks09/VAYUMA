const assert=require('node:assert/strict'); const fs=require('node:fs'); const path=require('node:path'); const puppeteer=require('puppeteer-core');
(async()=>{const browser=await puppeteer.launch({executablePath:process.env.VAYUMA_CHROMIUM_PATH,headless:true,args:['--no-sandbox']});try{
 const page=await browser.newPage(); await page.setContent('<form id="vy-profile-form"><input type="file" id="avatar" name="avatar"><p id="vy-photo-status"></p><button>Save</button></form>');
 await page.addScriptTag({content:fs.readFileSync(path.join(__dirname,'../vayuma/frontend/js/profile-photo.js'),'utf8')});
 await page.evaluate(async()=>{const c=document.createElement('canvas');c.width=1200;c.height=800; const ctx=c.getContext('2d');ctx.fillStyle='green';ctx.fillRect(0,0,c.width,c.height);const blob=await new Promise(r=>c.toBlob(r,'image/png'));const dt=new DataTransfer();dt.items.add(new File([blob],'photo.png',{type:'image/png'}));const el=document.querySelector('#avatar');el.files=dt.files;el.dispatchEvent(new Event('change'));});
 await page.waitForFunction(()=>document.querySelector('#vy-photo-status').textContent==='Photo ready to save.');
 const result=await page.evaluate(async()=>{const f=document.querySelector('#avatar').files[0];const b=await createImageBitmap(f);const result={type:f.type,size:f.size,width:b.width,height:b.height};b.close();return result;});
 assert.equal(result.type,'image/jpeg'); assert.equal(result.width,160);assert.equal(result.height,160);assert.ok(result.size<=81920);console.log('PASS client-side image crop and compression');
 }finally{await browser.close();}})().catch(e=>{console.error(e);process.exitCode=1});
