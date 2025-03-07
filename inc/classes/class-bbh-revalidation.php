<?php

/**
 * Revalidation.
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

final class BBH_Revalidation {
	/**
	 * The theme settings instance.
	 *
	 * @var BBH_Theme_Settings
	 */
	private BBH_Theme_Settings $theme_settings;

	/**
	 * The frontend revalidate URL.
	 *
	 * @var string
	 */
	private string $frontend_revalidate_url;

	public function __construct() {
		$this->theme_settings = BBH_Theme_Settings::get_instance();

		// URL สำหรับ revalidate (สามารถกำหนดผ่าน filter)
		$this->frontend_revalidate_url = (string) apply_filters(
			'bbh_revalidate_url',
			$this->theme_settings->get_frontend_url() . '/api/revalidate'
		);

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks(): void {
		// Hook เมื่อมีการอัพเดท content
		add_action( 'transition_post_status', array( $this, 'handle_transition' ), 10, 3 );

		// REST API endpoints สำหรับ manual revalidation
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register REST routes.
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			'bbh/v1',
			'/revalidate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_manual_revalidation' ),
				'permission_callback' => array( $this, 'verify_revalidate_request' ),
			)
		);
	}

	/**
	 * Handle manual revalidation.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return array|WP_Error The response.
	 */
	public function handle_manual_revalidation( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		$slug   = isset( $params['slug'] ) ? sanitize_text_field( $params['slug'] ) : null;

		if ( empty( $slug ) || ! preg_match( '/^\/[a-z0-9\-\/]+$/i', $slug ) ) {
			return new WP_Error( 'invalid_slug', 'Invalid or empty slug', array( 'status' => 400 ) );
		}

		$this->trigger_revalidation( $slug );

		return array(
			'success' => true,
			'message' => 'Revalidation triggered for slug: ' . esc_html( $slug ),
		);
	}

	/**
	 * Verify revalidate request.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return bool The result.
	 */
	public function verify_revalidate_request( WP_REST_Request $request ): bool {
		$auth_header = $request->get_header( 'X-Revalidate-Token' );
		return ! empty( $auth_header ) && $auth_header === $this->theme_settings->get_revalidate_token();
	}

	/**
	 * Handle transition.
	 *
	 * @param string   $new_status The new status.
	 * @param string   $old_status The old status.
	 * @param WP_Post  $post       The post.
	 */
	public function handle_transition( string $new_status, string $old_status, WP_Post $post ): void {
		// Do not run on autosave or cron.
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			return;
		}

		// Ignore drafts and inherited posts.
		if ( ( 'draft' === $new_status && 'draft' === $old_status ) || 'inherit' === $new_status ) {
			return;
		}

		// Determine the slug based on post type.
		$post_type = $post->post_type;
		$post_name = ! empty( $post->post_name ) ? $post->post_name : 'undefined';

		/**
		 * Configure the $slug based on your post types and front-end routing.
		 */
		switch ( $post_type ) {
			case 'post':
				$slug = "/{$this->theme_settings->get_blog_base()}/{$post_name}";
				break;
			default:
				$slug = $post_name;
				break;
		}

		$this->trigger_revalidation( $slug );
	}

	/**
	 * Trigger revalidation.
	 *
	 * @param string $slug The slug.
	 */
	private function trigger_revalidation( string $slug ): void {

		// Check necessary constants and slug.
		if ( ! $this->theme_settings->get_frontend_url() || ! $this->theme_settings->get_revalidate_token() || ! $slug ) {
			return;
		}

		$response = wp_remote_post(
			esc_url_raw( $this->frontend_revalidate_url ),
			array(
				'headers'  => array(
					'Content-Type'       => 'application/json',
					'X-Revalidate-Token' => esc_attr( $this->theme_settings->get_revalidate_token() ),
				),
				'body'     => wp_json_encode( array( 'slug' => $slug ) ),
				'timeout'  => 5,
				'blocking' => false,
			)
		);

		// Handle response errors.
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			error_log( 'Revalidation error: ' . wp_remote_retrieve_response_message( $response ) ); // phpcs:ignore
		}

		// Trigger Hook หลังจาก Revalidation เสร็จ
		// This hook is useful when you need to trigger something after revalidation is done.
		// For example, you can use it to send a notification to your team or to log the revalidation event.
		do_action( 'bbh_after_revalidation', $slug, $response );
	}
}
