/* PWA registration and install guidance. No notification permission is requested. */
(() => {
  'use strict';
  const script = document.currentScript;
  if (!script) return;
  const worker = new URL('../../sw.js', script.src);
  let installEvent = null;
  let notice = null;
  const entries = [];
  let dialog = null;
  let returnFocus = null;
  const dismissalKey = 'vayuma-install-dismissed-until';
  const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  const isAndroid = /Android/i.test(navigator.userAgent);
  const standalone = () => window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
  const sw = () => (document.querySelector('.va-language-selector a[lang="sw"].active') || document.documentElement.lang.toLowerCase().startsWith('sw'));
  const text = (en, kiswahili) => sw() ? kiswahili : en;
  const dismissed = () => { try { return Number(localStorage.getItem(dismissalKey) || 0) > Date.now(); } catch (_) { return false; } };
  const rememberDismissal = () => { try { localStorage.setItem(dismissalKey, String(Date.now() + 7 * 24 * 60 * 60 * 1000)); } catch (_) {} };

  window.addEventListener('beforeinstallprompt', event => {
    event.preventDefault();
    installEvent = event;
    if (document.readyState !== 'loading') showNotice();
  });
  window.addEventListener('appinstalled', () => {
    installEvent = null;
    if (notice) { notice.remove(); notice = null; }
    entries.forEach(entry => { entry.hidden = true; });
    closeDialog();
  });

  function closeDialog() {
    if (!dialog) return;
    dialog.remove(); dialog = null;
    if (returnFocus && returnFocus.isConnected) returnFocus.focus();
    returnFocus = null;
  }
  function instructions() {
    if (isIOS) return text('On your iPhone or iPad, open this site in Safari. Tap Share, then choose Add to Home Screen and tap Add. On newer iOS versions you may need to scroll down in the Share menu.', 'Kwenye iPhone au iPad, fungua tovuti hii katika Safari. Gusa Shiriki, kisha chagua Ongeza kwenye Skrini ya Mwanzo na uguse Ongeza. Huenda ukahitaji kusogeza chini kwenye menyu ya Shiriki.');
    if (isAndroid) return text('Open your browser menu (usually the three dots), then choose Install app or Add to Home screen. The wording depends on your browser.', 'Fungua menyu ya kivinjari (kwa kawaida nukta tatu), kisha chagua Sakinisha programu au Ongeza kwenye skrini ya mwanzo. Maneno hutegemea kivinjari chako.');
    return text('Use your browser menu and choose Install app or Add to Home screen, if available. You can continue using VAYUMA TRIX in your browser.', 'Tumia menyu ya kivinjari chako na uchague Sakinisha programu au Ongeza kwenye skrini ya mwanzo, ikiwa inapatikana. Unaweza kuendelea kutumia VAYUMA TRIX kwenye kivinjari.');
  }
  function showInstructions() {
    if (dialog) return;
    returnFocus = document.activeElement;
    dialog = document.createElement('div');
    dialog.className = 'va-install-backdrop';
    const box = document.createElement('section');
    box.className = 'va-install-dialog'; box.setAttribute('role', 'dialog'); box.setAttribute('aria-modal', 'true'); box.setAttribute('aria-labelledby', 'va-install-title');
    const title = document.createElement('h2'); title.id = 'va-install-title'; title.textContent = text('Install VAYUMA TRIX', 'Sakinisha VAYUMA TRIX');
    const paragraph = document.createElement('p'); paragraph.textContent = instructions();
    const close = document.createElement('button'); close.type = 'button'; close.className = 'va-install-close'; close.textContent = text('Got it', 'Nimeelewa'); close.addEventListener('click', closeDialog);
    box.append(title, paragraph, close); dialog.append(box); document.body.append(dialog);
    dialog.addEventListener('click', e => { if (e.target === dialog) closeDialog(); });
    dialog.addEventListener('keydown', e => { if (e.key === 'Escape') { e.preventDefault(); closeDialog(); } else if (e.key === 'Tab') { e.preventDefault(); close.focus(); } });
    close.focus();
  }
  async function requestInstall() {
    if (standalone()) return;
    if (!installEvent) { showInstructions(); return; }
    const event = installEvent; installEvent = null;
    try {
      await event.prompt();
      await event.userChoice;
    } catch (_) { showInstructions(); }
    // Browsers may dispatch a fresh beforeinstallprompt after a dismissal.
  }
  function addEntry() {
    if (standalone()) return;
    const sidebar = document.querySelector('aside.va-sidebar, aside.va-dash-sidebar');
    const sidebarTarget = sidebar && (sidebar.querySelector('.va-sidebar--bottom') || sidebar);
    const accountTarget = document.querySelector('.vy-user-menu__popover');
    // Account menu also covers shared pages with no sidebar. Dismissal of the
    // notification never dismisses these explicit navigation controls.
    const targets = [sidebarTarget, accountTarget].filter(Boolean);
    if (!targets.length) {
      const nav = document.querySelector('header.top nav');
      if (nav) targets.push(nav);
    }
    targets.forEach(target => {
      if (target.querySelector('.va-install-entry')) return;
      const entry = document.createElement('button'); entry.type = 'button';
      entry.className = 'va-install-entry' + (target === accountTarget ? ' va-install-entry--account' : (target === sidebarTarget ? '' : ' va-install-entry--top'));
      const icon = document.createElement('span'); icon.className = 'va-install-entry__icon'; icon.setAttribute('aria-hidden', 'true'); icon.textContent = '↓';
      const label = document.createElement('span'); label.textContent = text('Install app', 'Sakinisha programu');
      entry.append(icon, label); entry.addEventListener('click', requestInstall);
      if (target === sidebar) target.append(entry); else target.prepend(entry);
      entries.push(entry);
    });
  }
  function showNotice() {
    if (!document.body || notice || standalone() || dismissed() || !(isIOS || isAndroid || installEvent)) return;
    notice = document.createElement('section'); notice.className = 'va-install-notice'; notice.setAttribute('aria-label', text('Install VAYUMA TRIX', 'Sakinisha VAYUMA TRIX'));
    const title = document.createElement('strong'); title.textContent = text('Take VAYUMA TRIX with you', 'Tumia VAYUMA TRIX popote');
    const message = document.createElement('p'); message.textContent = isIOS ? text('In Safari, tap Share → Add to Home Screen. If you opened this in another app, open it in Safari first.', 'Katika Safari, gusa Shiriki → Ongeza kwenye Skrini ya Mwanzo. Ikiwa umefungua kupitia programu nyingine, fungua kwanza katika Safari.') : text('Add VAYUMA TRIX to your home screen for quick access.', 'Ongeza VAYUMA TRIX kwenye skrini ya mwanzo ili kufungua kwa urahisi.');
    const actions = document.createElement('div'); actions.className = 'va-install-actions';
    const install = document.createElement('button'); install.type = 'button'; install.textContent = text('Install app', 'Sakinisha programu'); install.addEventListener('click', requestInstall);
    const later = document.createElement('button'); later.type = 'button'; later.className = 'va-install-later'; later.textContent = text('Not now', 'Sio sasa'); later.addEventListener('click', () => { rememberDismissal(); notice.remove(); notice = null; });
    actions.append(install, later); notice.append(title, message, actions); document.body.append(notice);
  }
  function initialize() { addEntry(); showNotice(); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, {once:true}); else initialize();
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register(worker.href, {scope: new URL('./', worker).pathname}).catch(() => {});
    });
  }
})();
