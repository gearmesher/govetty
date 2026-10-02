<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plain-text, append-only transaction log -- one .txt file per calendar
 * day (named YYYY-MM-DD.txt), every line timestamped.
 *
 * Deliberately stored at wp-content/booking-logs/ -- a SIBLING of
 * wp-content/plugins/govetty-booking/, not a subfolder of it. Updating the
 * plugin (reinstalling the zip, which overwrites everything under the
 * plugin's own directory) never touches anything outside that directory,
 * so logs survive every update. Do not move this under
 * GOVETTY_BOOKING_PATH for the same reason.
 *
 * Off by default -- controlled by Govetty_Settings::is_logging_enabled()
 * ("Enable transaction logging" on the Settings screen). When off, log()
 * is a single get_option() check and returns immediately.
 *
 * PII NOTE: entries can include customer phone numbers and names, at the
 * same level of detail this plugin already stores in its own DB tables
 * (see Govetty_Data_Store's docblock -- this is not a new category of
 * data collection, just a second, human-readable record of it). The
 * directory ships with an .htaccess + index.php to block direct web
 * access on Apache. If this site runs on Nginx (or anything else that
 * doesn't honor .htaccess), /wp-content/booking-logs/ needs an equivalent
 * server-block deny rule -- that's outside what a WordPress plugin can
 * configure, so flag it to whoever manages the server before relying on
 * this in production. OTP codes themselves are never logged.
 */
class Govetty_Logger {

	const DIR_NAME = 'booking-logs';

	/**
	 * @param string $event Short UPPER_SNAKE event name, e.g. 'otp_verify'.
	 * @param array  $data  Arbitrary JSON-encodable context for the event.
	 */
	public static function log( $event, $data = array() ) {
		if ( ! Govetty_Settings::is_logging_enabled() ) {
			return;
		}

		$dir = self::dir_path();
		if ( ! self::ensure_dir( $dir ) ) {
			return; // Can't write -- logging must never break the request it's observing.
		}

		$file = trailingslashit( $dir ) . gmdate( 'Y-m-d' ) . '.txt';
		$line = sprintf(
			'[%s] %s %s',
			current_time( 'mysql' ),
			strtoupper( $event ),
			wp_json_encode( $data )
		);

		// FILE_APPEND + LOCK_EX: safe for concurrent requests hitting the
		// same day's file at once, which is the normal case on PHP-FPM.
		// @-suppressed deliberately -- a logging failure (disk full,
		// permissions changed underneath us) must not surface as a fatal
		// error on a real customer-facing request.
		@file_put_contents( $file, $line . PHP_EOL, FILE_APPEND | LOCK_EX ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}

	public static function dir_path() {
		return WP_CONTENT_DIR . '/' . self::DIR_NAME;
	}

	/**
	 * Ensures the log directory exists and is locked down. Called lazily
	 * on first log() rather than only on plugin activation, so turning
	 * logging on mid-flight (no deactivate/reactivate needed) still works,
	 * and so a directory accidentally deleted from the server gets its
	 * protection files restored the next time something is logged.
	 */
	private static function ensure_dir( $dir ) {
		if ( ! file_exists( $dir ) && ! wp_mkdir_p( $dir ) ) {
			return false;
		}

		$htaccess = trailingslashit( $dir ) . '.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			// Covers both Apache 2.4 (mod_authz_core) and 2.2-style
			// (mod_access_compat) hosts -- harmless no-op on Nginx, see
			// the class docblock for why Nginx needs a separate rule.
			@file_put_contents( // phpcs:ignore WordPress.PHP.NoSilencedErrors
				$htaccess,
				"<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n"
				. "<IfModule !mod_authz_core.c>\n\tDeny from all\n</IfModule>\n"
			);
		}

		$index = trailingslashit( $dir ) . 'index.php';
		if ( ! file_exists( $index ) ) {
			@file_put_contents( $index, "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}

		return is_dir( $dir ) && wp_is_writable( $dir );
	}
}
