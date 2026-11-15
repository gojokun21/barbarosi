/**
 * Formularul „Adaugă în coș” din pagina de produs.
 *
 * Formularul e cel standard WooCommerce și funcționează și fără JS (validare nativă cu `required`).
 * Cu JS: afișăm mărimea aleasă lângă etichetă și înlocuim bula nativă cu un mesaj propriu.
 *
 * <form x-data="productForm" @submit="submit"> ... <input type="radio" name="variation_id" data-label="M" @change="select($event.target)">
 */
const SIZE_INPUT = 'input[name="variation_id"]';

export default () => ({
	size: '',
	error: false,
	busy: false,

	init() {
		this.$el.noValidate = true;
		this.select(this.$el.querySelector(`${SIZE_INPUT}:checked`));
	},

	select(input) {
		if (!input) return;

		this.size = input.dataset.label;
		this.error = false;
	},

	submit(event) {
		const needsSize = this.$el.querySelector(SIZE_INPUT) !== null;

		if (needsSize && !this.$el.querySelector(`${SIZE_INPUT}:checked`)) {
			event.preventDefault();
			this.error = true;
			this.$el.querySelector(`${SIZE_INPUT}:not(:disabled)`)?.focus();
			return;
		}

		// Previne dublul click; butonul nu are `name`, deci dezactivarea nu schimbă datele trimise.
		this.busy = true;
	},
});
