# Gmail WooCommerce Notifier

Plugin para WordPress y WooCommerce que envía automáticamente correos electrónicos de confirmación de compra a los clientes a través de la **Gmail API (OAuth 2.0)** cuando se completa un pedido.

## Características

- **Envío seguro vía Gmail API**: Utiliza autenticación OAuth 2.0 de Google con flujo offline y refresco automático de tokens.
- **Notificación automática**: Envía un correo al cliente tras completar la compra (enganchado a `woocommerce_order_status_completed` y `woocommerce_thankyou`).
- **Prevención de duplicados**: Registra el meta-dato `_gwn_email_sent` en cada pedido para asegurar que solo se envíe una única confirmación por compra.
- **Soporte de caracteres especiales (UTF-8)**: Asunto codificado en base64 según estándar RFC 2047 (`=?UTF-8?B?...?=`) para mostrar tildes y caracteres especiales correctamente (ej. *Confirmación de compra*).
- **Panel de administración**: Página de configuración en **WooCommerce → Gmail Notifier** con verificación del estado de conexión y herramienta para enviar correos de prueba.

## Instalación y Configuración

1. **Dependencias PHP**:
   Ejecutar en la carpeta del plugin:
   ```bash
   composer install
   ```

2. **Credenciales de Google Cloud (OAuth 2.0)**:
   - Crear un proyecto en Google Cloud Console.
   - Habilitar la **Gmail API**.
   - Configurar la Pantalla de Consentimiento OAuth como **Externa** y añadir los correos de prueba como **Test users**.
   - Crear credenciales de tipo **ID de cliente de OAuth 2.0** (Aplicación web).
   - URI de redireccionamiento autorizada:
     `http://localhost/wordpress/wp-admin/admin-ajax.php?action=gmail_oauth_callback`
   - Descargar el JSON y guardarlo en:
     `wp-content/private/gmail-creds.json`

3. **Autorización**:
   - En el panel de WordPress, acceder a **WooCommerce → Gmail Notifier**.
   - Hacer clic en el enlace **"Abrir Google OAuth"** para autorizar el acceso con la cuenta de Gmail remitente.
   - Probar el envío utilizando el formulario de prueba en esa misma página.
