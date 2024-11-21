<?php
/**
 * Plugin Name: EFC
 * Description: The new EFC plugin (2024-05) will eventually replace "Custom Privacy Modifications", "Comment Recipients", "Lemon Squeezy Integration", "FC" and "Custom Analytics" plugins.
 * Version: 1.0.8
 * Requires at least: 6.5.3
 * Requires PHP: 8.0
 * Text Domain: efc
 */

/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define Version and Paths
 */
define( 'EFC_VERSION', '1.0.8' );
define( 'EFC_URL', plugin_dir_url( __FILE__ ) ); // https://example.com/wp-content/plugins/efc-plugin/
define( 'EFC_PATH', plugin_dir_path( __FILE__ ) ); // /absolute/path/to/wp-content/plugins/efc-plugin/
define( 'EFC_BASENAME', plugin_basename( __FILE__ ) ); // efc-plugin/efc-plugin.php
define( 'EFC_PLUGIN_FILE', __FILE__ ); // /absolute/path/to/wp-content/plugins/efc-plugin/efc-plugin.php

/**
 * Require Files
 */
require_once dirname( EFC_PLUGIN_FILE ) . '/functions.php';
require_once dirname( EFC_PLUGIN_FILE ) . '/filters.php';
require_once dirname( EFC_PLUGIN_FILE ) . '/actions.php';
