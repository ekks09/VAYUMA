/* Add accessible semantics to existing server/client feedback. Never invent or
   automatically dismiss a message, and never inspect or transmit form values. */
(() => {
  const errorSelector = '.form-alert, .login-error, .va-profile-error, .va-form-error, .va-alert--error, .va-toast--error, .onboard-error, .notice.error';
  const statusSelector = '.login-success, .va-alert--success, .va-toast--success, .onboard-notice, .notice.success';
  const enhance = element => {
    if (!element || element.nodeType !== 1) return;
    for (const [selector, role] of [[errorSelector, 'alert'], [statusSelector, 'status']]) {
      if (element.matches(selector) && !element.hasAttribute('role')) element.setAttribute('role', role);
      element.querySelectorAll(selector).forEach(node => { if (!node.hasAttribute('role')) node.setAttribute('role', role); });
    }
  };
  const start = () => {
    enhance(document.body);
    new MutationObserver(changes => changes.forEach(change => change.addedNodes.forEach(enhance))).observe(document.body, {childList:true, subtree:true});
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, {once:true}); else start();
})();
