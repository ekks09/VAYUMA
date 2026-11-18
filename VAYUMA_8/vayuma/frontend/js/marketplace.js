/* =========================================
   VAYUMA — Marketplace
   Product card interactions, category
   filters, search persistence.
   ========================================= */

(function () {
  'use strict';

  window.vaMarketplace = {
    initProductCards: function () {
      document.querySelectorAll('.va-product-card').forEach(function (card) {
        card.addEventListener('click', function (e) {
          if (e.target.tagName === 'A' || e.target.closest('a, button')) return;
          const link = card.querySelector('a[href*="produce_details"]');
          if (link) window.location.href = link.getAttribute('href');
        });
      });
    },

    initCategoryFilter: function () {
      const chips = document.querySelectorAll('.va-category-chip');
      chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
          chips.forEach(function (c) { c.classList.remove('va-category-chip--active'); });
          chip.classList.add('va-category-chip--active');
          const category = chip.getAttribute('data-category');
          const url = new URL(window.location.href);
          url.searchParams.set('category', category);
          window.location.href = url.toString();
        });
      });
    },

    persistSearch: function () {
      const form = document.querySelector('form[action*="market_connect"]');
      if (form) {
        form.addEventListener('submit', function () {
          const input = form.querySelector('input[name="search"]');
          if (input && input.value.trim()) {
            const url = new URL(form.action);
            url.searchParams.set('search', input.value.trim());
            form.action = url.toString();
          }
        });
      }
    }
  };

  if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', function () {
      window.vaMarketplace.initProductCards();
      window.vaMarketplace.initCategoryFilter();
      window.vaMarketplace.persistSearch();
    });
  }
})();
