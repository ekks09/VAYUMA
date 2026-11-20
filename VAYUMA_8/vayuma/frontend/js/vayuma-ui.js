/* =========================================
   VAYUMA CLEAN — UI JavaScript
   Toasts and simple helpers.
   ========================================= */

(function () {
  "use strict";

  function initializeSidebar() {
    const sidebar = document.getElementById("vaSidebar") || document.getElementById("vaDashSidebar");
    const overlay = document.getElementById("vaSidebarOverlay");
    const opener = sidebar
      ? document.querySelector('.va-mobile-menu-btn[aria-controls="' + sidebar.id + '"]')
      : null;
    if (!sidebar || !opener) return;

    const dashboardSidebar = sidebar.id === "vaDashSidebar";
    const openClass = dashboardSidebar ? "va-dash-sidebar--open" : "va-sidebar--open";
    const gridShell = !!sidebar.closest(".va-dash-layout, .va-simple-dashboard-grid");
    const mobile = window.matchMedia(gridShell ? "(max-width: 767px)" : (dashboardSidebar ? "(max-width: 650px)" : "(max-width: 1024px)"));
    let returnFocus = opener;
    let closeButton = sidebar.querySelector(".va-sidebar-close");

    if (!closeButton) {
      closeButton = document.createElement("button");
      closeButton.className = "va-sidebar-close";
      closeButton.type = "button";
      closeButton.setAttribute("aria-label", "Close navigation");
      closeButton.innerHTML = '<span aria-hidden="true">&times;</span>';
      sidebar.insertBefore(closeButton, sidebar.firstChild);
    }

    function setClosedState() {
      if (!mobile.matches) {
        sidebar.classList.remove(openClass);
        if (overlay) overlay.classList.remove("va-sidebar-overlay--active");
        document.body.classList.remove("va-sidebar-open");
        sidebar.inert = false;
        sidebar.removeAttribute("aria-hidden");
        opener.setAttribute("aria-expanded", "false");
        opener.setAttribute("aria-label", "Open navigation");
        return;
      }

      if (!sidebar.classList.contains(openClass)) {
        sidebar.inert = true;
        sidebar.setAttribute("aria-hidden", "true");
        opener.setAttribute("aria-expanded", "false");
        opener.setAttribute("aria-label", "Open navigation");
      }
    }

    window.vaOpenSidebar = function () {
      if (!mobile.matches) return;
      returnFocus = document.activeElement instanceof HTMLElement ? document.activeElement : opener;
      sidebar.inert = false;
      sidebar.removeAttribute("aria-hidden");
      sidebar.classList.add(openClass);
      if (overlay) overlay.classList.add("va-sidebar-overlay--active");
      document.body.classList.add("va-sidebar-open");
      opener.setAttribute("aria-expanded", "true");
      opener.setAttribute("aria-label", "Close navigation");
      closeButton.focus();
    };

    window.vaCloseSidebar = function () {
      const wasOpen = sidebar.classList.contains(openClass);
      sidebar.classList.remove(openClass);
      if (overlay) overlay.classList.remove("va-sidebar-overlay--active");
      document.body.classList.remove("va-sidebar-open");
      opener.setAttribute("aria-expanded", "false");
      opener.setAttribute("aria-label", "Open navigation");
      setClosedState();
      if (wasOpen && mobile.matches && returnFocus instanceof HTMLElement) returnFocus.focus();
    };

    opener.addEventListener("click", function () {
      if (sidebar.classList.contains(openClass)) window.vaCloseSidebar();
      else window.vaOpenSidebar();
    });
    closeButton.addEventListener("click", window.vaCloseSidebar);
    if (overlay) overlay.addEventListener("click", window.vaCloseSidebar);
    sidebar.querySelectorAll(dashboardSidebar ? ".va-dash-sidebar--nav-item[href]" : ".va-nav-item").forEach(function (item) {
      item.addEventListener("click", function () {
        if (mobile.matches) window.vaCloseSidebar();
      });
    });

    document.addEventListener("keydown", function (event) {
      if (!sidebar.classList.contains(openClass)) return;
      if (event.key === "Escape") {
        event.preventDefault();
        window.vaCloseSidebar();
        return;
      }
      if (event.key !== "Tab") return;

      const focusable = Array.from(sidebar.querySelectorAll(
        'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
      )).filter(function (element) {
        return !element.hasAttribute("hidden");
      });
      if (!focusable.length) return;

      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });

    if (typeof mobile.addEventListener === "function") {
      mobile.addEventListener("change", setClosedState);
    } else {
      mobile.addListener(setClosedState);
    }
    setClosedState();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializeSidebar, { once: true });
  } else {
    initializeSidebar();
  }

  const container = (() => {
    let el = document.getElementById("vaToastContainer");
    if (!el) {
      el = document.createElement("div");
      el.id = "vaToastContainer";
      el.className = "va-toast-container";
      document.body.appendChild(el);
    }
    return el;
  })();

  window.vaToast = function (message, type, duration) {
    type = type || "success";
    duration = duration || 4000;
    const t = document.createElement("div");
    t.className = "va-toast va-toast--" + type;
    const esc = function (s) {
      const d = document.createElement("div");
      d.textContent = s;
      return d.innerHTML;
    };
    const title = type === "success" ? "Success" : type === "error" ? "Error" : "Notice";
    t.innerHTML =
      '<div class="va-toast--title">' + esc(title) + '</div>' +
      '<div class="va-toast--msg">' + esc(message) + '</div>';
    container.appendChild(t);
    setTimeout(function () {
      t.style.opacity = "0";
      t.style.transform = "translateX(100%)";
      setTimeout(function () { t.remove(); }, 300);
    }, duration);
  };

  window.vaValidate = function (formSelector) {
    const f = document.querySelector(formSelector);
    if (!f) return true;
    let ok = true;
    f.querySelectorAll("input[required], select[required], textarea[required]").forEach(function (field) {
      if (!field.value.trim()) {
        field.classList.add("va-input--error");
        ok = false;
      } else {
        field.classList.remove("va-input--error");
      }
    });
    return ok;
  };
})();
