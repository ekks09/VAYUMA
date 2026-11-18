/* =========================================
   VAYUMA — Subscriptions
   Stakeholder selection, Paystack payment
   initiation, payment state handling.
   ========================================= */

(function () {
  'use strict';

  window.vaSubscription = {
    initStakeholderCards: function () {
      document.querySelectorAll('.va-stakeholder-card').forEach(function (card) {
        card.addEventListener('click', function (e) {
          if (e.target.tagName === 'A' || e.target.closest('a')) return;
          const link = card.querySelector('a');
          if (link) window.location.href = link.getAttribute('href');
        });
      });
    },

    initPaymentButton: function (selector, onInit) {
      const btn = document.querySelector(selector);
      if (!btn) return;
      btn.addEventListener('click', function (e) {
        if (btn.hasAttribute('disabled')) {
          e.preventDefault();
          return;
        }
        if (typeof onInit === 'function') {
          e.preventDefault();
          window.vaButtonLoading(btn, true);
          onInit(function () {
            window.vaButtonLoading(btn, false);
          }, function (err) {
            window.vaButtonLoading(btn, false);
            if (typeof window.vaNotify === 'object') {
              window.vaNotify.error(err || 'Payment could not be initialized. Please try again.');
            }
          });
        }
      });
    },

    showPaymentPending: function (msg) {
      if (typeof window.vaNotify === 'object') {
        window.vaNotify.warning(msg || 'Payment is being verified. Please wait.');
      }
    },

    showPaymentSuccess: function (msg) {
      if (typeof window.vaNotify === 'object') {
        window.vaNotify.success(msg || 'Payment verified successfully. Your subscription is now active.');
      }
    },

    showPaymentFailed: function (msg) {
      if (typeof window.vaNotify === 'object') {
        window.vaNotify.error(msg || 'Payment failed. Please try again.');
      }
    }
  };

  if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', function () {
      window.vaSubscription.initStakeholderCards();
    });
  }
})();
