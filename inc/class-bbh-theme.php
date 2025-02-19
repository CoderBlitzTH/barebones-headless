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

final class BBH_Theme {
	private BBH_Theme_Settings $theme_settings;

	/**
	 * Private constructor to prevent instantiation from outside of the class.
	 */
	public function __construct() {
		$this->theme_settings = BBH_Theme_Settings::get_instance();

		add_action( 'after_setup_theme', array( $this, 'setup_theme' ) );
		add_action( 'send_headers', array( $this, 'add_cors_headers' ) );
		add_action( 'send_headers', array( $this, 'add_security_headers' ) );

		/**
		 * Based on https://gist.github.com/jasonbahl/5dd6c046cd5a5d39bda9eaaf7e32a09d
		 */
		add_action( 'parse_request', array( $this, 'disable_frontend' ), 99 );
	}

	/**
	 * Sets up the theme.
	 */
	public function setup_theme(): void {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'menus' );
	}

	/**
	 * Adds CORS headers to the response.
	 */
	public function add_cors_headers(): void {
		$origin = get_http_origin();
		if ( $origin ) {
			header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $origin ) );
		}

		header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
		header( 'Access-Control-Allow-Credentials: true' );
		header( 'Access-Control-Allow-Headers: Authorization, Content-Type' );

		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'OPTIONS' === $_SERVER['REQUEST_METHOD'] ) {
			status_header( 200 );
			exit();
		}
	}

	/**
	 * Adds security headers to the HTTP response.
	 *
	 * This function sets several HTTP headers to enhance the security of the application:
	 * - X-Frame-Options: SAMEORIGIN
	 * - X-XSS-Protection: 1; mode=block
	 * - X-Content-Type-Options: nosniff
	 * - Referrer-Policy: strict-origin-when-cross-origin
	 */
	public function add_security_headers(): void {
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'X-XSS-Protection: 1; mode=block' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	}

	/**
	 * Redirects to the frontend application.
	 */
	public function disable_frontend(): void {
		/**
		 * Filters whether the current user has access to the front-end.
		 *
		 * By default, the front-end is disabled if the user doesn't
		 * have the capability to "edit_posts".
		 *
		 * Return true if you want the front-end to be disabled and
		 * the current user to be redirected to headless mode.
		 *
		 * @param bool $is_disable_frontend True if the current user doesn't have the capability to "edit_posts".
		 */
		$is_disable_frontend = (bool) apply_filters( 'bbh_is_disable_frontend', ! current_user_can( 'edit_posts' ) );

		if ( ! $is_disable_frontend ) {
			wp_safe_redirect( admin_url() );
			exit;
		}

		global $wp;

		/**
		 * If the request is not part of a CRON, REST Request, GraphQL Request or Admin request,
		 * output some basic, blank markup
		 */
		if (
			! defined( 'DOING_CRON' ) && ! defined( 'REST_REQUEST' ) && ! is_admin()
			&& ( empty( $wp->query_vars['rest_oauth1'] ) && ! defined( 'GRAPHQL_HTTP_REQUEST' ) )
		) {
			if ( strpos( home_url(), $this->theme_settings->get_frontend_url() ) === 0 ) {
				return;
			}

			wp_redirect( trailingslashit( $this->theme_settings->get_frontend_url() ) . $wp->request, 301 ); // phpcs:ignore
			exit;
		}
	}
}

new BBH_Theme();
