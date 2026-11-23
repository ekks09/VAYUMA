const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs'),path=require('node:path'),vm=require('node:vm');
const root=path.join(__dirname,'../vayuma'); const read=f=>fs.readFileSync(path.join(root,f),'utf8');
test('soil calculator script parses and does not invent nutrient or treatment rates',()=>{
 const source=read('soil_analysis.php'); const script=source.match(/<script>\s*([\s\S]*?)<\/script>/)[1];
 assert.doesNotThrow(()=>new vm.Script(script)); assert.match(script,/cannot be inferred/); assert.match(script,/No fertilizer or lime rate/); assert.doesNotMatch(source,/placeholder="e\.g\. 6\.5"\s+required/);
});
test('soil records use authenticated ownership, CSRF, units and manual migration',()=>{
 const source=read('soil_records.php'); assert.match(source,/logged_in/); assert.match(source,/vayuma_csrf_valid/); assert.match(source,/WHERE user_id=\?/); assert.match(source,/unit===/); assert.match(source,/not verified/);
 const migration=fs.readFileSync(path.join(__dirname,'../staging/sql/10_soil_records_REVIEW.sql'),'utf8'); assert.match(migration,/MANUAL REVIEW/); assert.match(migration,/INDEX idx_soil_records_user_date/);
});
test('weather distinguishes current and forecast, requires confirmation and avoids fake values',()=>{
 const php=read('get_climate.php'), js=read('frontend/js/climate-guidance.js'), ui=read('climate.php');
 assert.match(php,/precipitation_probability_max/); assert.match(php,/countryCode.*KE/); assert.match(php,/kenya_coordinates/); assert.match(php,/private, no-store/);
 assert.match(js,/forecast rain/); assert.match(js,/Current conditions|current\.rainfall/); assert.match(js,/Phone GPS location/); assert.match(js,/Unavailable/); assert.match(ui,/Confirm the matched place/);
});
test('external AI is gated, discloses third-party use and minimizes selected data',()=>{
 const php=read('ai_chat_api.php'), ui=read('frontend/js/ai-chat.js'); assert.match(php,/HTTP_X_CSRF_TOKEN/); assert.match(php,/VAYUMA_AI_EXTERNAL_ENABLED/); assert.match(php,/not independently verified/); assert.match(php,/vayuma_reserve_ai_request/); assert.match(ui,/external AI service/); assert.doesNotMatch(ui,/share_weather|share_soil/); assert.doesNotMatch(ui,/externalConsent|rule_based/);
});
