<?php
/**
 * Datele clientului: „Contact” (email, telefon) și adresa de facturare.
 *
 * Suprascrie woocommerce/templates/checkout/form-billing.php. Câmpurile de contact sunt scoase din
 * `.woocommerce-billing-fields__field-wrapper` intenționat: address-i18n.js reordonează rândurile din
 * wrapper după țară, iar țara / județul trebuie să rămână în `.woocommerce-billing-fields` (country-select.js).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 * @global WC_Checkout $checkout
 */

defined( 'ABSPATH' ) || exit;

$fields         = $checkout->get_checkout_fields( 'billing' );
$contact_fields = [];

// În ordinea din Checkout::CONTACT_FIELDS (emailul primul), nu după prioritatea WooCommerce.
foreach ( Checkout::CONTACT_FIELDS as $contact_key ) {
	if ( isset( $fields[ $contact_key ] ) ) {
		$contact_fields[ $contact_key ] = $fields[ $contact_key ];
	}
}

$address_fields = array_diff_key( $fields, $contact_fields );
?>
<div class="woocommerce-billing-fields">
	<?php if ( $contact_fields ) : ?>
		<section class="checkout-section" aria-labelledby="checkout-contact-title">
			<h2 class="checkout-section__title" id="checkout-contact-title"><?php esc_html_e( 'Contact', 'barbarosi' ); ?></h2>

			<div class="checkout-fields checkout-fields--contact">
				<?php
				foreach ( $contact_fields as $key => $field ) {
					woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
				}
				?>
			</div>
		</section>
	<?php endif; ?>

	<section class="checkout-section" aria-labelledby="checkout-billing-title">
		<h2 class="checkout-section__title" id="checkout-billing-title">
			<?php
			if ( wc_ship_to_billing_address_only() && WC()->cart->needs_shipping() ) {
				esc_html_e( 'Adresa de livrare și facturare', 'barbarosi' );
			} else {
				esc_html_e( 'Date de facturare', 'barbarosi' );
			}
			?>
		</h2>

		<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

		<div class="woocommerce-billing-fields__field-wrapper checkout-fields">
			<?php
			foreach ( $address_fields as $key => $field ) {
				woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
			}
			?>
		</div>

		<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
	</section>
</div>

<?php if ( ! is_user_logged_in() && $checkout->is_registration_enabled() ) : ?>
	<div class="woocommerce-account-fields checkout-account">
		<?php if ( ! $checkout->is_registration_required() ) : ?>

			<p class="form-row form-row-wide create-account">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" <?php checked( ( true === $checkout->get_value( 'createaccount' ) || ( true === apply_filters( 'woocommerce_create_account_default_checked', false ) ) ), true ); ?> type="checkbox" name="createaccount" value="1" /> <span><?php esc_html_e( 'Creează-mi un cont pentru comenzile viitoare', 'barbarosi' ); ?></span>
				</label>
			</p>

		<?php endif; ?>

		<?php do_action( 'woocommerce_before_checkout_registration_form', $checkout ); ?>

		<?php if ( $checkout->get_checkout_fields( 'account' ) ) : ?>

			<div class="create-account checkout-fields">
				<?php foreach ( $checkout->get_checkout_fields( 'account' ) as $key => $field ) : ?>
					<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
				<?php endforeach; ?>
			</div>

		<?php endif; ?>

		<?php do_action( 'woocommerce_after_checkout_registration_form', $checkout ); ?>
	</div>
<?php endif; ?>
