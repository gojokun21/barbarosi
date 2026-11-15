/**
 * Formular newsletter. Validarea (email + acord GDPR) o face browser-ul prin `required`;
 * `submit` rulează doar când formularul e valid.
 *
 * TODO: integrare cu providerul de email (Brevo / Mailchimp / Klaviyo) printr-un endpoint REST.
 * Până atunci doar simulăm abonarea.
 */
export default () => ({
	email: '',
	agreed: false,
	status: 'idle', // idle | loading | success
	error: '',

	async submit() {
		this.error = '';
		this.status = 'loading';

		try {
			await new Promise((resolve) => setTimeout(resolve, 400));
			this.status = 'success';
		} catch {
			this.status = 'idle';
			this.error = 'A apărut o eroare. Încearcă din nou.';
		}
	},
});
