<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stands in for Govetty_Api_Client when mock mode is on. Every method
 * mirrors that class's method signatures and return shape
 * (`['code' => int, 'body' => array, 'raw' => string]`) so
 * Govetty_Rest_Routes doesn't need to know which one it's talking to --
 * see Govetty_Rest_Routes::api_request().
 *
 * Response shapes are copied from the real API doc's own examples, so a
 * flow that works against these mocks should need no changes to work
 * against the real API later.
 */
class Govetty_Mock_Api {

	public static function request( $method, $path, $params = array() ) {
		$path = '/' . ltrim( $path, '/' );

		switch ( $path ) {
			case '/otp/start':
				return self::ok( array( 'status' => 'ok' ) );

			case '/otp/verify':
				return self::otp_verify( $params );

			case '/register':
				return self::register( $params );

			case '/info/breeds':
				return self::ok(
					array(
						'status'     => 'ok',
						'dog_breeds' => array(
							array(
								'id'    => 1,
								'label' => 'Beagle',
							),
							array(
								'id'    => 2,
								'label' => 'Golden Retriever',
							),
							array(
								'id'    => 3,
								'label' => 'Labrador Retriever',
							),
							array(
								'id'    => 4,
								'label' => 'Poodle',
							),
						),
						'cat_breeds' => array(
							array(
								'id'    => 1,
								'label' => 'British Shorthair',
							),
							array(
								'id'    => 2,
								'label' => 'Domestic Shorthair',
							),
							array(
								'id'    => 3,
								'label' => 'Siamese',
							),
						),
					)
				);

			case '/address/validate':
				return self::ok(
					array(
						'status'             => 'ok',
						'valid'              => true,
						'partial_match'      => false,
						'formatted_address'  => trim( (string) ( $params['address'] ?? '' ) ) . ' (mock validated)',
					)
				);

			case '/info/user':
				return self::ok(
					array(
						'status'   => 'ok',
						'customer' => self::mock_customer(),
						'pets'     => self::mock_pets(),
					)
				);

			case '/billing/getPlans':
				return self::ok(
					array(
						'plans'                        => array(
							array(
								'plan_key' => 'single_call',
								'price'    => 2500,
							),
							array(
								'plan_key' => 'single_pet_monthly',
								'price'    => 1900,
							),
						),
						'pets_with_active_subscription' => array(),
					)
				);

			case '/billing/createCheckoutSession':
				return self::checkout_session( $params );

			case '/slot/getAvailable':
				return self::ok(
					array(
						'status' => 'ok',
						'slots'  => self::mock_slots(),
					)
				);

			case '/slot/book':
				return self::ok( array( 'status' => 'ok' ) );

			case '/customer/attachPet':
				return self::ok(
					array(
						'id'   => wp_rand( 9000, 9999 ),
						'name' => $params['name'] ?? 'New pet',
					)
				);

			case '/customer/detachPet':
				return self::ok( array( 'status' => 'ok' ) );

			case '/getConferenceTokenAmount':
				return self::ok(
					array(
						'pet_id'                  => absint( $params['petId'] ?? 0 ),
						'conference_token_amount' => 3,
					)
				);

			case '/image':
				return self::ok( array( 'status' => 'ok' ) );

			default:
				return self::ok( array( 'status' => 'ok' ) );
		}
	}

	public static function upload_image( $tmp_file_path, $original_name, $mime_type ) {
		return self::ok(
			array(
				'image_id'  => wp_rand( 10000, 99999 ),
				'image_url' => '/api/cpp/image/mock',
			)
		);
	}

	// -----------------------------------------------------------------

	private static function ok( $body ) {
		return array(
			'code' => 200,
			'body' => $body,
			'raw'  => wp_json_encode( $body ),
		);
	}

	private static function error( $code, $body ) {
		return array(
			'code' => $code,
			'body' => $body,
			'raw'  => wp_json_encode( $body ),
		);
	}

	private static function mock_customer() {
		return array(
			'token'      => 'mock-token-' . wp_generate_password( 12, false ),
			'first_name' => 'Jamie',
			'last_name'  => 'Rivera',
		);
	}

	/**
	 * One pet with a balance, one without -- so both Pet Selection
	 * branches are reachable from the single "existing customer" scenario
	 * without needing more settings.
	 */
	private static function mock_pets() {
		return array(
			array(
				'id'            => 101,
				'name'          => 'Biscuit',
				'type'          => 'dog',
				'token_amount'  => 3,
				'image_id'      => null,
			),
			array(
				'id'            => 102,
				'name'          => 'Whiskers',
				'type'          => 'cat',
				'token_amount'  => 0,
				'image_id'      => null,
			),
		);
	}

