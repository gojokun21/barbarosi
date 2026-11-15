/**
 * Coșul (pagina „Coș”) — completează cart.js din WooCommerce, nu îl înlocuiește.
 *
 * - butoanele − / + din jurul cantității (woocommerce/global/quantity-input.php)
 * - actualizare automată: la schimbarea cantității apăsăm butonul ascuns „Actualizează coșul”,
 *   iar WooCommerce face restul prin AJAX (reîncarcă `.woocommerce-cart-form` și `.cart_totals`)
 * - după actualizare: numărul de produse din header / titlu și focusul pe butonul apăsat
 *
 * Event-urile WooCommerce (`updated_wc_div`) sunt jQuery — le ascultăm cu jQuery, dacă e încărcat.
 */
const UPDATE_DELAY = 600; // ms — câteva click-uri rapide pe +/- = o singură cerere
const FORM = '.woocommerce-cart-form';

let updateTimer = null;
let lastStep = null; // { name, step } — ca să readucem focusul după ce WooCommerce înlocuiește formularul

function step(button) {
	const input = button.closest('.quantity')?.querySelector('input.qty');
	if (!input || input.disabled || input.readOnly) return;

	const delta = Number(input.step) || 1;
	const min = input.min === '' ? 0 : Number(input.min);
	const max = input.max === '' ? Infinity : Number(input.max);
	const current = Number(input.value) || 0;
	const next = Math.min(max, Math.max(min, current + delta * Number(button.dataset.qtyStep)));

	if (next === current) return;

	input.value = next;
	lastStep = { name: input.name, step: button.dataset.qtyStep };
	// `change` nativ: îl prind și handler-ele jQuery ale WooCommerce (activează butonul de update).
	input.dispatchEvent(new Event('change', { bubbles: true }));
}

function scheduleUpdate(form) {
	clearTimeout(updateTimer);
	updateTimer = setTimeout(() => {
		form.querySelector('[name="update_cart"]')?.click();
	}, UPDATE_DELAY);
}

function syncCount() {
	const form = document.querySelector(FORM);
	const count = form ? Number(form.dataset.count) || 0 : 0;

	document.querySelectorAll('[data-cart-badge]').forEach((badge) => {
		badge.hidden = count === 0;
		const number = badge.querySelector('[data-cart-badge-count]');
		if (number) number.textContent = count;
	});

	if (form) {
		document.querySelectorAll('[data-cart-count-label]').forEach((label) => {
			label.textContent = form.dataset.countLabel;
		});
	}
}

function restoreFocus() {
	if (!lastStep) return;

	const input = document.querySelector(`${FORM} input[name="${CSS.escape(lastStep.name)}"]`);
	input?.closest('.quantity')?.querySelector(`[data-qty-step="${lastStep.step}"]`)?.focus();
	lastStep = null;
}

export default function initCart() {
	document.addEventListener('click', (event) => {
		const button = event.target.closest('[data-qty-step]');
		if (button) step(button);
	});

	document.addEventListener('change', (event) => {
		const input = event.target;
		if (!input.matches?.(`${FORM} input.qty`)) return;

		scheduleUpdate(input.form);
	});

	window.jQuery?.(document.body).on('updated_wc_div', () => {
		syncCount();
		restoreFocus();
	});
}
