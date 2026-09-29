<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the per-customer token issued by POST /otp/verify.
 *
 * The token is stored in an HttpOnly, Secure cookie set by PHP -- it is
 * never exposed to JavaScript. The browser doesn't need to read it
 * directly: it's sent automatically on subsequent fetch() calls (with
 * credentials: 'same-origin'), and the plugin's REST routes read it
 * server-side before forwarding requests to the CPP API.
 *
 * Note this token rotates on every successful /otp/verify call; calling
 * set_token() again simply overwrites the cookie with the new value.
 */
class Govetty_Customer_Session {

	const COOKIE_NAME = 'govetty_customer_token';

	public static function get_token() {
		if ( ! isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return '';
		}
		return sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );
	}

	public static function set_token( $token ) {
		$expires = time() + ( 30 * DAY_IN_SECONDS );

		// setcookie() must run before any output. REST callbacks run early
		// enough in the request lifecycle for this to work; if you see
		// "headers already sent" warnings, check for output-buffering /
		// other plugins hooking earlier than rest_api_init.
		setcookie(
			self::COOKIE_NAME,
			$token,
			array(
				'expires'  => $expires,
				'path'     => '/',
				'domain'   => '',
				'secure'   => true,
				'httponly' => true,
				'samesite' => 'Strict',
			)
		);
	}

	public static function clear_token() {
		setcookie( self::COOKIE_NAME, '', time() - HOUR_IN_SECONDS, '/' );
	}
}
