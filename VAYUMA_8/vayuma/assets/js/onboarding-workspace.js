// Progressive enhancement only; server validation and authorization remain authoritative.
(() => {
  // Do not cache or queue personal data. Browser connectivity is only a hint;
  // a successful server confirmation is still needed to establish a save.
  const offline = document.getElementById('onboard-offline');
  const syncConnectivity = () => { if (offline) offline.hidden = navigator.onLine !== false; };
  window.addEventListener('online', syncConnectivity);
  window.addEventListener('offline', syncConnectivity);
  syncConnectivity();
  const intake = document.getElementById('onboarding-form');
  if (intake) {
    const submit = intake.querySelector('button[type="submit"]');
    intake.addEventListener('submit', event => {
      if (navigator.onLine === false) { event.preventDefault(); syncConnectivity(); offline?.scrollIntoView({block:'nearest'}); return; }
      if (submit) { submit.disabled = true; submit.setAttribute('aria-busy', 'true'); }
    });
    window.addEventListener('pageshow', () => { if (submit) { submit.disabled = false; submit.removeAttribute('aria-busy'); } });
  }
  document.querySelectorAll('form[action="farmer_export.php"]').forEach(exportForm => exportForm.addEventListener('submit', event => {
    if (navigator.onLine === false) { event.preventDefault(); syncConnectivity(); offline?.scrollIntoView({block:'nearest'}); }
  }));
  const county = document.getElementById('onboarding-county');
  const countyFilter = document.getElementById('county-filter');
  if (county && countyFilter) {
    const search = document.getElementById('county-search');
    const letters = document.getElementById('county-letters');
    const status = document.getElementById('county-filter-status');
    const options = Array.from(county.options);
    const allowed = 'BEGHIKLMNSTUVW';
    const available = new Set(options.slice(1).map(option => option.textContent.trim().charAt(0).toUpperCase()));
    let activeLetter = '';
    const filter = () => {
      const selected = county.value;
      const query = search.value.trim().toLocaleLowerCase();
      let matches = 0;
      options.forEach((option, index) => {
        const name = option.textContent.trim();
        const match = index > 0 && (!activeLetter || name.toUpperCase().startsWith(activeLetter)) && name.toLocaleLowerCase().includes(query);
        if (match) matches++;
        if (!index || match || (selected && option.value === selected)) county.appendChild(option);
        else option.remove();
      });
      county.value = selected;
      status.textContent = matches ? '' : status.dataset.empty;
      letters.querySelectorAll('button').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.letter === activeLetter)));
    };
    ['', ...Array.from(allowed).filter(letter => available.has(letter))].forEach(letter => {
      const button = document.createElement('button');
      button.type = 'button'; button.dataset.letter = letter; button.textContent = letter || letters.dataset.all;
      button.setAttribute('aria-pressed', String(!letter));
      button.addEventListener('click', () => { activeLetter = letter; if (!letter) search.value = ''; filter(); });
      letters.appendChild(button);
    });
    search.addEventListener('input', filter);
    county.addEventListener('change', filter);
    countyFilter.hidden = false;
  }
  const role = document.getElementById('onboarding-role');
  const fields = document.querySelectorAll('[data-farmer-field]');
  if (role) {
    const updateFields = () => {
      const farmer = role.value === 'farmer';
      fields.forEach(field => {
        field.hidden = !farmer;
        const input = field.querySelector('input, select');
        if (input) input.required = farmer;
      });
    };
    const focusDetails = () => {
      updateFields();
      const target = role.value === 'farmer' ? document.querySelector('[data-farmer-field]:not([hidden]) input') : intake?.querySelector('[name="preferred_language"]');
      if (target) { target.scrollIntoView({block:'nearest'}); target.focus({preventScroll:true}); }
    };
    role.addEventListener('change', updateFields);
    role.addEventListener('dblclick', focusDetails);
    const detailsButton = document.getElementById('onboarding-role-details');
    if (detailsButton) { detailsButton.hidden = false; detailsButton.addEventListener('click', focusDetails); }
    // Optional category cards can opt in with data-onboarding-role and keep the
    // select as the authoritative form control. Native buttons remain keyboard accessible.
    const selectCard = card => {
      if (!card || !intake.contains(card) || !Array.from(role.options).some(option => option.value === card.dataset.onboardingRole)) return false;
      role.value = card.dataset.onboardingRole;
      role.dispatchEvent(new Event('change', {bubbles:true}));
      return true;
    };
    intake?.addEventListener('click', event => { selectCard(event.target.closest('[data-onboarding-role]')); });
    intake?.addEventListener('dblclick', event => { if (selectCard(event.target.closest('[data-onboarding-role]'))) focusDetails(); });
    intake?.addEventListener('keydown', event => {
      const card = event.target.closest('[data-onboarding-role]');
      if (card && !card.matches('button, a, input, select') && (event.key === 'Enter' || event.key === ' ')) {
        if (selectCard(card)) { event.preventDefault(); focusDetails(); }
      }
    });
    updateFields();
  }
  const records = Array.from(document.querySelectorAll('.onboard-record'));
  const expand = document.getElementById('expand-records');
  const syncExpand = () => { if (expand) { const all = records.length > 0 && records.every(record => record.open); expand.textContent = all ? expand.dataset.collapse : expand.dataset.expand; expand.setAttribute('aria-expanded', String(all)); } };
  if (expand && records.length) {
    expand.hidden = false;
    expand.addEventListener('click', () => { const open = !records.every(record => record.open); records.forEach(record => { record.open = open; }); syncExpand(); });
    records.forEach(record => record.addEventListener('toggle', () => { syncExpand(); if (!record.open) record.querySelectorAll('.onboard-id-toggle[aria-pressed="true"]').forEach(button => button.click()); }));
    syncExpand();
  }
  document.querySelectorAll('.onboard-id-toggle').forEach(button => {
    const id = button.previousElementSibling;
    if (!id || !id.classList.contains('onboard-id')) return;
    button.hidden = false;
    button.addEventListener('click', () => { const show = button.getAttribute('aria-pressed') !== 'true'; id.textContent = show ? id.dataset.id : id.dataset.mask; button.setAttribute('aria-pressed', String(show)); button.textContent = show ? button.dataset.hide : button.dataset.show; });
  });
  const reveal = document.getElementById('onboard-show-key');
  const passphrase = document.getElementById('staff-passphrase');
  if (reveal && passphrase) {
    reveal.hidden = false;
    reveal.addEventListener('click', () => {
      const show = passphrase.type === 'password';
      passphrase.type = show ? 'text' : 'password';
      reveal.setAttribute('aria-pressed', String(show));
      reveal.textContent = show ? 'Hide passphrase' : 'Show passphrase';
    });
  }
})();
