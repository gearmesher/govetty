<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers wp-json/govetty/v1/* routes that proxy to the Marpet CPP API.
 *
 * The browser only ever talks to these routes. They attach the
 * server-side ApiUser credential (via Govetty_Api_Client) and the
 * customer_token cookie (via Govetty_Customer_Session) before forwarding
 * to the real API, so neither secret is ever exposed client-side.
 */
class Govetty_Rest_Routes {

	const NAMESPACE_ = 'govetty/v1';

	public static function register_routes() {
		$routes = array(
			array( 'POST', '/otp/start', array( __CLASS__, 'otp_start' ) ),
			array( 'POST', '/otp/verify', array( __CLASS__, 'otp_verify' ) ),
			array( 'POST', '/register', array( __CLASS__, 'register_customer' ) ),
			array( 'GET', '/info/user', array( __CLASS__, 'info_user' ) ),
			array( 'GET', '/info/breeds', array( __CLASS__, 'info_breeds' ) ),
			array( 'POST', '/address/validate', array( __CLASS__, 'address_validate' ) ),
			array( 'GET', '/billing/plans', array( __CLASS__, 'billing_get_plans' ) ),
			array( 'POST', '/billing/checkout', array( __CLASS__, 'billing_checkout' ) ),
			array( 'GET', '/slot/available', array( __CLASS__, 'slot_available' ) ),
			array( 'POST', '/slot/book', array( __CLASS__, 'slot_book' ) ),
			array( 'POST', '/customer/attach-pet', array( __CLASS__, 'attach_pet' ) ),
			array( 'POST', '/customer/detach-pet', array( __CLASS__, 'detach_pet' ) ),
			array( 'GET', '/conference-token', array( __CLASS__, 'conference_token' ) ),
			array( 'POST', '/image/upload', array( __CLASS__, 'image_upload' ) ),
			array( 'POST', '/image/assign', array( __CLASS__, 'image_assign' ) ),
			array( 'POST', '/billing/confirm-return', array( __CLASS__, 'billing_confirm_return' ) ),
		);

		foreach ( $routes as $route ) {
			list( $method, $path, $handler ) = $route;
			register_rest_route(
				self::NAMESPACE_,
				$path,
				array(
					'methods'             => $method,
					'callback'            => $handler,
					'permission_callback' => array( __CLASS__, 'check_nonce' ),
				)
			);
		}
	}

