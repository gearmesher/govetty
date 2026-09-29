<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Serves a plain HTML stand-in for Stripe's hosted checkout page when
 * mock mode is on, reached via the checkout_url that
 * Govetty_Mock_Api::checkout_session() hands back. Intercepts the request
 * early (on `init`) and exits, rather than adding rewrite rules for what
 * is only ever a testing aid.
 */
class Govetty_Mock_Checkout {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_render' ) );
	}

	public static function maybe_render() {
		if ( empty( $_GET['govetty_mock_checkout'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$session_id = isset( $_GET['session'] ) ? sanitize_text_field( wp_unslash( $_GET['session'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$data       = $session_id ? get_transient( 'govetty_mock_checkout_' . $session_id ) : false;

		if ( ! $data ) {
			wp_die(
				'This mock checkout link has expired or was already used. Go back and pick a plan again.',
				'Mock checkout expired',
				array( 'response' => 410 )
			);
		}

		self::render( $data );
		exit;
	}

	private static function render( $data ) {
		$success_url = esc_url( $data['success_url'] );
		$cancel_url  = esc_url( $data['cancel_url'] );
		$plan_key    = esc_html( $data['plan_key'] ?: 'n/a' );
		?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Mock Stripe Checkout</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
	body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background:#0a2540; color:#fff; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
	.card { background:#fff; color:#0a2540; border-radius:12px; padding:32px; max-width:400px; width:90%; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,.3); }
	.badge { display:inline-block; background:#ffe58f; color:#7a5b00; font-size:12px; font-weight:700; padding:4px 10px; border-radius:999px; margin-bottom:16px; letter-spacing:.02em; }
	h1 { font-size:20px; margin:0 0 6px; }
	p { color:#5b6874; font-size:14px; line-height:1.5; }
	.btn { display:block; width:100%; padding:12px; border-radius:8px; font-weight:600; text-decoration:none; margin-top:12px; box-sizing:border-box; }
	.btn-pay { background:#635bff; color:#fff; }
	.btn-cancel { background:#f3f4f6; color:#0a2540; }
</style>
</head>
<body>
	<div class="card">
		<span class="badge">MOCK CHECKOUT &mdash; TEST MODE</span>
		<h1>Simulated Stripe payment</h1>
		<p>Plan: <?php echo $plan_key; ?><br>No real charge will be made. Choose an outcome to continue testing the flow.</p>
		<a class="btn btn-pay" href="<?php echo $success_url; ?>">Simulate successful payment</a>
		<a class="btn btn-cancel" href="<?php echo $cancel_url; ?>">Simulate cancelled payment</a>
	</div>
</body>
</html>
		<?php
	}
}
