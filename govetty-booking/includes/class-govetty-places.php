<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canadian address autocomplete for the registration form, backed by Google
 * Places API (New). Runs server-side so the Google key never reaches the
 * browser; the browser only talks to this plugin's own REST routes.
 *
 * Needs a Google Cloud key with "Places API (New)" enabled, restricted to this
 * server's IP address, defined in wp-config.php:
 *
 *   define( 'GOVETTY_GOOGLE_MAPS_KEY', 'the-key' );
 *
 * Without a key (and outside mock mode) autocomplete is simply switched off
 * and the registration form falls back to typing each address field by hand.
 */
class Govetty_Places {

	const AUTOCOMPLETE_URL = 'https://places.googleapis.com/v1/places:autocomplete';
	const DETAILS_URL      = 'https://places.googleapis.com/v1/places/';

	// Requests allowed per visitor IP per window. Each keystroke batch costs
	// money on Google's side, so anonymous callers must not be unlimited.
	const RATE_LIMIT  = 90;
	const RATE_WINDOW = 600;

	public static function key() {
		return defined( 'GOVETTY_GOOGLE_MAPS_KEY' ) ? (string) GOVETTY_GOOGLE_MAPS_KEY : '';
	}

	public static function is_enabled() {
		return Govetty_Settings::is_mock_mode() || '' !== self::key();
	}

	/**
	 * @return array|WP_Error { suggestions: [ { place_id, text } ] }
	 */
	public static function suggest( $input, $session_token ) {
		$input = trim( (string) $input );
		if ( strlen( $input ) < 3 ) {
			return array( 'suggestions' => array() );
		}
		if ( Govetty_Settings::is_mock_mode() ) {
			return array( 'suggestions' => self::mock_suggestions( $input ) );
		}
		if ( self::rate_limited() ) {
			return new WP_Error( 'govetty_rate_limited', 'rate_limited', array( 'status' => 429 ) );
		}

		$response = wp_remote_post(
			self::AUTOCOMPLETE_URL,
			array(
				'timeout' => 8,
				'headers' => array(
					'Content-Type'   => 'application/json',
					'X-Goog-Api-Key' => self::key(),
				),
				'body'    => wp_json_encode(
					array(
						'input'               => $input,
						'includedRegionCodes' => array( 'ca' ),
						'includedPrimaryTypes' => array( 'street_address', 'premise', 'subpremise' ),
						'sessionToken'        => $session_token,
					)
				),
			)
		);
		$body = self::decode( $response, 'places_autocomplete' );
		if ( is_wp_error( $body ) ) {
			return $body;
		}

		$out = array();
		foreach ( (array) ( $body['suggestions'] ?? array() ) as $s ) {
			$p = $s['placePrediction'] ?? null;
			if ( $p && ! empty( $p['placeId'] ) ) {
				$out[] = array(
					'place_id' => (string) $p['placeId'],
					'text'     => (string) ( $p['text']['text'] ?? '' ),
				);
			}
		}
		return array( 'suggestions' => $out );
	}

