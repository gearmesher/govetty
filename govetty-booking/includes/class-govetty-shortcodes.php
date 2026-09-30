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
	 * Server-rendered login/logout link -- no React, so it's cheap enough
	 * to sit in the header on every page. Login state is read straight from
	 * the customer_token cookie (Govetty_Customer_Session), the same signal
	 * PersonalAreaImageUpload.jsx's /info/user call relies on -- presence of
	 * the cookie means "looks logged in", not a guarantee the token is still
	 * valid upstream (that's only checked when it's actually used).
	 *
	 * If this page is served from a full-page cache (e.g. a caching plugin)
	 * for anonymous visitors, make sure that cache varies on the
	 * govetty_customer_token cookie, or logged-in customers can get served a
	 * cached "Log in" link.
	 *
	 * Attributes:
	 *   login_text, logout_text  -- link label for each state.
	 *   login_url                -- defaults to the [govetty_booking] page
	 *                                (Govetty_Rest_Routes::booking_page_url()),
	 *                                since that's where OTP login happens.
	 *   redirect                 -- where to land after logout; defaults to
	 *                                the current URL.
	 *   class                    -- class on the wrapping <a>.
	 *   text_class                -- optional class wrapping just the label
	 *                                text in its own <span> (e.g. to reuse a
	 *                                theme's existing responsive show/hide
	 *                                class on the text vs. an icon).
	 *   icon                     -- raw HTML (e.g. an inline <svg>) rendered
	 *                                alongside the label; only ever set this
	 *                                from trusted template code, never from
	 *                                user input.
	 */
	public static function render_login( $atts ) {
		$atts = shortcode_atts(
			array(
				'login_text'  => __( 'Log in', 'govetty-booking' ),
				'logout_text' => __( 'Logout', 'govetty-booking' ),
				'login_url'   => '',
				'redirect'    => '',
				'class'       => 'gv-login-link',
				'text_class'  => '',
				'icon'        => '',
			),
			$atts,
			'govetty_login'
		);

		$logged_in = (bool) Govetty_Customer_Session::get_token();
		$icon      = $atts['icon']; // Raw HTML by design -- see docblock.

		if ( $logged_in ) {
			$redirect = $atts['redirect'] ? $atts['redirect'] : self::current_url();
			return sprintf(
				'<a href="#" class="%1$s" data-govetty-logout="1" data-redirect="%2$s">%3$s%4$s</a>',
				esc_attr( $atts['class'] ),
				esc_url( $redirect ),
				$icon,
				self::label( $atts['logout_text'], $atts['text_class'] )
			);
		}

		$login_url = $atts['login_url'] ? $atts['login_url'] : Govetty_Rest_Routes::booking_page_url();
		return sprintf(
			'<a href="%1$s" class="%2$s">%3$s%4$s</a>',
			esc_url( $login_url ),
			esc_attr( $atts['class'] ),
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
		// dropped into the theme header via do_shortcode() -- so it's
		// enqueued unconditionally rather than gated behind has_shortcode()
		// like the full booking app bundle below.
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
