<?php
/**
 * Plugin Name: PixelCore Analytics
 * Description: Google Analytics 4 for the published PixelCore storefront.
 * Version: 1.0.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pixelcore_public_analytics_is_enabled(): bool {
	return ! is_admin() && '20.106.154.224' === wp_parse_url( home_url(), PHP_URL_HOST );
}

function pixelcore_public_analytics_tag( string $confirmation_url = '' ): void {
	$measurement_id = 'G-MLVVSB2ZBK';
	$tag_url = 'https://www.googletagmanager.com/gtag/js?id=' . $measurement_id;
	?>
	<script async referrerpolicy="no-referrer" src="<?php echo esc_url( $tag_url ); ?>"></script>
	<script>
	window.dataLayer = window.dataLayer || [];
	function gtag() { window.dataLayer.push(arguments); }
	gtag('js', new Date());
	{
		const config = {
            page_location: location.origin + location.pathname
        };
		if (document.referrer) {
			const referrer = new URL(document.referrer);
			config.page_referrer = referrer.origin + referrer.pathname;
		}
		const confirmationUrl = <?php echo wp_json_encode( $confirmation_url, JSON_HEX_TAG ); ?>;
		if (confirmationUrl) {
			config.page_location = confirmationUrl;
			config.send_page_view = false;
		}
		gtag('config', <?php echo wp_json_encode( $measurement_id ); ?>, config);
	}
	</script>
	<?php
}

function pixelcore_public_analytics_is_storefront(): bool {
	return pixelcore_public_analytics_is_enabled()
		&& ! ( function_exists( 'is_order_received_page' ) && is_order_received_page() )
        && ! ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) )
		&& ! ( function_exists( 'is_account_page' ) && is_account_page() );
}

add_action( 'wp_head', static function () {
	if ( pixelcore_public_analytics_is_storefront() ) {
		pixelcore_public_analytics_tag();
	}
}, 1 );

add_action( 'woocommerce_add_to_cart', static function ( $cart_item_key, $product_id, $quantity, $variation_id ) {
	if ( ! pixelcore_public_analytics_is_enabled() || is_admin() || ! WC()->session ) {
		return;
	}

	$product = wc_get_product( $variation_id ?: $product_id );
	if ( ! $product || $quantity <= 0 ) {
		return;
	}

	$price = wc_get_price_excluding_tax( $product );
	$event = array(
		'currency' => get_woocommerce_currency(),
		'value'    => round( $price * $quantity, wc_get_price_decimals() ),
		'items'    => array(
			array(
				'item_id'   => (string) $product->get_id(),
				'item_name' => $product->get_name(),
				'price'     => $price,
				'quantity'  => $quantity,
			),
		),
	);

	if ( doing_action( 'wc_ajax_add_to_cart' ) && 'yes' !== get_option( 'woocommerce_cart_redirect_after_add' ) ) {
		// The classic AJAX handler passes fragments to added_to_cart after a successful request.
		add_filter( 'woocommerce_add_to_cart_fragments', static function ( array $fragments ) use ( $event ): array {
			$fragments['#pixelcore-analytics-cart-event'] = '<span data-event="' . esc_attr( wp_json_encode( $event ) ) . '"></span>';
			return $fragments;
		} );
		return;
	}

	// Product forms and redirecting add-to-cart requests render a new page.
	$session = WC()->session;
	$events = $session->get( 'pixelcore_public_analytics_cart_events', array() );
	$events[] = $event;
	$session->set( 'pixelcore_public_analytics_cart_events', array_slice( $events, -20 ) );
}, 10, 4 );

add_action( 'wp_footer', static function () {
	if ( ! pixelcore_public_analytics_is_storefront() ) {
		return;
	}

	$session = function_exists( 'WC' ) ? WC()->session : null;
	$events = $session ? $session->get( 'pixelcore_public_analytics_cart_events', array() ) : array();
	if ( $events ) {
		$session->__unset( 'pixelcore_public_analytics_cart_events' );
	}
    $checkout_event = null;
    if ( function_exists( 'is_checkout' ) && is_checkout() && WC()->cart && ! WC()->cart->is_empty() ) {
        $items = array();
        foreach ( WC()->cart->get_cart() as $line ) {
            $product = $line['data'];
            $items[] = array(
                'item_id' => (string) $product->get_id(),
                'item_name' => $product->get_name(),
                'price' => (float) $line['line_total'] / max( 1, $line['quantity'] ),
                'quantity' => (int) $line['quantity'],
            );
        }
        $checkout_event = array(
            'currency' => get_woocommerce_currency(),
            'value' => (float) WC()->cart->get_cart_contents_total(),
            'items' => $items,
        );
    }
	?>
	<script>
	(() => {
		const checkoutEvent = <?php echo wp_json_encode( $checkout_event, JSON_HEX_TAG ); ?>;
        if (checkoutEvent) gtag('event', 'begin_checkout', checkoutEvent);
        const sendCartEvent = (event) => gtag('event', 'add_to_cart', event);
		(<?php echo wp_json_encode( $events, JSON_HEX_TAG ); ?>).forEach(sendCartEvent);

		if (window.jQuery) {
			window.jQuery(document.body).on('added_to_cart.pixelcoreAnalytics', (_event, fragments) => {
				const fragment = fragments?.['#pixelcore-analytics-cart-event'];
				if (!fragment) return;

				const template = document.createElement('template');
				template.innerHTML = fragment;
				const payload = template.content.firstElementChild?.dataset.event;
				if (payload) sendCartEvent(JSON.parse(payload));
			});
		}

		document.addEventListener('click', (event) => {
			const target = event.target.closest?.('a[href], button, [role="button"]');
			if (!target || target.closest('#wpadminbar, [disabled], [aria-disabled="true"]')) return;

			const link = target.closest('a[href]');
			const destination = link instanceof HTMLAnchorElement ? link : null;
			if (destination && destination.origin !== location.origin) return;

			const label = target.getAttribute('aria-label') || target.textContent;
			gtag('event', 'internal_click', {
				click_text: label.replace(/\s+/g, ' ').trim().slice(0, 100),
				link_path: destination?.pathname,
			});
		}, {capture: true});
	})();
	</script>
	<?php
}, 30 );

add_action( 'woocommerce_thankyou', static function ( $order_id ) {
	if ( ! pixelcore_public_analytics_is_enabled() || ! is_order_received_page() ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order || ! ( $order->is_paid() || ( 'cod' === $order->get_payment_method() && $order->has_status( array( 'processing', 'on-hold', 'completed' ) ) ) ) ) {
		return;
	}

	$items = array();
	$value = 0;
	foreach ( $order->get_items() as $item ) {
		$quantity = $item->get_quantity();
		if ( $quantity <= 0 ) {
			continue;
		}
		$total = (float) $item->get_total();
		$value += $total;
		$items[] = array(
			'item_id'   => (string) ( $item->get_variation_id() ?: $item->get_product_id() ),
			'item_name' => $item->get_name(),
            'price'     => $total / $quantity,
			'quantity'  => $quantity,
		);
	}
	if ( ! $items ) {
		return;
	}

	// WooCommerce has verified access to this order. Omit the order key from the page URL sent to GA4.
	pixelcore_public_analytics_tag( wc_get_endpoint_url( 'order-received', '', wc_get_checkout_url() ) );
	$purchase = array(
		'page_location' => wc_get_endpoint_url( 'order-received', '', wc_get_checkout_url() ),
        'transaction_id' => 'pixelcore-' . $order->get_id(),
		'currency'       => $order->get_currency(),
		'value'          => round( $value, wc_get_price_decimals() ),
		'tax'            => (float) $order->get_total_tax(),
		'shipping'       => (float) $order->get_shipping_total(),
		'items'          => $items,
	);
	?>
	<script>gtag('event', 'purchase', <?php echo wp_json_encode( $purchase, JSON_HEX_TAG ); ?>);</script>
	<?php
}, 20 );
