<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers [govetty_booking] and [govetty_personal_area] and only loads the
 * built JS/CSS bundle on pages that actually use one of them -- the rest
 * of the site stays untouched.
 */
class Govetty_Shortcodes {

	public static function init() {
		add_shortcode( 'govetty_booking', array( __CLASS__, 'render_booking' ) );
		add_shortcode( 'govetty_personal_area', array( __CLASS__, 'render_personal_area' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue_assets' ) );
	}

	public static function render_booking() {
		return '<div id="govetty-booking-root"></div>';
	}

	public static function render_personal_area() {
		return '<div id="govetty-personal-area-root"></div>';
	}

	public static function maybe_enqueue_assets() {
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
