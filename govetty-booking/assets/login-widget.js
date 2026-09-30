/**
 * Click handler for the [govetty_login] shortcode's logout state.
 *
 * Deliberately plain JS with no build step, since this loads sitewide
 * (e.g. from the theme header on every page) rather than only on the
 * booking/personal-area pages that pull in the React bundle. Depends on
 * the `govetty-booking-config` handle for window.GovettyBooking.root/nonce
 * (see govetty-booking.php's govetty_booking_register_config()).
 */
(function () {
  'use strict';

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
      // page rather than leaving a dead link, even though the cookie
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
        window.location.href = redirect;
      });
  });
})();
