/* A URL fragment is not sent to the server or written in HTTP access logs. */
(() => {
  const match = /^#token=([a-f0-9]{64})$/.exec(window.location.hash);
  if (!match) return;
  const input = document.getElementById('resetToken');
  if (!input) return;
  input.value = match[1];
  // Remove the credential from the address bar and the current history entry.
  try { history.replaceState(null, '', window.location.pathname + window.location.search); } catch (_) { /* The form remains usable. */ }
})();
