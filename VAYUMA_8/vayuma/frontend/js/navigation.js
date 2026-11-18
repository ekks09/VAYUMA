/* =========================================
   VAYUMA — Navigation helpers
   Active state management, breadcrumb,
   sidebar link highlighting.
   ========================================= */

(function () {
  'use strict';

  window.vaNav = {
    setActive: function (href) {
      document.querySelectorAll('.va-nav-item').forEach(function (item) {
        const itemHref = item.getAttribute('href');
        const isActive = itemHref === href || (href !== '/' && itemHref && href.startsWith(itemHref + '?'));
        item.classList.toggle('va-nav-item--active', isActive);
        if (isActive) {
          item.setAttribute('aria-current', 'page');
        } else {
          item.removeAttribute('aria-current');
        }
      });
    },

    highlightCurrent: function () {
      const currentPath = window.location.pathname.split('/').pop();
      const query = window.location.search;
      document.querySelectorAll('.va-nav-item').forEach(function (item) {
        const itemHref = item.getAttribute('href');
        const base = itemHref ? itemHref.split('?')[0] : '';
        const isActive = base === currentPath || (query && base === currentPath && itemHref.includes(query.split('=')[1] || ''));
        item.classList.toggle('va-nav-item--active', isActive);
        if (isActive) item.setAttribute('aria-current', 'page');
        else item.removeAttribute('aria-current');
      });
    }
  };

  if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', function () {
      window.vaNav.highlightCurrent();
    });
  }
})();
