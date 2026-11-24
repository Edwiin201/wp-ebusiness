<?php
/**
 * Plugin Name: PixelCore Checkout
 * Description: Evita esperas indefinidas al confirmar pedidos y ayuda a prevenir reenvíos duplicados.
 * Version: 1.0.0
 * Requires Plugins: woocommerce
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', static function () {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_wc_endpoint_url( 'order-pay' ) ) {
		return;
	}
	wp_enqueue_script(
		'pixelcore-checkout',
		plugins_url( 'checkout.js', __FILE__ ),
		array( 'jquery', 'wc-checkout' ),
		'1.0.0',
		true
	);
} );
