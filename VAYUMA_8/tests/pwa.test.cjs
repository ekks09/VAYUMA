const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

function worker() {
  const listeners = {}, stored = new Map();
  let response = null, fail = false;
  const cache = {put: async (req, res) => stored.set(typeof req === 'string' ? req : req.url, res), match: async req => stored.get(typeof req === 'string' ? req : req.url)};
  const context = {URL, Request, Response, self: {registration: {scope:'https://example.test/app/'}, clients: {claim: async () => {}}, addEventListener: (type, fn) => listeners[type] = fn}, caches: {open: async () => cache, keys: async () => [], delete: async () => {}}, fetch: async () => {if (fail) throw Error('offline'); return response;}};
  vm.runInNewContext(fs.readFileSync(path.join(__dirname,'../vayuma/sw.js'),'utf8'), context);
  const send = (url, options = {}) => {
    let result;
    const request = {url: new URL(url, 'https://example.test/app/').href, method: 'GET', mode: 'cors', headers: new Headers(), ...options};
    listeners.fetch({request, respondWith: p => result = p});
    return result;
  };
  const makeResponse = (type, headers = {}) => ({ok:true, redirected:false, type:'basic', headers:new Headers({'content-type': type, ...headers}), clone() {return this;}});
  return {send, stored, set: value => response = value, offline: () => fail = true, makeResponse};
}
test('never intercepts private, API, arbitrary or non-GET requests', () => {
  const w=worker();
  for (const url of ['api/subscription_status.php','api/order_payment_status.php','auth/login.php','auth/bank_proof.php','avatar.php','profile.php','my_orders.php','subscription_receipt.php','subscription.php','payment.php','mpesa_receipt.php','mpesa_callback.php','paystack_webhook.php','uploads/file.jpg','assets/css/style.css?token=private','https://other.test/a']) assert.equal(w.send(url), undefined, url);
  assert.equal(w.send('assets/css/style.css', {method:'POST'}), undefined);
  assert.equal(w.send('assets/css/style.css', {headers:new Headers({range:'bytes=0-1'})}), undefined);
});
test('caches only correct public content types and respects no-store', async () => {
  const w=worker(); const url='assets/css/style.css';
  w.set(w.makeResponse('text/html')); await w.send(url); assert.equal(w.stored.size,0);
  w.set(w.makeResponse('text/css', {'cache-control':'private, no-store'})); await w.send(url); assert.equal(w.stored.size,0);
  w.set(w.makeResponse('text/css')); await w.send(url); assert.equal(w.stored.size,1);
  w.offline(); assert.equal((await w.send(url)).headers.get('content-type'),'text/css');
});
test('navigation offline returns the static offline document, not a cached account page', async () => {
  const w=worker(); const offline=w.makeResponse('text/html'); w.stored.set('https://example.test/app/offline.html',offline); w.offline();
  assert.equal(await w.send('mpesa_receipt.php', {mode:'navigate'}),offline);
});