	private static function otp_verify( $params ) {
		$code = (string) ( $params['code'] ?? '' );
		if ( ! preg_match( '/^\d{6}$/', $code ) ) {
			return self::error( 422, array(
				'status'  => 'error',
				'message' => 'invalid_code_format',
			) );
		}

		// If this phone already went through mock /register in this
		// session, always recognize it now -- regardless of the configured
		// scenario. Without this, re-verifying right after registration
		// (see the bookingMachine's postRegistration purpose) would
		// incorrectly still look like an unrecognized number under the
		// "new customer" scenario.
		$registered = self::get_registered( $params['phone'] ?? '' );
		if ( $registered ) {
			return self::ok(
				array(
					'status'   => 'ok',
					'customer' => $registered['customer'],
					'pets'     => $registered['pets'],
				)
			);
		}

		if ( 'existing_customer' === Govetty_Settings::scenario() ) {
			return self::ok(
				array(
					'status'   => 'ok',
					'customer' => self::mock_customer(),
					'pets'     => self::mock_pets(),
				)
			);
		}

		return self::ok(
			array(
				'status'   => 'ok',
				'customer' => null,
				'pets'     => array(),
			)
		);
	}

	private static function register( $params ) {
		$pets     = $params['pets'] ?? array();
		$pet_name = $pets[0]['name'] ?? 'Your pet';
		$pet_id   = wp_rand( 9000, 9999 );

		// Remember this phone as "registered" so the OTP re-verification
		// that follows registration (see plugin README, gap #1) finds a
		// real customer instead of looping back to "not recognized".
		self::set_registered(
			$params['phone'] ?? '',
			array(
				'customer' => array(
					'token'      => 'mock-token-' . wp_generate_password( 12, false ),
					'first_name' => $params['first_name'] ?? 'New',
					'last_name'  => $params['last_name'] ?? 'Customer',
				),
				'pets'     => array(
					array(
						'id'           => $pet_id,
						'name'         => $pet_name,
						'type'         => $pets[0]['type'] ?? 'dog',
						'token_amount' => 0,
						'image_id'     => $pets[0]['image_id'] ?? null,
					),
				),
			)
		);

		return self::ok(
			array(
				'customer_id' => wp_rand( 1000, 9999 ),
				'pets'        => array(
					array(
						'pet_id' => $pet_id,
						'name'   => $pet_name,
					),
				),
			)
		);
	}

	private static function registered_transient_key( $phone ) {
		return 'govetty_mock_registered_' . md5( (string) $phone );
	}

	private static function get_registered( $phone ) {
		if ( ! $phone ) {
			return false;
		}
		return get_transient( self::registered_transient_key( $phone ) );
	}

	private static function set_registered( $phone, $data ) {
		if ( ! $phone ) {
			return;
		}
		set_transient( self::registered_transient_key( $phone ), $data, HOUR_IN_SECONDS );
	}

	/**
	 * No real Stripe session exists in mock mode, so this points the
	 * "checkout_url" at a lightweight interstitial page served by
	 * Govetty_Mock_Checkout instead. The real success_url/cancel_url are
	 * stashed in a short-lived transient so that page knows where to send
	 * the browser back to, without trusting a URL round-tripped through
	 * the query string.
	 */
	private static function checkout_session( $params ) {
		$session_id = wp_generate_password( 24, false, false );

		set_transient(
			'govetty_mock_checkout_' . $session_id,
			array(
				'success_url' => $params['success_url'] ?? home_url( '/' ),
				'cancel_url'  => $params['cancel_url'] ?? home_url( '/' ),
				'plan_key'    => $params['plan_key'] ?? '',
			),
			30 * MINUTE_IN_SECONDS
		);

		$checkout_url = add_query_arg(
			array(
				'govetty_mock_checkout' => '1',
				'session'               => $session_id,
			),
			home_url( '/' )
		);

		return self::ok(
			array(
				'status'       => 'ok',
				'checkout_url' => $checkout_url,
			)
		);
	}

	private static function mock_slots() {
		$vets  = array( 'Dr. Cohen', 'Dr. Levi', 'Dr. Alvarez', 'Dr. Nguyen' );
		$slots = array();
		for ( $i = 0; $i < 4; $i++ ) {
			$hour    = 9 + ( $i * 2 );
			$slots[] = array(
				'slot_id'         => $i + 1,
				'date'            => gmdate( 'Y-m-d', strtotime( '+' . ( $i + 1 ) . ' day' ) ),
				'time_start'      => sprintf( '%02d:00', $hour ),
				'time_end'        => sprintf( '%02d:30', $hour ),
				'id_veterinary'   => $i + 1,
				'veterinary_name' => $vets[ $i % count( $vets ) ],
			);
		}
		return $slots;
	}
}
