/**
 * Behavior for the [govetty_login] shortcode: the logged-in "Dashboard"
 * account popup, and the logout click handler inside it.
 *
 * Deliberately plain JS with no build step, since this loads sitewide
 * (e.g. from the theme header on every page) rather than only on the
 * booking/personal-area pages that pull in the React bundle. Depends on
 * the `govetty-booking-config` handle for window.GovettyBooking.root/nonce
 * (see govetty-booking.php's govetty_booking_register_config()).
 *
 * The popup itself is a native <details>/<summary> element (see
 * Govetty_Shortcodes::render_login()) -- that already gives tap-to-toggle
 * and keyboard support with zero JS, which is the actual mobile-safe
 * baseline. Everything below is progressive enhancement on top of that:
 * hover-to-open on devices with real hover, closing on an outside click,
 * and closing on Escape. If this script fails to load for any reason, the
 * popup still opens and closes fine via tap/click on the <summary>.
 */
(function () {
  'use strict';

  function closeAllMenusExcept(except) {
    document.querySelectorAll('.gv-login-menu[open]').forEach(function (menu) {
      if (menu !== except) {
        menu.open = false;
      }
    });
  }

  // Only one popup open at a time (defensive -- normally there's just one
  // instance on the page, e.g. in the header).
  document.addEventListener(
    'toggle',
    function (event) {
      var menu = event.target;
      if (menu.matches && menu.matches('.gv-login-menu') && menu.open) {
        closeAllMenusExcept(menu);
      }
    },
    true // 'toggle' doesn't bubble -- listen on the capture phase instead.
  );

  // Tapping/clicking anywhere outside an open popup closes it. Native
  // <details> has no built-in "close on outside click" behavior.
  document.addEventListener('click', function (event) {
    var openMenu = document.querySelector('.gv-login-menu[open]');
    if (openMenu && !openMenu.contains(event.target)) {
      openMenu.open = false;
    }
  });

  // Escape closes the open popup and returns focus to its toggle.
  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    var openMenu = document.querySelector('.gv-login-menu[open]');
    if (!openMenu) return;
    openMenu.open = false;
    var summary = openMenu.querySelector('summary');
    if (summary) summary.focus();
  });

  // Hover-to-open, only for devices that actually have real hover. Touch
  // devices are deliberately excluded -- a synthetic "hover" firing on tap
  // would otherwise force a touch user to tap twice (once to trigger the
  // hover-open, once more to actually follow a link), which is exactly the
  // mobile hover trap this needs to avoid. Touch/keyboard already get a
  // working popup for free from the native <details> tap/Enter toggle.
  var supportsHover =
    window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;

  if (supportsHover) {
    document.querySelectorAll('.gv-login-menu').forEach(function (menu) {
      var closeTimer = null;
      menu.addEventListener('mouseenter', function () {
        clearTimeout(closeTimer);
        menu.open = true;
      });
      menu.addEventListener('mouseleave', function () {
        // Small delay so moving the mouse from the toggle down into the
        // panel doesn't momentarily close it in transit.
        closeTimer = setTimeout(function () {
          menu.open = false;
        }, 150);
      });
    });
  }

  // Call balance in the dropdown. Fetched the first time the menu opens (not
  // on every page load, to keep the sitewide header cheap), summed across the
  // customer's pets from /info/user. Failures just leave the row hidden.
  var balanceState = 'idle';

  function renderBalance(menu, total) {
    var row = menu.querySelector('[data-govetty-balance]');
    var value = menu.querySelector('[data-govetty-balance-value]');
    if (!row || !value) return;
    value.textContent = total === 1 ? '1 call' : total + ' calls';
    row.hidden = false;
  }

  function loadBalance(menu) {
    var config = window.GovettyBooking;
    if (balanceState !== 'idle' || !config || !config.root) return;
    balanceState = 'loading';
    fetch(config.root + 'info/user', {
      credentials: 'same-origin',
      headers: { 'X-WP-Nonce': config.nonce },
    })
      .then(function (res) {
        return res.ok ? res.json() : null;
      })
      .then(function (data) {
        var pets = data && (data.pets || (data.data && data.data.pets));
        if (!Array.isArray(pets)) {
          balanceState = 'idle'; // Try again next time it opens.
          return;
        }
        var total = pets.reduce(function (sum, pet) {
          return sum + (parseInt(pet.token_amount, 10) || 0);
        }, 0);
        balanceState = 'done';
        renderBalance(menu, total);
      })
      .catch(function () {
        balanceState = 'idle';
      });
  }

  document.addEventListener(
    'toggle',
    function (event) {
      var menu = event.target;
      if (menu.matches && menu.matches('.gv-login-menu') && menu.open) {
        loadBalance(menu);
      }
    },
    true
  );

  // Logout: works whether the [data-govetty-logout] control is the plain
  // link from the logged-out... er, logged-in-without-a-popup case, or the
  // menu item button inside the new popup -- same handler either way.
  document.addEventListener('click', function (event) {
    var link = event.target.closest ? event.target.closest('[data-govetty-logout]') : null;
    if (!link) {
      return;
    }
    event.preventDefault();

    if (link.getAttribute('aria-busy') === 'true') {
      return; // Already logging out -- ignore a double-click.
    }

    var redirect = link.getAttribute('data-redirect') || window.location.href;
    var config = window.GovettyBooking;

    if (!config || !config.root) {
      // Config didn't load for some reason -- still send them off the
      // page rather than leaving a dead control, even though the cookie
      // won't get cleared.
      window.location.href = redirect;
      return;
    }

    link.setAttribute('aria-busy', 'true');

    fetch(config.root + 'logout', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-WP-Nonce': config.nonce },
    })
      .catch(function () {
        // Network error -- nothing more to retry here; fall through to
        // the redirect below either way so the visitor isn't stuck.
      })
      .then(function () {
        // A changing query value defeats page caches that would otherwise
        // hand back the cached logged-in header after the redirect.
        try {
          var target = new URL(redirect, window.location.href);
          target.searchParams.set('govetty_logged_out', String(Date.now()));
          window.location.href = target.toString();
        } catch (e) {
          window.location.href = redirect;
        }
      });
  });

  // "You've been logged out" notice. The logout handler above redirects with
  // ?govetty_logged_out=<timestamp>; show a small fixed popup once, then strip
  // the parameter so a refresh clears it (it stays until then -- no timer).
  (function showLoggedOutNotice() {
    var url;
    try {
      url = new URL(window.location.href);
    } catch (e) {
      return;
    }
    if (!url.searchParams.has('govetty_logged_out')) {
      return;
    }

    url.searchParams.delete('govetty_logged_out');
    if (window.history && window.history.replaceState) {
      window.history.replaceState(null, '', url.pathname + url.search + url.hash);
    }

    function render() {
      var note = document.createElement('div');
      note.className = 'gv-toast';
      note.setAttribute('role', 'status');

      var text = document.createElement('span');
      text.textContent = "You've been logged out.";
      note.appendChild(text);

      var close = document.createElement('button');
      close.type = 'button';
      close.className = 'gv-toast-close';
      close.setAttribute('aria-label', 'Dismiss');
      close.textContent = '\u00d7';
      close.addEventListener('click', function () {
        note.remove();
      });
      note.appendChild(close);

      document.body.appendChild(note);
    }

    if (document.body) {
      render();
    } else {
      document.addEventListener('DOMContentLoaded', render);
    }
  })();
})();
