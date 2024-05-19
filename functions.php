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
 */
function efc_get_env(): string {
	return wp_get_environment_type();
}


