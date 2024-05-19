<?php

/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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




