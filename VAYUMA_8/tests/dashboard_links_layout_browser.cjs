// Browser layout checks for mocked, pre-rendered sidebar destinations; not a real login/DB test.
const assert = require('node:assert/strict');
const puppeteer = require('puppeteer-core');
const base = process.env.VAYUMA_DASHBOARD_FIXTURE_URL;
if (!base) throw new Error('Set VAYUMA_DASHBOARD_FIXTURE_URL');
const pages = {
  farmer:['add_produce.php','budget_planner.php','crop_calendar.php','crop_opportunities.php','disease_helper.php','farming_score.php','my_produce.php','my_orders.php','seller_orders.php','profile.php','soil_analysis.php','climate.php','ai_advisor.php','market_connect.php','subscription.php'],
  buyer:['buyer_profile.php','recommended.php','deliveries.php','my_orders.php'], consumer:['consumer_profile.php'],
  extension_officer:['extension_profile.php','extension_farmers.php','extension_visits.php','extension_reports.php','extension_resources.php'],
  transport_provider:['transport_profile.php','transport_deliveries.php'], cooperative:['cooperative_profile.php'], researcher:['researcher_profile.php'],
  financial_institution:['finance_profile.php','stakeholder_directory.php'], manufacturer:['manufacturer_profile.php','stakeholder_directory.php','stakeholder_offers.php','stakeholder_requests.php'],
  strategic_partner:['stakeholder_profile.php'], input_supplier:['input_supplier_profile.php'], warehouse_provider:['warehouse_provider_profile.php'], retailer:['retailer_profile.php'], agribusiness:['agribusiness_profile.php'], insurance_provider:['insurance_provider_profile.php'], government_officer:['government_officer_profile.php'], ngo_partner:['ngo_partner_profile.php'], agricultural_service_provider:['agricultural_service_provider_profile.php'], veterinary_officer:['veterinary_officer_profile.php','agricultural_support_requests.php'], admin:['admin_subscriptions.php','admin_verification.php','admin.php']
};
(async()=>{const browser=await puppeteer.launch({executablePath:process.env.VAYUMA_CHROMIUM_PATH,headless:true,args:['--no-sandbox','--disable-dev-shm-usage']});let errors=[];
try {const page=await browser.newPage();page.on('pageerror',e=>errors.push(page.url() + ': ' + e.message));let count=0;
for(const [role,files] of Object.entries(pages))for(const file of files)for(const width of [320,390,768,1280]){
 await page.setViewport({width,height:900});const response=await page.goto(`${base}/link-${role}-${file}.html`,{waitUntil:'load'});assert.equal(response.status(),200,`${role} ${file}`);
 const layout=await page.evaluate(()=>({width:innerWidth,scroll:document.documentElement.scrollWidth}));assert.ok(layout.scroll<=width+1,`${role} ${file}: ${JSON.stringify(layout)}`);
 if(file.endsWith('_profile.php')){const nav=await page.$('.va-profile-navigation');if(nav){const bounds=await page.$eval('.va-profile-navigation',e=>[...e.children].map(c=>{const r=c.getBoundingClientRect();return {left:r.left,right:r.right};}));for(const r of bounds)assert.ok(r.left>=-1&&r.right<=width+1,`${file} nav ${JSON.stringify(r)}`);}}
 if(['market_connect.php','admin_subscriptions.php'].includes(file)){
  const state=await page.evaluate(()=>{let a=document.querySelector('aside.va-sidebar').getBoundingClientRect(),m=document.querySelector('.va-main--content').getBoundingClientRect();return {sidebarRight:a.right,mainLeft:m.left};});
  if(width>=768)assert.ok(state.sidebarRight<=state.mainLeft+1,`${file} sidebar overlaps main at ${width}`);
  else {const button=await page.$eval('.va-mobile-menu-btn',e=>{let r=e.getBoundingClientRect();return {width:r.width,height:r.height};});assert.ok(button.width>=44 && button.height>=44,`${file} menu touch target ${JSON.stringify(button)}`);await page.click('.va-mobile-menu-btn');assert.equal(await page.$eval('.va-mobile-menu-btn',e=>e.getAttribute('aria-expanded')),'true');await page.keyboard.press('Escape');assert.equal(await page.$eval('.va-mobile-menu-btn',e=>e.getAttribute('aria-expanded')),'false');}
 }
 count++;
}
await page.goto(`${base}/link-farmer-budget_planner.php.html`);
await page.select('#crop','Maize'); await page.type('#expectedYield','10'); await page.type('#pricePerKg','5'); await page.type('#seeds','100'); await page.click('.calculate-button');
assert.match(await page.$eval('#budgetTip',e=>e.textContent),/Warning/); assert.notEqual(await page.$eval('#results',e=>getComputedStyle(e).display),'none');
assert.deepEqual(errors,[]); console.log(`PASS ${count} synthetic sidebar-page layouts and mobile navigation`);
} finally {await browser.close();}})().catch(e=>{console.error(e);process.exitCode=1});
