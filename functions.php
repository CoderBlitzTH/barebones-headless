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

// Optional. JWT auth refresh token.
if ( ! defined( 'GRAPHQL_JWT_AUTH_SECRET_KEY' ) ) {
	define( 'GRAPHQL_JWT_AUTH_SECRET_KEY', '' ); // phpcs:ignore
}

// Define constants.
define( 'BBH_THEME_DIR', trailingslashit( get_stylesheet_directory() ) );
define( 'BBH_THEME_URL', trailingslashit( get_stylesheet_directory_uri() ) );
define( 'BBH_THEME_VERSION', '1.0.0' );

require_once BBH_THEME_DIR . 'inc/class-bbh-theme-settings.php';
require_once BBH_THEME_DIR . 'inc/class-bbh-theme.php';
require_once BBH_THEME_DIR . 'inc/class-bbh-revalidation.php';
require_once BBH_THEME_DIR . 'inc/class-bbh-link-modifier.php';
