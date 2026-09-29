<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings > GoVetty Booking. A single toggle plus a scenario picker so the
 * whole flow can be clicked through without a live CPP API connection or
 * real Stripe/SMS costs.
 */
class Govetty_Settings {

	const OPTION_MOCK_MODE = 'govetty_booking_mock_mode';
	const OPTION_SCENARIO  = 'govetty_booking_mock_scenario';

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_notices', array( __CLASS__, 'maybe_show_notice' ) );
	}

	public static function is_mock_mode() {
		return '1' === get_option( self::OPTION_MOCK_MODE, '0' );
	}

	public static function scenario() {
		$value = get_option( self::OPTION_SCENARIO, 'new_customer' );
		return in_array( $value, array( 'new_customer', 'existing_customer' ), true ) ? $value : 'new_customer';
	}

	public static function register_settings() {
		register_setting( 'govetty_booking_settings', self::OPTION_MOCK_MODE, array( 'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ) ) );
		register_setting( 'govetty_booking_settings', self::OPTION_SCENARIO, array( 'sanitize_callback' => array( __CLASS__, 'sanitize_scenario' ) ) );

		add_settings_section( 'govetty_booking_main', 'Mock mode', array( __CLASS__, 'section_intro' ), 'govetty-booking-settings' );
		add_settings_field( self::OPTION_MOCK_MODE, 'Enable mock mode', array( __CLASS__, 'field_mock_mode' ), 'govetty-booking-settings', 'govetty_booking_main' );
		add_settings_field( self::OPTION_SCENARIO, 'Scenario to simulate', array( __CLASS__, 'field_scenario' ), 'govetty-booking-settings', 'govetty_booking_main' );
	}

	public static function sanitize_checkbox( $value ) {
		return '1' === $value ? '1' : '0';
	}

	public static function sanitize_scenario( $value ) {
		return in_array( $value, array( 'new_customer', 'existing_customer' ), true ) ? $value : 'new_customer';
	}

	public static function section_intro() {
		echo '<p>When mock mode is on, the booking flow (' . esc_html__( '[govetty_booking] / [govetty_personal_area]', 'govetty-booking' )
			. ') never calls the real CPP API or Stripe. OTP sending is skipped, any 6-digit code is accepted, '
			. 'and Stripe checkout is replaced with a simple test page so you can click through the full flow end to end.</p>';
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

	public static function field_scenario() {
		$current = self::scenario();
		?>
		<select name="<?php echo esc_attr( self::OPTION_SCENARIO ); ?>">
			<option value="new_customer" <?php selected( $current, 'new_customer' ); ?>>New customer (registration flow)</option>
			<option value="existing_customer" <?php selected( $current, 'existing_customer' ); ?>>Existing customer -- 2 pets, one with a call balance and one without</option>
		</select>
		<p class="description">
			Controls what a successful OTP verification returns. The "existing customer" scenario has one pet
			with a balance and one without, so you can reach both Pet Selection branches from a single test run.
		</p>
		<?php
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
