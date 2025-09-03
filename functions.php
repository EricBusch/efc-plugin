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

	if ( is_null( WC()->cart ) ) {
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

/**
 * Checks if we should force user to check out page for this product.
 *
 * @param int $product_id The WooCommerce Product ID.
 *
 * @return bool
 */
function efc_force_checkout( int $product_id ): bool {
	$meta_key = 'eslfc_force_checkout';

	return boolval( get_post_meta( $product_id, $meta_key, true ) );
}

/**
 * Modify the SQL query clauses to prioritize featured products while maintaining existing sort order.
 *
 * This function is used as a callback for the 'posts_clauses' filter to modify
 * WooCommerce product queries so that featured products appear first, followed by
 * regular products, while both groups maintain their original sorting order.
 *
 * @param array $clauses The query clauses array containing 'fields', 'join', 'where', 'groupby', 'orderby', 'limits'.
 * @param WP_Query $query The WP_Query instance.
 *
 * @return array Modified query clauses.
 * @since 1.0.13
 */
function efc_featured_products_posts_clauses( array $clauses, WP_Query $query ): array {
	global $wpdb;

	// Only modify queries for product post type
	if ( ! isset( $query->query_vars['post_type'] ) || $query->query_vars['post_type'] !== 'product' ) {
		return $clauses;
	}

	// Only apply to main queries (not admin, not sub-queries)
	if ( is_admin() || ! $query->is_main_query() ) {
		return $clauses;
	}

	// Only apply to shop page and product category pages
	if ( ! ( is_shop() || is_product_category() ) ) {
		return $clauses;
	}

	// Get featured product IDs
	$featured_ids = wc_get_featured_product_ids();
	
	if ( empty( $featured_ids ) ) {
		return $clauses;
	}

	// Convert featured IDs to comma-separated string for SQL
	$featured_ids_str = implode( ',', array_map( 'intval', $featured_ids ) );

	// Add a custom field to the SELECT clause to mark featured products
	$clauses['fields'] .= ", CASE WHEN {$wpdb->posts}.ID IN ({$featured_ids_str}) THEN 0 ELSE 1 END AS is_not_featured";

	// Modify the ORDER BY clause to sort by featured status first, then by existing order
	$existing_orderby = $clauses['orderby'];
	
	// If there's no existing orderby, use the default (date DESC)
	if ( empty( $existing_orderby ) ) {
		$existing_orderby = "{$wpdb->posts}.post_date DESC";
	}

	// Prepend the featured products ordering
	$clauses['orderby'] = "is_not_featured ASC, " . $existing_orderby;

	return $clauses;
}

