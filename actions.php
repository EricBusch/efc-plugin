<?php

/**
 * Exit if accessed directly.
 */
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * Remove the sticky WooCommerce header on the product add and edit pages.
 */
add_action('admin_head', function () {

    global $pagenow;

    if ( ! is_admin()) {
        return;
    }

    if ( ! in_array($pagenow, ['post-new.php', 'post.php'])) {
        return;
    }

    echo '<style>.woocommerce-layout__header-wrapper{display:none !important;}</style>';
});

/**
 * Display Cross-Sell Products underneath main product.
 *
 * @return string
 * @since 1.0.7
 */
add_action('woocommerce_after_single_product_summary', function () {

    global $product;

    $cross_sell_ids = $product->get_cross_sell_ids();

    if (count($cross_sell_ids) < 1) {
        return;
    }

    $ids = implode(', ', $cross_sell_ids);

    $html = '<div style="clear:left">';
    $html .= '<h3>You might be interested in...</h3>';
    $html .= do_shortcode('[products limit="3" columns="3" ids="'.$ids.'"]');
    $html .= '</div>';

    echo $html;
}, 5);

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




