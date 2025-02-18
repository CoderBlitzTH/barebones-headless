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

/**
 * Check for the BBH_FRONTEND_URL constant.
 */
if ( ! defined( 'BBH_FRONTEND_URL' ) ) {
	define( 'BBH_FRONTEND_URL', 'https://domain.test' );
}

// Any random string. This must match the .env variable in the Next.js frontend.
if ( ! defined( 'BBH_PREVIEW_SECRET' ) ) {
	define( 'BBH_PREVIEW_SECRET', 'preview' );
}

// Any random string. This must match the .env variable in the Next.js frontend.
if ( ! defined( 'BBH_REVALIDATION_SECRET' ) ) {
	define( 'BBH_REVALIDATION_SECRET', 'revalidate' );
}

// Theme setup.
require BBH_THEME_DIR . '/inc/theme.php';

// Redirect theme requests to frontend.
require BBH_THEME_DIR . '/inc/redirect.php';

// Links class.
require BBH_THEME_DIR . '/inc/classes/class-bbh-links.php';

// Revalidation class.
require BBH_THEME_DIR . '/inc/classes/class-bbh-revalidation.php';
