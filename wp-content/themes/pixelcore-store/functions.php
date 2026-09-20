<?php
declare(strict_types=1);

add_action( 'after_setup_theme', static function () {
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
} );

add_action( 'wp_enqueue_scripts', static function () {
	wp_enqueue_style( 'hello-elementor-parent', get_template_directory_uri() . '/style.css', [], wp_get_theme( 'hello-elementor' )->get( 'Version' ) );
	wp_enqueue_style( 'pixelcore-store', get_stylesheet_uri(), [ 'hello-elementor-parent' ], wp_get_theme()->get( 'Version' ) );
} );

function pixelcore_shop_url(): string {
	return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/tienda/' );
}

function pixelcore_cart_count(): int {
	return function_exists( 'WC' ) && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
}

add_action( 'wp_body_open', static function () {
	$categories = [
		'Gaming'        => 'gaming',
		'Hardware'      => 'hardware',
		'Ergonomía'     => 'ergonomia',
		'Accesorios'    => 'accesorios',
		'Productividad' => 'productividad',
	];
	?>
	<header class="px-global-header">
		<div class="px-container px-header-row">
			<a class="px-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><span class="px-brandmark">PX</span>PixelCore</a>
			<nav class="px-main-nav" aria-label="Navegación principal">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Inicio</a>
				<a href="<?php echo esc_url( pixelcore_shop_url() ); ?>">Tienda</a>
				<a href="<?php echo esc_url( home_url( '/categorias/' ) ); ?>">Categorías</a>
				<a href="<?php echo esc_url( home_url( '/guia-de-compra/' ) ); ?>">Guía de compra</a>
				<a href="<?php echo esc_url( home_url( '/nosotros/' ) ); ?>">Nosotros</a>
				<a href="<?php echo esc_url( home_url( '/soporte/' ) ); ?>">Soporte</a>
			</nav>
			<div class="px-header-actions">
				<a class="px-icon-link" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">Mi cuenta</a>
				<a class="px-cart-link" href="<?php echo esc_url( wc_get_cart_url() ); ?>">Carrito <span class="px-cart-count"><?php echo esc_html( (string) pixelcore_cart_count() ); ?></span></a>
			</div>
		</div>
		<div class="px-container px-mobile-cats">
			<?php foreach ( $categories as $label => $slug ) : ?>
				<a href="<?php echo esc_url( home_url( '/product-category/' . $slug . '/' ) ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</div>
	</header>
	<?php
}, 5 );

add_action( 'wp_footer', static function () {
	?>
	<footer class="px-site-footer">
		<div class="px-container">
			<div class="px-footer-grid">
				<div><a class="px-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><span class="px-brandmark">PX</span>PixelCore</a><p>Accesorios y componentes para mejorar tu espacio de trabajo, estudio y gaming.</p></div>
				<div><h3>Comprar</h3><a href="<?php echo esc_url( pixelcore_shop_url() ); ?>">Todos los productos</a><a href="<?php echo esc_url( home_url( '/categorias/' ) ); ?>">Categorías</a><a href="<?php echo esc_url( wc_get_cart_url() ); ?>">Carrito</a></div>
				<div><h3>Ayuda</h3><a href="<?php echo esc_url( home_url( '/guia-de-compra/' ) ); ?>">Guía de compra</a><a href="<?php echo esc_url( home_url( '/soporte/' ) ); ?>">Soporte</a><a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">Mi cuenta</a></div>
				<div><h3>PixelCore</h3><a href="<?php echo esc_url( home_url( '/nosotros/' ) ); ?>">Nosotros</a><p>Atención local y recomendaciones claras.</p></div>
			</div>
			<div class="px-footer-bottom"><span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> PixelCore.</span><span>Precios expresados en soles peruanos.</span></div>
		</div>
	</footer>
	<?php
} );

add_filter( 'woocommerce_add_to_cart_fragments', static function ( array $fragments ): array {
	$fragments['span.px-cart-count'] = '<span class="px-cart-count">' . esc_html( (string) pixelcore_cart_count() ) . '</span>';
	return $fragments;
} );

add_filter( 'loop_shop_columns', static fn() => 4 );
add_filter( 'woocommerce_output_related_products_args', static function ( array $args ): array {
	$args['posts_per_page'] = 4;
	$args['columns'] = 4;
	return $args;
} );
add_filter( 'woocommerce_product_add_to_cart_text', static fn() => 'Agregar al carrito' );
add_filter( 'woocommerce_product_single_add_to_cart_text', static fn() => 'Agregar al carrito' );

add_filter( 'gettext', static function ( string $translated, string $text, string $domain ): string {
	if ( 'woocommerce' !== $domain ) {
		return $translated;
	}
	$translations = [
		'Home'                                      => 'Inicio',
		'Default sorting'                           => 'Orden predeterminado',
		'Sort by popularity'                        => 'Ordenar por popularidad',
		'Sort by average rating'                    => 'Ordenar por valoración',
		'Sort by latest'                            => 'Ordenar por novedades',
		'Sort by price: low to high'                => 'Precio: menor a mayor',
		'Sort by price: high to low'                => 'Precio: mayor a menor',
		'Add to cart'                               => 'Agregar al carrito',
		'View cart'                                 => 'Ver carrito',
		'Remove item'                               => 'Quitar producto',
		'Thumbnail image'                           => 'Imagen',
		'Product'                                   => 'Producto',
		'Price'                                     => 'Precio',
		'Quantity'                                  => 'Cantidad',
		'Subtotal'                                  => 'Subtotal',
		'Coupon:'                                   => 'Cupón:',
		'Coupon code'                               => 'Código de cupón',
		'Apply coupon'                              => 'Aplicar cupón',
		'Update cart'                               => 'Actualizar carrito',
		'Cart totals'                               => 'Total del carrito',
		'Shipment'                                  => 'Entrega',
		'Shipping'                                  => 'Envío',
		'Shipping options will be updated during checkout.' => 'Las opciones de entrega se confirmarán al finalizar la compra.',
		'Calculate shipping'                        => 'Calcular entrega',
		'Total'                                     => 'Total',
		'Proceed to checkout'                       => 'Finalizar compra',
		'Have a coupon?'                             => '¿Tienes un cupón?',
		'Click here to enter your code'              => 'Haz clic aquí para ingresar tu código',
		'Billing details'                            => 'Datos de facturación',
		'First name'                                 => 'Nombre',
		'Last name'                                  => 'Apellidos',
		'Country / Region'                           => 'País / Región',
		'Street address'                             => 'Dirección',
		'Apartment, suite, unit, etc. (optional)'    => 'Departamento, interior, etc. (opcional)',
		'Town / City'                                => 'Ciudad',
		'State / County'                             => 'Departamento / Provincia',
		'Postcode / ZIP'                             => 'Código postal',
		'Phone (optional)'                           => 'Teléfono (opcional)',
		'Email address'                              => 'Correo electrónico',
		'Create an account?'                         => '¿Crear una cuenta?',
		'Ship to a different address?'               => '¿Enviar a otra dirección?',
		'Order notes'                                => 'Notas del pedido',
		'Your order'                                 => 'Tu pedido',
		'Payment methods'                            => 'Métodos de pago',
		'Place order'                                => 'Realizar pedido',
		'privacy policy'                             => 'política de privacidad',
		'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our' => 'Tus datos personales se usarán para procesar el pedido, mejorar tu experiencia y para los fines descritos en nuestra',
		'Related products'                          => 'Productos relacionados',
		'Description'                               => 'Descripción',
		'Reviews'                                   => 'Valoraciones',
	];
	return $translations[ $text ] ?? $translated;
}, 20, 3 );
