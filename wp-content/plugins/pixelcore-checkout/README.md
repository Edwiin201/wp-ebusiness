# PixelCore Checkout

Protección para el checkout clásico de WooCommerce (shortcode).

- A los 12 segundos muestra un aviso sin interrumpir la confirmación.
- Limita a 60 segundos la espera de la petición de crear pedido.
- Conserva los manejadores nativos de validación y de errores de WooCommerce.
- Ante error de transporte no reenvía el pedido y deshabilita el botón hasta recargar. El servidor podría haber creado el pedido aunque no llegase su respuesta.
- No cambia estados de pedidos, cobros, stock, correos ni medición de Analytics.

## Instalación

Instalar esta carpeta como plugin de WordPress y activar **PixelCore Checkout**. Copiar el repositorio al servidor o hacer `git pull` no activa automáticamente un plugin nuevo. No editar WooCommerce.

## Comprobación

Ejecutar `node tests/checkout.test.cjs` desde esta carpeta y `php -l pixelcore-checkout.php`.
Comprobar en un entorno de desarrollo una respuesta lenta, un timeout, una validación rechazada y una compra correcta. No usar datos de clientes ni generar ventas reales para diagnosticar.

## Diagnóstico del servidor publicado

El 23 de noviembre de 2026 los pedidos 155 y 156 figuraban guardados y en Processing (contra entrega). El registro de correo notificó `Could not instantiate mail function`. La petición de confirmación también pudo terminar correctamente durante la revisión; no se pudo reproducir el bloqueo original de forma consistente.

Este cambio previene la espera indefinida del navegador. No demuestra ni corrige por sí solo un fallo de PHP, red, proxy o transporte de correo. Configurar un servicio de correo autorizado en el servidor y revisar sus registros si persiste la lentitud. No habilitar Gmail ni enviar mensajes a clientes como parte de esta corrección.

El pedido 157 se creó accidentalmente durante la revisión, fue cancelado y se repuso el stock; no debe contarse como venta real. Una cancelación en WooCommerce no elimina un evento purchase ya enviado a GA4.
