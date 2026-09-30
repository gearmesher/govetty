<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Local activity log built from data that already passes through this
 * plugin's own REST proxy -- NOT a substitute for a real customer
 * database or purchase ledger.
 *
 * Why this exists instead of calling an admin API: the Marpet CPP API has
 * no endpoint to list all customers or fetch purchase history. Every
 * customer-related endpoint (/info/user, /billing/getPlans,
 * /getConferenceTokenAmount) requires a customer_token that only that one
 * customer holds after logging in -- there's no way for an admin to look
 * up an arbitrary customer. So this table is the best available
 * substitute: a record of what's actually happened through this widget,
 * not a live view of the real system of record.
 *
 * Three tables:
 * - {prefix}govetty_customers: upserted whenever someone completes OTP
 *   verification or registration through this widget. A last-known
 *   snapshot (pets, balances), not a live sync -- it's only as fresh as
 *   that customer's last visit.
 * - {prefix}govetty_checkout_events: one row per Stripe Checkout session
 *   this widget has created. "returned_success" means the browser came
 *   back via the success redirect -- it is NOT a confirmed payment, since
 *   the connected API has no webhook to verify that server-side. Treat
 *   these figures as directional, not as an accounting record.
 * - {prefix}govetty_phone_change_requests: a customer-submitted request
 *   (from the Personal Area) to change their registered phone number,
 *   held for web-admin approval rather than applied immediately. IMPORTANT
 *   caveat, same spirit as the two tables above: approving a request here
 *   only updates this plugin's own local govetty_customers snapshot row --
 *   there is no confirmed Marpet CPP API endpoint to change the actual
 *   login-credential phone number on their side. Until/unless the backend
 *   team confirms and wires up a real endpoint for that, an "approved"
 *   request does NOT let the customer log in with the new number; it's
 *   recorded here so the approval workflow the client asked for exists and
 *   is visible, and so this gap is easy to find and finish once a real
 *   endpoint exists. Don't build customer-facing copy that implies this is
 *   already a full phone-number change.
 */
class Govetty_Data_Store {

	const CUSTOMERS_TABLE      = 'govetty_customers';
	const EVENTS_TABLE         = 'govetty_checkout_events';
	const PHONE_REQUESTS_TABLE = 'govetty_phone_change_requests';
	const DB_VERSION_OPT       = 'govetty_booking_db_version';

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset_collate = $wpdb->get_charset_collate();

