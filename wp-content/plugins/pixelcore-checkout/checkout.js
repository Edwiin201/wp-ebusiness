/* global jQuery, wc_checkout_params */
(function ($) {
	'use strict';
	if (typeof wc_checkout_params === 'undefined') return;

	// Only the classic WooCommerce order submission, never other AJAX requests.
	const endpoint = new URL(wc_checkout_params.checkout_url, window.location.href);
	$.ajaxPrefilter(function (options, originalOptions, xhr) {
		const url = new URL(options.url, window.location.href);
		if ((options.type || 'GET').toUpperCase() !== 'POST' ||
			url.origin !== endpoint.origin || url.pathname !== endpoint.pathname ||
			url.searchParams.get('wc-ajax') !== 'checkout') return;

		const form = document.querySelector('form.checkout');
		if (!form) return;
		let notice = document.getElementById('pixelcore-checkout-status');
		if (!notice) {
			notice = document.createElement('div');
			notice.id = 'pixelcore-checkout-status';
			notice.className = 'woocommerce-info';
			notice.setAttribute('role', 'status');
			notice.setAttribute('aria-live', 'polite');
			form.parentNode.insertBefore(notice, form);
		}
		notice.hidden = true;
		const slowTimer = window.setTimeout(function () {
			notice.textContent = 'Estamos confirmando tu pedido. Espera y no vuelvas a enviarlo ni cierres esta página.';
			notice.hidden = false;
		}, 12000);

		// WooCommerce has no default timeout for this request. Let its own error
		// handler release the overlay, validate fields and remove unload guards.
		options.timeout = options.timeout > 0 ? Math.min(options.timeout, 60000) : 60000;
		const nativeError = options.error;
		options.error = function (request, status, error) {
			if (typeof nativeError === 'function') nativeError.apply(this, arguments);
			// The server may have saved the order even if its response was lost.
			// Never claim failure, payment, or success, and never retry automatically.
			notice.textContent = 'No pudimos confirmar la respuesta de la tienda. El pedido podría haberse registrado. Antes de volver a comprar, revisa tus pedidos en Mi cuenta o consulta con la tienda. No repitas el pedido sin comprobarlo. Después de revisarlo, recarga esta página para continuar.';
			notice.hidden = false;
			const button = form.querySelector('#place_order');
			if (button) button.disabled = true;
		};
		xhr.always(function () {
			window.clearTimeout(slowTimer);
			if (xhr.statusText !== 'timeout' && notice.textContent.startsWith('Estamos confirmando')) {
				notice.hidden = true;
			}
		});
	});
})(jQuery);
