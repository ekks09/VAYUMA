(function () {
  function init() {
    document.querySelectorAll('.vy-user-menu').forEach(function (menu) {
      const trigger = menu.querySelector('summary');
      function sync() { trigger.setAttribute('aria-expanded', menu.open ? 'true' : 'false'); }
      menu.addEventListener('toggle', sync);
      document.addEventListener('click', function (event) { if (menu.open && !menu.contains(event.target)) { menu.open = false; sync(); } });
      document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && menu.open) { menu.open = false; sync(); trigger.focus(); } });
      sync();
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once:true }); else init();
})();
