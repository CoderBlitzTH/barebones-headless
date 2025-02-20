<?php

/**
 * Theme functionality.
 *
 * @author ColderBlitz
 * @package barebones-headless
 * @since 1.0.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.1 404 Not Found' );
	exit;
}

// Define constants.
define( 'BBH_THEME_DIR', trailingslashit( get_stylesheet_directory() ) );
define( 'BBH_THEME_URL', trailingslashit( get_stylesheet_directory_uri() ) );
define( 'BBH_THEME_VERSION', '1.0.0' );

if ( ! class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory') ) {
	require_once BBH_THEME_DIR . 'update-checker/plugin-update-checker.php';
	$update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/CoderBlitzTH/barebones-headless/',
		__FILE__,
		'barebones-headless'
	);
}

require_once BBH_THEME_DIR . 'inc/class-bbh-theme-settings.php';
require_once BBH_THEME_DIR . 'inc/class-bbh-theme.php';
require_once BBH_THEME_DIR . 'inc/class-bbh-revalidation.php';
require_once BBH_THEME_DIR . 'inc/class-bbh-link-modifier.php';
