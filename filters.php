<?php

/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
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



