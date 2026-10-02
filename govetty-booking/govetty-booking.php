<?php
/**
 * Plugin Name:       GoVetty Booking (CPP API Bridge)
 * Description:       Secure WordPress bridge to the Marpet CPP API. Exposes a set of
 *                     wp-json/govetty/v1/* REST routes that proxy the booking flow
 *                     (OTP login, registration, plans, Stripe checkout, slots, image
 *                     upload) to the external Marpet CPP API, so the ApiUser server
 *                     credential never has to be exposed to the browser.
 * Version:           1.9.0
 * Requires PHP:      7.4
 * Requires at least: 6.0
 * License:           GPL v2 or later
 * Text Domain:       govetty-booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'GOVETTY_BOOKING_VERSION', '1.9.0' );
define( 'GOVETTY_BOOKING_PATH', plugin_dir_path( __FILE__ ) );
define( 'GOVETTY_BOOKING_URL', plugin_dir_url( __FILE__ ) );

/**
 * --------------------------------------------------------------------------
 * Configuration
 * --------------------------------------------------------------------------
 * Define these in wp-config.php (preferred) or as real environment
 * variables. Do NOT hardcode them here and do NOT store them as a regular
 * wp_options row -- GOVETTY_API_TOKEN is the server-level ApiUser credential
 * and must stay out of the database and out of version control.
 *
 *   define( 'GOVETTY_API_BASE_URL', 'https://api.marpet.example.com' );
 *   define( 'GOVETTY_API_TOKEN', 'the-server-side-api-token' );
 */
if ( ! defined( 'GOVETTY_API_BASE_URL' ) ) {
	define( 'GOVETTY_API_BASE_URL', getenv( 'GOVETTY_API_BASE_URL' ) ?: '' );
}
if ( ! defined( 'GOVETTY_API_TOKEN' ) ) {
	define( 'GOVETTY_API_TOKEN', getenv( 'GOVETTY_API_TOKEN' ) ?: '' );
}

require_once GOVETTY_BOOKING_PATH . 'includes/class-govetty-api-client.php';
require_once GOVETTY_BOOKING_PATH . 'includes/class-govetty-customer-session.php';
require_once GOVETTY_BOOKING_PATH . 'includes/class-govetty-logger.php';
require_once GOVETTY_BOOKING_PATH . 'includes/class-govetty-settings.php';
require_once GOVETTY_BOOKING_PATH . 'includes/class-govetty-mock-api.php';
require_once GOVETTY_BOOKING_PATH . 'includes/class-govetty-mock-checkout.php';
require_once GOVETTY_BOOKING_PATH . 'includes/class-govetty-data-store.php';
require_once GOVETTY_BOOKING_PATH . 'includes/class-govetty-rest-routes.php';
require_once GOVETTY_BOOKING_PATH . 'includes/class-govetty-shortcodes.php';
require_once GOVETTY_BOOKING_PATH . 'includes/class-govetty-admin-menu.php';
require_once GOVETTY_BOOKING_PATH . 'includes/class-govetty-admin-customers.php';
require_once GOVETTY_BOOKING_PATH . 'includes/class-govetty-admin-subscriptions.php';
require_once GOVETTY_BOOKING_PATH . 'includes/class-govetty-admin-phone-requests.php';

register_activation_hook( __FILE__, array( 'Govetty_Data_Store', 'install' ) );

add_action( 'rest_api_init', array( 'Govetty_Rest_Routes', 'register_routes' ) );
add_action( 'init', array( 'Govetty_Shortcodes', 'init' ) );
add_action( 'init', array( 'Govetty_Mock_Checkout', 'init' ) );
// Safety net for sites that had the plugin active before these tables
// existed -- a plain file update doesn't fire register_activation_hook.
add_action( 'admin_init', array( 'Govetty_Data_Store', 'maybe_install' ) );
Govetty_Settings::init();
Govetty_Admin_Menu::init();

/**
 * Registers (but doesn't enqueue) a handle carrying the REST root + a
 * nonce, so any frontend script can do:
 *   fetch( GovettyBooking.root + 'otp/start', {
 *     method: 'POST',
 *     credentials: 'same-origin',
 *     headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': GovettyBooking.nonce },
 *     body: JSON.stringify({ phone: '6135550123' })
 *   })
 *
 * Registered globally (cheap -- it's just wp_localize_script bookkeeping,
 * nothing is downloaded), but only actually enqueued -- alongside the real
 * app bundle -- on pages that contain one of this plugin's shortcodes.
 * See Govetty_Shortcodes::maybe_enqueue_assets().
 */
add_action( 'wp_enqueue_scripts', 'govetty_booking_register_config' );
function govetty_booking_register_config() {
	wp_register_script( 'govetty-booking-config', false, array(), GOVETTY_BOOKING_VERSION, true );
	wp_localize_script(
		'govetty-booking-config',
		'GovettyBooking',
		array(
			'root'            => esc_url_raw( rest_url( Govetty_Rest_Routes::NAMESPACE_ . '/' ) ),
			'nonce'           => wp_create_nonce( 'wp_rest' ),
			'mockMode'        => Govetty_Settings::is_mock_mode(),
			'mockScenario'    => Govetty_Settings::scenario(),
			'personalAreaUrl' => esc_url_raw( Govetty_Rest_Routes::personal_area_page_url() ),
		)
	);
}

/**
 * Warn in wp-admin if the plugin isn't configured yet, instead of failing
 * silently on every API call.
 */
add_action( 'admin_notices', 'govetty_booking_check_config' );
function govetty_booking_check_config() {
	if ( empty( GOVETTY_API_BASE_URL ) || empty( GOVETTY_API_TOKEN ) ) {
		echo '<div class="notice notice-error"><p><strong>GoVetty Booking:</strong> '
			. 'GOVETTY_API_BASE_URL and/or GOVETTY_API_TOKEN are not defined. '
			. 'Set them in wp-config.php before this plugin can reach the CPP API.</p></div>';
	}
}
