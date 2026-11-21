// Progressive enhancement only; server validation and authorization remain authoritative.
(() => {
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
  const button = document.getElementById('copy-claim-code');
  const code = document.getElementById('staff-claim-code');
  const status = document.getElementById('copy-claim-status');
  if (button && code && status && window.isSecureContext && navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
    button.hidden = false;
    button.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(code.textContent.trim());
        status.textContent = button.dataset.copied;
      } catch (_) {
        status.textContent = button.dataset.failed;
      }
    });
  }
})();
