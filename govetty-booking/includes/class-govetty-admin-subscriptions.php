<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Govetty_Events_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'event',
				'plural'   => 'events',
				'ajax'     => false,
			)
		);
	}

	public function get_columns() {
		return array(
			'customer_name' => 'Customer',
			'pet_name'      => 'Pet',
			'plan_key'      => 'Plan',
			'price_cents'   => 'Price',
			'status'        => 'Status',
			'created_at'    => 'Date',
		);
	}

	public function prepare_items() {
		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$hide_mock    = empty( $_GET['show_mock'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$result = Govetty_Data_Store::get_events(
			array(
				'per_page'  => $per_page,
				'offset'    => ( $current_page - 1 ) * $per_page,
				'hide_mock' => $hide_mock,
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
		esc_html_e( 'No checkout activity recorded yet.', 'govetty-booking' );
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'customer_name':
				$name  = $item['customer_name'] ?: $item['phone'];
				$badge = ! empty( $item['is_mock'] ) ? ' <span class="govetty-badge-mock">TEST</span>' : '';
				return esc_html( $name ) . $badge;
			case 'pet_name':
				return esc_html( $item['pet_name'] );
			case 'plan_key':
				return esc_html( $item['plan_key'] );
			case 'price_cents':
				return null !== $item['price_cents'] ? esc_html( '$' . number_format( $item['price_cents'] / 100, 2 ) ) : '&mdash;';
			case 'status':
				return Govetty_Admin_Customers::status_label( $item['status'] );
			case 'created_at':
				return esc_html( mysql2date( 'M j, Y g:ia', $item['created_at'] ) );
			default:
				return '';
		}
	}
}

class Govetty_Admin_Subscriptions {

	public static function render() {
		$summary    = Govetty_Data_Store::get_summary();
		$table      = new Govetty_Events_List_Table();
		$table->prepare_items();
		$show_mock  = ! empty( $_GET['show_mock'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$toggle_url = $show_mock ? remove_query_arg( 'show_mock' ) : add_query_arg( 'show_mock', '1' );
		?>
		<div class="wrap">
			<h1>Subscriptions &amp; Revenue</h1>

			<div class="notice notice-warning">
				<p><strong>Read this before trusting these numbers:</strong> the connected API has no purchase-history
				or revenue endpoint, and no payment webhook confirms a charge actually succeeded. Every row below
				reflects a Stripe Checkout session this widget <em>created</em> &mdash; &ldquo;Returned via success
				link&rdquo; only means the browser came back through the success redirect, which can&rsquo;t be
				treated as proof of payment. For real accounting, use Stripe&rsquo;s own Dashboard as the source of
				truth; treat this page as a fast, directional view tied to plans and pets, not a financial record.
				Test/mock data is always excluded from the totals below.</p>
			</div>

			<div class="govetty-stat-cards">
				<?php
				self::stat_card( 'Checkout sessions started', number_format_i18n( $summary['initiated'] ) );
				self::stat_card( 'Returned via success link', number_format_i18n( $summary['returned'] ) );
				self::stat_card( 'Estimated revenue', '$' . number_format( $summary['revenue_cents'] / 100, 2 ) );
				?>
			</div>

			<h2>By plan</h2>
			<table class="widefat striped" style="max-width:720px;">
				<thead>
					<tr><th>Plan</th><th>Started</th><th>Returned success</th><th>Estimated revenue</th></tr>
				</thead>
				<tbody>
				<?php if ( empty( $summary['by_plan'] ) ) : ?>
					<tr><td colspan="4">No checkout activity recorded yet.</td></tr>
				<?php else : ?>
					<?php foreach ( $summary['by_plan'] as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row['plan_key'] ); ?></td>
							<td><?php echo (int) $row['initiated_count']; ?></td>
							<td><?php echo (int) $row['returned_count']; ?></td>
							<td>$<?php echo number_format( $row['revenue_cents'] / 100, 2 ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>

			<h2 style="margin-top:28px; display:flex; align-items:center; justify-content:space-between;">
				<span>All checkout activity</span>
				<a href="<?php echo esc_url( $toggle_url ); ?>" style="font-size:13px; font-weight:400;">
					<?php echo $show_mock ? 'Hide test/mock entries' : 'Show test/mock entries'; ?>
				</a>
			</h2>
			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( Govetty_Admin_Menu::SLUG . '-subscriptions' ); ?>">
				<?php if ( $show_mock ) : ?>
					<input type="hidden" name="show_mock" value="1">
				<?php endif; ?>
				<?php $table->display(); ?>
			</form>
		</div>
		<style>
			.govetty-stat-cards { display: flex; gap: 16px; margin: 20px 0; flex-wrap: wrap; }
			.govetty-stat-card { background: #fff; border: 1px solid #dcdcde; border-radius: 6px; padding: 16px 20px; min-width: 180px; }
			.govetty-stat-card .label { font-size: 13px; color: #646970; }
			.govetty-stat-card .value { font-size: 26px; font-weight: 600; margin-top: 4px; }
			<?php echo Govetty_Admin_Customers::badge_css(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</style>
		<?php
	}

	private static function stat_card( $label, $value ) {
		printf(
			'<div class="govetty-stat-card"><div class="label">%s</div><div class="value">%s</div></div>',
			esc_html( $label ),
			esc_html( $value )
		);
	}
}
