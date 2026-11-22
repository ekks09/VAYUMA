const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const root=path.join(__dirname,'../vayuma');
const read=name=>fs.readFileSync(path.join(root,name),'utf8');
test('stakeholder metrics and payment card describe their actual scope',()=>{
 const page=read('stakeholder_dashboard_core.php');
 assert.match(page,/Your offers \(all statuses\)/);
 assert.match(page,/Verified profiles in other roles/);
 assert.match(page,/<h3>Marketplace orders<\/h3>/);
 assert.doesNotMatch(page,/<h3>Payments<\/h3>/);
});
test('offer fields and directory filters have accessible names',()=>{
 const page=read('stakeholder_offers.php');
 for(const name of ['title','offer_type','county','price_note','availability','description']) {
  assert.match(page,new RegExp(`for="offer-${name}"`));
  assert.match(page,new RegExp(`id="offer-${name}" name="${name}"`));
 }
 assert.doesNotMatch(page,/opportunities from verified VAYUMA stakeholders/);
 const directory=read('stakeholder_directory.php');
 assert.match(directory,/aria-label="Filter by stakeholder role"/);
 assert.match(directory,/aria-label="Filter by county"/);
});
