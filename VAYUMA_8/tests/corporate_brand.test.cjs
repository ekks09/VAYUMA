const {test}=require('node:test'),assert=require('node:assert/strict');
const fs=require('node:fs'),path=require('node:path');
const root=path.resolve(__dirname,'../vayuma');
const read=f=>fs.readFileSync(path.join(root,f),'utf8');
test('welcome presents responsive corporate lockup, translations and legal attribution',()=>{
 const welcome=read('welcome.php'),lang=read('includes/language.php');
 assert.match(welcome,/<title>Welcome \| VAYUMA TRIX AGRITECH INNOVATIONS KENYA<\/title>/);
 for(const text of ['corporate-lockup__mark','corporate-lockup__name','AGRITECH INNOVATIONS','KENYA','VAYUMA TRIX AI','og:title','og:description','All rights reserved.'])assert.ok(welcome.includes(text),text);
 assert.match(welcome,/<h1>\s*<\?= htmlspecialchars\(t\('welcome_title'\)/);
 assert.match(lang,/VAYUMA TRIX AGRITECH INNOVATIONS KENYA brings farmers/);assert.match(lang,/VAYUMA TRIX AGRITECH INNOVATIONS KENYA inawaleta/);
 assert.match(lang,/Ask about your selected data and VAYUMA TRIX tools/);
 assert.match(read('assets/css/brand.css'),/corporate-lockup__country::before/);
});
test('shared brand and offline cache use new assets',()=>{
 const name='VAYUMA TRIX AGRITECH INNOVATIONS KENYA',manifest=JSON.parse(read('manifest.json'));
 assert.equal(manifest.name,name);assert.equal(manifest.short_name,'VAYUMA TRIX');assert.deepEqual(manifest,JSON.parse(read('manifest.webmanifest')));
 for(const icon of manifest.icons){assert.ok(icon.src.startsWith('assets/images/brand/'));assert.ok(fs.existsSync(path.join(root,icon.src.split('?')[0])));}
 const pwa=read('includes/pwa.php'),worker=read('sw.js');for(const f of ['favicon.ico','apple-touch-icon.png'])assert.ok(pwa.includes('assets/images/brand/'+f));
 for(const f of ['shield-source.png','app-icon-192.png','app-icon-512.png','maskable-192.png','maskable-512.png','apple-touch-icon.png'])assert.ok(worker.includes('assets/images/brand/'+f));
 assert.match(read('includes/dashboard_brand.php'),/VAYUMA TRIX/);assert.match(read('includes/dashboard_topbar.php'),/AGRITECH INNOVATIONS KENYA/);
});
