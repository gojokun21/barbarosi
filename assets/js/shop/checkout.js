/**
 * Checkout — completează checkout.js din WooCommerce.
 *
 * Totalul din butonul „Vezi sumarul comenzii” (mobil) stă în afara fragmentelor reîmprospătate prin AJAX,
 * așa că îl copiem din sumar după fiecare `updated_checkout` (event jQuery).
 */
function syncSummaryTotal() {
	const total = document.querySelector('.woocommerce-checkout-review-order-table [data-order-total]');
	if (!total) return;

	document.querySelectorAll('[data-summary-total]').forEach((target) => {
		target.textContent = total.textContent.trim();
	});
}

export default function initCheckout() {
	window.jQuery?.(document.body).on('updated_checkout', syncSummaryTotal);
}
