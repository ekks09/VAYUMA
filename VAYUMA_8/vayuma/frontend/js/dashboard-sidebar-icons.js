/* Decorative sidebar symbols only. Destinations and disabled states stay server-rendered. */
(() => {
  'use strict';
  const paths = {
    home:'M3 10 12 3l9 7v10h-6v-6H9v6H3z',
    category:'M4 4h6v6H4z M14 4h6v6h-6z M4 14h6v6H4z M14 14h6v6h-6z',
    profile:'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8z M4 21a8 8 0 0 1 16 0',
    weather:'M17 10a4 4 0 1 0-5-5 M6 19h11a3.5 3.5 0 0 0 0-7 5 5 0 0 0-9-1 4 4 0 0 0-2 8z',
    soil:'M4 15h16 M6 18h12 M9 21h6 M12 15V7 M12 11C8 11 6 9 6 6c4 0 6 2 6 5z M12 9c0-3 2-5 6-5 0 3-2 5-6 5z',
    crop:'M12 21v-9 M12 15C7 15 4 12 4 7c5 0 8 3 8 8z M12 12c0-5 3-8 8-8 0 5-3 8-8 8z',
    market:'M3 10h18l-2-6H5z M5 10v10h14V10 M9 20v-6h6v6',
    orders:'M6 4h12v16H6z M9 8h6 M9 12h6 M9 16h4',
    truck:'M3 6h11v10H3z M14 10h4l3 3v3h-7z M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4z M18 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4z',
    money:'M3 7h18v10H3z M12 10a2 2 0 1 0 0 4 2 2 0 0 0 0-4z M6 10v4 M18 10v4',
    ai:'M5 8h14v11H5z M12 4v4 M9 12h.01 M15 12h.01 M9 16h6',
    book:'M3 5h6a3 3 0 0 1 3 2 3 3 0 0 1 3-2h6v14h-6a3 3 0 0 0-3 2 3 3 0 0 0-3-2H3z M12 7v14',
    health:'M8 10h8v5a4 4 0 0 1-8 0z M9 10V8a3 3 0 0 1 6 0v2 M4 12h4 M16 12h4 M5 18l3-2 M19 18l-3-2 M12 10v8',
    calendar:'M4 6h16v14H4z M8 3v6 M16 3v6 M4 10h16 M8 14h3 M8 17h6',
    people:'M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6z M3 20v-1a6 6 0 0 1 12 0v1 M16 6a3 3 0 0 1 0 6 M17 14a5 5 0 0 1 4 5v1',
    card:'M3 6h18v12H3z M3 10h18 M7 15h4',
    exit:'M10 4H5v16h5 M14 8l4 4-4 4 M8 12h10',
    download:'M12 3v12 M8 11l4 4 4-4 M4 18v3h16v-3',
    settings:'M4 6h16 M4 12h16 M4 18h16 M9 4v4 M15 10v4 M10 16v4'
  };
  function kind(el) {
    const href=(el.getAttribute('href')||'').toLowerCase();
    // Ignore return URLs so a profile link returning to a dashboard is not
    // mistaken for the dashboard action itself. Preserve explicit feature keys.
    const feature=(href.match(/[?&]feature=([a-z_]+)/)||[])[1]||'';
    const value=(href.split('?')[0]+' '+feature+' '+el.textContent+' '+(el.getAttribute('title')||'')).toLowerCase();
    if(el.classList.contains('va-category-link')) return 'category';
    if(el.classList.contains('va-personal-details-link')) return 'profile';
    if(el.classList.contains('va-install-entry')) return 'download';
    if(/logout|sign out/.test(value)) return 'exit';
    if(/dashboard|dashibodi/.test(value)) return 'home';
    if(/subscription|billing|usajili/.test(value)) return 'card';
    if(/profile|account|settings|wasifu/.test(value)) return 'profile';
    if(/climate|weather|forecast|hewa/.test(value)) return 'weather';
    if(/soil|udongo/.test(value)) return 'soil';
    if(/disease|pest|health|veterinar/.test(value)) return 'health';
    if(/calendar|visit|schedule/.test(value)) return 'calendar';
    if(/\bai\b|advisory/.test(value)) return 'ai';
    if(/directory|people|member|farmers/.test(value)) return 'people';
    if(/produce|crop|farm|input|crops/.test(value)) return 'crop';
    if(/market|offer|exchange|products|sell|buy/.test(value)) return 'market';
    if(/transport|delivery|deliveries|vehicle/.test(value)) return 'truck';
    if(/order|request|report|history/.test(value)) return 'orders';
    if(/finance|loan|budget|earning|money|insurance|bank/.test(value)) return 'money';
    if(/research|resource|education|knowledge/.test(value)) return 'book';
    return 'settings';
  }
  function initialize() {
    document.querySelectorAll('aside.va-sidebar, aside.va-dash-sidebar').forEach(sidebar=>{
      if(!sidebar.querySelector('.va-category-link')) return;
      sidebar.classList.add('va-sidebar--visual');
      sidebar.querySelectorAll('.va-nav-item, .va-dash-sidebar--nav-item, .va-logout-item, .va-sidebar--bottom > a[href="subscription.php"], .va-install-entry').forEach(el=>{
        if(el.querySelector(':scope > .va-action-symbol')) return;
        const type=kind(el), symbol=document.createElement('span');symbol.className='va-action-symbol va-action-symbol--'+type;symbol.setAttribute('aria-hidden','true');
        const svg=document.createElementNS('http://www.w3.org/2000/svg','svg');svg.setAttribute('viewBox','0 0 24 24');svg.setAttribute('fill','none');svg.setAttribute('stroke','currentColor');svg.setAttribute('stroke-width','1.8');svg.setAttribute('stroke-linecap','round');svg.setAttribute('stroke-linejoin','round');
        const path=document.createElementNS('http://www.w3.org/2000/svg','path');path.setAttribute('d',paths[type]);svg.appendChild(path);symbol.appendChild(svg);
        const old=el.querySelector(':scope > svg, :scope > .va-nav-icon, :scope > .va-dash-sidebar--icon, :scope > .va-install-entry__icon, :scope > span[aria-hidden="true"]');if(old)old.classList.add('va-action-original-icon');
        el.insertBefore(symbol,el.firstChild);
      });
    });
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initialize,{once:true});else initialize();
})();
