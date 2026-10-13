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
				return self::info_user( $params );

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

	/**
	 * Mock customers are chosen by the LAST DIGIT of the phone number, so
	 * every branch of the booking flow can be exercised without a settings
	 * switch (mirrors the "Customer Status" diamond on the Figma board):
	 *
	 *   1 -> existing, two pets: Biscuit has calls, Whiskers has none
	 *   2 -> existing, one pet (Whiskers), no calls   -> straight to Plan Selection
	 *   3 -> existing, one pet (Biscuit), has calls   -> Pet Selection
	 *   4 -> existing, two pets, neither has calls    -> Pet Selection, then Plans
	 *   anything else -> not recognized (new customer -> Registration)
	 *
	 * A number that has been through mock /register is recognized from then
	 * on, whatever its last digit.
	 */
	private static function scenario_pets( $phone ) {
		$biscuit_calls = array( 'id' => 101, 'name' => 'Biscuit', 'type' => 'dog', 'token_amount' => 3, 'image_id' => null );
		$biscuit_none  = array( 'id' => 101, 'name' => 'Biscuit', 'type' => 'dog', 'token_amount' => 0, 'image_id' => null );
		$whiskers      = array( 'id' => 102, 'name' => 'Whiskers', 'type' => 'cat', 'token_amount' => 0, 'image_id' => null );

		switch ( substr( preg_replace( '/\D/', '', (string) $phone ), -1 ) ) {
			case '1':
				return array( $biscuit_calls, $whiskers );
			case '2':
				return array( $whiskers );
			case '3':
				return array( $biscuit_calls );
			case '4':
				return array( $biscuit_none, $whiskers );
		}
		return null;
	}

	private static function session_key( $token ) {
		return 'govetty_mock_session_' . md5( (string) $token );
	}

	/**
	 * Starts a mock session: the token the browser will hold maps to this
	 * customer/pets, so /info/user (and balance changes) work per login.
	 */
	private static function start_session( $phone, $customer, $pets ) {
		$token             = 'mock-token-' . wp_generate_password( 12, false );
		$customer['token'] = $token;
		set_transient(
			self::session_key( $token ),
			array(
				'phone'    => (string) $phone,
				'customer' => $customer,
				'pets'     => $pets,
			),
			12 * HOUR_IN_SECONDS
		);
		return $customer;
	}

	private static function info_user( $params ) {
		$token   = (string) ( $params['customer_token'] ?? '' );
		$session = $token ? get_transient( self::session_key( $token ) ) : false;
		if ( ! $session ) {
			return self::error( 422, array( 'status' => 'error', 'message' => 'customer_not_found' ) );
		}
		return self::ok(
			array(
				'status'   => 'ok',
				'customer' => $session['customer'],
				'pets'     => $session['pets'],
			)
		);
	}

	/**
	 * Mock stand-in for a successful purchase: adds call balance to the pet
	 * so "has balance" branches can be reached by buying a plan in mock mode.
	 */
	public static function grant_balance( $token, $pet_id, $plan_key ) {
		$session = $token ? get_transient( self::session_key( $token ) ) : false;
		if ( ! $session ) {
			return;
		}
		$calls = false !== strpos( (string) $plan_key, 'monthly' ) ? 10 : 1;
		foreach ( $session['pets'] as $i => $pet ) {
			if ( (int) $pet['id'] === (int) $pet_id ) {
				$session['pets'][ $i ]['token_amount'] = (int) $pet['token_amount'] + $calls;
			}
		}
		set_transient( self::session_key( $token ), $session, 12 * HOUR_IN_SECONDS );

		$registered = self::get_registered( $session['phone'] );
		if ( $registered ) {
			$registered['pets'] = $session['pets'];
			self::set_registered( $session['phone'], $registered );
		}
	}

	private static function otp_verify( $params ) {
		$code = (string) ( $params['code'] ?? '' );
		if ( ! preg_match( '/^\d{6}$/', $code ) ) {
			return self::error( 422, array(
				'status'  => 'error',
				'message' => 'invalid_code_format',
			) );
		}

		$phone = (string) ( $params['phone'] ?? '' );

		// A phone that already went through mock /register is recognized
		// from then on (this is also what the post-registration OTP re-run
		// relies on).
		$registered = self::get_registered( $phone );
		if ( $registered ) {
			$customer = self::start_session( $phone, $registered['customer'], $registered['pets'] );
			return self::ok(
				array(
					'status'   => 'ok',
					'customer' => $customer,
					'pets'     => $registered['pets'],
				)
			);
		}

		$pets = self::scenario_pets( $phone );
		if ( null !== $pets ) {
			$customer = self::start_session(
				$phone,
				array(
					'first_name' => 'Jamie',
					'last_name'  => 'Rivera',
				),
				$pets
			);
			return self::ok(
				array(
					'status'   => 'ok',
					'customer' => $customer,
					'pets'     => $pets,
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
