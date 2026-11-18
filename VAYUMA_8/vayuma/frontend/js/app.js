/* =========================================
   VAYUMA CLEAN — UI JavaScript
   Toasts, navigation, form validation,
   subscriptions, marketplace helpers.
   ========================================= */

(function () {
  'use strict';

  /* ── Toast notifications ── */
  const container = (() => {
    let el = document.getElementById('vaToastContainer');
    if (!el) {
      el = document.createElement('div');
      el.id = 'vaToastContainer';
      el.className = 'va-toast-container';
      document.body.appendChild(el);
    }
    return el;
  })();

  window.vaToast = function (message, type, duration) {
    type = type || 'success';
    duration = duration || 4000;
    const t = document.createElement('div');
    t.className = 'va-toast va-toast--' + type;
    const esc = function (s) {
      const d = document.createElement('div');
      d.textContent = s;
      return d.innerHTML;
    };
    const title = type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Notice';
    t.innerHTML =
      '<div class="va-toast--title">' + esc(title) + '</div>' +
      '<div class="va-toast--msg">' + esc(message) + '</div>';
    container.appendChild(t);
    setTimeout(function () {
      t.style.opacity = '0';
      t.style.transform = 'translateX(100%)';
      setTimeout(function () { t.remove(); }, 300);
    }, duration);
  };

  /* ── Form validation ── */
  window.vaValidate = function (formSelector) {
    const f = document.querySelector(formSelector);
    if (!f) return true;
    let ok = true;
    f.querySelectorAll('input[required], select[required], textarea[required]').forEach(function (field) {
      if (!field.value.trim()) {
        field.classList.add('va-input--error');
        ok = false;
      } else {
        field.classList.remove('va-input--error');
      }
    });
    return ok;
  };

  /* ── Sidebar toggle (mobile) ── */
  window.vaToggleSidebar = function () {
    const sidebar = document.getElementById('vaSidebar');
    const overlay = document.getElementById('vaSidebarOverlay');
    if (sidebar) sidebar.classList.toggle('va-sidebar--open');
    if (overlay) overlay.classList.toggle('va-sidebar-overlay--active');
  };

  window.vaCloseSidebar = function () {
    const sidebar = document.getElementById('vaSidebar');
    const overlay = document.getElementById('vaSidebarOverlay');
    if (sidebar) sidebar.classList.remove('va-sidebar--open');
    if (overlay) overlay.classList.remove('va-sidebar-overlay--active');
  };

  /* ── Confirmation dialog ── */
  window.vaConfirm = function (message, onConfirm) {
    const overlay = document.createElement('div');
    overlay.className = 'va-modal-overlay';
    overlay.innerHTML =
      '<div class="va-modal">' +
      '  <div class="va-modal-body" style="text-align:center">' +
      '    <div class="va-confirm-dialog--title">Confirm action</div>' +
      '    <div class="va-confirm-dialog--desc">' + message + '</div>' +
      '    <div class="va-confirm-dialog--actions">' +
      '      <button type="button" class="va-btn va-btn--outline" onclick="this.closest(\'.va-modal-overlay\').remove()">Cancel</button>' +
      '      <button type="button" class="va-btn va-btn--primary" id="va-confirm-yes">Confirm</button>' +
      '    </div>' +
      '  </div>' +
      '</div>';
    document.body.appendChild(overlay);
    document.getElementById('va-confirm-yes').addEventListener('click', function () {
      overlay.remove();
      if (typeof onConfirm === 'function') onConfirm();
    });
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) overlay.remove();
    });
  };

  /* ── Button loading state ── */
  window.vaButtonLoading = function (btn, loading) {
    if (loading) {
      btn.setAttribute('disabled', 'disabled');
      btn.dataset.vaOriginalText = btn.innerHTML;
      btn.innerHTML = '<span style="display:inline-block;width:16px;height:16px;border:2px solid rgba(255,255,255,0.3);border-radius:50%;border-top-color:#fff;animation:spin 1s linear infinite;"></span>';
    } else {
      btn.removeAttribute('disabled');
      if (btn.dataset.vaOriginalText) {
        btn.innerHTML = btn.dataset.vaOriginalText;
        delete btn.dataset.vaOriginalText;
      }
    }
  };

  /* ── Dark mode toggle ── */
  window.vaToggleTheme = function () {
    const html = document.documentElement;
    const isDark = html.classList.toggle('va-dark');
    localStorage.setItem('vaTheme', isDark ? 'dark' : 'light');
    return isDark;
  };

  /* ── Initialize dark mode from localStorage ── */
  function vaInitTheme() {
    const saved = localStorage.getItem('vaTheme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (saved === 'dark' || (!saved && prefersDark)) {
      document.documentElement.classList.add('va-dark');
    }
  }

  /* ── Initialize on load ── */
  if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', function () {
      vaInitTheme();

      const themeToggle = document.querySelector('.va-theme-toggle');
      if (themeToggle) {
        themeToggle.addEventListener('click', window.vaToggleTheme);
      }

      const overlay = document.getElementById('vaSidebarOverlay');
      const toggle = document.querySelector('[data-va-toggle-sidebar]');
      if (overlay) {
        overlay.addEventListener('click', window.vaCloseSidebar);
      }
      if (toggle) {
        toggle.addEventListener('click', window.vaToggleSidebar);
      }
      document.querySelectorAll('.va-nav-item').forEach(function (item) {
        item.addEventListener('click', function () {
          if (window.innerWidth <= 768) {
            window.vaCloseSidebar();
          }
        });
      });
    });
  }
})();