		$customers_table = $wpdb->prefix . self::CUSTOMERS_TABLE;
		$sql1            = "CREATE TABLE $customers_table (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			phone VARCHAR(32) NOT NULL,
			first_name VARCHAR(255) NOT NULL DEFAULT '',
			last_name VARCHAR(255) NOT NULL DEFAULT '',
			pets_json LONGTEXT NULL,
			is_mock TINYINT(1) NOT NULL DEFAULT 0,
			first_seen DATETIME NOT NULL,
			last_seen DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY phone (phone)
		) $charset_collate;";

		$events_table = $wpdb->prefix . self::EVENTS_TABLE;
		$sql2         = "CREATE TABLE $events_table (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			checkout_ref VARCHAR(40) NOT NULL,
			phone VARCHAR(32) NOT NULL DEFAULT '',
			customer_name VARCHAR(255) NOT NULL DEFAULT '',
			pet_id BIGINT NOT NULL DEFAULT 0,
			pet_name VARCHAR(255) NOT NULL DEFAULT '',
			plan_key VARCHAR(64) NOT NULL DEFAULT '',
			price_cents BIGINT NULL,
			status VARCHAR(32) NOT NULL DEFAULT 'initiated',
			is_mock TINYINT(1) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY checkout_ref (checkout_ref),
			KEY phone (phone),
			KEY plan_key (plan_key),
			KEY status (status)
		) $charset_collate;";

		$requests_table = $wpdb->prefix . self::PHONE_REQUESTS_TABLE;
		$sql3           = "CREATE TABLE $requests_table (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			current_phone VARCHAR(32) NOT NULL,
			requested_phone VARCHAR(32) NOT NULL,
			customer_name VARCHAR(255) NOT NULL DEFAULT '',
			status VARCHAR(32) NOT NULL DEFAULT 'pending',
			admin_note TEXT NULL,
			requested_at DATETIME NOT NULL,
			reviewed_at DATETIME NULL,
			reviewed_by BIGINT UNSIGNED NULL,
			PRIMARY KEY  (id),
			KEY current_phone (current_phone),
			KEY status (status)
		) $charset_collate;";

		dbDelta( $sql1 );
		dbDelta( $sql2 );
		dbDelta( $sql3 );
	}

	/**
	 * Safety net for sites that already had the plugin active before this
	 * feature shipped: a straight file update doesn't fire
	 * register_activation_hook (that only runs on an actual
	 * deactivate/reactivate), so this runs the same idempotent dbDelta
	 * call once per version bump instead.
	 */
	public static function maybe_install() {
		if ( get_option( self::DB_VERSION_OPT ) !== GOVETTY_BOOKING_VERSION ) {
			self::install();
			update_option( self::DB_VERSION_OPT, GOVETTY_BOOKING_VERSION );
		}
	}

	public static function upsert_customer( $phone, $first_name, $last_name, $pets, $is_mock = 0 ) {
		global $wpdb;
		if ( ! $phone ) {
			return;
		}
		$table    = $wpdb->prefix . self::CUSTOMERS_TABLE;
		$now      = current_time( 'mysql' );
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE phone = %s", $phone ) );

		$data = array(
			'phone'      => $phone,
			'first_name' => (string) $first_name,
			'last_name'  => (string) $last_name,
			'pets_json'  => wp_json_encode( $pets ),
			'is_mock'    => $is_mock ? 1 : 0,
			'last_seen'  => $now,
		);

		if ( $existing ) {
			$wpdb->update( $table, $data, array( 'id' => $existing ) );
		} else {
			$data['first_seen'] = $now;
			$wpdb->insert( $table, $data );
		}
	}

	public static function log_checkout_initiated( $args ) {
		global $wpdb;
		$table = $wpdb->prefix . self::EVENTS_TABLE;
		$now   = current_time( 'mysql' );
		$wpdb->insert(
			$table,
			array(
				'checkout_ref'  => $args['checkout_ref'],
				'phone'         => $args['phone'] ?? '',
				'customer_name' => $args['customer_name'] ?? '',
				'pet_id'        => (int) ( $args['pet_id'] ?? 0 ),
				'pet_name'      => $args['pet_name'] ?? '',
				'plan_key'      => $args['plan_key'] ?? '',
				'price_cents'   => isset( $args['price_cents'] ) ? (int) $args['price_cents'] : null,
				'status'        => 'initiated',
				'is_mock'       => ! empty( $args['is_mock'] ) ? 1 : 0,
				'created_at'    => $now,
				'updated_at'    => $now,
			)
		);
	}

	public static function mark_checkout_returned( $checkout_ref, $outcome ) {
		global $wpdb;
		$table  = $wpdb->prefix . self::EVENTS_TABLE;
		$status = 'success' === $outcome ? 'returned_success' : 'returned_cancelled';
		$wpdb->update(
			$table,
			array(
				'status'     => $status,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'checkout_ref' => sanitize_text_field( $checkout_ref ) )
		);
	}

	public static function get_customers( $args = array() ) {
		global $wpdb;
		$table    = $wpdb->prefix . self::CUSTOMERS_TABLE;
		$per_page = $args['per_page'] ?? 20;
		$offset   = $args['offset'] ?? 0;

		$rows  = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $table ORDER BY last_seen DESC LIMIT %d OFFSET %d", $per_page, $offset ),
			ARRAY_A
		);
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
		return array(
			'rows'  => $rows,
			'total' => $total,
		);
	}

	public static function get_customer_by_id( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::CUSTOMERS_TABLE;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ), ARRAY_A );
	}

	public static function get_events_for_phone( $phone ) {
		global $wpdb;
		$table = $wpdb->prefix . self::EVENTS_TABLE;
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $table WHERE phone = %s ORDER BY created_at DESC", $phone ),
			ARRAY_A
		);
	}

	public static function get_events( $args = array() ) {
		global $wpdb;
		$table     = $wpdb->prefix . self::EVENTS_TABLE;
		$per_page  = $args['per_page'] ?? 20;
		$offset    = $args['offset'] ?? 0;
		$hide_mock = ! empty( $args['hide_mock'] );
		$where     = $hide_mock ? 'WHERE is_mock = 0' : '';

		$rows  = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $table $where ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset ),
			ARRAY_A
		);
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table $where" );
		return array(
			'rows'  => $rows,
			'total' => $total,
		);
	}

	/**
	 * Always excludes mock/test rows -- these are meant to read as real
	 * numbers, so test data never gets a chance to inflate them even if
	 * someone forgets mock mode is on.
	 */
	public static function get_summary() {
		global $wpdb;
		$table = $wpdb->prefix . self::EVENTS_TABLE;

		$initiated = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE is_mock = 0" );
		$returned  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE is_mock = 0 AND status = 'returned_success'" );
		$revenue   = (int) $wpdb->get_var( "SELECT COALESCE(SUM(price_cents),0) FROM $table WHERE is_mock = 0 AND status = 'returned_success'" );

		$by_plan = $wpdb->get_results(
			"SELECT plan_key,
			        COUNT(*) AS initiated_count,
			        SUM(CASE WHEN status = 'returned_success' THEN 1 ELSE 0 END) AS returned_count,
			        COALESCE(SUM(CASE WHEN status = 'returned_success' THEN price_cents ELSE 0 END),0) AS revenue_cents
			 FROM $table
			 WHERE is_mock = 0
			 GROUP BY plan_key
			 ORDER BY revenue_cents DESC",
			ARRAY_A
		);

		return array(
			'initiated'     => $initiated,
			'returned'      => $returned,
			'revenue_cents' => $revenue,
			'by_plan'       => $by_plan,
		);
	}

	// ---------------------------------------------------------------------
	// Phone change requests -- see the class docblock for the important
	// caveat about what "approved" actually does (and doesn't do) here.
	// ---------------------------------------------------------------------

	/**
	 * Records a new request. Deliberately allowed even if an earlier
	 * pending request exists for this phone -- the admin list shows every
	 * request, newest first, so a customer resubmitting after being
	 * declined (or just changing their mind about the new number) doesn't
	 * get silently blocked. The dashboard only ever surfaces the latest one.
	 */
	public static function create_phone_change_request( $current_phone, $requested_phone, $customer_name = '' ) {
		global $wpdb;
		$table = $wpdb->prefix . self::PHONE_REQUESTS_TABLE;
		$wpdb->insert(
			$table,
			array(
				'current_phone'   => $current_phone,
				'requested_phone' => $requested_phone,
				'customer_name'   => (string) $customer_name,
				'status'          => 'pending',
				'requested_at'    => current_time( 'mysql' ),
			)
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * The single most recent request for this phone, if any -- used by the
	 * dashboard to show "pending approval" / "declined" state next to the
	 * phone number rather than a bare Edit control.
	 */
	public static function get_latest_phone_change_request( $current_phone ) {
		global $wpdb;
		$table = $wpdb->prefix . self::PHONE_REQUESTS_TABLE;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE current_phone = %s ORDER BY requested_at DESC LIMIT 1",
				$current_phone
			),
			ARRAY_A
		);
	}

	public static function get_phone_change_requests( $args = array() ) {
		global $wpdb;
		$table    = $wpdb->prefix . self::PHONE_REQUESTS_TABLE;
		$per_page = $args['per_page'] ?? 20;
		$offset   = $args['offset'] ?? 0;
		$status   = $args['status'] ?? '';
		$where    = $status ? $wpdb->prepare( 'WHERE status = %s', $status ) : '';

		$rows  = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $table $where ORDER BY requested_at DESC LIMIT %d OFFSET %d", $per_page, $offset ),
			ARRAY_A
		);
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table $where" );
		return array(
			'rows'  => $rows,
			'total' => $total,
		);
	}

	public static function get_phone_change_request_by_id( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::PHONE_REQUESTS_TABLE;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ), ARRAY_A );
	}

	/**
	 * Marks the request approved AND updates the local govetty_customers
	 * snapshot row's phone column -- but that snapshot is only ever read
	 * back by this plugin's own admin screens/dashboard payload, never used
	 * to authenticate. It does NOT change what phone number the customer
	 * actually logs in with on Marpet's side. See the class docblock.
	 */
	public static function approve_phone_change_request( $id, $reviewed_by, $admin_note = '' ) {
		global $wpdb;
		$request = self::get_phone_change_request_by_id( $id );
		if ( ! $request || 'pending' !== $request['status'] ) {
			return false;
		}

		$table = $wpdb->prefix . self::PHONE_REQUESTS_TABLE;
		$wpdb->update(
			$table,
			array(
				'status'      => 'approved',
				'admin_note'  => (string) $admin_note,
				'reviewed_at' => current_time( 'mysql' ),
				'reviewed_by' => (int) $reviewed_by,
			),
			array( 'id' => (int) $id )
		);

		$customers_table = $wpdb->prefix . self::CUSTOMERS_TABLE;
		$wpdb->update(
			$customers_table,
			array( 'phone' => $request['requested_phone'] ),
			array( 'phone' => $request['current_phone'] )
		);

		return true;
	}

	public static function reject_phone_change_request( $id, $reviewed_by, $admin_note = '' ) {
		global $wpdb;
		$request = self::get_phone_change_request_by_id( $id );
		if ( ! $request || 'pending' !== $request['status'] ) {
			return false;
		}

		$table = $wpdb->prefix . self::PHONE_REQUESTS_TABLE;
		$wpdb->update(
			$table,
			array(
				'status'      => 'rejected',
				'admin_note'  => (string) $admin_note,
				'reviewed_at' => current_time( 'mysql' ),
				'reviewed_by' => (int) $reviewed_by,
			),
			array( 'id' => (int) $id )
		);

		return true;
	}
}
