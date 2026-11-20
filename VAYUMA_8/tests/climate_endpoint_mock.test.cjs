const {test}=require('node:test'), assert=require('node:assert/strict');
const path=require('node:path'), {spawnSync}=require('node:child_process');
const php=process.env.VAYUMA_PHP||'php', file=path.resolve(__dirname,'../vayuma/get_climate.php');
test('climate endpoint refuses anonymous and invalid input before network access',()=>{
 function req(session,method,get){const code=`session_start(); $_SESSION=json_decode(${JSON.stringify(JSON.stringify(session))},true); $_SERVER['REQUEST_METHOD']=${JSON.stringify(method)}; $_GET=json_decode(${JSON.stringify(JSON.stringify(get))},true); require ${JSON.stringify(file)};`;const r=spawnSync(php,['-d','display_errors=0','-r',code],{encoding:'utf8'});assert.equal(r.status,0,r.stderr);return JSON.parse(r.stdout);}
 assert.match(req({},'GET',{}).message,/sign in/i);
 const user={user_id:1,logged_in:true};
 assert.match(req(user,'POST',{}).message,/only GET/i);
 assert.match(req(user,'GET',{mode:'forecast',lat:'-89',lon:'1'}).message,/valid location/i);
 assert.match(req(user,'GET',{mode:'search',location:'x'}).message,/2–80/i);
 assert.match(req({...user,climate_requests:Array(30).fill(Math.floor(Date.now()/1000))},'GET',{mode:'search',location:'Nakuru'}).message,/too many/i);
});
