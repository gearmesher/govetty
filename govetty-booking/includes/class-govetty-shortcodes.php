<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers [govetty_booking], [govetty_personal_area] and [govetty_login],
 * and only loads the full React bundle on pages that actually use one of
 * the first two -- the rest of the site stays untouched. [govetty_login] is
 * handled separately below since, unlike the other two, it's meant to sit
 * in a template (e.g. the theme header) on every page, not just a specific
 * booking/account page.
 */
class Govetty_Shortcodes {

	public static function init() {
		add_shortcode( 'govetty_booking', array( __CLASS__, 'render_booking' ) );
		add_shortcode( 'govetty_personal_area', array( __CLASS__, 'render_personal_area' ) );
		add_shortcode( 'govetty_login', array( __CLASS__, 'render_login' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue_assets' ) );
	}

	public static function render_booking() {
		return '<div id="govetty-booking-root"></div>';
	}

	public static function render_personal_area() {
		return '<div id="govetty-personal-area-root"></div>';
	}

	/**
	 * Server-rendered login link / account popup -- no React, so it's cheap
	 * enough to sit in the header on every page. Login state is read
	 * straight from the customer_token cookie (Govetty_Customer_Session),
	 * the same signal the dashboard's /info/user call relies on -- presence
	 * of the cookie means "looks logged in", not a guarantee the token is
	 * still valid upstream (that's only checked when it's actually used).
	 *
	 * If this page is served from a full-page cache (e.g. a caching plugin)
	 * for anonymous visitors, make sure that cache varies on the
	 * govetty_customer_token cookie, or logged-in customers can get served a
	 * cached "Log in" link.
	 *
	 * Logged out: a plain <a> to the booking page (OTP login happens there).
	 *
	 * Logged in: a native <details>/<summary> popup labeled with
	 * dashboard_text ("Dashboard" by default) -- <details> works with tap
	 * and keyboard with no JS at all (best-practice baseline for a mobile
	 * disclosure widget); login-widget.js layers hover-to-open on top for
	 * devices with real hover, plus outside-click/Escape-to-close. First
	 * item in the popup is "Account" (links to the [govetty_personal_area]
	 * page), then "Logout".
	 *
	 * Attributes:
	 *   login_text                -- logged-out link label / aria-label.
	 *   dashboard_text             -- logged-in popup toggle label
	 *                                 (default "Dashboard").
	 *   account_text, logout_text -- the two popup menu item labels.
	 *   login_url                 -- defaults to the [govetty_booking] page
	 *                                 (Govetty_Rest_Routes::booking_page_url()).
	 *   account_url                -- defaults to the [govetty_personal_area]
	 *                                 page (Govetty_Rest_Routes::personal_area_page_url()).
	 *   redirect                  -- where to land after logout; defaults to
	 *                                 the current URL.
	 *   class                     -- class on the logged-out <a> / logged-in
	 *                                 <summary> toggle.
	 *   text_class                 -- optional class wrapping just the label
	 *                                 text in its own <span> (e.g. to reuse a
	 *                                 theme's existing responsive show/hide
	 *                                 class on the text vs. an icon).
	 *   icon                      -- raw HTML (e.g. an inline <svg>) rendered
	 *                                 alongside the label; only ever set this
	 *                                 from trusted template code, never from
	 *                                 user input.
	 */
	public static function render_login( $atts ) {
		$atts = shortcode_atts(
			array(
				'login_text'     => __( 'Log in', 'govetty-booking' ),
				'dashboard_text' => __( 'Dashboard', 'govetty-booking' ),
				'account_text'   => __( 'Account', 'govetty-booking' ),
				'logout_text'    => __( 'Logout', 'govetty-booking' ),
				'login_url'      => '',
				'account_url'    => '',
				'redirect'       => '',
				'class'          => 'gv-login-link',
				'text_class'     => '',
				'icon'           => '',
			),
			$atts,
			'govetty_login'
		);

		$logged_in = (bool) Govetty_Customer_Session::get_token();
		$icon      = $atts['icon']; // Raw HTML by design -- see docblock.

		if ( $logged_in ) {
			$redirect    = $atts['redirect'] ? $atts['redirect'] : self::current_url();
			$account_url = $atts['account_url'] ? $atts['account_url'] : Govetty_Rest_Routes::personal_area_page_url();

			return sprintf(
				'<details class="gv-login-menu">' .
					'<summary class="%1$s gv-login-menu-btn" aria-label="%2$s">%3$s%4$s</summary>' .
					'<div class="gv-login-menu-panel" role="menu">' .
						'<a href="%5$s" class="gv-login-menu-item" role="menuitem">%6$s</a>' .
						'<button type="button" class="gv-login-menu-item" role="menuitem" data-govetty-logout="1" data-redirect="%7$s">%8$s</button>' .
					'</div>' .
				'</details>',
				esc_attr( $atts['class'] ),
				esc_attr( $atts['dashboard_text'] ),
				$icon,
				self::label( $atts['dashboard_text'], $atts['text_class'] ),
				esc_url( $account_url ),
				esc_html( $atts['account_text'] ),
				esc_url( $redirect ),
				esc_html( $atts['logout_text'] )
			);
		}

		// govetty_intent=login tells the booking flow this click came from the
		// header's login control, not from "book a call" -- so once OTP
		// verifies an existing customer, bookingMachine.js sends them straight
		// to the Personal Area dashboard instead of into pet/plan selection.
		// A new (unregistered) phone still goes through registration either
		// way -- the intent only short-circuits the *existing customer*
		// branch. Appended even when login_url is overridden, since the
		// action is still "log in" regardless of which page it points at.
		$login_url = $atts['login_url'] ? $atts['login_url'] : Govetty_Rest_Routes::booking_page_url();
		$login_url = add_query_arg( 'govetty_intent', 'login', $login_url );
		return sprintf(
			'<a href="%1$s" class="%2$s" aria-label="%3$s">%4$s%5$s</a>',
			esc_url( $login_url ),
			esc_attr( $atts['class'] ),
			esc_attr( $atts['login_text'] ),
			$icon,
			self::label( $atts['login_text'], $atts['text_class'] )
		);
	}

	private static function label( $text, $text_class ) {
		if ( ! $text_class ) {
			return esc_html( $text );
		}
		return sprintf( '<span class="%1$s">%2$s</span>', esc_attr( $text_class ), esc_html( $text ) );
	}

	private static function current_url() {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
		return home_url( $request_uri );
	}

	public static function maybe_enqueue_assets() {
		// [govetty_login] is tiny (no React) and expected sitewide -- e.g.
		// dropped into the theme header via do_shortcode() -- so both its
		// script and stylesheet are enqueued unconditionally rather than
		// gated behind has_shortcode() like the full booking app bundle
		// below.
		wp_enqueue_style(
			'govetty-login-widget',
			GOVETTY_BOOKING_URL . 'assets/login-widget.css',
			array(),
			GOVETTY_BOOKING_VERSION
		);
		wp_enqueue_script(
			'govetty-login-widget',
			GOVETTY_BOOKING_URL . 'assets/login-widget.js',
			array( 'govetty-booking-config' ),
			GOVETTY_BOOKING_VERSION,
			true
		);

		if ( ! self::current_page_needs_assets() ) {
			return;
		}

		wp_enqueue_style(
			'govetty-booking-app',
			GOVETTY_BOOKING_URL . 'assets/build/booking-app.css',
			array(),
			GOVETTY_BOOKING_VERSION
		);

		// Registered (not just enqueued) in govetty-booking.php's
		// govetty_booking_register_config(), which also attaches the
		// GovettyBooking.root/nonce globals this bundle needs.
		wp_enqueue_script(
			'govetty-booking-app',
			GOVETTY_BOOKING_URL . 'assets/build/booking-app.js',
			array( 'govetty-booking-config' ),
			GOVETTY_BOOKING_VERSION,
			true
		);
	}

	/**
	 * True on any singular page/post whose content contains one of this
	 * plugin's shortcodes. Doesn't currently check widgets, template parts,
	 * or block-theme template content outside the post body -- if you're
	 * placing the shortcode somewhere other than the main content area,
	 * extend this check (or just always-enqueue on the specific booking
	 * page IDs via a filter).
	 */
	private static function current_page_needs_assets() {
		if ( ! is_singular() ) {
			return false;
		}
		$post = get_post();
		if ( ! $post ) {
			return false;
		}
		return has_shortcode( $post->post_content, 'govetty_booking' )
			|| has_shortcode( $post->post_content, 'govetty_personal_area' );
	}
}
