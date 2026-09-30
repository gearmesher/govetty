<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Govetty_Admin_Menu {

	const SLUG = 'govetty-booking';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
	}

	public static function register_menu() {
		add_menu_page(
			'GoVetty Booking',
			'GoVetty Booking',
			'manage_options',
			self::SLUG,
			array( 'Govetty_Admin_Customers', 'render' ),
			'dashicons-heart',
			58
		);

		add_submenu_page( self::SLUG, 'Customers', 'Customers', 'manage_options', self::SLUG, array( 'Govetty_Admin_Customers', 'render' ) );
		add_submenu_page( self::SLUG, 'Subscriptions & Revenue', 'Subscriptions & Revenue', 'manage_options', self::SLUG . '-subscriptions', array( 'Govetty_Admin_Subscriptions', 'render' ) );
		add_submenu_page( self::SLUG, 'Phone Change Requests', self::phone_requests_menu_title(), 'manage_options', self::SLUG . '-phone-requests', array( 'Govetty_Admin_Phone_Requests', 'render' ) );
		add_submenu_page( self::SLUG, 'Settings', 'Settings', 'manage_options', self::SLUG . '-settings', array( 'Govetty_Settings', 'render_settings_page' ) );
	}

	/**
	 * "Phone Change Requests" with a pending-count bubble, same convention
	 * WP core uses for e.g. Comments/Plugin-updates counts.
	 */
	private static function phone_requests_menu_title() {
		$pending = Govetty_Data_Store::get_phone_change_requests( array( 'status' => 'pending', 'per_page' => 1 ) );
		$count   = (int) $pending['total'];
		if ( ! $count ) {
			return 'Phone Change Requests';
		}
		return sprintf( 'Phone Change Requests <span class="awaiting-mod count-%1$d"><span class="pending-count">%1$d</span></span>', $count );
	}

	public static function url( $suffix = '' ) {
		return admin_url( 'admin.php?page=' . self::SLUG . $suffix );
	}
}
