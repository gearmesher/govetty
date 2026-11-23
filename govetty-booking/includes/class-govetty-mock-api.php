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
						'slots'  => self::available_slots( $params ),
					)
				);

			case '/slot/reserveSlot':
				return self::reserve_slot( $params );

			case '/slot/book':
				return self::book_slot( $params );

			case '/customer/attachPet':
				$token   = (string) ( $params['customer_token'] ?? '' );
				$session = $token ? get_transient( self::session_key( $token ) ) : false;
				if ( ! $session ) {
					return self::error( 422, array( 'status' => 'error', 'message' => 'customer_not_found' ) );
				}
				$pet = array(
					'id'           => wp_rand( 9000, 9999 ),
					'name'         => sanitize_text_field( $params['name'] ?? 'New pet' ),
					'type'         => sanitize_text_field( $params['type'] ?? '' ),
					'token_amount' => 0,
					'image_id'     => $params['image_id'] ?? null,
				);
				$session['pets'][] = $pet;
				set_transient( self::session_key( $token ), $session, 12 * HOUR_IN_SECONDS );
				return self::ok(
					array(
						'id'   => $pet['id'],
						'name' => $pet['name'],
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

	private static function slot_reservation_key( $slot_id ) {
		return 'govetty_mock_slot_reservation_' . absint( $slot_id );
	}

	private static function available_slots( $params ) {
		$token     = md5( (string) ( $params['customer_token'] ?? '' ) );
		$slots     = self::mock_slots();
		$available = array();
		foreach ( $slots as $slot ) {
			$reservation = get_transient( self::slot_reservation_key( $slot['slot_id'] ) );
			if ( ! $reservation || $reservation['token'] === $token ) {
				$available[] = $slot;
			}
		}
		return $available;
	}

	private static function reserve_slot( $params ) {
		$token   = (string) ( $params['customer_token'] ?? '' );
		$pet_id  = absint( $params['pet_id'] ?? 0 );
		$slot_id = absint( $params['slot_id'] ?? 0 );
		$session = $token ? get_transient( self::session_key( $token ) ) : false;
		if ( ! $session || ! $pet_id || ! $slot_id ) {
			return self::error( 422, array( 'status' => 'error', 'message' => 'invalid_reservation' ) );
		}
		$owns_pet = false;
		foreach ( $session['pets'] as $pet ) {
			if ( (int) $pet['id'] === $pet_id ) {
				$owns_pet = true;
				break;
			}
		}
		if ( ! $owns_pet ) {
			return self::error( 404, array( 'status' => 'error', 'message' => 'pet_not_found' ) );
		}

		$available = array_filter(
			self::mock_slots(),
			static function ( $slot ) use ( $slot_id ) {
				return (int) $slot['slot_id'] === $slot_id;
			}
		);
		if ( ! $available ) {
			return self::error( 404, array( 'status' => 'error', 'message' => 'slot_not_found' ) );
		}

		$key         = self::slot_reservation_key( $slot_id );
		$reservation = get_transient( $key );
		if ( $reservation && ( $reservation['token'] !== md5( $token ) || (int) $reservation['pet_id'] !== $pet_id ) ) {
			return self::error( 409, array( 'status' => 'error', 'message' => 'slot_unavailable' ) );
		}

		set_transient(
			$key,
			array(
				'token'  => md5( $token ),
				'pet_id' => $pet_id,
			),
			5 * MINUTE_IN_SECONDS
		);
		return self::ok( array( 'status' => 'ok', 'slot_id' => $slot_id ) );
	}

	private static function book_slot( $params ) {
		$token   = (string) ( $params['customer_token'] ?? '' );
		$pet_id  = absint( $params['pet_id'] ?? 0 );
		$slot_id = absint( $params['slot_id'] ?? 0 );
		$session = $token ? get_transient( self::session_key( $token ) ) : false;
		$key     = self::slot_reservation_key( $slot_id );
		$hold    = $slot_id ? get_transient( $key ) : false;
		if ( ! $session || ! $hold || $hold['token'] !== md5( $token ) || (int) $hold['pet_id'] !== $pet_id ) {
			return self::error( 409, array( 'status' => 'error', 'message' => 'slot_reservation_expired' ) );
		}

		$pet_index = null;
		foreach ( $session['pets'] as $index => $pet ) {
			if ( (int) $pet['id'] === $pet_id ) {
				$pet_index = $index;
				break;
			}
		}
		if ( null === $pet_index ) {
			return self::error( 404, array( 'status' => 'error', 'message' => 'pet_not_found' ) );
		}
		if ( (int) $session['pets'][ $pet_index ]['token_amount'] < 1 ) {
			return self::error( 422, array( 'status' => 'error', 'message' => 'insufficient_consultation_balance' ) );
		}

		--$session['pets'][ $pet_index ]['token_amount'];
		set_transient( self::session_key( $token ), $session, 12 * HOUR_IN_SECONDS );
		$registered = self::get_registered( $session['phone'] );
		if ( $registered ) {
			$registered['pets'] = $session['pets'];
			self::set_registered( $session['phone'], $registered );
		}
		delete_transient( $key );

		$join_url = add_query_arg(
			array(
				'govetty_mock_consultation' => '1',
				'join'                      => wp_generate_password( 32, false ),
			),
			home_url( '/' )
		);
		return self::ok( array( 'status' => 'ok', 'slot_id' => $slot_id, 'join_url' => $join_url ) );
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
