<?php

/**
 * Theme functionality.
 *
 * @author ColderBlitz
 * @package barebones-headless
 * @since 1.1.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.1 404 Not Found' );
	exit;
}

// Define constants.
define( 'BBH_THEME_DIR', trailingslashit( get_stylesheet_directory() ) );
define( 'BBH_THEME_URL', trailingslashit( get_stylesheet_directory_uri() ) );
define( 'BBH_THEME_VERSION', '1.1.0' );

// Include library.
require_once BBH_THEME_DIR . 'lib/wp-dismiss-notice/wp-dismiss-notice.php';
require_once BBH_THEME_DIR . 'lib/wp-dependency-installer/wp-dependency-installer.php';
require_once BBH_THEME_DIR . 'lib/wp-dependency-installer/wp-dependency-installer-skin.php';

// Include classes.
require_once BBH_THEME_DIR . 'inc/classes/class-bbh-theme-settings.php';
require_once BBH_THEME_DIR . 'inc/classes/class-bbh-theme.php';
require_once BBH_THEME_DIR . 'inc/classes/class-bbh-revalidation.php';
require_once BBH_THEME_DIR . 'inc/classes/class-bbh-link-modifier.php';

// WPGraphQL
require_once BBH_THEME_DIR . 'inc/wpgraphql-functions.php';

if ( ! class_exists( 'YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
	require_once BBH_THEME_DIR . 'lib/update-checker/plugin-update-checker.php';
	$bbh_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/CoderBlitzTH/barebones-headless/',
		__FILE__,
		'barebones-headless'
	);
}

if ( ! function_exists( 'bbh_get_wpdi' ) ) {
	/**
	 * Get the WP Dependency Installer instance.
	 *
	 * @since 1.1.0
	 * @return WP_Dependency_Installer
	 */
	function bbh_get_wpdi(): WP_Dependency_Installer {
		return WP_Dependency_Installer::instance( BBH_THEME_DIR );
	}

	// Run the WP Dependency Installer.
	bbh_get_wpdi()->run();
}

// Set the vendor directory to `/lib`.
add_filter( 'dismiss_notice_vendor_dir', static fn() => '/lib' );
