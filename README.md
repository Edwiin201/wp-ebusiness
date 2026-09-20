# PixelCore — tienda de accesorios para PC

Tienda WordPress y WooCommerce para accesorios, hardware, ergonomía, gaming y productividad.

## Contenido incluido

- Tema hijo personalizado en `wp-content/themes/pixelcore-store`.
- Catálogo de 22 productos, páginas y ajustes en `database/wp-computer-store.sql`.
- Imágenes del catálogo en `wp-content/uploads`.
- Carrito, checkout, cuenta, pago contra entrega y recojo coordinado.

## Instalación local con XAMPP

1. Copia el proyecto dentro de `C:\xampp\htdocs\wordpress`.
2. Crea una base de datos llamada `wp-computer-store`.
3. Importa `database/wp-computer-store.sql` desde phpMyAdmin.
4. Configura `wp-config.php` usando `wp-config-sample.php` como referencia.
5. Inicia Apache y MySQL y abre `http://localhost/wordpress/`.

El respaldo no contiene las tablas de usuarios ni sesiones. Se deben conservar o crear credenciales de administrador en cada instalación.
