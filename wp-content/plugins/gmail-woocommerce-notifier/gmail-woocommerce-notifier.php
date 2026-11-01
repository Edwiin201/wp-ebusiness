<?php
/**
 * Plugin Name: Gmail WooCommerce Notifier
 * Description: Envía un correo de confirmación a la cuenta Gmail del cliente usando la Gmail API cuando se completa una compra.
 * Version: 1.0
 * Author: Tu Nombre
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Ruta al archivo JSON con las credenciales OAuth 2.0 (ajusta si lo guardas en otro sitio)
define( 'GMAIL_CREDS_PATH', WP_CONTENT_DIR . '/private/gmail-creds.json' );

// Cargar la librería de Google (Composer autoload). Asegúrate de haber ejecutado `composer install` dentro del plugin.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p>Gmail WooCommerce Notifier: falta la carpeta <code>vendor/</code>. Ejecuta <code>composer install</code> en la carpeta del plugin.</p></div>';
    } );
    return; // Detener la carga del resto del plugin
}

// ---------------------------------------------------------------------------
// OAuth callback (admin‑ajax.php)
// ---------------------------------------------------------------------------
function gwn_handle_oauth_callback() {
    if ( isset( $_GET['code'] ) && isset( $_GET['action'] ) && $_GET['action'] === 'gmail_oauth_callback' ) {
        error_log('Gmail Notifier: handling OAuth callback');
        $client = new Google_Client();
        $client->setAuthConfig( GMAIL_CREDS_PATH );
        $client->addScope( Google_Service_Gmail::MAIL_GOOGLE_COM );
        $client->setAccessType( 'offline' );
        $client->setPrompt( 'select_account consent' );
        $access = $client->fetchAccessTokenWithAuthCode( $_GET['code'] );
        if ( array_key_exists( 'error', $access ) ) {
            error_log('Gmail OAuth callback error: ' . $access['error'] );
        } else {
            update_option( 'gwn_gmail_api_token', $access );
            error_log('Gmail Notifier: OAuth token saved');
        }
        $url = remove_query_arg( array( 'code', 'action' ) );
        wp_redirect( $url );
        exit;
    }
}
add_action( 'admin_init', 'gwn_handle_oauth_callback' );

// ---------------------------------------------------------------------------
// Admin menu (under WooCommerce)
// ---------------------------------------------------------------------------
function gwn_add_admin_menu() {
    add_submenu_page(
        'woocommerce',          // parent slug
        'Gmail Notifier',
        'Gmail Notifier',
        'manage_options',
        'gwn-settings',
        'gwn_admin_page'
    );
}
add_action( 'admin_menu', 'gwn_add_admin_menu' );

// ---------------------------------------------------------------------------
// Admin page (settings) with test email form
// ---------------------------------------------------------------------------
function gwn_admin_page() {
    // Handle test email submission
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gwn_test_email']) && check_admin_referer('gwn_send_test_email') ) {
        $test_email = sanitize_email( $_POST['gwn_test_email'] );
        if ( $test_email ) {
            $subject = 'Prueba de Gmail Notifier';
            $body = '<h2>Este es un mensaje de prueba</h2><p>Si ves esto, la integración funciona.</p>';
            $sent = gwn_send_gmail_message( $test_email, $subject, $body );
            if ( $sent ) {
                echo '<div class="notice notice-success"><p>Correo de prueba enviado a ' . esc_html( $test_email ) . '.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Error al enviar el correo de prueba. Revisa los logs.</p></div>';
            }
        }
    }

    echo '<div class="wrap"><h1>Gmail Notifier Settings</h1>';
    $client = gwn_get_google_client();
    if ( $client ) {
        echo '<p>Gmail API está autorizada y lista para enviar correos.</p>';
    } else {
        echo '<p>La autorización no está completada. Usa el enlace anterior para autorizar.</p>';
    }

    // Test email form
    echo '<h2>Enviar correo de prueba</h2>';
    echo '<form method="post">';
    wp_nonce_field('gwn_send_test_email');
    echo '<p><label for="gwn_test_email">Correo electrónico de prueba:</label> ';
    echo '<input type="email" id="gwn_test_email" name="gwn_test_email" required style="width:300px;" /></p>';
    echo '<p><input type="submit" class="button button-primary" value="Enviar prueba" /></p>';
    echo '</form>';
    echo '</div>';
}

// ---------------------------------------------------------------------------
// Google client helper
// ---------------------------------------------------------------------------
function gwn_get_google_client() {
    error_log('Gmail Notifier: entering gwn_get_google_client()');
    if ( ! file_exists( GMAIL_CREDS_PATH ) ) {
        if ( current_user_can( 'manage_options' ) ) {
            echo '<div class="notice notice-error"><p>Gmail Notifier: falta el archivo de credenciales <code>' . esc_html( GMAIL_CREDS_PATH ) . '</code>. Crea un proyecto en Google Cloud, habilita la Gmail API y descarga el JSON aquí.</p></div>';
        }
        return null;
    }
    $client = new Google_Client();
    $client->setAuthConfig( GMAIL_CREDS_PATH );
    $client->addScope( Google_Service_Gmail::MAIL_GOOGLE_COM );
    $client->setAccessType( 'offline' );
    $client->setPrompt( 'select_account consent' );

    $token = get_option( 'gwn_gmail_api_token' );
    if ( $token ) {
        $client->setAccessToken( $token );
        if ( $client->isAccessTokenExpired() ) {
            $refresh = $client->fetchAccessTokenWithRefreshToken( $client->getRefreshToken() );
            update_option( 'gwn_gmail_api_token', $client->getAccessToken() );
        }
    } else {
        if ( current_user_can( 'manage_options' ) ) {
            $auth_url = $client->createAuthUrl();
            echo '<div class="notice notice-info"><p>Autoriza la Gmail API para el plugin: <a href="' . esc_url( $auth_url ) . '" target="_blank" rel="noopener">Abrir Google OAuth</a></p></div>';
        }
        return null;
    }
    return $client;
}

// ---------------------------------------------------------------------------
// Send email via Gmail API
// ---------------------------------------------------------------------------
function gwn_send_gmail_message( $to, $subject, $html_body ) {
    $client = gwn_get_google_client();
    if ( ! $client ) return false;
    $service = new Google_Service_Gmail( $client );
    $boundary = uniqid( 'np' );
    $raw = "From: me\r\n";
    $raw .= "To: {$to}\r\n";
    $subject_encoded = '=?UTF-8?B?' . base64_encode( $subject ) . '?=';
    $raw .= "Subject: {$subject_encoded}\r\n";
    $raw .= "MIME-Version: 1.0\r\n";
    $raw .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n\r\n";
    $plain = strip_tags( $html_body );
    $raw .= "--{$boundary}\r\n";
    $raw .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $raw .= "{$plain}\r\n\r\n";
    $raw .= "--{$boundary}\r\n";
    $raw .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $raw .= "{$html_body}\r\n\r\n";
    $raw .= "--{$boundary}--";
    $encoded = rtrim( strtr( base64_encode( $raw ), '+/', '-_' ), '=' );
    $msg = new Google_Service_Gmail_Message();
    $msg->setRaw( $encoded );
    try {
        $sent = $service->users_messages->send( 'me', $msg );
        return $sent;
    } catch ( Exception $e ) {
        error_log( 'Gmail send error: ' . $e->getMessage() );
        return false;
    }
}

// ---------------------------------------------------------------------------
// ---------------------------------------------------------------------------
// WooCommerce order completed hook (and thankyou)
// ---------------------------------------------------------------------------
function gwn_send_confirmation_email( $order_id ) {
    // Avoid duplicate emails
    if ( get_post_meta( $order_id, '_gwn_email_sent', true ) ) {
        return;
    }
    error_log('Gmail Notifier: send confirmation email for order ID ' . $order_id);
    if ( ! function_exists( 'wc_get_order' ) ) {
        error_log('Gmail Notifier: WooCommerce functions not available');
        return;
    }
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        error_log('Gmail Notifier: Order object not found');
        return;
    }
    $email = $order->get_billing_email();
    if ( ! $email ) {
        error_log('Gmail Notifier: No billing email for order');
        return;
    }
    $subject = 'Confirmación de compra #' . $order->get_order_number();
    $body = '<h2>Gracias por tu compra</h2>';
    $body .= '<p>Hemos recibido tu pedido <strong>#' . $order->get_order_number() . '</strong>.</p>';
    $body .= '<p>Detalles:</p><ul>';
    foreach ( $order->get_items() as $item ) {
        $product = $item->get_product();
        $name = $product ? $product->get_name() : $item->get_name();
        $qty = $item->get_quantity();
        $body .= '<li>' . esc_html( $name ) . ' × ' . intval( $qty ) . '</li>';
    }
    $body .= '</ul>';
    $body .= '<p>Si tienes alguna duda, responde a este correo.</p>';
    $body .= '<p>¡Gracias!</p>';
    $sent = gwn_send_gmail_message( $email, $subject, $body );
    if ( $sent ) {
        error_log('Gmail Notifier: Email sent successfully to ' . $email);
        update_post_meta( $order_id, '_gwn_email_sent', true );
    } else {
        error_log('Gmail Notifier: FAILED to send email to ' . $email);
    }
}

// Send on order completed
add_action( 'woocommerce_order_status_completed', 'gwn_send_confirmation_email', 10, 1 );
// Also send on thankyou page (after checkout) for orders that may not go through completed immediately
add_action( 'woocommerce_thankyou', 'gwn_send_confirmation_email', 10, 1 );
// ---------------------------------------------------------------------------
function gwn_woocommerce_order_completed( $order_id ) {
    error_log('Gmail Notifier: order completed hook fired for order ID ' . $order_id);
    if ( ! function_exists( 'wc_get_order' ) ) {
        error_log('Gmail Notifier: WooCommerce functions not available');
        return;
    }
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        error_log('Gmail Notifier: Order object not found');
        return;
    }
    $email = $order->get_billing_email();
    if ( ! $email ) {
        error_log('Gmail Notifier: No billing email for order');
        return;
    }
    $subject = 'Confirmación de compra #' . $order->get_order_number();
    $body = '<h2>Gracias por tu compra</h2>';
    $body .= '<p>Hemos recibido tu pedido <strong>#' . $order->get_order_number() . '</strong>.</p>';
    $body .= '<p>Detalles:</p><ul>';
    foreach ( $order->get_items() as $item ) {
        $product = $item->get_product();
        $name = $product ? $product->get_name() : $item->get_name();
        $qty = $item->get_quantity();
        $body .= '<li>' . esc_html( $name ) . ' × ' . intval( $qty ) . '</li>';
    }
    $body .= '</ul>';
    $body .= '<p>Si tienes alguna duda, responde a este correo.</p>';
    $body .= '<p>¡Gracias!</p>';
    $sent = gwn_send_gmail_message( $email, $subject, $body );
    if ( $sent ) {
        error_log('Gmail Notifier: Email sent successfully to ' . $email);
    } else {
        error_log('Gmail Notifier: FAILED to send email to ' . $email);
    }
}
add_action( 'woocommerce_order_status_completed', 'gwn_woocommerce_order_completed', 10, 1 );
?>