	/**
	 * @return array|WP_Error { street, unit, city, province, postal_code, country }
	 */
	public static function details( $place_id, $session_token ) {
		if ( Govetty_Settings::is_mock_mode() ) {
			$found = self::mock_place( $place_id );
			return $found ? $found : new WP_Error( 'govetty_place_not_found', 'place_not_found', array( 'status' => 404 ) );
		}
		if ( self::rate_limited() ) {
			return new WP_Error( 'govetty_rate_limited', 'rate_limited', array( 'status' => 429 ) );
		}

		$url      = add_query_arg( 'sessionToken', rawurlencode( $session_token ), self::DETAILS_URL . rawurlencode( $place_id ) );
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 8,
				'headers' => array(
					'X-Goog-Api-Key'   => self::key(),
					'X-Goog-FieldMask' => 'addressComponents',
				),
			)
		);
		$body = self::decode( $response, 'places_details' );
		if ( is_wp_error( $body ) ) {
			return $body;
		}
		return self::parse_components( (array) ( $body['addressComponents'] ?? array() ) );
	}

	/**
	 * Maps Google's address components onto the form's fields. Public so it
	 * can be exercised without a network call.
	 */
	public static function parse_components( $components ) {
		$by_type = array();
		foreach ( $components as $c ) {
			foreach ( (array) ( $c['types'] ?? array() ) as $type ) {
				if ( ! isset( $by_type[ $type ] ) ) {
					$by_type[ $type ] = $c;
				}
			}
		}
		$long  = function ( $type ) use ( $by_type ) {
			return isset( $by_type[ $type ] ) ? (string) ( $by_type[ $type ]['longText'] ?? '' ) : '';
		};
		$short = function ( $type ) use ( $by_type ) {
			return isset( $by_type[ $type ] ) ? (string) ( $by_type[ $type ]['shortText'] ?? '' ) : '';
		};

		$city = $long( 'locality' );
		foreach ( array( 'postal_town', 'sublocality_level_1', 'administrative_area_level_3' ) as $fallback ) {
			if ( '' === $city ) {
				$city = $long( $fallback );
			}
		}

		return array(
			'street'      => trim( $long( 'street_number' ) . ' ' . $long( 'route' ) ),
			'unit'        => $long( 'subpremise' ),
			'city'        => $city,
			'province'    => strtoupper( $short( 'administrative_area_level_1' ) ),
			'postal_code' => strtoupper( $long( 'postal_code' ) ),
			'country'     => 'Canada',
		);
	}

	private static function decode( $response, $log_event ) {
		if ( is_wp_error( $response ) ) {
			Govetty_Logger::log( $log_event . '_failed', array( 'error' => $response->get_error_message() ) );
			return new WP_Error( 'govetty_places_unreachable', 'places_unavailable', array( 'status' => 502 ) );
		}
		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 || ! is_array( $body ) ) {
			// Google's error message is useful to the developer (bad key,
			// API not enabled, key restriction) and contains no secrets.
			Govetty_Logger::log(
				$log_event . '_failed',
				array(
					'status'  => $code,
					'message' => is_array( $body ) ? ( $body['error']['message'] ?? null ) : null,
				)
			);
			return new WP_Error( 'govetty_places_error', 'places_unavailable', array( 'status' => 502 ) );
		}
		return $body;
	}

	private static function rate_limited() {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key = 'govetty_places_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n >= self::RATE_LIMIT ) {
			return true;
		}
		set_transient( $key, $n + 1, self::RATE_WINDOW );
		return false;
	}

	// ---------------------------------------------------------------------
	// Mock mode: a few real-format Canadian addresses so the flow can be
	// tried without a Google key or any network access.
	// ---------------------------------------------------------------------

	private static function mock_places() {
		return array(
			'mock-1' => array( '7030 Woodbine Avenue, Markham, ON, Canada', '7030 Woodbine Avenue', '', 'Markham', 'ON', 'L3R 6G2' ),
			'mock-2' => array( '81 Rimmington Drive, Thornhill, ON, Canada', '81 Rimmington Drive', '', 'Thornhill', 'ON', 'L4J 6J9' ),
			'mock-3' => array( '100 Queen Street West, Toronto, ON, Canada', '100 Queen Street West', 'Unit 802', 'Toronto', 'ON', 'M5H 2N2' ),
			'mock-4' => array( '1055 West Georgia Street, Vancouver, BC, Canada', '1055 West Georgia Street', '', 'Vancouver', 'BC', 'V6E 3P3' ),
		);
	}

	private static function mock_suggestions( $input ) {
		$out    = array();
		$needle = strtolower( $input );
		foreach ( self::mock_places() as $id => $p ) {
			if ( false !== strpos( strtolower( $p[0] ), $needle ) || preg_match( '/^\d/', $input ) ) {
				$out[] = array(
					'place_id' => $id,
					'text'     => $p[0],
				);
			}
		}
		return $out;
	}

	private static function mock_place( $place_id ) {
		$places = self::mock_places();
		if ( ! isset( $places[ $place_id ] ) ) {
			return null;
		}
		$p = $places[ $place_id ];
		return array(
			'street'      => $p[1],
			'unit'        => $p[2],
			'city'        => $p[3],
			'province'    => $p[4],
			'postal_code' => $p[5],
			'country'     => 'Canada',
		);
	}
}
