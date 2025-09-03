<?php

/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove the sticky WooCommerce header on the product add and edit pages.
 */
add_action( 'admin_head', function () {

	global $pagenow;

	if ( ! is_admin() ) {
		return;
	}

	if ( ! in_array( $pagenow, [ 'post-new.php', 'post.php' ] ) ) {
		return;
	}

	echo '<style>.woocommerce-layout__header-wrapper{display:none !important;}</style>';
} );

/**
 * Display Cross-Sell Products underneath main product.
 *
 * @return string
 * @since 1.0.7
 */
add_action( 'woocommerce_after_single_product_summary', function () {

	global $product;

	$cross_sell_ids = $product->get_cross_sell_ids();

	if ( count( $cross_sell_ids ) < 1 ) {
		return;
	}

	$ids = implode( ', ', $cross_sell_ids );

	$html = '<div style="clear:left">';
	$html .= '<h3>You might be interested in...</h3>';
	$html .= do_shortcode( '[products limit="3" columns="3" ids="' . $ids . '"]' );
	$html .= '</div>';

	echo $html;
}, 5 );

/**
 * The following 4 "add_action" calls (which have been commented out) all
 * work to create a new layout on the Single Product Page where
 *      - The Product Title is on a row above the image
 *      - The Star ratings is below the product title
 *      - The tall Ad appears as a 3rd column floated to the right of the Variation dropdown menus.
 */

/*
add_action( 'init', function () {
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
} );

add_action( 'woocommerce_before_single_product_summary', 'woocommerce_template_single_title', 1 );
add_action( 'woocommerce_before_single_product_summary', 'woocommerce_template_single_rating', 3 );
add_action( 'woocommerce_before_single_product_summary', function () {
	echo '<div style="height:600px;width:289px;float:right;margin-left:20px;"><!-- Ad Goes Here --></div>';
}, 5 );
*/

/**
 * Hook to modify product queries to show featured products first.
 *
 * This action hooks into 'pre_get_posts' to attach our custom posts_clauses filter
 * for product queries on shop and category pages.
 *
 * @param WP_Query $query The WP_Query instance.
 * @since 1.0.13
 */
add_action( 'pre_get_posts', function ( WP_Query $query ) {
	
	// Only apply to main queries (not admin, not sub-queries)
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	// Only apply to product queries
	if ( ! isset( $query->query_vars['post_type'] ) || $query->query_vars['post_type'] !== 'product' ) {
		return;
	}

	// Check if this is a shop page or product category page using query variables
	// instead of conditional functions which may not work reliably during pre_get_posts
	$is_shop_page = false;
	$is_category_page = false;
	
	// Check for shop page: main product query without taxonomy restrictions
	if ( empty( $query->query_vars['taxonomy'] ) && empty( $query->query_vars['tax_query'] ) ) {
		$is_shop_page = true;
	}
	
	// Check for category page: has taxonomy query for product_cat
	if ( ! empty( $query->query_vars['taxonomy'] ) && $query->query_vars['taxonomy'] === 'product_cat' ) {
		$is_category_page = true;
	} elseif ( ! empty( $query->query_vars['tax_query'] ) ) {
		// Check tax_query array for product_cat taxonomy
		foreach ( $query->query_vars['tax_query'] as $tax_query_item ) {
			if ( is_array( $tax_query_item ) && isset( $tax_query_item['taxonomy'] ) && $tax_query_item['taxonomy'] === 'product_cat' ) {
				$is_category_page = true;
				break;
			}
		}
	}
	
	// Only apply to shop page and product category pages
	if ( ! ( $is_shop_page || $is_category_page ) ) {
		return;
	}

	// Attach our custom posts_clauses filter
	add_filter( 'posts_clauses', 'efc_featured_products_posts_clauses', 10, 2 );
	
}, 20 );


