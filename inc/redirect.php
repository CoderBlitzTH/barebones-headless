<?php

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.1 404 Not Found' );
	exit;
}

if ( ! function_exists( 'bbh_redirect_user_based_on_role' ) ) {
	/**
	 * Redirect users based on their role.
	 */
	function bbh_redirect_user_based_on_role(): void {
		// Skip redirection for admin panel, login page, and REST API requests
		if ( is_admin() || defined( 'REST_REQUEST' ) || strpos( $_SERVER['REQUEST_URI'], 'wp-login.php' ) !== false ) {
			return;
		}

		// Redirect admins and editors to wp-admin, others to the frontend
		if ( user_can( get_current_user_id(), 'edit_posts' ) ) {
			wp_safe_redirect( admin_url() );
		} else {
			wp_safe_redirect( BBH_FRONTEND_URL );
		}

		exit;
	}

	add_action( 'template_redirect', 'bbh_redirect_user_based_on_role' );
}
