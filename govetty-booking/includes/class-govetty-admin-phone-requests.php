<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Govetty_Phone_Requests_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'phone change request',
				'plural'   => 'phone change requests',
				'ajax'     => false,
			)
		);
	}

	public function get_columns() {
		return array(
			'customer_name' => 'Customer',
			'current_phone' => 'Current phone',
			'requested_phone' => 'Requested phone',
			'status'        => 'Status',
			'requested_at'  => 'Requested',
			'actions'       => 'Actions',
		);
	}

	public function prepare_items() {
		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$status       = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$result       = Govetty_Data_Store::get_phone_change_requests(
			array(
				'per_page' => $per_page,
				'offset'   => ( $current_page - 1 ) * $per_page,
				'status'   => $status,
			)
		);

		$this->items           = $result['rows'];
		$this->_column_headers = array( $this->get_columns(), array(), array() );
		$this->set_pagination_args(
			array(
				'total_items' => $result['total'],
				'per_page'    => $per_page,
			)
		);
	}

	public function no_items() {
		esc_html_e( 'No phone change requests yet.', 'govetty-booking' );
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'customer_name':
				return esc_html( $item['customer_name'] ?: '(no name on file)' );
			case 'current_phone':
				return esc_html( $item['current_phone'] );
			case 'requested_phone':
				return '<strong>' . esc_html( $item['requested_phone'] ) . '</strong>';
			case 'requested_at':
				return esc_html( mysql2date( 'M j, Y g:ia', $item['requested_at'] ) );
			default:
				return '';
		}
	}

	public function column_status( $item ) {
		$labels = array(
			'pending'  => '<span class="govetty-badge-pending">Pending</span>',
			'approved' => '<span class="govetty-badge-approved">Approved</span>',
			'rejected' => '<span class="govetty-badge-rejected">Rejected</span>',
		);
		return $labels[ $item['status'] ] ?? esc_html( $item['status'] );
	}

	public function column_actions( $item ) {
		if ( 'pending' !== $item['status'] ) {
			return $item['reviewed_at'] ? esc_html( mysql2date( 'M j, Y g:ia', $item['reviewed_at'] ) ) : '&mdash;';
		}

		$approve_url = wp_nonce_url(
			add_query_arg(
				array(
					'page'                    => Govetty_Admin_Menu::SLUG . '-phone-requests',
					'govetty_phone_action'    => 'approve',
					'request_id'              => $item['id'],
				),
				admin_url( 'admin.php' )
			),
			'govetty_phone_request_' . $item['id']
		);
		$reject_url = wp_nonce_url(
			add_query_arg(
				array(
					'page'                 => Govetty_Admin_Menu::SLUG . '-phone-requests',
					'govetty_phone_action' => 'reject',
					'request_id'           => $item['id'],
				),
				admin_url( 'admin.php' )
			),
			'govetty_phone_request_' . $item['id']
		);

		return sprintf(
			'<a href="%s" class="button button-primary button-small">Approve</a> <a href="%s" class="button button-small">Reject</a>',
			esc_url( $approve_url ),
			esc_url( $reject_url )
		);
	}
}

class Govetty_Admin_Phone_Requests {

	public static function render() {
		self::handle_action();

		$table = new Govetty_Phone_Requests_List_Table();
		$table->prepare_items();
		?>
		<div class="wrap">
			<h1>Phone Change Requests</h1>
			<?php self::render_caveat(); ?>
			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( Govetty_Admin_Menu::SLUG . '-phone-requests' ); ?>">
				<?php $table->display(); ?>
			</form>
		</div>
		<style><?php echo self::badge_css(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></style>
		<?php
	}

	/**
	 * Handles the Approve/Reject links above. Deliberately a plain
	 * GET+nonce action (matching this plugin's existing admin-screen
	 * style) rather than a form POST -- there's no destructive data loss
	 * risk here (the request row is never deleted, just marked), so the
	 * lighter-weight pattern is fine.
	 */
	private static function handle_action() {
		if ( empty( $_GET['govetty_phone_action'] ) || empty( $_GET['request_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$request_id = absint( $_GET['request_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		check_admin_referer( 'govetty_phone_request_' . $request_id );

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_GET['govetty_phone_action'] ) );
		$user   = wp_get_current_user();

		if ( 'approve' === $action ) {
			$request = Govetty_Data_Store::get_phone_change_request_by_id( $request_id );
			Govetty_Data_Store::approve_phone_change_request( $request_id, $user->ID );
			Govetty_Logger::log(
				'phone_change_approved',
				array(
					'request_id'      => $request_id,
					'current_phone'   => $request['current_phone'] ?? null,
					'requested_phone' => $request['requested_phone'] ?? null,
					'admin'           => $user->user_login,
				)
			);
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-success is-dismissible"><p>Request approved. Note: this updates this '
						. 'plugin\'s local customer record only -- see the caveat below about the customer\'s actual login '
						. 'phone number.</p></div>';
				}
			);
		} elseif ( 'reject' === $action ) {
			$request = Govetty_Data_Store::get_phone_change_request_by_id( $request_id );
			Govetty_Data_Store::reject_phone_change_request( $request_id, $user->ID );
			Govetty_Logger::log(
				'phone_change_rejected',
				array(
					'request_id'      => $request_id,
					'current_phone'   => $request['current_phone'] ?? null,
					'requested_phone' => $request['requested_phone'] ?? null,
					'admin'           => $user->user_login,
				)
			);
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-success is-dismissible"><p>Request rejected.</p></div>';
				}
			);
		}
	}

	private static function render_caveat() {
		echo '<div class="notice notice-warning"><p><strong>Important:</strong> approving a request here updates only '
			. 'this plugin\'s own local record of the customer\'s phone number (shown on the Customers screen and in '
			. 'their Personal Area). It does <strong>not</strong> change the phone number the customer actually logs in '
			. 'with -- the connected Marpet CPP API does not currently expose an endpoint to change that. Until the '
			. 'backend team confirms and wires one up, treat approval here as recording the decision, not completing '
			. 'the change end-to-end.</p></div>';
	}

	public static function badge_css() {
		return '.govetty-badge-pending{display:inline-block;background:#fff3d6;color:#8a6100;font-size:11px;font-weight:700;padding:2px 8px;border-radius:3px;}'
			. '.govetty-badge-approved{display:inline-block;background:#d7f0e3;color:#1c6b45;font-size:11px;font-weight:700;padding:2px 8px;border-radius:3px;}'
			. '.govetty-badge-rejected{display:inline-block;background:#fde2e1;color:#a1272b;font-size:11px;font-weight:700;padding:2px 8px;border-radius:3px;}';
	}
}
