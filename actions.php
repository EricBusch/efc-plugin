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
 * Plugin snippet: show WooCommerce featured products first on shop & category/tag pages
 * Paste into a plugin file or your theme's functions.php
 *
 * https://chatgpt.com/share/68b8427d-937c-8007-b2ca-54b6debc9670
 */
add_action( 'pre_get_posts', function ( $query ) {

	// only modify frontend main queries
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	
	// ensure WooCommerce is active and required functions exist
	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_featured_product_ids' ) ) {
		error_log( 'EFC Plugin: WooCommerce not active or wc_get_featured_product_ids function not found' );
		return;
	}
	
	// target shop, product category and product tag archives
	if ( ! ( is_shop() || is_product_category() || is_product_tag() ) ) {
		return;
	}

	// get featured product IDs with error handling
	try {
		$featured_ids = wc_get_featured_product_ids();
	} catch ( Exception $e ) {
		error_log( 'EFC Plugin: Error getting featured product IDs - ' . $e->getMessage() );
		return;
	}

	// sanitize and dedupe
	$featured_ids = array_map( 'absint', array_unique( (array) $featured_ids ) );

	if ( empty( $featured_ids ) ) {
		return; // nothing to do
	}

	// store on query so posts_clauses can see it
	$query->set( 'efc_featured_product_ids', $featured_ids );

	// attach clauses filter with higher priority to prevent conflicts
	add_filter( 'posts_clauses', 'efc_featured_products_posts_clauses', 30, 2 );
} );

/**
 * Modifies WordPress query clauses to prioritize featured products.
 *
 * This function modifies the ORDER BY clause of WooCommerce product queries
 * to display featured products first on shop pages, product category pages,
 * and product tag pages. It only operates when featured product IDs have been
 * set on the query via the 'efc_featured_product_ids' parameter.
 *
 * @param array    $clauses Array of query clauses including 'orderby', 'where', etc.
 * @param WP_Query $query   The WordPress query object being modified.
 * @return array Modified clauses array with updated ORDER BY to prioritize featured products.
 */
function efc_featured_products_posts_clauses( $clauses, $query ) {

	// validate input parameters
	if ( ! is_array( $clauses ) || ! is_object( $query ) ) {
		error_log( 'EFC Plugin: Invalid parameters passed to efc_featured_products_posts_clauses' );
		return is_array( $clauses ) ? $clauses : [];
	}

	// only alter if our pre_get_posts set the featured IDs on this specific query
	$featured = $query->get( 'efc_featured_product_ids' );

	if ( empty( $featured ) || ! is_array( $featured ) ) {
		return $clauses;
	}

	global $wpdb;

	// validate wpdb is available
	if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
		error_log( 'EFC Plugin: WordPress database object not available' );
		return $clauses;
	}

	// ensure integers and build list with validation
	$featured = array_map( 'absint', array_filter( $featured, 'is_numeric' ) );
	$featured = array_filter( $featured ); // remove zeros
	
	if ( empty( $featured ) ) {
		return $clauses;
	}

	// limit the number of IDs to prevent extremely long queries (max 100)
	if ( count( $featured ) > 100 ) {
		$featured = array_slice( $featured, 0, 100 );
		error_log( 'EFC Plugin: Featured products list truncated to 100 items for performance' );
	}

	$ids_list = implode( ',', $featured );

	if ( empty( $ids_list ) ) {
		return $clauses;
	}

	// ensure orderby key exists in clauses
	if ( ! isset( $clauses['orderby'] ) ) {
		$clauses['orderby'] = '';
	}

	// Put featured products first. We prepend this priority to any existing ORDER BY.
	// (wp_posts.ID IN (1,2,3)) returns 1 for featured products, so DESC places them before others.
	$featured_order = "({$wpdb->posts}.ID IN ($ids_list)) DESC";

	if ( ! empty( $clauses['orderby'] ) ) {
		$clauses['orderby'] = $featured_order . ', ' . $clauses['orderby'];
	} else {
		// fallback ordering if none provided
		$clauses['orderby'] = $featured_order . ", {$wpdb->posts}.post_date DESC";
	}

	return $clauses;
}


