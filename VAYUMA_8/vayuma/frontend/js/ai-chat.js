/* Shared page/drawer chat; never render provider or user text as HTML. */
(() => {
  'use strict';
  const words = {
    en: {title:'VAYUMA TRIX AI', ready:'Ask a question', clear:'Clear chat', close:'Close chat', welcome:'Hello! Ask me about farming, business, everyday questions or using VAYUMA. Farm context is optional.', prompts:['Crop advice','Soil health','Pest & disease','Business ideas','Help with VAYUMA','General questions'], context:'+ Attach farm context', crop:'Crop', stage:'Growth stage', county:'County', soil:'Saved soil report', weather:'Active forecast', placeholder:'Ask a question…', send:'Send', caption:'Your question, recent messages, account category and selected context are sent to an external AI service. Verify important advice.', typing:'VAYUMA TRIX AI is thinking', error:'Unable to get a response. Please try again.', missing:'Some selected context was unavailable and was not sent.', copied:'', open:'Open VAYUMA TRIX AI', lang:'Switch language', contextHint:'Optional: attach only the farm context you want to share. You can ask without it.', bottom:'Scroll to bottom ↓', copy:'Copy response', copied:'Copied', copyError:'Copy failed', play:'Read aloud', stop:'Stop', remove:'Remove'},
    sw: {title:'VAYUMA TRIX AI', ready:'Uliza swali', clear:'Futa mazungumzo', close:'Funga mazungumzo', welcome:'Habari! Uliza kuhusu kilimo, biashara, maswali ya kila siku au kutumia VAYUMA. Taarifa za shamba si lazima.', prompts:['Ushauri wa mazao','Afya ya udongo','Wadudu na magonjwa','Mawazo ya biashara','Msaada wa VAYUMA','Maswali ya jumla'], context:'+ Ambatanisha taarifa za shamba', crop:'Zao', stage:'Hatua ya ukuaji', county:'Kaunti', soil:'Ripoti ya udongo', weather:'Utabiri uliopo', placeholder:'Uliza swali…', send:'Tuma', caption:'Swali, mazungumzo ya karibuni, aina ya akaunti na taarifa ulizochagua hutumwa kwa huduma ya AI ya nje. Thibitisha ushauri muhimu.', typing:'VAYUMA TRIX AI inafikiri', error:'Jibu halijapatikana. Tafadhali jaribu tena.', missing:'Baadhi ya taarifa zilizochaguliwa hazikupatikana na hazikutumwa.', copied:'', open:'Fungua VAYUMA TRIX AI', lang:'Badili lugha', contextHint:'Si lazima: ambatanisha tu taarifa za shamba unazotaka kushiriki. Unaweza kuuliza bila hizo.', bottom:'Nenda chini ↓', copy:'Nakili jibu', copied:'Imenakiliwa', copyError:'Kunakili kumeshindikana', play:'Soma kwa sauti', stop:'Acha', remove:'Ondoa'}
  };
  const icon = '<svg viewBox="0 0 48 48" aria-hidden="true"><path fill="currentColor" d="M24 3a21 21 0 1 0 19.3 29.3L24 24 43.3 15.7A21 21 0 0 0 24 3Z"/><circle cx="23" cy="12" r="2.4" fill="#fff"/></svg>';
  let language = document.documentElement.lang.toLowerCase().startsWith('sw') ? 'sw' : 'en';
  const page = document.querySelector('[data-ai-page]');
  const csrf = document.querySelector('meta[name="vayuma-ai-csrf"]')?.content;
  if (!csrf) return;
  const root = document.createElement('div');
  root.className = page ? 'vayuma-chat vayuma-chat--page' : 'vayuma-chat vayuma-chat--drawer';
  root.innerHTML = `<header class="vai-head"><div class="vai-mark">${icon}</div><div class="vai-head-title"><strong data-text="title"></strong><small><i aria-hidden="true"></i><span data-text="ready"></span></small></div><div class="vai-actions"><div class="vai-lang" role="group" aria-label="Language"><button type="button" data-lang="en">EN</button><button type="button" data-lang="sw">SW</button></div><button type="button" class="vai-clear" aria-label="Clear chat" title="Clear chat">↺</button>${page ? '' : '<button type="button" class="vai-close" aria-label="Close chat">×</button>'}</div></header>
  <div class="vai-main"><div class="vai-messages" role="log" aria-live="polite" aria-relevant="additions"><div class="vai-welcome" data-text="welcome"></div></div><button type="button" class="vai-bottom" data-text="bottom" hidden></button><div class="vai-prompts" aria-label="Suggested questions"></div><div class="vai-typing" hidden role="status"><span></span><span></span><span></span><b class="vai-sr" data-text="typing"></b></div></div>
  <div class="vai-compose"><details class="vai-context"><summary data-text="context"></summary><div class="vai-context-grid"><label><span data-text="crop"></span><input name="crop" maxlength="100" autocomplete="off"></label><label><span data-text="stage"></span><input name="stage" maxlength="100" autocomplete="off"></label><label class="vai-county"><span data-text="county"></span><input name="location" maxlength="100" autocomplete="off"></label><label class="vai-check"><input type="checkbox" name="share_soil"><span data-text="soil"></span></label><label class="vai-check"><input type="checkbox" name="share_weather"><span data-text="weather"></span></label></div><small data-text="contextHint"></small></details><div class="vai-chips" aria-live="polite"></div><form class="vai-form"><label class="vai-sr" for="vayuma-ai-question">Question</label><textarea id="vayuma-ai-question" name="question" rows="1" maxlength="2000" required></textarea><button type="submit" data-text="send"></button></form><p class="vai-caption" data-text="caption"></p></div>`;
  const by = s => root.querySelector(s);
  const messages = by('.vai-messages'), prompts = by('.vai-prompts'), text = by('textarea'), form = by('.vai-form'), typing = by('.vai-typing');
  let controller = null, requestId = 0, launcher = null, overlay = null, lastFocus = null, history = [], speakingButton = null;
  const bottom = by('.vai-bottom');
  function translate() {
    const t=words[language]; root.querySelectorAll('[data-text]').forEach(e=>{e.textContent=t[e.dataset.text] || '';});
    text.placeholder=t.placeholder; by('.vai-lang').setAttribute('aria-label',t.lang); root.querySelectorAll('[data-lang]').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.lang === language))); updateChips(); by('.vai-clear').setAttribute('aria-label',t.clear); by('.vai-clear').title=t.clear;
    if (by('.vai-close')) by('.vai-close').setAttribute('aria-label',t.close);
    if (launcher) {launcher.setAttribute('aria-label',t.open); launcher.querySelector('.vai-tooltip').textContent=t.title;}
    prompts.replaceChildren(); t.prompts.forEach(label=>{const b=document.createElement('button');b.type='button';b.textContent=label;b.addEventListener('click',()=>{text.value=label; resize();text.focus();});prompts.appendChild(b);});
  }
  function resize(){text.style.height='auto'; text.style.height=Math.min(text.scrollHeight,144)+'px';}
  // Render a small, safe subset of common model formatting using DOM nodes only.
  function assistantContent(container, content) {
    const inline = (element, value) => {
      value.split(/(\*\*[^*\n]+\*\*)/g).forEach(part => {
        if (part.startsWith('**') && part.endsWith('**') && part.length > 4) {
          const strong=document.createElement('strong');strong.textContent=part.slice(2,-2);element.appendChild(strong);
        } else element.appendChild(document.createTextNode(part));
      });
    };
    let list=null;
    content.split(/\r?\n/).forEach(line=>{
      const bullet=line.match(/^\s*[-*]\s+(.+)$/);
      if (bullet) { if (!list) {list=document.createElement('ul');container.appendChild(list);} const li=document.createElement('li');inline(li,bullet[1]);list.appendChild(li);return; }
      list=null;
      if (!line.trim()) return;
      const heading=line.match(/^#{1,3}\s+(.+)$/);const p=document.createElement(heading?'strong':'p');inline(p,heading?heading[1]:line);container.appendChild(p);
    });
  }
  function updateBottom() { bottom.hidden = messages.scrollHeight - messages.scrollTop - messages.clientHeight < 80; }
  messages.addEventListener('scroll', updateBottom, {passive:true});
  bottom.addEventListener('click', () => messages.scrollTo({top:messages.scrollHeight, behavior:'smooth'}));
  function updateChips() {
    const chips=by('.vai-chips'); chips.replaceChildren();
    ['crop','stage','location','share_soil','share_weather'].forEach(name=>{
      const input=by('[name='+name+']'); const value=input.type==='checkbox' ? (input.checked ? words[language][name==='share_soil'?'soil':'weather'] : '') : input.value.trim();
      if (!value) return;
      const chip=document.createElement('button'); chip.type='button'; chip.className='vai-chip'; chip.textContent=value+' ×'; chip.setAttribute('aria-label', words[language].remove+' '+value);
      chip.addEventListener('click',()=>{if(input.type==='checkbox')input.checked=false;else input.value='';updateChips();});chips.appendChild(chip);
    });
  }
  root.querySelectorAll('.vai-context input').forEach(input=>{input.addEventListener('input',updateChips);input.addEventListener('change',updateChips);});
  function stopSpeech(){if ('speechSynthesis' in window) window.speechSynthesis.cancel(); if(speakingButton){speakingButton.textContent=words[language].play;speakingButton=null;}}
  function addMessage(content, kind){
    const item=document.createElement('div');item.className='vai-message vai-message--'+kind;
    if(kind==='assistant') assistantContent(item,content); else item.textContent=content;
    messages.appendChild(item);
    if(kind==='assistant') {
      const actions=document.createElement('div'); actions.className='vai-response-actions';
      const copy=document.createElement('button'); copy.type='button';copy.textContent=words[language].copy;
      copy.addEventListener('click',async()=>{try{await navigator.clipboard.writeText(content);copy.textContent=words[language].copied;}catch(_){copy.textContent=words[language].copyError;}setTimeout(()=>{copy.textContent=words[language].copy;},2000);});actions.appendChild(copy);
      if ('speechSynthesis' in window && 'SpeechSynthesisUtterance' in window) {
        const play=document.createElement('button'); play.type='button';play.textContent=words[language].play;
        play.addEventListener('click',()=>{if(speakingButton===play){stopSpeech();return;}stopSpeech();const speech=new SpeechSynthesisUtterance(content);speech.lang=language==='sw'?'sw-KE':'en-KE';speakingButton=play;play.textContent=words[language].stop;speech.onend=speech.onerror=()=>{if(speakingButton===play){speakingButton=null;play.textContent=words[language].play;}};window.speechSynthesis.speak(speech);});actions.appendChild(play);
      }
      messages.appendChild(actions);
    } else if(kind==='user') { messages.scrollTop=messages.scrollHeight; prompts.hidden=true; }
    updateBottom(); return item;
  }
  // Navigation comes from the app's server-side catalogue, never parsed from
  // model text. Reject external URLs and anything outside known local routes.
  function addNavigation(links) {
    if (!Array.isArray(links)) return;
    const allowed = /^(?:profile\.php|role\.php|dashboard\.php|[a-z0-9_]+_dashboard\.php|[a-z0-9_]+_profile\.php|profile_setup\.php|subscription\.php|market_connect\.php|my_orders\.php|stakeholder_offers\.php|stakeholder_requests\.php|stakeholder_directory\.php|feature_access\.php\?feature=(?:climate|soil)|budget_planner\.php|crop_calendar\.php|disease_helper\.php|onboarding\.php|claim_account\.php)$/;
    const nav = document.createElement('nav'); nav.className = 'vai-navigation';
    nav.setAttribute('aria-label', language === 'sw' ? 'Viungo vya VAYUMA' : 'VAYUMA links');
    links.slice(0,4).forEach(item => {
      if (!item || typeof item.label !== 'string' || item.label.length > 100 || typeof item.href !== 'string' || !allowed.test(item.href)) return;
      const link = document.createElement('a'); link.href = item.href; link.textContent = item.label; nav.appendChild(link);
    });
    if (nav.childElementCount) { messages.appendChild(nav); }
  }
  function clear(){stopSpeech();prompts.hidden=false;bottom.hidden=true;requestId++; if(controller) controller.abort();controller=null;history=[];typing.hidden=true;form.querySelector('button').disabled=false;messages.replaceChildren();const welcome=document.createElement('div');welcome.className='vai-welcome';welcome.dataset.text='welcome';messages.appendChild(welcome);text.value='';root.querySelectorAll('.vai-context input').forEach(input=>{if(input.type==='checkbox')input.checked=false;else input.value='';});by('.vai-context').open=false;resize();translate();text.focus();}
  by('.vai-clear').addEventListener('click',clear);
  root.querySelectorAll('[data-lang]').forEach(button=>button.addEventListener('click',()=>{stopSpeech();language=button.dataset.lang;translate();}));
  text.addEventListener('input',resize);
  text.addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey&&!e.isComposing){e.preventDefault();form.requestSubmit();}});
  form.addEventListener('submit',async e=>{
    e.preventDefault();const question=text.value.trim();if(!question||controller)return;
    addMessage(question,'user'); text.value='';resize();typing.hidden=false;form.querySelector('button').disabled=true;
    const id=++requestId;controller=new AbortController();
    const data={question,language,history:history.slice(-6),crop:by('[name=crop]').value.trim(),stage:by('[name=stage]').value.trim(),location:by('[name=location]').value.trim(),share_soil:by('[name=share_soil]').checked,share_weather:by('[name=share_weather]').checked};
    try{const res=await fetch('ai_chat_api.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},body:JSON.stringify(data),signal:controller.signal}); const reply=await res.json();if(id!==requestId)return;if(!res.ok||reply.success!==true||typeof reply.answer!=='string'){const issue=new Error('AI request failed');issue.userMessage=typeof reply.error==='string'?reply.error:words[language].error;throw issue;}const aiMessage=addMessage(reply.answer,'assistant');addNavigation(reply.navigation_links);history.push({role:'user',content:Array.from(question).slice(0,1000).join('')},{role:'assistant',content:Array.from(reply.answer).slice(0,1000).join('')});history=history.slice(-6);if(Array.isArray(reply.context_used)&&reply.context_used.some(v=>typeof v==='string'&&/^(No recent saved weather|No saved soil report|Soil history unavailable)/.test(v)))addMessage(words[language].missing,'error'); aiMessage.scrollIntoView({behavior:'smooth',block:'start'}); updateBottom();}
    catch(err){if(id!==requestId||err.name==='AbortError')return;addMessage(err.userMessage||words[language].error,'error');if(!text.value){text.value=question;resize();}}
    finally{if(id===requestId){controller=null;typing.hidden=true;form.querySelector('button').disabled=false;if(page || (overlay && !overlay.hidden))text.focus({preventScroll:true});}}
  });
  if(page){page.appendChild(root);}else{
    launcher=document.createElement('button');launcher.type='button';launcher.className='vayuma-ai-fab';launcher.setAttribute('aria-expanded','false');launcher.setAttribute('aria-controls','vayuma-ai-drawer');launcher.innerHTML=`${icon}<span class="vai-tooltip">VAYUMA TRIX AI</span>`;
    overlay=document.createElement('div');overlay.className='vai-overlay';overlay.hidden=true;overlay.innerHTML='<div class="vai-backdrop"></div>';root.id='vayuma-ai-drawer';root.setAttribute('role','dialog');root.setAttribute('aria-modal','true');root.setAttribute('aria-label','VAYUMA TRIX AI');overlay.appendChild(root);document.body.append(launcher,overlay);
    const close=()=>{stopSpeech();overlay.hidden=true;launcher.setAttribute('aria-expanded','false');if(lastFocus)lastFocus.focus();};
    launcher.addEventListener('click',()=>{lastFocus=document.activeElement;overlay.hidden=false;launcher.setAttribute('aria-expanded','true');text.focus();});
    by('.vai-close').addEventListener('click',close);overlay.querySelector('.vai-backdrop').addEventListener('click',close);
    overlay.addEventListener('keydown',e=>{if(e.key==='Escape'){e.preventDefault();close();}else if(e.key==='Tab'){const focus=[...root.querySelectorAll('button:not([disabled]),textarea,input,summary')].filter(el=>el.getClientRects().length);if(!focus.length)return;const first=focus[0],last=focus[focus.length-1];if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus();}else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus();}}});
  }
  translate();
})();
