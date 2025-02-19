<?php

/**
 * Link Modifier functions.
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

final class BBH_Link_Modifier {
	/**
	 * The theme settings instance.
	 *
	 * @var BBH_Theme_Settings
	 */
	private BBH_Theme_Settings $theme_settings;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->theme_settings = BBH_Theme_Settings::get_instance();

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks(): void {
		// Modifies the REST response to include the link to the post.
		add_filter( 'rest_prepare_post', array( $this, 'modify_rest_response' ), 10, 2 );
		add_filter( 'rest_prepare_page', array( $this, 'modify_rest_response' ), 10, 2 );

		// Modifies the post link and page link.
		add_filter( 'preview_post_link', array( $this, 'modify_preview_post_link' ), 10, 2 );
		add_filter( 'post_link', array( $this, 'modify_post_link' ), 10, 2 );
		add_filter( 'page_link', array( $this, 'modify_page_link' ), 10 );
	}

	/**
	 * Modifies the REST response to include the link to the post.
	 *
	 * @param WP_REST_Response $response The REST response.
	 * @param WP_Post          $post     The post object.
	 *
	 * @return WP_REST_Response The modified REST response.
	 */
	public function modify_rest_response( WP_REST_Response $response, WP_Post $post ): WP_REST_Response {
		// Call the action hook to allow other plugins to modify the response before it's sent.
		// This action hook is called right before the response is sent to the client.
		do_action( 'bbh_before_rest_response', $response );

		// If the response has a valid post ID, add the link to the post.
		if ( ! empty( $response->data ) ) {
			if ( 'post' === $post->post_type ) {
				// Get the post slug and add the link to the post.
				$post_slug              = get_post_field( 'post_name', $post->ID );
				$response->data['link'] = $this->theme_settings->get_frontend_url() . '/' .
					$this->theme_settings->get_blog_base() . '/' . $post_slug;
			} else {
				// Get the page slug and add the link to the page.
				$response->data['link'] = str_replace(
					home_url(),
					$this->theme_settings->get_frontend_url(),
					$response->data['link']
				);
			}

			// If the response has a content property, replace the home_url() with the frontend URL.
			if ( ! empty( $response->data['content']['rendered'] ) ) {
				$response->data['content']['rendered'] = str_replace(
					home_url(),
					$this->theme_settings->get_frontend_url(),
					$response->data['content']['rendered']
				);
			}
		}

		return $response;
	}

	/**
	 * Customize the preview button in the WordPress admin.
	 *
	 * This method modifies the preview link for a post to point to a headless client setup.
	 *
	 * @param string  $link Original WordPress preview link.
	 * @param WP_Post $post Current post object.
	 * @return string Modified headless preview link.
	 */
	public function modify_preview_post_link( string $link, WP_Post $post ): string {
		// Return the original link if the frontend URL or preview secret are not defined.
		if ( ! $this->theme_settings->get_frontend_url() || ! $this->theme_settings->get_preview_secret() ) {
			return $link;
		}

		// Update the preview link to point to the front-end.
		return add_query_arg(
			array( 'secret' => $this->theme_settings->get_preview_secret() ),
			esc_url_raw( "{$this->theme_settings->get_frontend_url()}/preview/{$post->ID}" )
		);
	}

	/**
	 * Modifies the post link.
	 *
	 * @param string $url The original URL.
	 * @param WP_Post $post The post object.
	 *
	 * @return string The modified URL.
	 */
	public function modify_post_link( string $url, WP_Post $post ): string {
		// Add blog base to posts but not pages
		if ( 'post' === get_post_type( $post ) ) {
			$post_slug = get_post_field( 'post_name', $post->ID );
			return $this->theme_settings->get_frontend_url() . '/' . $this->theme_settings->get_blog_base() . '/' . $post_slug;
		}
		return str_replace( home_url(), $this->theme_settings->get_frontend_url(), $url );
	}

	/**
	 * Modifies the page link.
	 *
	 * @param string $url The original URL.
	 *
	 * @return string The modified URL.
	 */
	public function modify_page_link( string $url ): string {
		return str_replace( home_url(), $this->theme_settings->get_frontend_url(), $url );
	}
}

new BBH_Link_Modifier();
