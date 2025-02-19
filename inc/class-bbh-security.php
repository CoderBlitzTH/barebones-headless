<?php

/**
 * Security functions.
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

final class BBH_Security {

	/**
	 * BBH_Security constructor.
	 *
	 * Initializes the security headers by hooking into the 'send_headers' action.
	 */
	public function __construct() {
		add_action( 'send_headers', array( $this, 'add_security_headers' ) );
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
}

// Instantiate the BBH_Security class to apply security headers.
new BBH_Security();
