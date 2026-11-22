const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs'); const path=require('node:path'); const root=path.join(__dirname,'../vayuma');
test('manifest, icon formats and iOS metadata are coherent',()=>{
 const manifest=JSON.parse(fs.readFileSync(path.join(root,'manifest.json')));
 assert.deepEqual(manifest,JSON.parse(fs.readFileSync(path.join(root,'manifest.webmanifest'))));
 for(const key of ['name','short_name','start_url','scope','display','theme_color','background_color']) assert.ok(manifest[key],key);
 assert.equal(manifest.display,'standalone');
 const base='https://example.test/'; const scope=new URL(manifest.scope,new URL('manifest.json',base)); const start=new URL(manifest.start_url,new URL('manifest.json',base));
 assert.equal(scope.pathname,'/'); assert.equal(start.origin,scope.origin); assert.ok(start.pathname.startsWith(scope.pathname));
 for (const purpose of ['any','maskable']) for(const size of [192,512]) {
   const icon=manifest.icons.find(x=>x.purpose.split(/\s+/).includes(purpose) && x.sizes===`${size}x${size}`); assert.ok(icon,`${purpose} ${size}`); assert.equal(icon.type,'image/png');
   const bytes=fs.readFileSync(path.join(root,icon.src)); assert.equal(bytes.subarray(0,8).toString('hex'),'89504e470d0a1a0a'); assert.equal(bytes.readUInt32BE(16),size); assert.equal(bytes.readUInt32BE(20),size);
 }
 const head=fs.readFileSync(path.join(root,'includes/pwa.php'),'utf8'); assert.match(head,/manifest\.json/); assert.match(head,/apple-mobile-web-app-capable/); assert.match(head,/name="mobile-web-app-capable"/); assert.match(head,/apple-touch-icon/);
 const icon=fs.readFileSync(path.join(root,'assets/images/brand/apple-touch-icon.png')); assert.equal(icon.readUInt32BE(16),180);assert.equal(icon.readUInt32BE(20),180);
});
