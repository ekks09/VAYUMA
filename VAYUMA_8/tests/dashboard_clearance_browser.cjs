// Synthetic static fixtures only: guard/database behavior and real devices are not tested.
const assert=require('node:assert/strict'),puppeteer=require('puppeteer-core');
const base=process.env.VAYUMA_DASHBOARD_FIXTURE_URL;if(!base)throw Error('Set VAYUMA_DASHBOARD_FIXTURE_URL');
const roles=['farmer','buyer','consumer','transport_provider','researcher','extension_officer','cooperative','financial_institution','input_supplier','manufacturer','warehouse_provider','retailer','agribusiness','veterinary_officer','insurance_provider','government_officer','ngo_partner','agricultural_service_provider','strategic_partner'];
(async()=>{const browser=await puppeteer.launch({executablePath:process.env.VAYUMA_CHROMIUM_PATH,args:['--no-sandbox','--disable-dev-shm-usage']});const failures=[];try{
for(const role of roles){const page=await browser.newPage();try{for(const width of [320,390,767,768,1024,1280]){
 await page.setViewport({width,height:844});await page.goto(`${base}/${role}.html`,{waitUntil:'load'});
 const info=await page.evaluate(()=>{
  const fab=document.querySelector('.vayuma-ai-fab');const targets=[...document.querySelectorAll('main a,main button,main input,main select,main textarea')].filter(e=>e.offsetWidth&&e.offsetHeight&&!e.closest('.vayuma-chat'));
  const clashes=[];for(const e of targets){e.scrollIntoView({block:'center'});let a=e.getBoundingClientRect(),b=fab?.getBoundingClientRect();if(b&&a.left<b.right&&a.right>b.left&&a.top<b.bottom&&a.bottom>b.top)clashes.push((e.innerText||e.getAttribute('aria-label')||e.tagName).trim().slice(0,60));}
  const main=document.querySelector('.va-main--content,main.va-dash-main,main.va-generic-main');const sidebar=document.querySelector('aside.va-sidebar,aside.va-dash-sidebar');return {overflow:document.documentElement.scrollWidth-innerWidth,hasFab:!!fab,clashes,mainWidth:main?.getBoundingClientRect().width,mainLeft:main?.getBoundingClientRect().left,sidebarRight:sidebar?.getBoundingClientRect().right};});
 assert.equal(info.overflow,0,`${width}: horizontal overflow`);assert.ok(info.hasFab,`${width}: no AI launcher`);assert.deepEqual(info.clashes,[],`${width}: launcher overlaps controls`);if(width===1280){assert.ok(info.mainWidth>width*.45,`${role}: collapsed desktop content ${info.mainWidth}`);assert.ok(info.sidebarRight<=info.mainLeft+1,`${role}: sidebar overlaps content`);}
 }console.log(`PASS ${role}: launcher control clearance and no page overflow`);}catch(e){failures.push(`${role}: ${e.message}`);}finally{await page.close();}}
}finally{await browser.close();}if(failures.length){console.error(failures.join('\n'));process.exitCode=1;}})().catch(e=>{console.error(e);process.exitCode=1});
