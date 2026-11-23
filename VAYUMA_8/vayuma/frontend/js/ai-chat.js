/* Shared page/drawer chat; never render provider or user text as HTML. */
(() => {
  'use strict';
  const words = {
    en: {title:'VAYUMA TRIX AI', ready:'Ask a question', clear:'Clear chat', close:'Close chat', welcome:'Hello! Ask me about farming, business, everyday questions or using VAYUMA. Farm context is optional.', prompts:['Crop advice','Soil health','Pest & disease','Business ideas','Help with VAYUMA','General questions'], context:'+ Attach farm context', crop:'Crop', stage:'Growth stage', county:'County', soil:'Saved soil report', weather:'Active forecast', placeholder:'Ask VAYUMA anything…', send:'Send', caption:'Questions, category and selected context go to an external AI service. Chats are saved for this session. Verify advice.', typing:'VAYUMA TRIX AI is thinking', error:'Unable to get a response. Please try again.', missing:'Some selected context was unavailable and was not sent.', copied:'', open:'Open VAYUMA TRIX AI', lang:'Switch language', contextHint:'Optional: attach only the farm context you want to share. You can ask without it.', bottom:'Scroll to bottom ↓', copy:'Copy response', copied:'Copied', copyError:'Copy failed', play:'Read aloud', stop:'Stop', remove:'Remove', history:'Chat history', newChat:'New Chat', emptyHistory:'No saved conversations in this session.', sessionNotice:'Chats are saved for this sign-in session only.', mode:'Mode', closeHistory:'Close history', voice:'Voice input', voiceError:'Voice input is unavailable. Try typing.', refine:'Modify response', regenerate:'Regenerate', up:'Helpful', down:'Not helpful', feedbackError:'Feedback could not be saved.', topics:'Suggested topics', done:'Done', saveError:'Chat could not be saved in this session.', deleteChat:'Delete saved chat'},
    sw: {title:'VAYUMA TRIX AI', ready:'Uliza swali', clear:'Futa mazungumzo', close:'Funga mazungumzo', welcome:'Habari! Uliza kuhusu kilimo, biashara, maswali ya kila siku au kutumia VAYUMA. Taarifa za shamba si lazima.', prompts:['Ushauri wa mazao','Afya ya udongo','Wadudu na magonjwa','Mawazo ya biashara','Msaada wa VAYUMA','Maswali ya jumla'], context:'+ Ambatanisha taarifa za shamba', crop:'Zao', stage:'Hatua ya ukuaji', county:'Kaunti', soil:'Ripoti ya udongo', weather:'Utabiri uliopo', placeholder:'Uliza swali…', send:'Tuma', caption:'Swali, aina ya akaunti na taarifa ulizochagua hutumwa kwa huduma ya AI ya nje. Mazungumzo huhifadhiwa katika kipindi hiki. Thibitisha ushauri muhimu.', typing:'VAYUMA TRIX AI inafikiri', error:'Jibu halijapatikana. Tafadhali jaribu tena.', missing:'Baadhi ya taarifa zilizochaguliwa hazikupatikana na hazikutumwa.', copied:'', open:'Fungua VAYUMA TRIX AI', lang:'Badili lugha', contextHint:'Si lazima: ambatanisha tu taarifa za shamba unazotaka kushiriki. Unaweza kuuliza bila hizo.', bottom:'Nenda chini ↓', copy:'Nakili jibu', copied:'Imenakiliwa', copyError:'Kunakili kumeshindikana', play:'Soma kwa sauti', stop:'Acha', remove:'Ondoa', history:'Historia ya mazungumzo', newChat:'Mazungumzo mapya', emptyHistory:'Hakuna mazungumzo yaliyohifadhiwa katika kipindi hiki.', sessionNotice:'Mazungumzo huhifadhiwa katika kipindi hiki cha kuingia pekee.', mode:'Aina', closeHistory:'Funga historia', voice:'Ingiza kwa sauti', voiceError:'Sauti haipatikani. Jaribu kuandika.', refine:'Badili jibu', regenerate:'Jibu tena', up:'Imesaidia', down:'Haijasaidia', feedbackError:'Maoni hayakuhifadhiwa.', topics:'Mada za kuchagua', done:'Maliza', saveError:'Mazungumzo hayajahifadhiwa katika kipindi hiki.', deleteChat:'Futa mazungumzo yaliyohifadhiwa'}
  };
  const icon = '<svg viewBox="0 0 48 48" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="M9 10h30v23H20l-9 6v-6H9z M18 26c0-7 4-11 12-12 0 8-4 12-12 12z M17 29l10-10"/></svg>';
  let language = document.documentElement.lang.toLowerCase().startsWith('sw') ? 'sw' : 'en';
  const page = document.querySelector('[data-ai-page]');
  const csrf = document.querySelector('meta[name="vayuma-ai-csrf"]')?.content;
  if (!csrf) return;
  const root = document.createElement('div');
  root.className = page ? 'vayuma-chat vayuma-chat--page' : 'vayuma-chat vayuma-chat--drawer';
  root.innerHTML = `<header class="vai-head"><button type="button" class="vai-history-toggle" aria-label="Chat history">☰</button><div class="vai-head-title"><strong data-text="title"></strong><div class="vai-head-pills"><label class="vai-mode-label"><span class="vai-sr" data-text="mode"></span><select class="vai-mode" aria-label="Mode"><option value="general">General</option><option value="agri-pro">Agri-Pro</option></select></label><div class="vai-lang" role="group" aria-label="Language"><button type="button" data-lang="en">EN</button><button type="button" data-lang="sw">SW</button></div></div></div><div class="vai-actions"><button type="button" class="vai-clear" aria-label="Clear chat" title="Clear chat">↺</button>${page ? '<a class="vai-close-page" href="'+(document.body.dataset.dashboard || 'role.php')+'" aria-label="Back to dashboard">×</a>' : '<button type="button" class="vai-close" aria-label="Close chat">×</button>'}</div></header>
  <aside class="vai-history" hidden aria-label="Chat history"><div class="vai-history-head"><strong data-text="history"></strong><button type="button" class="vai-history-close" aria-label="Close history">×</button></div><button type="button" class="vai-history-new" data-text="newChat"></button><p data-text="sessionNotice"></p><div class="vai-history-list"></div></aside>
  <div class="vai-main"><div class="vai-new-row"><button type="button" class="vai-new" data-text="newChat"></button></div><div class="vai-messages" role="log" aria-live="polite" aria-relevant="additions"><div class="vai-welcome" data-text="welcome"></div></div><button type="button" class="vai-bottom" data-text="bottom" hidden></button><div class="vai-typing" hidden role="status"><span></span><span></span><span></span><b class="vai-sr" data-text="typing"></b></div></div>
  <div class="vai-compose"><div class="vai-prompts" aria-label="Suggested questions"></div><div class="vai-chips" aria-live="polite"></div><form class="vai-form"><details class="vai-context"><summary aria-label="Attach farm context" title="Attach farm context">+</summary><div class="vai-context-sheet"><strong data-text="context"></strong><div class="vai-context-grid"><label><span data-text="crop"></span><input name="crop" maxlength="100" autocomplete="off"></label><label><span data-text="stage"></span><input name="stage" maxlength="100" autocomplete="off"></label><label class="vai-county"><span data-text="county"></span><input name="location" maxlength="100" autocomplete="off"></label><label class="vai-check"><input type="checkbox" name="share_soil"><span data-text="soil"></span></label><label class="vai-check"><input type="checkbox" name="share_weather"><span data-text="weather"></span></label></div><small data-text="contextHint"></small><strong data-text="topics"></strong><div class="vai-topics"></div><button type="button" class="vai-context-done" data-text="done"></button></div></details><label class="vai-sr" for="vayuma-ai-question">Question</label><textarea id="vayuma-ai-question" name="question" rows="1" maxlength="8000" required></textarea><button type="button" class="vai-voice" aria-label="Voice input" hidden>🎙</button><button type="submit" class="vai-send" aria-label="Send">➜</button></form><p class="vai-voice-status" role="status"></p><p class="vai-caption" data-text="caption"></p></div>`;
  const by = s => root.querySelector(s);
  const messages = by('.vai-messages'), prompts = by('.vai-prompts'), text = by('textarea'), form = by('.vai-form'), typing = by('.vai-typing');
  let controller = null, requestId = 0, launcher = null, overlay = null, lastFocus = null, history = [], speakingButton = null, conversation = [], chatId = newChatId(), saveChain = Promise.resolve(), stopRecognition = () => {};
  function newChatId(){const bytes=new Uint8Array(16); window.crypto.getRandomValues(bytes); return [...bytes].map(n=>n.toString(16).padStart(2,'0')).join('');}
  function apiCall(data){return fetch('ai_chat_api.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},body:JSON.stringify(data)});}
  const bottom = by('.vai-bottom');
  function translate() {
    const t=words[language]; root.querySelectorAll('[data-text]').forEach(e=>{e.textContent=t[e.dataset.text] || '';});
    text.placeholder=t.placeholder; by('.vai-lang').setAttribute('aria-label',t.lang); root.querySelectorAll('[data-lang]').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.lang === language))); updateChips(); by('.vai-clear').setAttribute('aria-label',t.clear); by('.vai-clear').title=t.clear;
    if (by('.vai-close')) by('.vai-close').setAttribute('aria-label',t.close);
    by('.vai-history-toggle').setAttribute('aria-label',t.history);by('.vai-history-close').setAttribute('aria-label',t.closeHistory);by('.vai-voice').setAttribute('aria-label',t.voice);by('.vai-context summary').setAttribute('aria-label',t.context);by('.vai-mode').setAttribute('aria-label',t.mode);by('.vai-send').setAttribute('aria-label',t.send);
    if (launcher) {launcher.setAttribute('aria-label',t.open); launcher.querySelector('.vai-tooltip').textContent=t.title;}
    const topics=by('.vai-topics');topics.replaceChildren(); (language==='sw'?['Kalenda ya mazao','Wadudu na magonjwa','Bei za soko']:['Crop Calendar','Pest & Disease','Market Prices']).forEach(label=>{const b=document.createElement('button');b.type='button';b.textContent=label;b.addEventListener('click',()=>{text.value=label;by('.vai-context').open=false;resize();text.focus();});topics.appendChild(b);});
    prompts.replaceChildren(); t.prompts.slice(0,4).forEach(label=>{const b=document.createElement('button');b.type='button';b.textContent=label;b.addEventListener('click',()=>{text.value=label; resize();text.focus();});prompts.appendChild(b);});
  }
  function resize(){text.style.height='auto'; text.style.height=Math.min(text.scrollHeight,110)+'px';}
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
      const bullet=line.match(/^\s*([-*]|\d+[.)])\s+(.+)$/);
      if (bullet) { const tag=/^\d/.test(bullet[1])?'ol':'ul'; if (!list || list.tagName.toLowerCase()!==tag) {list=document.createElement(tag);container.appendChild(list);} const li=document.createElement('li');inline(li,bullet[2]);list.appendChild(li);return; }
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
  function addMessage(content, kind, messageId='', previousQuestion=''){
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
      if (messageId) {
        const status=document.createElement('span');status.className='vai-feedback-status';status.setAttribute('role','status');actions.appendChild(status);
        ['up','down'].forEach(rating=>{const b=document.createElement('button');b.type='button';b.textContent=rating==='up'?'👍':'👎';b.setAttribute('aria-label',words[language][rating]);b.setAttribute('aria-pressed','false');b.addEventListener('click',async()=>{const value=b.getAttribute('aria-pressed')==='true'?'clear':rating;try{const res=await apiCall({action:'feedback',message_id:messageId,rating:value});const result=await res.json();if(!res.ok||!result.success)throw new Error();actions.querySelectorAll('[data-rating]').forEach(x=>x.setAttribute('aria-pressed','false'));if(value!=='clear')b.setAttribute('aria-pressed','true');status.textContent='';}catch(_){status.textContent=words[language].feedbackError;}});b.dataset.rating=rating;actions.appendChild(b);});
      }
      const modify=document.createElement('details');modify.className='vai-modify';const summary=document.createElement('summary');summary.textContent='✨';summary.setAttribute('aria-label',words[language].refine);modify.appendChild(summary);const menu=document.createElement('div');menu.className='vai-modify-menu';
      (language==='sw'?['Fupisha jibu','Eleza kwa lugha rahisi','Tafsiri kwa Kiswahili','Toa hatua za kufuata']:['Make it shorter','Explain in simple terms','Translate to Kiswahili','Give a step-by-step action plan']).forEach((label,index)=>{const b=document.createElement('button');b.type='button';b.textContent=label;b.addEventListener('click',()=>{modify.open=false;if(index===2){language='sw';translate();}text.value=label;form.requestSubmit();});menu.appendChild(b);});modify.appendChild(menu);actions.appendChild(modify);
      if(previousQuestion){const retry=document.createElement('button');retry.type='button';retry.textContent='↻';retry.setAttribute('aria-label',words[language].regenerate);retry.addEventListener('click',()=>{text.value=previousQuestion;form.requestSubmit();});actions.appendChild(retry);}
      messages.appendChild(actions);
    } else if(kind==='user') { item.scrollIntoView({behavior:'smooth',block:'end'}); }
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
  function clear(){stopSpeech();stopRecognition();prompts.hidden=false;root.classList.remove('vai-conversation-started');conversation=[];chatId=newChatId();by('.vai-history').hidden=true;bottom.hidden=true;requestId++; if(controller) controller.abort();controller=null;history=[];typing.hidden=true;form.querySelector('[type=submit]').disabled=false;messages.replaceChildren();const welcome=document.createElement('div');welcome.className='vai-welcome';welcome.dataset.text='welcome';messages.appendChild(welcome);text.value='';root.querySelectorAll('.vai-context input').forEach(input=>{if(input.type==='checkbox')input.checked=false;else input.value='';});by('.vai-context').open=false;resize();translate();text.focus();}
  by('.vai-clear').addEventListener('click',clear);
  by('.vai-new').addEventListener('click',clear);by('.vai-history-new').addEventListener('click',clear);
  by('.vai-history-close').addEventListener('click',()=>{by('.vai-history').hidden=true;by('.vai-history-toggle').focus();});
  by('.vai-context-done').addEventListener('click',()=>{by('.vai-context').open=false;});
  root.addEventListener('click',e=>{const context=by('.vai-context');if(context.open&&!context.contains(e.target))context.open=false;});
  root.addEventListener('keydown',e=>{if(e.key==='Escape'&&by('.vai-context').open){e.preventDefault();e.stopPropagation();by('.vai-context').open=false;by('.vai-context summary').focus();}else if(e.key==='Escape'&&!by('.vai-history').hidden){e.preventDefault();e.stopPropagation();by('.vai-history').hidden=true;by('.vai-history-toggle').focus();}});
  by('.vai-history-toggle').addEventListener('click',async()=>{
    const drawer=by('.vai-history');drawer.hidden=false;const list=by('.vai-history-list');list.replaceChildren();
    try{const res=await apiCall({action:'history_list'}),data=await res.json();if(!res.ok||!data.success)throw new Error();
      if(!data.conversations.length){list.textContent=words[language].emptyHistory;return;}
      data.conversations.slice().reverse().forEach(c=>{const b=document.createElement('button');b.type='button';b.textContent=c.turns.find(t=>t.role==='user')?.content.slice(0,60)||words[language].newChat;b.addEventListener('click',()=>{requestId++;if(controller)controller.abort();controller=null;typing.hidden=true;form.querySelector('[type=submit]').disabled=false;history=[];conversation=c.turns;chatId=c.id;messages.replaceChildren();let previous='';c.turns.forEach(t=>{if(t.role==='user')previous=t.content;addMessage(t.content,t.role,'',previous);history.push({role:t.role,content:Array.from(t.content).slice(0,2000).join('')});});history=history.slice(-6);root.classList.toggle('vai-conversation-started',c.turns.length>0);drawer.hidden=true;});const row=document.createElement('div');row.className='vai-history-row';row.appendChild(b);const remove=document.createElement('button');remove.type='button';remove.className='vai-history-delete';remove.textContent='×';remove.setAttribute('aria-label',words[language].deleteChat);remove.addEventListener('click',async()=>{try{await saveChain;const res=await apiCall({action:'history_delete',id:c.id});const data=await res.json();if(!res.ok||!data.success)throw new Error();row.remove();if(!list.childElementCount)list.textContent=words[language].emptyHistory;}catch(_){list.setAttribute('aria-label',words[language].error);}});row.appendChild(remove);list.appendChild(row);});
    }catch(_){list.textContent=words[language].error;}
  });
  const Recognition=window.SpeechRecognition||window.webkitSpeechRecognition;
  if(Recognition){const voice=by('.vai-voice');const voiceStatus=by('.vai-voice-status');voice.hidden=false;voice.title=language==='sw'?'Sauti inaweza kutumwa kwa huduma ya utambuzi ya kivinjari.':'Speech may be sent to your browser’s recognition service.';let recognition=null;voice.addEventListener('click',()=>{if(recognition){recognition.stop();return;}voiceStatus.textContent='';recognition=new Recognition();recognition.lang=language==='sw'?'sw-KE':'en-KE';recognition.interimResults=false;voice.classList.add('vai-voice--active');recognition.onresult=e=>{text.value+=(text.value?' ':'')+e.results[0][0].transcript;resize();};recognition.onerror=()=>{voiceStatus.textContent=words[language].voiceError;};recognition.onend=()=>{voice.classList.remove('vai-voice--active');recognition=null;};try{recognition.start();}catch(_){voice.classList.remove('vai-voice--active');recognition=null;voiceStatus.textContent=words[language].voiceError;}});stopRecognition=()=>{if(recognition){try{recognition.stop();}catch(_){recognition=null;voice.classList.remove('vai-voice--active');}}};}

  root.querySelectorAll('[data-lang]').forEach(button=>button.addEventListener('click',()=>{stopSpeech();language=button.dataset.lang;translate();}));
  text.addEventListener('input',resize);
  text.addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey&&!e.isComposing){e.preventDefault();form.requestSubmit();}});
  form.addEventListener('submit',async e=>{
    e.preventDefault();const question=text.value.trim();if(!question||controller)return;
    stopRecognition();addMessage(question,'user'); text.value='';resize();text.blur();root.classList.add('vai-conversation-started');typing.hidden=false;form.querySelector('[type=submit]').disabled=true;
    const id=++requestId;controller=new AbortController();
    const data={question,language,mode:by('.vai-mode').value,history:history.slice(-6),crop:by('[name=crop]').value.trim(),stage:by('[name=stage]').value.trim(),location:by('[name=location]').value.trim(),share_soil:by('[name=share_soil]').checked,share_weather:by('[name=share_weather]').checked};
    try{const res=await fetch('ai_chat_api.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},body:JSON.stringify(data),signal:controller.signal}); const reply=await res.json();if(id!==requestId)return;if(!res.ok||reply.success!==true||typeof reply.answer!=='string'){const issue=new Error('AI request failed');issue.userMessage=typeof reply.error==='string'?reply.error:words[language].error;throw issue;}const aiMessage=addMessage(reply.answer,'assistant',reply.message_id||'',question);addNavigation(reply.navigation_links);history.push({role:'user',content:Array.from(question).slice(0,2000).join('')},{role:'assistant',content:Array.from(reply.answer).slice(0,2000).join('')});history=history.slice(-6);conversation.push({role:'user',content:question},{role:'assistant',content:Array.from(reply.answer).slice(0,8000).join('')});conversation=conversation.slice(-20);const savedSnapshot={action:'history_save',id:chatId,turns:conversation.slice()};saveChain=saveChain.then(async()=>{const r=await apiCall(savedSnapshot);if(!r.ok)throw new Error();}).catch(()=>{by('.vai-history [data-text=sessionNotice]').textContent=words[language].saveError;});if(Array.isArray(reply.context_used)&&reply.context_used.some(v=>typeof v==='string'&&/^(No recent saved weather|No saved soil report|Soil history unavailable)/.test(v)))addMessage(words[language].missing,'error'); aiMessage.scrollIntoView({behavior:'smooth',block:'start'}); updateBottom();}
    catch(err){if(id!==requestId||err.name==='AbortError')return;addMessage(err.userMessage||words[language].error,'error');if(!text.value){text.value=question;resize();}}
    finally{if(id===requestId){controller=null;typing.hidden=true;form.querySelector('[type=submit]').disabled=false;}}
  });
  if(page){
    page.appendChild(root);
    // Keep the dedicated AI page within the visual viewport on mobile too.
    const viewport=window.visualViewport;let previousHeight=viewport ? viewport.height : 0;
    const syncPage=(scrollForKeyboard=false)=>{
      if(!viewport || window.innerWidth>=768){['--vai-vv-height','--vai-vv-width','--vai-vv-top','--vai-vv-left'].forEach(prop=>root.style.removeProperty(prop));return;}
      const height=viewport.height;root.style.setProperty('--vai-vv-height',height+'px');root.style.setProperty('--vai-vv-width',viewport.width+'px');root.style.setProperty('--vai-vv-top',viewport.offsetTop+'px');root.style.setProperty('--vai-vv-left',viewport.offsetLeft+'px');
      if(scrollForKeyboard && height<previousHeight && root.contains(document.activeElement) && document.activeElement.matches('textarea,input')){messages.scrollTop=messages.scrollHeight;if(document.activeElement===text)by('.vai-compose').scrollTop=by('.vai-compose').scrollHeight;updateBottom();}previousHeight=height;
    };
    if(viewport){viewport.addEventListener('resize',()=>syncPage(true));viewport.addEventListener('scroll',()=>syncPage());}window.addEventListener('resize',()=>syncPage());syncPage();
  }else{
    launcher=document.createElement('button');launcher.type='button';launcher.className='vayuma-ai-fab';launcher.setAttribute('aria-expanded','false');launcher.setAttribute('aria-controls','vayuma-ai-drawer');launcher.innerHTML='<img src="assets/images/brand/app-icon-192.png" alt=""><span class="vai-tooltip">VAYUMA TRIX AI</span>';
    overlay=document.createElement('div');overlay.className='vai-overlay';overlay.hidden=true;overlay.innerHTML='<div class="vai-backdrop"></div>';root.id='vayuma-ai-drawer';root.setAttribute('role','dialog');root.setAttribute('aria-modal','true');root.setAttribute('aria-label','VAYUMA TRIX AI');overlay.appendChild(root);document.body.append(launcher,overlay);
    // Dynamic viewport units cover normal browser chrome. VisualViewport also
    // tracks keyboards and offset visual viewports on older mobile browsers.
    const viewport=window.visualViewport;
    let previousHeight=viewport ? viewport.height : 0;
    const syncViewport=(scrollForKeyboard=false)=>{
      if (overlay.hidden) return;
      if (!viewport || window.innerWidth >= 768) {
        ['--vai-vv-height','--vai-vv-width','--vai-vv-top','--vai-vv-left'].forEach(prop=>overlay.style.removeProperty(prop));
        return;
      }
      const height=viewport.height;
      overlay.style.setProperty('--vai-vv-height',height+'px');
      overlay.style.setProperty('--vai-vv-width',viewport.width+'px');
      overlay.style.setProperty('--vai-vv-top',viewport.offsetTop+'px');
      overlay.style.setProperty('--vai-vv-left',viewport.offsetLeft+'px');
      if (scrollForKeyboard && height < previousHeight && root.contains(document.activeElement) && document.activeElement.matches('textarea,input')) {
        messages.scrollTop=messages.scrollHeight;
        // If optional context is expanded, keep the composer itself visible.
        if (document.activeElement === text) by('.vai-compose').scrollTop=by('.vai-compose').scrollHeight;
        updateBottom();
      }
      previousHeight=height;
    };
    if(viewport){viewport.addEventListener('resize',()=>syncViewport(true));viewport.addEventListener('scroll',()=>syncViewport());}
    window.addEventListener('resize',()=>syncViewport());
    const close=()=>{stopSpeech();stopRecognition();overlay.hidden=true;document.body.classList.remove('vai-dialog-open');launcher.setAttribute('aria-expanded','false');if(lastFocus)lastFocus.focus({preventScroll:true});};
    launcher.addEventListener('click',()=>{lastFocus=document.activeElement;overlay.hidden=false;document.body.classList.add('vai-dialog-open');launcher.setAttribute('aria-expanded','true');if(viewport)previousHeight=viewport.height;syncViewport();by('.vai-history-toggle').focus({preventScroll:true});});
    by('.vai-close').addEventListener('click',close);overlay.querySelector('.vai-backdrop').addEventListener('click',close);
    overlay.addEventListener('keydown',e=>{if(e.key==='Escape'){e.preventDefault();close();}else if(e.key==='Tab'){const focus=[...root.querySelectorAll('button:not([disabled]),textarea,input,select,summary,a[href]')].filter(el=>el.getClientRects().length);if(!focus.length)return;const first=focus[0],last=focus[focus.length-1];if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus();}else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus();}}});
  }
  translate();
})();
