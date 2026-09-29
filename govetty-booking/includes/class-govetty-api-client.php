<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thin server-side client for the Marpet CPP API.
 *
 * IMPORTANT: This class is the ONLY place MARPET_API_TOKEN (the ApiUser
 * credential) is used. It is attached server-side to every outgoing
 * request and must never be sent to, logged for, or read by the browser.
 */
class Govetty_Api_Client {

	const BASE_PATH = '/api/cpp';

	/**
	 * Perform a JSON request against the CPP API.
	 *
	 * @param string $method HTTP method: GET, POST, PATCH, DELETE.
	 * @param string $path   Endpoint path, e.g. '/otp/start'.
	 * @param array  $params Data to send. For GET/DELETE this becomes a query
	 *                       string; for POST/PATCH it's sent as a JSON body.
	 * @return array{code:int,body:array|null,raw:string}
	 */
	public static function request( $method, $path, $params = array() ) {
		$method = strtoupper( $method );
		$url    = self::build_url( $path );

		$args = array(
			'method'  => $method,
			'timeout' => 15,
			'headers' => self::auth_headers(),
		);

		if ( in_array( $method, array( 'GET', 'DELETE' ), true ) ) {
			if ( ! empty( $params ) ) {
				$url = add_query_arg( $params, $url );
			}
		} else {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = wp_json_encode( $params );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'code' => 502,
				'body' => array(
					'status'  => 'error',
					'message' => 'upstream_unreachable',
					'detail'  => $response->get_error_message(),
				),
				'raw'  => '',
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$body = json_decode( $raw, true );

		return array(
			'code' => $code,
			'body' => is_array( $body ) ? $body : null,
			'raw'  => $raw,
		);
	}

	/**
	 * Proxy a multipart image upload straight through to the CPP API.
	 *
	 * Uses cURL directly rather than wp_remote_request(), because the WP
	 * HTTP API's multipart/form-data support is inconsistent across the
	 * different transports hosts fall back to.
	 *
	 * @param string $tmp_file_path Path to the uploaded temp file
	 *                              (e.g. $_FILES['image']['tmp_name']).
	 * @param string $original_name Original filename.
	 * @param string $mime_type     Detected mime type.
	 * @return array{code:int,body:array|null,raw:string}
	 */
	public static function upload_image( $tmp_file_path, $original_name, $mime_type ) {
		if ( ! function_exists( 'curl_init' ) ) {
			return array(
				'code' => 500,
				'body' => array(
					'status'  => 'error',
					'message' => 'curl_unavailable',
				),
				'raw'  => '',
			);
		}

		$url       = self::build_url( '/image/upload' );
		$curl_file = new CURLFile( $tmp_file_path, $mime_type, $original_name );

		$ch = curl_init();
		curl_setopt_array(
			$ch,
			array(
				CURLOPT_URL            => $url,
				CURLOPT_POST           => true,
				CURLOPT_POSTFIELDS     => array( 'image' => $curl_file ),
				CURLOPT_HTTPHEADER     => self::auth_headers_flat(),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT        => 30,
			)
		);

		$raw  = curl_exec( $ch );
		$err  = curl_error( $ch );
		$code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		if ( false === $raw ) {
			return array(
				'code' => 502,
				'body' => array(
					'status'  => 'error',
					'message' => 'upstream_unreachable',
					'detail'  => $err,
				),
				'raw'  => '',
			);
		}

		$body = json_decode( $raw, true );

		return array(
			'code' => $code,
			'body' => is_array( $body ) ? $body : null,
			'raw'  => $raw,
		);
	}

	private static function build_url( $path ) {
		return rtrim( GOVETTY_API_BASE_URL, '/' ) . self::BASE_PATH . '/' . ltrim( $path, '/' );
	}

	/**
	 * Headers carrying the server-level ApiUser credential.
	 *
	 * Confirmed by the Marpet backend team: sent as a custom header,
	 * `X-Api-Token: <token>` -- not a Bearer Authorization header (that was
	 * this plugin's earlier default guess, since the API doc itself never
	 * specified the transport). Still overridable via the
	 * `govetty_api_auth_headers` filter without touching this file, in
	 * case a future environment expects something different again.
	 */
	private static function auth_headers() {
		$headers = array(
			'X-Api-Token' => GOVETTY_API_TOKEN,
			'Accept'      => 'application/json',
		);
		return apply_filters( 'govetty_api_auth_headers', $headers );
	}

	private static function auth_headers_flat() {
		$flat = array();
		foreach ( self::auth_headers() as $key => $value ) {
			$flat[] = "$key: $value";
		}
		return $flat;
	}
}