	/**
	 * Basic CSRF check via WordPress's standard REST nonce.
	 *
	 * This is a reasonable v1 default, but WP nonces for logged-out
	 * visitors are weaker than for authenticated users. For anything that
	 * moves money or PII, pair this with Origin/Referer validation and
	 * keep the CPP API's own rate limiting (e.g. on /otp/start) as a
	 * second line of defense.
	 */
	public static function check_nonce( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'govetty_bad_nonce', 'Invalid or missing nonce.', array( 'status' => 403 ) );
		}
		return true;
	}

	// ---------------------------------------------------------------------
	// OTP
	// ---------------------------------------------------------------------

	public static function otp_start( WP_REST_Request $request ) {
		$phone = sanitize_text_field( (string) $request->get_param( 'phone' ) );
		$ip    = self::get_client_ip( $request );

		$result = self::api_request(
			'POST',
			'/otp/start',
			array(
				'phone'   => $phone,
				'user_ip' => $ip,
			)
		);

		return self::passthrough( $result );
	}

	public static function otp_verify( WP_REST_Request $request ) {
		$phone = sanitize_text_field( (string) $request->get_param( 'phone' ) );
		$code  = sanitize_text_field( (string) $request->get_param( 'code' ) );

		$result = self::api_request(
			'POST',
			'/otp/verify',
			array(
				'phone' => $phone,
				'code'  => $code,
			)
		);

		if ( 200 === $result['code'] && ! empty( $result['body']['customer']['token'] ) ) {
			$token    = $result['body']['customer']['token'];
			$customer = $result['body']['customer'];
			$pets     = $result['body']['pets'] ?? array();

			Govetty_Customer_Session::set_token( $token );

			// Bridges customer_token -> phone/name/pets for later calls in
			// this same session (e.g. billing_checkout logging) that only
			// have the token to work with -- the CPP API never echoes phone
			// back on any endpoint, so this is the only place it's available.
			set_transient(
				'govetty_token_meta_' . md5( $token ),
				array(
					'phone'      => $phone,
					'first_name' => $customer['first_name'] ?? '',
					'last_name'  => $customer['last_name'] ?? '',
					'pets'       => $pets,
				),
				DAY_IN_SECONDS
			);

			Govetty_Data_Store::upsert_customer(
				$phone,
				$customer['first_name'] ?? '',
				$customer['last_name'] ?? '',
				$pets,
				Govetty_Settings::is_mock_mode() ? 1 : 0
			);
		}

		// The API returns a single combined `invalid_or_expired` for both
		// a wrong code and an expired one (confirmed by the backend team --
		// there is no separate "wrong code" vs "expired code" distinction
		// to surface). bookingMachine.js's guard chain already collapses
		// every non-too_many_attempts failure into that one message; don't
		// try to split it further client-side.

		// Strip the raw token before the response reaches the browser --
		// the frontend only needs to know it's logged in and see
		// customer/pet details, not the token value itself.
		if ( isset( $result['body']['customer']['token'] ) ) {
			unset( $result['body']['customer']['token'] );
		}

		return self::passthrough( $result );
	}

	// ---------------------------------------------------------------------
	// Registration
	// ---------------------------------------------------------------------

	public static function register_customer( WP_REST_Request $request ) {
		$payload = $request->get_json_params();
		if ( ! is_array( $payload ) ) {
			return new WP_Error( 'govetty_bad_payload', 'Expected a JSON body.', array( 'status' => 400 ) );
		}

		// Confirmed field set (per client, still pending final sign-off
		// from Oren on their end): customer -- name, email, phone, address,
		// zip; pet -- name, dob, chip, gender. image_id is optional and
		// intentionally omitted by the frontend when no photo was taken
		// (see Registration.jsx) -- do not add/remove fields here beyond
		// what's confirmed.
		$result = self::api_request( 'POST', '/register', $payload );

		if ( 200 === $result['code'] && ! empty( $result['body']['pets'] ) ) {
			// /register's response only returns {pet_id, name} per pet --
			// merge in the type/etc. from what was actually submitted so the
			// local log has a complete-enough snapshot. token_amount is
			// always 0 here; a brand-new pet has no call balance yet.
			$submitted_pets = (array) ( $payload['pets'] ?? array() );
			$pets           = array();
			foreach ( $result['body']['pets'] as $i => $returned_pet ) {
				$pets[] = array(
					'id'           => $returned_pet['pet_id'] ?? 0,
					'name'         => $returned_pet['name'] ?? '',
					'type'         => $submitted_pets[ $i ]['type'] ?? '',
					'token_amount' => 0,
					'image_id'     => $submitted_pets[ $i ]['image_id'] ?? null,
				);
			}

			Govetty_Data_Store::upsert_customer(
				sanitize_text_field( (string) ( $payload['phone'] ?? '' ) ),
				sanitize_text_field( (string) ( $payload['first_name'] ?? '' ) ),
				sanitize_text_field( (string) ( $payload['last_name'] ?? '' ) ),
				$pets,
				Govetty_Settings::is_mock_mode() ? 1 : 0
			);
		}

		// TODO(temporary-workaround): /register's documented response
		// (customer_id + pets) does not include a customer_token, and
		// /otp/verify returns customer: null for unrecognized numbers --
		// i.e. before registration. As documented today there's no way to
		// obtain a token in the same request as registering. The client has
		// explicitly confirmed this is a TEMPORARY state, not the final
		// architecture -- the frontend currently works around it by
		// re-running /otp/start + /otp/verify silently for the same phone
		// right after a successful registration (see bookingMachine.js,
		// the `registering` state's onDone handler, otpPurpose:
		// 'postRegistration'). DO NOT treat this second OTP round-trip as
		// permanent design -- remove it once /register (or an equivalent
		// endpoint) returns a token directly, and simplify the state
		// machine accordingly at that point.
		return self::passthrough( $result );
	}

	// ---------------------------------------------------------------------
	// Info
	// ---------------------------------------------------------------------

	public static function info_user( WP_REST_Request $request ) {
		$token = Govetty_Customer_Session::get_token();
		if ( ! $token ) {
			return self::unauthenticated();
		}
		$result = self::api_request( 'GET', '/info/user', array( 'customer_token' => $token ) );
		return self::passthrough( $result );
	}

	public static function info_breeds( WP_REST_Request $request ) {
		$result = self::api_request( 'GET', '/info/breeds' );
		return self::passthrough( $result );
	}

	public static function address_validate( WP_REST_Request $request ) {
		$address = sanitize_text_field( (string) $request->get_param( 'address' ) );
		$result  = self::api_request( 'POST', '/address/validate', array( 'address' => $address ) );
		return self::passthrough( $result );
	}

	// ---------------------------------------------------------------------
	// Billing
	// ---------------------------------------------------------------------

	public static function billing_get_plans( WP_REST_Request $request ) {
		$token = Govetty_Customer_Session::get_token();
		if ( ! $token ) {
			return self::unauthenticated();
		}
		$pet_id = absint( $request->get_param( 'pet_id' ) );

		// Plan keys confirmed by the client as matching the frontend:
		// single_call, single_pet_monthly, multi_pet_monthly, with tiered
		// pet-count discounts (15/25/30%) computed entirely server-side via
		// stripe_plans + doc_discounts_array. This plugin never computes
		// pricing/discounts itself -- it only displays whatever `price`
		// this endpoint returns (see PlanSelection.jsx's PLAN_INFO map,
		// which is display copy only). Nothing to change here.
		$result = self::api_request(
			'GET',
			'/billing/getPlans',
			array(
				'customer_token' => $token,
				'pet_id'         => $pet_id,
			)
		);
		return self::passthrough( $result );
	}

	public static function billing_checkout( WP_REST_Request $request ) {
		$token = Govetty_Customer_Session::get_token();
		if ( ! $token ) {
			return self::unauthenticated();
		}
		$payload  = (array) $request->get_json_params();
		$plan_key = sanitize_text_field( (string) ( $payload['plan_key'] ?? '' ) );
		$pet_ids  = array_map( 'absint', (array) ( $payload['pet_ids'] ?? array() ) );
		$pet_id   = $pet_ids[0] ?? 0;

		// checkout_ref correlates this session with the confirm-return call
		// the frontend makes when the browser lands back on success_url /
		// cancel_url -- see billing_confirm_return() below.
		$checkout_ref = wp_generate_password( 24, false, false );
		$success_url  = add_query_arg(
			array(
				'govetty_step' => 'payment-success',
				'checkout_ref' => $checkout_ref,
			),
			self::booking_page_url()
		);
		$cancel_url = add_query_arg(
			array(
				'govetty_step' => 'payment-cancelled',
				'checkout_ref' => $checkout_ref,
			),
			self::booking_page_url()
		);

		// NOTE: the client has asked us to send the exact staging and
		// production domains for their Stripe redirect allow-list. Confirm
		// booking_page_url() below resolves to the real domain in each
		// environment (it defaults to home_url('/book-a-vet/'), filterable
		// via `govetty_booking_page_path`) and pass those two domains back
		// to the backend team -- until they're allow-listed,
		// createCheckoutSession will fail with invalid_success_url /
		// invalid_cancel_url regardless of anything else being correct.
		$result = self::api_request(
			'POST',
			'/billing/createCheckoutSession',
			array(
				'customer_token' => $token,
				'plan_key'       => $plan_key,
				'pet_ids'        => $pet_ids,
				'success_url'    => $success_url,
				'cancel_url'     => $cancel_url,
			)
		);

		if ( 200 === $result['code'] && ! empty( $result['body']['checkout_url'] ) ) {
			self::log_checkout_started( $token, $checkout_ref, $plan_key, $pet_id );
		}

		return self::passthrough( $result );
	}

	/**
	 * Best-effort local logging for the Subscriptions & Revenue admin page.
	 * Never blocks or fails the actual checkout -- if any lookup here comes
	 * back empty, the event is still logged with whatever's known.
	 */
	private static function log_checkout_started( $token, $checkout_ref, $plan_key, $pet_id ) {
		$meta     = get_transient( 'govetty_token_meta_' . md5( $token ) ) ?: array();
		$phone    = $meta['phone'] ?? '';
		$name     = trim( ( $meta['first_name'] ?? '' ) . ' ' . ( $meta['last_name'] ?? '' ) );
		$pet_name = '';
		foreach ( (array) ( $meta['pets'] ?? array() ) as $pet ) {
			if ( (int) ( $pet['id'] ?? 0 ) === $pet_id ) {
				$pet_name = $pet['name'] ?? '';
				break;
			}
		}

		// Re-fetch the plan's price ourselves rather than trusting a
		// client-supplied value, since this feeds the revenue log --
		// getPlans is cheap and idempotent.
		$price_cents  = null;
		$plans_result = self::api_request( 'GET', '/billing/getPlans', array(
			'customer_token' => $token,
			'pet_id'         => $pet_id,
		) );
		if ( 200 === $plans_result['code'] && ! empty( $plans_result['body']['plans'] ) ) {
			foreach ( $plans_result['body']['plans'] as $plan ) {
				if ( ( $plan['plan_key'] ?? '' ) === $plan_key ) {
					$price_cents = (int) $plan['price'];
					break;
				}
			}
		}

		Govetty_Data_Store::log_checkout_initiated(
			array(
				'checkout_ref'  => $checkout_ref,
				'phone'         => $phone,
				'customer_name' => $name,
				'pet_id'        => $pet_id,
				'pet_name'      => $pet_name,
				'plan_key'      => $plan_key,
				'price_cents'   => $price_cents,
				'is_mock'       => Govetty_Settings::is_mock_mode() ? 1 : 0,
			)
		);
	}

	/**
	 * Pinged once by the frontend when it lands on the booking page via
	 * govetty_step=payment-success/cancelled (see PostPaymentApp.jsx /
	 * ResumePlanSelection.jsx). Only updates the local log's status --
	 * this is NOT payment verification. The client has confirmed there is
	 * currently no webhook or server-side payment check on their end
	 * either, so this remains the only signal available until that's built.
	 */
	public static function billing_confirm_return( WP_REST_Request $request ) {
		$payload = (array) $request->get_json_params();
		$ref     = sanitize_text_field( (string) ( $payload['checkout_ref'] ?? '' ) );
		$outcome = sanitize_text_field( (string) ( $payload['outcome'] ?? '' ) );

		if ( $ref && in_array( $outcome, array( 'success', 'cancelled' ), true ) ) {
			Govetty_Data_Store::mark_checkout_returned( $ref, $outcome );
		}

		return new WP_REST_Response( array( 'status' => 'ok' ), 200 );
	}

	// ---------------------------------------------------------------------
	// Slots
	// ---------------------------------------------------------------------

	public static function slot_available( WP_REST_Request $request ) {
		$token = Govetty_Customer_Session::get_token();
		if ( ! $token ) {
			return self::unauthenticated();
		}
		$pet_count = absint( $request->get_param( 'pet_count' ) );

		// Still a documented placeholder upstream (hard-coded slots, no
		// real availability) -- timeline unconfirmed as of the last check
		// with the backend team.
		$result = self::api_request(
			'GET',
			'/slot/getAvailable',
			array(
				'customer_token' => $token,
				'pet_count'      => $pet_count,
			)
		);
		return self::passthrough( $result );
	}

	public static function slot_book( WP_REST_Request $request ) {
		$token = Govetty_Customer_Session::get_token();
		if ( ! $token ) {
			return self::unauthenticated();
		}
		$payload = (array) $request->get_json_params();

		$result = self::api_request(
			'POST',
			'/slot/book',
			array(
				'customer_token' => $token,
				'pet_id'         => absint( $payload['pet_id'] ?? 0 ),
				'slot_id'        => absint( $payload['slot_id'] ?? 0 ),
			)
		);

		// NOTE (flag for backend): per the API doc, /slot/book currently
		// only validates the request shape and doesn't persist a booking
		// record yet. Treat "Booking Confirmation" in the UI as
		// provisional until that's implemented upstream.
		return self::passthrough( $result );
	}

	// ---------------------------------------------------------------------
	// Pets
	// ---------------------------------------------------------------------

	public static function attach_pet( WP_REST_Request $request ) {
		$token = Govetty_Customer_Session::get_token();
		if ( ! $token ) {
			return self::unauthenticated();
		}
		$payload                   = (array) $request->get_json_params();
		$payload['customer_token'] = $token;

		$result = self::api_request( 'POST', '/customer/attachPet', $payload );
		return self::passthrough( $result );
	}

	public static function detach_pet( WP_REST_Request $request ) {
		$token = Govetty_Customer_Session::get_token();
		if ( ! $token ) {
			return self::unauthenticated();
		}
		$pet_id = absint( $request->get_param( 'pet_id' ) );

		$result = self::api_request(
			'POST',
			'/customer/detachPet',
			array(
				'customer_token' => $token,
				'pet_id'         => $pet_id,
			)
		);
		return self::passthrough( $result );
	}

	public static function conference_token( WP_REST_Request $request ) {
		$token = Govetty_Customer_Session::get_token();
		if ( ! $token ) {
			return self::unauthenticated();
		}
		$pet_id = absint( $request->get_param( 'pet_id' ) );

		$result = self::api_request(
			'GET',
			'/getConferenceTokenAmount',
			array(
				'customer_token' => $token,
				'petId'          => $pet_id,
			)
		);
		return self::passthrough( $result );
	}

	// ---------------------------------------------------------------------
	// Image upload
	// ---------------------------------------------------------------------

	public static function image_upload( WP_REST_Request $request ) {
		$files = $request->get_file_params();
		if ( empty( $files['image']['tmp_name'] ) ) {
			return new WP_Error( 'govetty_no_file', 'No image file received.', array( 'status' => 422 ) );
		}

		$file   = $files['image'];
		$result = self::api_upload_image( $file['tmp_name'], $file['name'], $file['type'] );

		return self::passthrough( $result );
	}

	/**
	 * Assigns a previously uploaded image to a pet -- used by the Personal
	 * Area's one-time upload flow (Registration's own optional upload goes
	 * straight into /register instead, since it already has an image_id
	 * to attach at creation time).
	 */
	public static function image_assign( WP_REST_Request $request ) {
		$token = Govetty_Customer_Session::get_token();
		if ( ! $token ) {
			return self::unauthenticated();
		}
		$payload = (array) $request->get_json_params();

		// Ownership check: /image PATCH on the CPP API doesn't take a
		// customer_token itself (see doc §3.10), so make sure the pet_id
		// belongs to the calling customer before forwarding, rather than
		// relying on the upstream API to enforce it.
		$pet_id   = absint( $payload['pet_id'] ?? 0 );
		$owned    = self::api_request( 'GET', '/info/user', array( 'customer_token' => $token ) );
		$owns_pet = false;
		if ( 200 === $owned['code'] && ! empty( $owned['body']['pets'] ) ) {
			foreach ( $owned['body']['pets'] as $pet ) {
				if ( (int) $pet['id'] === $pet_id ) {
					$owns_pet = true;
					break;
				}
			}
		}
		if ( ! $owns_pet ) {
			return new WP_REST_Response( array( 'status' => 'error', 'message' => 'pet_not_found' ), 422 );
		}

		$result = self::api_request(
			'PATCH',
			'/image',
			array(
				'image_id' => absint( $payload['image_id'] ?? 0 ),
				'pet_id'   => $pet_id,
			)
		);
		return self::passthrough( $result );
	}

	// ---------------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------------

	/**
	 * Every route goes through here instead of calling Govetty_Api_Client
	 * directly, so mock mode (Settings > GoVetty Booking) is a single
	 * branch point rather than a check duplicated in every handler.
	 */
	private static function api_request( $method, $path, $params = array() ) {
		if ( Govetty_Settings::is_mock_mode() ) {
			return Govetty_Mock_Api::request( $method, $path, $params );
		}
		return Govetty_Api_Client::request( $method, $path, $params );
	}

	private static function api_upload_image( $tmp_file_path, $original_name, $mime_type ) {
		if ( Govetty_Settings::is_mock_mode() ) {
			return Govetty_Mock_Api::upload_image( $tmp_file_path, $original_name, $mime_type );
		}
		return Govetty_Api_Client::upload_image( $tmp_file_path, $original_name, $mime_type );
	}

	/**
	 * URL of the page carrying the [govetty_booking] shortcode. Defaults to
	 * /book-a-vet/ -- override with the govetty_booking_page_path filter
	 * (or just create the page at that slug) if you place the shortcode
	 * somewhere else. This is also the exact URL that needs to go on the
	 * Stripe success/cancel domain allow-list (see billing_checkout()).
	 */
	public static function booking_page_url() {
		$path = apply_filters( 'govetty_booking_page_path', '/book-a-vet/' );
		return home_url( $path );
	}

	private static function passthrough( $result ) {
		$body = null !== $result['body']
			? $result['body']
			: array(
				'status'  => 'error',
				'message' => 'invalid_upstream_response',
			);

		return new WP_REST_Response( $body, $result['code'] ?: 502 );
	}

	private static function unauthenticated() {
		return new WP_REST_Response(
			array(
				'status'  => 'error',
				'message' => 'not_logged_in',
			),
			401
		);
	}

	/**
	 * Resolve the real visitor IP for OTP rate limiting, accounting for a
	 * reverse proxy/CDN in front of WordPress. Confirm which header your
	 * host/CDN actually sets (e.g. Cloudflare uses CF-Connecting-IP) before
	 * relying on this in production -- an unverified X-Forwarded-For can be
	 * spoofed by the client.
	 *
	 * NOTE: every request this plugin proxies shares this one WordPress
	 * server's outbound IP as far as the CPP API's own rate limiter is
	 * concerned (20 req/hr/IP on /otp/start per the doc) -- this function
	 * only affects what user_ip we report to the API, it doesn't change
	 * that shared-IP exposure. Worth flagging to the backend team before
	 * production traffic makes that limit a real problem.
	 */
	private static function get_client_ip( WP_REST_Request $request ) {
		$candidate_headers = array( 'CF-Connecting-IP', 'X-Forwarded-For' );

		foreach ( $candidate_headers as $header ) {
			$value = $request->get_header( $header );
			if ( $value ) {
				$parts = explode( ',', $value );
				return trim( $parts[0] );
			}
		}

		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
	}
}
