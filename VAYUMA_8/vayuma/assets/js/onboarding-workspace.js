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
  const exportForm = document.querySelector('form[action="farmer_export.php"]');
  if (exportForm) exportForm.addEventListener('submit', event => {
    if (navigator.onLine === false) { event.preventDefault(); syncConnectivity(); offline?.scrollIntoView({block:'nearest'}); }
  });
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
    role.addEventListener('change', updateFields);
    updateFields();
  }
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
