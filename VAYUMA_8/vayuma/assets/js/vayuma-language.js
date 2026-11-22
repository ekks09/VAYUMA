/* VAYUMA bilingual interface helper (English / Kiswahili).
 * This translates common interface labels and attributes without changing
 * form actions, database values, URLs, or application logic.
 */
(function () {
  'use strict';
  if (window.__vayumaLanguageInitialized) return;
  window.__vayumaLanguageInitialized = true;
  const swLink = document.querySelector('.va-language-selector a[lang="sw"]');
  const language = swLink && swLink.classList.contains('active') ? 'sw' : (document.documentElement.lang || 'en');
  document.documentElement.lang = language;
  if (language !== 'sw') return;

  const dictionary = {
    'Dashboard':'Dashibodi','Home':'Nyumbani','Welcome':'Karibu','Welcome Back':'Karibu Tena',
    'Profile':'Wasifu','My Profile':'Wasifu Wangu','Settings':'Mipangilio','Logout':'Ondoka',
    'Log Out':'Ondoka','Login':'Ingia','Log In':'Ingia','Register':'Jisajili','Sign Up':'Jisajili',
    'Create Account':'Fungua Akaunti','Save':'Hifadhi','Save Changes':'Hifadhi Mabadiliko',
    'Cancel':'Ghairi','Submit':'Wasilisha','Search':'Tafuta','Search...':'Tafuta...',
    'Back':'Rudi','Back to Dashboard':'Rudi kwenye Dashibodi','Return to Dashboard':'Rudi kwenye Dashibodi',
    'Marketplace':'Soko','Market Connect':'Unganisha na Soko','Orders':'Maagizo','My Orders':'Maagizo Yangu',
    'Payments':'Malipo','Payment':'Malipo','Notifications':'Arifa','Help':'Msaada','Contact Us':'Wasiliana Nasi',
    'Add Produce':'Ongeza Mazao','Add Product':'Ongeza Bidhaa','View Produce':'Tazama Mazao',
    'View Details':'Tazama Maelezo','Buy':'Nunua','Sell':'Uza','Contact Farmer':'Wasiliana na Mkulima',
    'Crop Advice':'Ushauri wa Mazao','Soil Analysis':'Uchambuzi wa Udongo','Soil Health':'Afya ya Udongo',
    'Weather':'Hali ya Hewa','Weather Guidance':'Mwongozo wa Hali ya Hewa','Climate Guidance':'Mwongozo wa Hali ya Hewa',
    'Disease Helper':'Msaada wa Magonjwa','Crop Calendar':'Kalenda ya Mazao','Crop Opportunities':'Fursa za Mazao',
    'Farming Score':'Alama ya Kilimo','Budget Planner':'Mpangaji wa Bajeti','Livestock':'Mifugo',
    'Ask VAYUMA TRIX AI':'Uliza VAYUMA TRIX AI','Send':'Tuma','Clear Conversation':'Futa Mazungumzo',
    'Name':'Jina','Full Name':'Jina Kamili','Email':'Barua Pepe','Phone':'Simu','Phone Number':'Nambari ya Simu',
    'Email or Phone Number':'Barua Pepe au Nambari ya Simu','Password':'Nenosiri','Confirm Password':'Thibitisha Nenosiri',
    'Location':'Eneo','County':'Kaunti','Sub-county':'Kaunti Ndogo','Address':'Anwani',
    'Description':'Maelezo','Quantity':'Kiasi','Price':'Bei','Amount':'Kiasi cha Pesa',
    'Status':'Hali','Date':'Tarehe','Actions':'Vitendo','Update':'Sasisha','Edit':'Hariri','Delete':'Futa',
    'View':'Tazama','Download':'Pakua','Upload':'Pakia','Choose File':'Chagua Faili',
    'No results found':'Hakuna matokeo yaliyopatikana','No records found':'Hakuna rekodi zilizopatikana',
    'Loading...':'Inapakia...','Please wait...':'Tafadhali subiri...','Success':'Imefanikiwa',
    'Error':'Hitilafu','Something went wrong. Please try again.':'Hitilafu imetokea. Tafadhali jaribu tena.',
    'Are you sure?':'Una uhakika?','Required':'Inahitajika','Optional':'Si lazima',
    'Farmer':'Mkulima','Buyer':'Mnunuzi','Consumer':'Mtumiaji','Transporter':'Msafirishaji',
    'Researcher':'Mtafiti','Extension Officer':'Afisa Ugani','Cooperative':'Ushirika',
    'Manufacturer':'Mtengenezaji','Retailer':'Muuzaji wa Rejareja','Finance':'Fedha',
    'Insurance Provider':'Mtoa Bima','Government Officer':'Afisa wa Serikali',
    'English':'Kiingereza','Kiswahili':'Kiswahili','Language':'Lugha',
    'Pending':'Inasubiri','Approved':'Imeidhinishwa','Rejected':'Imekataliwa','Completed':'Imekamilika',
    'Paid':'Imelipwa','Unpaid':'Haijalipwa','Available':'Inapatikana','Unavailable':'Haipatikani',
    'Farmer Dashboard':'Dashibodi ya Mkulima','Buyer Dashboard':'Dashibodi ya Mnunuzi',
    'Consumer Dashboard':'Dashibodi ya Mtumiaji','Cooperative Dashboard':'Dashibodi ya Ushirika',
    'Researcher Dashboard':'Dashibodi ya Mtafiti','Extension Dashboard':'Dashibodi ya Afisa Ugani',
    'Transport Dashboard':'Dashibodi ya Msafirishaji','Finance Dashboard':'Dashibodi ya Fedha',
    'Manufacturer Dashboard':'Dashibodi ya Mtengenezaji','Retailer Dashboard':'Dashibodi ya Muuzaji wa Rejareja',
    'Insurance Provider Dashboard':'Dashibodi ya Mtoa Bima','Government Officer Dashboard':'Dashibodi ya Afisa wa Serikali',
    'Agribusiness Dashboard':'Dashibodi ya Biashara ya Kilimo',
    'Agricultural Service Provider Dashboard':'Dashibodi ya Mtoa Huduma za Kilimo',
    'Warehouse Provider Dashboard':'Dashibodi ya Mtoa Huduma za Ghala',
    'Veterinary Officer Dashboard':'Dashibodi ya Afisa wa Mifugo',
    'Input Supplier Dashboard':'Dashibodi ya Msambazaji wa Pembejeo',
    'NGO Partner Dashboard':'Dashibodi ya Mshirika wa Shirika Lisilo la Kiserikali',
    'Stakeholder Dashboard':'Dashibodi ya Mdau','Your workspace':'Eneo Lako la Kazi',
    'Published offers':'Matoleo Yaliyochapishwa','Pending incoming requests':'Maombi Mapya Yanayosubiri',
    'Verified stakeholders available':'Wadau Waliothibitishwa Wanaopatikana',
    'Edit profile':'Hariri Wasifu','Manage requests':'Simamia Maombi','Browse directory':'Vinjari Orodha',
    'Open marketplace':'Fungua Soko','Open deliveries':'Fungua Usafirishaji','View orders':'Tazama Maagizo',
    'Welcome to VAYUMA':'Karibu VAYUMA','Smart Agricultural Ecosystem':'Mfumo Mahiri wa Kilimo',
    'Connect with farmers and buyers':'Ungana na wakulima na wanunuzi',
    'My Produce':'Mazao Yangu','My Listings':'Matangazo Yangu','Manage Orders':'Simamia Maagizo',
    'Delivery':'Uwasilishaji','Deliveries':'Usafirishaji','Request Delivery':'Omba Usafirishaji',
    'Bank Transfer':'Uhamisho wa Benki','M-Pesa':'M-Pesa','Pay Now':'Lipa Sasa',
    'Payment History':'Historia ya Malipo','Transaction Reference':'Nambari ya Muamala'
  };

  const normalized = new Map(Object.entries(dictionary).map(([a,b]) => [a.toLocaleLowerCase(), b]));
  function translateTextNode(node) {
    if (!node || !node.nodeValue || !node.nodeValue.trim()) return;
    const original = node.nodeValue;
    const trimmed = original.trim();
    const translated = normalized.get(trimmed.toLocaleLowerCase());
    if (translated) node.nodeValue = original.replace(trimmed, translated);
  }
  function translateElement(root) {
    if (!root) return;
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
      acceptNode(node) {
        const parent = node.parentElement;
        if (!parent || parent.closest('script,style,textarea,code,pre,noscript')) return NodeFilter.FILTER_REJECT;
        return NodeFilter.FILTER_ACCEPT;
      }
    });
    let node;
    while ((node = walker.nextNode())) translateTextNode(node);
    if (root.querySelectorAll) {
      root.querySelectorAll('[placeholder],[title],[aria-label],input[type="submit"],input[type="button"]').forEach(el => {
        ['placeholder','title','aria-label'].forEach(attr => {
          if (!el.hasAttribute(attr)) return;
          const value = el.getAttribute(attr);
          const result = normalized.get((value || '').trim().toLocaleLowerCase());
          if (result) el.setAttribute(attr, result);
        });
      });
      root.querySelectorAll('input[type="submit"],input[type="button"]').forEach(el => {
        const result = normalized.get((el.value || '').trim().toLocaleLowerCase());
        if (result) el.value = result;
      });
    }
  }
  document.documentElement.lang = 'sw';
  translateElement(document.body);
  const observer = new MutationObserver(records => records.forEach(record => {
    record.addedNodes.forEach(node => {
      if (node.nodeType === Node.TEXT_NODE) translateTextNode(node);
      else if (node.nodeType === Node.ELEMENT_NODE) translateElement(node);
    });
  }));
  observer.observe(document.body, {childList:true, subtree:true});
})();
