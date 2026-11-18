/* =========================================
   VAYUMA — Notifications
   Toast dispatch helper used across pages.
   ========================================= */

(function () {
  'use strict';

  window.vaNotify = {
    success: function (msg, duration) {
      if (typeof window.vaToast === 'function') window.vaToast(msg, 'success', duration);
    },
    error: function (msg, duration) {
      if (typeof window.vaToast === 'function') window.vaToast(msg, 'error', duration);
    },
    warning: function (msg, duration) {
      if (typeof window.vaToast === 'function') window.vaToast(msg, 'warning', duration);
    }
  };
})();
