<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Govetty_Customers_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'customer',
				'plural'   => 'customers',
				'ajax'     => false,
			)
		);
	}

	public function get_columns() {
		return array(
			'name'       => 'Name',
			'phone'      => 'Phone',
			'pets'       => 'Pets',
			'first_seen' => 'First seen',
			'last_seen'  => 'Last seen',
		);
	}

	public function prepare_items() {
		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$result       = Govetty_Data_Store::get_customers(
			array(
				'per_page' => $per_page,
				'offset'   => ( $current_page - 1 ) * $per_page,
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
		esc_html_e( 'No customers have logged in or registered through the booking widget yet.', 'govetty-booking' );
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'phone':
				return esc_html( $item['phone'] );
			case 'first_seen':
				return esc_html( mysql2date( 'M j, Y', $item['first_seen'] ) );
			case 'last_seen':
				return esc_html( mysql2date( 'M j, Y g:ia', $item['last_seen'] ) );
			default:
				return '';
		}
	}

	public function column_name( $item ) {
		$name = trim( $item['first_name'] . ' ' . $item['last_name'] );
		$name = $name ?: '(no name on file)';
		$url  = add_query_arg(
			array(
				'page'     => Govetty_Admin_Menu::SLUG,
				'customer' => $item['id'],
			),
			admin_url( 'admin.php' )
		);
		$badge = ! empty( $item['is_mock'] ) ? ' <span class="govetty-badge-mock">TEST</span>' : '';
		return sprintf( '<a href="%s"><strong>%s</strong></a>%s', esc_url( $url ), esc_html( $name ), $badge );
	}

	public function column_pets( $item ) {
		$pets = json_decode( (string) $item['pets_json'], true );
		if ( empty( $pets ) ) {
			return '&mdash;';
		}
		$parts = array();
		foreach ( $pets as $pet ) {
			$balance = (int) ( $pet['token_amount'] ?? 0 );
			$parts[] = sprintf(
				'%s (%s, %d call%s)',
				esc_html( $pet['name'] ?? '?' ),
				esc_html( $pet['type'] ?? '?' ),
				$balance,
				1 === $balance ? '' : 's'
			);
		}
		return implode( '<br>', $parts );
	}
}

class Govetty_Admin_Customers {

	public static function render() {
		if ( isset( $_GET['customer'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			self::render_detail( absint( $_GET['customer'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		self::render_list();
	}

	private static function render_list() {
		$table = new Govetty_Customers_List_Table();
		$table->prepare_items();
		?>
		<div class="wrap">
			<h1>Customers</h1>
			<?php self::render_caveat(); ?>
			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( Govetty_Admin_Menu::SLUG ); ?>">
				<?php $table->display(); ?>
			</form>
		</div>
		<?php
	}

	private static function render_detail( $id ) {
		$customer = Govetty_Data_Store::get_customer_by_id( $id );
		if ( ! $customer ) {
			echo '<div class="wrap"><h1>Customer not found</h1></div>';
			return;
		}
		$pets     = json_decode( (string) $customer['pets_json'], true ) ?: array();
		$events   = Govetty_Data_Store::get_events_for_phone( $customer['phone'] );
		$back_url = remove_query_arg( 'customer' );
		$name     = trim( $customer['first_name'] . ' ' . $customer['last_name'] ) ?: '(no name on file)';
		?>
		<div class="wrap">
			<p><a href="<?php echo esc_url( $back_url ); ?>">&larr; Back to Customers</a></p>
			<h1>
				<?php echo esc_html( $name ); ?>
				<?php if ( ! empty( $customer['is_mock'] ) ) : ?>
					<span class="govetty-badge-mock">TEST</span>
				<?php endif; ?>
			</h1>
			<p>
				<strong>Phone:</strong> <?php echo esc_html( $customer['phone'] ); ?><br>
				<strong>First seen:</strong> <?php echo esc_html( mysql2date( 'M j, Y g:ia', $customer['first_seen'] ) ); ?><br>
				<strong>Last seen:</strong> <?php echo esc_html( mysql2date( 'M j, Y g:ia', $customer['last_seen'] ) ); ?>
			</p>

			<h2>Pets</h2>
			<table class="widefat striped" style="max-width:600px;">
				<thead>
					<tr><th>Name</th><th>Type</th><th>Call balance</th></tr>
				</thead>
				<tbody>
				<?php if ( empty( $pets ) ) : ?>
					<tr><td colspan="3">No pets on file.</td></tr>
				<?php else : ?>
					<?php foreach ( $pets as $pet ) : ?>
						<tr>
							<td><?php echo esc_html( $pet['name'] ?? '?' ); ?></td>
							<td><?php echo esc_html( $pet['type'] ?? '?' ); ?></td>
							<td><?php echo (int) ( $pet['token_amount'] ?? 0 ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>

			<h2 style="margin-top:24px;">Checkout activity</h2>
			<p class="description"><?php self::events_caveat_text(); ?></p>
			<table class="widefat striped">
				<thead>
					<tr><th>Plan</th><th>Pet</th><th>Price</th><th>Status</th><th>Date</th></tr>
				</thead>
				<tbody>
				<?php if ( empty( $events ) ) : ?>
					<tr><td colspan="5">No checkout activity recorded.</td></tr>
				<?php else : ?>
					<?php foreach ( $events as $event ) : ?>
						<tr>
							<td><?php echo esc_html( $event['plan_key'] ); ?></td>
							<td><?php echo esc_html( $event['pet_name'] ); ?></td>
							<td><?php echo null !== $event['price_cents'] ? esc_html( '$' . number_format( $event['price_cents'] / 100, 2 ) ) : '&mdash;'; ?></td>
							<td><?php echo esc_html( self::status_label( $event['status'] ) ); ?><?php echo ! empty( $event['is_mock'] ) ? ' <span class="govetty-badge-mock">TEST</span>' : ''; ?></td>
							<td><?php echo esc_html( mysql2date( 'M j, Y g:ia', $event['created_at'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<style><?php echo self::badge_css(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></style>
		<?php
	}

	public static function status_label( $status ) {
		$labels = array(
			'initiated'          => 'Checkout started',
			'returned_success'   => 'Returned via success link',
			'returned_cancelled' => 'Cancelled',
		);
		return $labels[ $status ] ?? $status;
	}

	private static function render_caveat() {
		echo '<div class="notice notice-info"><p>This list includes customers who have logged in or registered '
			. 'through the GoVetty booking widget. It is not a complete customer database &mdash; the connected API '
			. 'has no endpoint to list all customers, so this reflects activity through this widget only, as of '
			. 'each customer\'s last visit.</p></div>';
		echo '<style>' . self::badge_css() . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function events_caveat_text() {
		echo '"Returned via success link" means the browser came back through the payment-success redirect &mdash; '
			. 'it is not a confirmed payment. There is currently no payment webhook connected, so treat this as '
			. 'directional, not a financial record.';
	}

	public static function badge_css() {
		return '.govetty-badge-mock{display:inline-block;background:#ffe58f;color:#7a5b00;font-size:10px;font-weight:700;padding:1px 6px;border-radius:3px;vertical-align:middle;letter-spacing:.02em;}';
	}
}
