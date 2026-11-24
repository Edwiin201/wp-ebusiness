<?php
// Isolated rendering checks: no database, network calls, emails or real orders.
define( 'ABSPATH', __DIR__ );
$hooks = array();
$product_page = false;
function add_action( $name, $callback, ...$args ) { $GLOBALS['hooks'][$name][] = $callback; }
function is_admin() { return false; }
function home_url() { return 'https://example.test'; }
function wp_parse_url( $url, $part ) { return parse_url( $url, $part ); }
function is_order_received_page() { return false; }
function is_wc_endpoint_url( $endpoint ) { return false; }
function is_account_page() { return false; }
function is_product() { return $GLOBALS['product_page']; }
function is_checkout() { return false; }
function get_queried_object_id() { return 107; }
function get_woocommerce_currency() { return 'PEN'; }
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
function wc_get_price_excluding_tax( $product ) { return 55; }
function wc_get_product( $id ) {
    return new class {
        function get_id() { return 107; }
        function get_name() { return 'Adaptador "USB" <prueba>'; }
    };
}
require dirname( __DIR__ ) . '/pixelcore-analytics.php';
function expect( $condition, $message ) {
    if ( ! $condition ) { throw new RuntimeException( $message ); }
}
function footer_output() {
    ob_start();
    foreach ( $GLOBALS['hooks']['wp_footer'] as $callback ) { $callback(); }
    return ob_get_clean();
}
$ordinary_page = footer_output();
expect( str_contains( $ordinary_page, 'const productView = null;' ), 'Non-product page must not emit item views.' );
$product_page = true;
$output = footer_output();
preg_match( '/const productView = (.*);/', $output, $match );
$event = json_decode( $match[1], true, 512, JSON_THROW_ON_ERROR );
expect( $event['currency'] === 'PEN' && $event['value'] === 55, 'Wrong currency or item value.' );
expect( $event['items'][0]['item_id'] === '107' && $event['items'][0]['quantity'] === 1, 'Wrong product identity or quantity.' );
expect( $event['items'][0]['item_name'] === 'Adaptador "USB" <prueba>', 'Product name must survive JSON encoding.' );
expect( ! str_contains( $match[1], '<prueba>' ), 'HTML-sensitive product names must be escaped in inline script.' );
expect( substr_count( $output, "gtag('event', 'view_item', productView)" ) === 1, 'Only one item-view dispatch per render.' );
echo "PASS: product detail payload, currency/value, identity, JSON escaping and non-product isolation.\n";
