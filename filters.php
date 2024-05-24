<?php

/**
 * Exit if accessed directly.
 */

use MailPoet\API\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @TEMP - This code is only needed during the 2024-05 updates.
 *
 * This code disables the "Free Downloads WooCommerce Pro" plugin (WP Enhanced)
 * for a specific WC Product ID.
 *
 * Basically it lets us fallback to regular WooCommerce functionality for
 * a free downloadable product so that we can test how WooCommerce handles free downloads
 * while still keeping the "Free Downloads WooCommerce Pro" activated for
 * the rest of the products on the site.
 *
 * This code can be removed once the "Free Downloads WooCommerce Pro" plugin
 * is deactivated and uninstalled.
 *
 * @param bool $is_free
 * @param WC_Product $product
 * @param int $product_id
 *
 * @return bool
 * @since 1.0.0
 */
function efc_disable_free_downloads_plugin_for_specific_products( bool $is_free, WC_Product $product, int $product_id ): bool {

	if ( efc_get_env() === 'production' ) {
		$ids_of_products_we_are_testing_woocommerce_checkout = [ 11483 ];
	} else {
		$ids_of_products_we_are_testing_woocommerce_checkout = [ 120 ];
	}

	if ( in_array( absint( $product->get_parent_id() ), $ids_of_products_we_are_testing_woocommerce_checkout, true ) ) {
		return false;
	}

	return $is_free;
}

add_filter( 'somdn_is_free', 'efc_disable_free_downloads_plugin_for_specific_products', 10, 3 );
add_filter( 'somdn_is_product_free_for_user', 'efc_disable_free_downloads_plugin_for_specific_products', 10, 3 );

/**
 * Attempts to display the "display_name" of a commenter instead of "comment_author" field.
 *
 * Basically we are adding this so that registered users don't automatically
 * have their full first and last name added to their comments... how annoying.
 *
 * This will display whatever the user has selected under the "Display name publicly as"
 * field on their profile page.
 *
 * This won't break existing comments because those were added by "guests" and since their
 * user data doesn't exist, this won't have an effect.
 *
 * This expects that the commenter is actually in our "users" database table and
 * has an account on our site.
 *
 * @param string $comment_author The comment author's username.
 * @param string $comment_id The comment ID as a numeric string.
 * @param WP_Comment $comment The comment object.
 *
 * @return string
 * @since 1.0.0
 */
function efc_force_display_name_when_displaying_comment_author( string $comment_author, string $comment_id, WP_Comment $comment ): string {

	$user = ! empty( $comment->user_id ) ? get_userdata( $comment->user_id ) : false;

	if ( $user ) {
		$comment_author = $user->display_name;
	}

	return $comment_author;
}

add_filter( 'get_comment_author', 'efc_force_display_name_when_displaying_comment_author', 10, 3 );

/**
 * Remove some fields from the Billings form on the Checkout page.
 *
 * This ONLY works on the Shortcode-based Checkout page. If it's a
 * Block-based Checkout page, this code will absolutely NOT work.
 *
 * @see https://woocommerce.com/document/using-the-new-block-based-checkout/
 *
 * @param array[] $fields
 *
 * @return array
 * @since 1.0.1
 * @throws Exception
 */
function efc_remove_some_fields_from_billing_form_on_checkout_page( array $fields ): array {

	/**
	 * There's no need to collect a phone number so remove even
	 * if the cart is valued more than $0.
	 */
	unset( $fields['billing']['billing_phone'] );

	/**
	 * There's no need to collect "order notes" as we are not delivering
	 * anything therefore there's no real reason for the user to leave
	 * a note about their order.
	 */
	unset( $fields['order']['order_comments'] );

	/**
	 * If the cart's total value is 0, then also remove the following fields.
	 */
	if ( efc_get_cart_total() <= 0 ) {
		unset(
			$fields['billing']['billing_company'],
			$fields['billing']['billing_country'],
			$fields['billing']['billing_address_1'],
			$fields['billing']['billing_address_2'],
			$fields['billing']['billing_city'],
			$fields['billing']['billing_state'],
			$fields['billing']['billing_postcode']
		);
	}

	/**
	 * Add a MailPoet opt-in checkbox just below "Username"
	 * input field. This basically does the same thing as the "Opt-in on checkout"
	 * checkbox on this page /wp-admin/admin.php?page=mailpoet-settings#/woocommerce
	 * however this adds the checkbox underneath the "Username" field
	 * and checks the box by default.
	 *
	 * This only appears when the user is not yet logged in.
	 */
	$fields['account'][ efc_mailpoet_woocommerce_checkout_optin_key() ] = efc_mailpoet_woocommerce_checkout_optin_options();

	/**
	 * Display the opt-in checkbox if user is already logged in and
	 * has a status of "unconfirmed" for their subscriber status.
	 *
	 * This only appears if:
	 *  - the MailPoet API class exists
	 *  - the user is logged in
	 *  - the user's MailPoet status is "unconfirmed".
	 *
	 * @see https://github.com/mailpoet/mailpoet/blob/trunk/doc/api_methods/GetSubscriber.md
	 */
	if ( class_exists( API::class ) && is_user_logged_in() ) {
		try {
			$current_user = wp_get_current_user();
			$user_email   = $current_user->user_email;
			$mailpoet_api = API::MP( 'v1' );
			try {
				$subscriber = $mailpoet_api->getSubscriber( $user_email );
				if ( $subscriber['status'] === 'unconfirmed' ) {
					$fields['order'][ efc_mailpoet_woocommerce_checkout_optin_key() ] = efc_mailpoet_woocommerce_checkout_optin_options();
				}
			} catch ( Exception ) {
			}
		} catch ( Exception ) {
		}
	}

	return $fields;
}

add_filter( 'woocommerce_checkout_fields', 'efc_remove_some_fields_from_billing_form_on_checkout_page' );

/**
 * Change the [Place order] button to say [Download files] when the
 * value of the cart is $0 (ie. free).
 *
 * @param string $text
 *
 * @return string
 * @since 1.0.3
 */
function efc_change_place_order_button_text_when_cart_is_free( string $text ): string {

	if ( efc_get_cart_total() <= 0 ) {
		$text = __( 'Download files', 'efc' );
	}

	return $text;
}

add_filter( 'woocommerce_order_button_text', 'efc_change_place_order_button_text_when_cart_is_free' );

