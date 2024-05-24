<?php

/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simpler version of wp_get_environment_type().
 *
 * @return string
 * @since 1.0.0
 */
function efc_get_env(): string {
	return wp_get_environment_type();
}

/**
 * Gets cart total. This is the total of items in the cart,
 * but after discounts. Subtotal is before discounts.
 *
 * @return float
 * @since 1.0.3
 */
function efc_get_cart_total(): float {

	if ( ! function_exists( 'WC' ) ) {
		return 0;
	}

	return WC()->cart->get_cart_contents_total();
}

/**
 * This is the name attribute for the <input type="checkbox"> element when
 * we are adding our own MailPoet WooCommerce Checkout Opt-in checkbox.
 *
 * @return string
 * @since 1.0.3
 */
function efc_mailpoet_woocommerce_checkout_optin_key(): string {
	return 'mailpoet_woocommerce_checkout_optin';
}

/**
 * These are the options for the MailPoet WooCommerce Checkout Opt-in checkbox.
 *
 * @return array
 * @since 1.0.3
 */
function efc_mailpoet_woocommerce_checkout_optin_options(): array {
	return [
		'label'         => __( 'I would like to receive exclusive emails with discounts and product information.', 'efc' ),
		'type'          => 'checkbox',
		'checked_value' => '1',
		'default'       => '1',
	];
}


