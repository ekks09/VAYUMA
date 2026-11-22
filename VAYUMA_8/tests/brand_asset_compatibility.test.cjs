const {test}=require('node:test'),assert=require('node:assert/strict');
const fs=require('node:fs'),path=require('node:path'),vm=require('node:vm');
const root=path.join(__dirname,'../vayuma');
const read=p=>fs.readFileSync(path.join(root,p));
test('legacy image URLs contain new branding for old pages and workers',()=>{
 for(const name of ['icon-192','icon-512','maskable-192','maskable-512','apple-touch-icon']){
  const modern=(name.startsWith('icon-')?'app-':'')+name+'.png';
  assert.deepEqual(read('frontend/images/pwa/'+name+'.png'),read('assets/images/brand/'+modern),name);
 }
 const left=read('assets/images/vayuma-logo.jpg'),right=read('frontend/images/vayuma-logo.jpg');
 assert.deepEqual(left,right);assert.equal(left.subarray(0,3).toString('hex'),'ffd8ff');
});
test('favicon contains 16 and 32 pixel variants and manifests are versioned',()=>{
 const ico=read('assets/images/brand/favicon.ico');assert.equal(ico.readUInt16LE(2),1);assert.ok(ico.readUInt16LE(4)>=2);
 const sizes=Array.from({length:ico.readUInt16LE(4)},(_,i)=>[ico[6+i*16],ico[7+i*16]]);assert.ok(sizes.some(s=>s[0]===16&&s[1]===16));assert.ok(sizes.some(s=>s[0]===32&&s[1]===32));
 assert.deepEqual(ico,read('favicon.ico'));assert.match(read('includes/pwa.php').toString(),/manifest\.json\?v=brand-1/);
 const m=JSON.parse(read('manifest.json'));for(const size of ['192x192','512x512'])assert.ok(m.icons.some(i=>i.sizes===size&&i.purpose==='any maskable'));
});
test('service worker upgrades public cache and bypasses manifest',async()=>{
 const listeners={},deleted=[];let claimed=false;
 const caches={keys:async()=>['vayuma-public-v16','vayuma-public-v17','vayuma-public-v18','other-app-cache'],delete:async key=>{deleted.push(key);},open:async()=>({})};
 const self={registration:{scope:'https://example.test/app/'},clients:{claim:async()=>{claimed=true;}},addEventListener:(type,fn)=>listeners[type]=fn};
 vm.runInNewContext(read('sw.js').toString(),{URL,self,caches,fetch:async()=>{},Response});
 let wait;listeners.activate({waitUntil:p=>{wait=p}});await wait;assert.deepEqual(deleted.sort(),['vayuma-public-v16','vayuma-public-v17']);assert.ok(claimed);
 let intercepted=false;listeners.fetch({request:{method:'GET',url:'https://example.test/app/manifest.json?v=brand-1',mode:'cors',headers:new Headers()},respondWith:()=>{intercepted=true;}});assert.equal(intercepted,false);
});
