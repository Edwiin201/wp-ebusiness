<?php
/**
 * Plugin Name: PixelCore Correos en Español
 * Description: Traduce las confirmaciones y detalles de pedidos sin cambiar el idioma del sitio.
 * Version: 1.0.0
 */
defined( 'ABSPATH' ) || exit;

function pixelcore_spanish_email_active(): bool {
    return ! empty( $GLOBALS['pixelcore_spanish_email_active'] );
}

// Preview rendering does not call WC_Email::setup_locale().
add_action( 'woocommerce_email_header', static function ( $heading, $email ) {
    $GLOBALS['pixelcore_spanish_email_active'] = $email && in_array( $email->id, array( 'customer_processing_order', 'customer_completed_order', 'customer_invoice' ), true );
}, 0, 2 );
add_action( 'woocommerce_email_footer', static function () {
    $GLOBALS['pixelcore_spanish_email_active'] = false;
}, PHP_INT_MAX );

add_filter( 'woocommerce_allow_switching_email_locale', static function ( $enabled, $email ) {
    $GLOBALS['pixelcore_spanish_email_active'] = in_array( $email->id, array( 'customer_processing_order', 'customer_completed_order', 'customer_invoice' ), true );
    return $enabled;
}, 10, 2 );

add_filter( 'woocommerce_allow_restoring_email_locale', static function ( $enabled ) {
    $GLOBALS['pixelcore_spanish_email_active'] = false;
    return $enabled;
} );

function pixelcore_spanish_email_catalog(): void {
    if ( ! is_textdomain_loaded( 'pixelcore-email-es' ) ) {
        load_textdomain( 'pixelcore-email-es', __DIR__ . '/languages/woocommerce-es_ES.mo', 'es_ES' );
    }
}

add_filter( 'gettext_woocommerce', static function ( $translation, $text ) {
    if ( ! pixelcore_spanish_email_active() ) { return $translation; }
    pixelcore_spanish_email_catalog();
    return translate( $text, 'pixelcore-email-es' );
}, 10, 2 );

add_filter( 'gettext_with_context_woocommerce', static function ( $translation, $text, $context ) {
    if ( ! pixelcore_spanish_email_active() ) { return $translation; }
    pixelcore_spanish_email_catalog();
    return translate_with_gettext_context( $text, $context, 'pixelcore-email-es' );
}, 10, 3 );

add_filter( 'ngettext_woocommerce', static function ( $translation, $single, $plural, $number ) {
    if ( ! pixelcore_spanish_email_active() ) { return $translation; }
    pixelcore_spanish_email_catalog();
    return _n( $single, $plural, $number, 'pixelcore-email-es' );
}, 10, 4 );

foreach ( array( 'customer_processing_order', 'customer_completed_order', 'customer_invoice' ) as $email_id ) {
    add_filter( 'woocommerce_email_subject_' . $email_id, static function ( $subject, $order ) use ( $email_id ) {
        if ( ! $order instanceof WC_Order ) { return $subject; }
        $prefix = 'customer_processing_order' === $email_id ? 'Confirmación de tu compra' : ( 'customer_completed_order' === $email_id ? 'Tu pedido está completado' : 'Detalles de tu pedido' );
        return sprintf( '%s #%s — PixelCore', $prefix, $order->get_order_number() );
    }, 10, 2 );
    add_filter( 'woocommerce_email_heading_' . $email_id, static function () use ( $email_id ) {
        return 'customer_processing_order' === $email_id ? '¡Gracias por tu compra!' : ( 'customer_completed_order' === $email_id ? 'Tu pedido está completado' : 'Detalles de tu pedido' );
    } );
    add_filter( 'woocommerce_email_additional_content_' . $email_id, static function () {
        return 'Gracias por confiar en PixelCore. Si necesitas ayuda con tu pedido, responde a este correo.';
    } );
}

add_filter( 'woocommerce_email_footer_text', static function ( $text ) {
    return pixelcore_spanish_email_active() ? 'PixelCore · Gracias por confiar en nosotros.' : $text;
} );

// Older orders can retain gateway/shipping titles saved in English.
add_filter( 'woocommerce_get_order_item_totals', static function ( $totals ) {
    if ( ! pixelcore_spanish_email_active() ) { return $totals; }
    $titles = array(
        'Cash on delivery' => 'Pago contra entrega',
        'Direct bank transfer' => 'Transferencia bancaria directa',
        'Check payments' => 'Pago con cheque',
        'Flat rate' => 'Tarifa fija',
        'Free shipping' => 'Envío gratuito',
        'Local pickup' => 'Recogida en tienda',
    );
    foreach ( array( 'payment_method', 'shipping' ) as $key ) {
        if ( isset( $totals[ $key ]['value'] ) ) {
            $totals[ $key ]['value'] = strtr( $totals[ $key ]['value'], $titles );
        }
        if ( isset( $totals[ $key ]['label'] ) ) {
            $totals[ $key ]['label'] = strtr( $totals[ $key ]['label'], $titles );
        }
        if ( isset( $totals[ $key ]['meta'] ) && is_string( $totals[ $key ]['meta'] ) ) {
            $totals[ $key ]['meta'] = strtr( $totals[ $key ]['meta'], $titles );
        }
    }
    return $totals;
} );
