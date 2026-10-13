<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings > GoVetty Booking. A mock-mode toggle so the whole flow can be
 * clicked through without a live CPP API connection or real Stripe/SMS
 * costs (which customer you get is decided by the phone number's last
 * digit -- see Govetty_Mock_Api), plus the transaction-logging toggle.
 */
class Govetty_Settings {

	const OPTION_MOCK_MODE = 'govetty_booking_mock_mode';
	const OPTION_LOGGING   = 'govetty_booking_enable_logging';

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_notices', array( __CLASS__, 'maybe_show_notice' ) );
	}

	public static function is_mock_mode() {
		return '1' === get_option( self::OPTION_MOCK_MODE, '0' );
	}

	public static function is_logging_enabled() {
		return '1' === get_option( self::OPTION_LOGGING, '0' );
	}

	public static function register_settings() {
		register_setting( 'govetty_booking_settings', self::OPTION_MOCK_MODE, array( 'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ) ) );
		register_setting( 'govetty_booking_settings', self::OPTION_LOGGING, array( 'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ) ) );

		add_settings_section( 'govetty_booking_main', 'Mock mode', array( __CLASS__, 'section_intro' ), 'govetty-booking-settings' );
		add_settings_field( self::OPTION_MOCK_MODE, 'Enable mock mode', array( __CLASS__, 'field_mock_mode' ), 'govetty-booking-settings', 'govetty_booking_main' );

		add_settings_section( 'govetty_booking_logging', 'Transaction logging', array( __CLASS__, 'section_logging_intro' ), 'govetty-booking-settings' );
		add_settings_field( self::OPTION_LOGGING, 'Enable transaction logging', array( __CLASS__, 'field_logging' ), 'govetty-booking-settings', 'govetty_booking_logging' );
	}

	public static function sanitize_checkbox( $value ) {
		return '1' === $value ? '1' : '0';
	}

	public static function section_intro() {
		echo '<p>When mock mode is on, the booking flow (' . esc_html__( '[govetty_booking] / [govetty_personal_area]', 'govetty-booking' )
			. ') never calls the real CPP API or Stripe. OTP sending is skipped, any 6-digit code is accepted, '
			. 'and Stripe checkout is replaced with a simple test page so you can click through the full flow end to end.</p>'
			. '<p>Every scenario is available at once -- which customer you get depends on the <strong>last digit of the phone number</strong> you enter:</p>'
			. '<ul style="list-style:disc;margin-left:20px">'
			. '<li><strong>1</strong> &mdash; existing customer, two pets: Biscuit has calls, Whiskers has none</li>'
			. '<li><strong>2</strong> &mdash; existing customer, one pet (Whiskers) with no calls &rarr; goes straight to Plan Selection</li>'
			. '<li><strong>3</strong> &mdash; existing customer, one pet (Biscuit) with calls &rarr; Pet Selection</li>'
			. '<li><strong>4</strong> &mdash; existing customer, two pets, neither has calls &rarr; Pet Selection, then Plan Selection</li>'
			. '<li><strong>any other digit</strong> &mdash; new customer &rarr; Registration (and recognized as existing afterwards)</li>'
			. '</ul>'
			. '<p>Buying a plan on the mock checkout page adds calls to that pet, so you can walk from &ldquo;no balance&rdquo; into &ldquo;has balance&rdquo;.</p>';
	}

	public static function field_mock_mode() {
		// WordPress only calls update_option() for fields present in the
		// submitted form -- an unchecked checkbox isn't submitted at all,
		// so without this hidden fallback, unchecking the box and saving
		// silently leaves mock mode on. The hidden field below always
		// submits "0"; the checkbox (later in the DOM) overrides it with
		// "1" when checked, since browsers keep the last value for
		// duplicate field names.
		printf(
			'<input type="hidden" name="%1$s" value="0">'
			. '<label><input type="checkbox" name="%1$s" value="1" %2$s> Enable mock mode -- no real API, SMS, or Stripe calls are made</label>',
			esc_attr( self::OPTION_MOCK_MODE ),
			checked( self::is_mock_mode(), true, false )
		);
	}

	public static function section_logging_intro() {
		echo '<p>Writes one plain-text log file per day -- OTP sends/verifications, registrations, checkout sessions '
			. 'and their success/cancel returns, bookings, and phone-change requests (approvals/rejections included) '
			. '-- to <code>' . esc_html( str_replace( ABSPATH, '', Govetty_Logger::dir_path() ) ) . '</code>. '
			. 'That folder lives alongside <code>wp-content/plugins/</code>, not inside this plugin\'s own folder, '
			. 'so updating the plugin never touches or clears it.</p>'
			. '<p class="description">Entries can include customer phone numbers and names (the same data already '
			. 'stored in this plugin\'s own tables -- see the Customers screen). The folder is locked down from '
			. 'direct web access via <code>.htaccess</code>, which protects it on Apache; if this site runs on '
			. 'Nginx, ask whoever manages the server to add an equivalent deny rule for that path.</p>';
	}

	public static function field_logging() {
		printf(
			'<input type="hidden" name="%1$s" value="0">'
			. '<label><input type="checkbox" name="%1$s" value="1" %2$s> Enable transaction logging</label>',
			esc_attr( self::OPTION_LOGGING ),
			checked( self::is_logging_enabled(), true, false )
		);
	}

	public static function render_settings_page() {
		?>
		<div class="wrap">
			<h1>GoVetty Booking</h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'govetty_booking_settings' );
				do_settings_sections( 'govetty-booking-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public static function maybe_show_notice() {
		if ( ! self::is_mock_mode() ) {
			return;
		}
		echo '<div class="notice notice-warning"><p><strong>GoVetty Booking:</strong> Mock mode is ON -- '
			. 'the booking flow is using fake data and will not reach the real CPP API or Stripe. '
			. '<a href="' . esc_url( Govetty_Admin_Menu::url( '-settings' ) ) . '">Turn it off</a> before launch.</p></div>';
	}
}
