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
 *
 * @since 1.0.13
 */
add_action( 'pre_get_posts', function ( WP_Query $query ) {

	// Only apply to main queries (not admin, not sub-queries)
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

//	 Only apply to product queries
//	if ( ! isset( $query->query_vars['post_type'] ) || $query->query_vars['post_type'] !== 'product' ) {
//		return;
//	}

	// Only apply to shop page and product category pages
	if ( ! ( is_shop() || is_product_category() ) ) {
		return;
	}

	// Attach our custom posts_clauses filter
	add_filter( 'posts_clauses', 'efc_featured_products_posts_clauses', 10, 2 );

}, 20 );

/**
 * Disables the setting of Cookies which function to pre-populate the comment form's
 * input fields with a commenter's previously supplied name & email address.
 *
 * @since 1.0.16
 */
function efc_disable_comment_cookies() {
	remove_action( 'set_comment_cookies', 'wp_set_comment_cookies' );
}

add_action( 'init', 'efc_disable_comment_cookies' );

/**
 * This modifies the "Recent Product Reviews" widget supplied by WooCommerce.
 *
 * By default, the widget just display the product image, product name, start rating and the author name.
 *
 * This appends the actual review to the bottom of each reviewed item.
 *
 * @since 1.0.16
 */
add_action( 'woocommerce_widget_product_review_item_end', function ( $args ) {
	printf( '<p class="eslfc_widget_review_content">%s</p>', esc_html( $args['comment']->comment_content ) );
} );

/**
 * Filter the Rank Math product entity to add a default size if one is missing.
 *
 * This ensures that WooCommerce products have a 'size' property in their
 * rich snippet data, which is sometimes required by external platforms.
 *
 * @see https://chatgpt.com/share/6945746c-9d48-8007-af73-02de4263b5ce
 * @see https://chatgpt.com/share/69457f35-53b0-8007-b366-471a9d766328
 *
 * @param array $entity The product entity data.
 *
 * @return array Modified product entity data.
 * @since 1.0.18
 */
add_action( 'rank_math/json_ld', function ( $data ) {

	$attr  = 'size';
	$value = 'One Size';

	if ( empty( $data['richSnippet'] ) ) {
		return $data;
	}

	if ( empty( $data['richSnippet']['hasVariant'] ) ) {
		return $data;
	}

	foreach ( $data['richSnippet']['hasVariant'] as $key => $variant ) {

		if ( $variant['@type'] !== 'Product' ) {
			continue;
		}

		if ( empty( $variant[ $attr ] ) ) {
			$data['richSnippet']['hasVariant'][ $key ][ $attr ] = $value;
		}
	}

	return $data;

}, 999 );


/**
 * Remove the default WooCommerce product title from the shop loop.
 *
 * @since 1.0.20
 */
function efc_remove_default_product_title_hook(): void {
	remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title' );
}

add_action( 'init', 'efc_remove_default_product_title_hook' );

/**
 * Reinsert the default WooCommerce product title with conditional heading tags.
 *
 * This function echoes the product title wrapped in either h4 or h2 tags
 * based on whether the current loop is within a shortcode.
 *
 * Products displayed in the sidebar widgets are rendered via shortcodes that's why
 * we are targetting shortcodes.
 *
 * @since 1.0.20
 */
function efc_reinsert_default_product_title_hook(): void {

	$class = apply_filters( 'woocommerce_product_loop_title_classes', 'woocommerce-loop-product__title' );

	if ( wc_get_loop_prop( 'is_shortcode' ) ) {
		echo '<h4 class="' . esc_attr( $class ) . '">' . get_the_title() . '</h4>';
	} else {
		echo '<h2 class="' . esc_attr( $class ) . '">' . get_the_title() . '</h2>';
	}
}

add_action( 'woocommerce_shop_loop_item_title', 'efc_reinsert_default_product_title_hook' );
