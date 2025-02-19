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
		add_action( 'after_setup_theme', array( $this, 'setup_theme' ) );
		add_action( 'send_headers', array( $this, 'add_cors_headers' ) );
		add_action( 'template_redirect', array( $this, 'redirect_to_frontend' ) );
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
	 * Redirects to the frontend application.
	 */
	public function redirect_to_frontend(): void {
		if ( is_admin() || wp_doing_ajax() || defined( 'REST_REQUEST' ) ) {
			return;
		}

		if ( strpos( home_url(), $this->theme_settings->get_frontend_url() ) === 0 ) {
			return;
		}

		$request_uri = $_SERVER['REQUEST_URI'];
		wp_safe_redirect( $this->theme_settings->get_frontend_url() . $request_uri, 301 );
		exit;
	}
}

new BBH_Theme();
