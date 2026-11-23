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
  // Use a single editable county control when datalist is supported; retain the
  // real select as a no-JS / unsupported-browser fallback.
  const county = document.getElementById('onboarding-county');
  const countyFallback = document.getElementById('county-fallback');
  if (county && countyFallback && 'list' in county && window.HTMLDataListElement) {
    county.name = 'county'; county.disabled = false; county.required = true; county.hidden = false;
    countyFallback.disabled = true; countyFallback.hidden = true;
  } else if (countyFallback) {
    const label = document.querySelector('label[for="onboarding-county"]');
    if (label) label.htmlFor = 'county-fallback';
  }
  // Navigation links remain usable as anchors without JavaScript.
  const nav = document.querySelector('[data-workspace-nav]');
  if (nav) {
    const links = Array.from(nav.querySelectorAll('a[href^="#"]'));
    const targets = links.map(link => document.getElementById(link.hash.slice(1)));
    const activate = id => {
      if (!targets.some(target => target && target.id === id)) id = 'add-person';
      targets.forEach(target => { if (target) target.hidden = target.id !== id; });
      links.forEach(link => { if (link.hash === '#' + id) link.setAttribute('aria-current', 'page'); else link.removeAttribute('aria-current'); });
    };
    const fromHash = () => location.hash.slice(1);
    links.forEach(link => link.addEventListener('click', event => {
      event.preventDefault();
      if (location.hash !== link.hash) history.pushState(null, '', link.hash);
      activate(link.hash.slice(1));
    }));
    window.addEventListener('popstate', () => activate(fromHash()));
    window.addEventListener('hashchange', () => activate(fromHash()));
    activate(nav.dataset.postTab || fromHash() || 'add-person');
  }
  let goIntakeStep = () => {};
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
      if (target) { goIntakeStep(1); target.scrollIntoView({block:'nearest'}); target.focus({preventScroll:true}); }
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
  // Guided form: the complete form remains available when JavaScript is off.
  if (intake) {
    const steps = Array.from(intake.querySelectorAll('[data-intake-step]'));
    const progress = intake.querySelector('.onboard-progress');
    const next = intake.querySelector('.onboard-step-next');
    const back = intake.querySelector('.onboard-step-back');
    const submit = intake.querySelector('.onboard-submit');
    if (steps.length === 2 && progress && next && back && submit) {
      let active = 0;
      goIntakeStep = index => {
        active = index === 1 ? 1 : 0;
        steps.forEach((step, i) => { step.hidden = i !== active; });
        next.hidden = active !== 0; back.hidden = active !== 1; submit.hidden = active !== 1;
        progress.hidden = false;
        progress.querySelector('.onboard-progress-text').textContent = active ? progress.dataset.second : progress.dataset.first;
        progress.classList.toggle('onboard-progress--second', active === 1);
      };
      next.addEventListener('click', () => {
        const invalid = Array.from(steps[0].querySelectorAll('input,select,textarea')).find(input => !input.disabled && !input.checkValidity());
        if (invalid) { invalid.reportValidity(); return; }
        goIntakeStep(1); progress.scrollIntoView({block:'nearest'});
        const first = Array.from(steps[1].querySelectorAll('input,select,textarea')).find(input => !input.disabled && !input.closest('[hidden]'));
        if (first) first.focus({preventScroll:true});
      });
      back.addEventListener('click', () => { goIntakeStep(0); progress.scrollIntoView({block:'nearest'}); next.focus({preventScroll:true}); });
      // Browser validation can find a field on a different step, including after
      // navigating directly from the category control.
      intake.addEventListener('invalid', event => {
        const index = steps.findIndex(step => step.contains(event.target));
        if (index !== -1 && index !== active) goIntakeStep(index);
      }, true);
      goIntakeStep(0);
    }
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
