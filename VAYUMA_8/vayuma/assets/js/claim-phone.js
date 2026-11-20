// Transfer only a phone-like value within this tab; never put it in a URL or server log.
(() => {
  const key = 'vayuma_claim_phone';
  document.querySelectorAll('[data-claim-source]').forEach(link => link.addEventListener('click', () => {
    const value = (document.getElementById(link.dataset.claimSource)?.value || '').trim();
    try {
      if (/^\+?[0-9\s().\-]{9,25}$/.test(value)) sessionStorage.setItem(key, value);
      else sessionStorage.removeItem(key);
    } catch (_) { /* Storage can be disabled; the claim form remains usable. */ }
  }));
  const phone = document.getElementById('claim-phone');
  if (phone) try {
    const value = sessionStorage.getItem(key); sessionStorage.removeItem(key);
    if (!phone.value && value && /^\+?[0-9\s().\-]{9,25}$/.test(value)) phone.value = value;
  } catch (_) { /* User can enter the number manually. */ }
})();
