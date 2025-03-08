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
		// Modifies the post link and page link.
		add_filter( 'preview_post_link', array( $this, 'modify_preview_post_link' ), 10, 2 );
		add_filter( 'post_link', array( $this, 'modify_post_link' ), 10, 2 );
		add_filter( 'page_link', array( $this, 'modify_page_link' ) );

		// Modifies the category link and tag link.
		add_filter( 'category_link', array( $this, 'modify_category_link' ) );
		add_filter( 'tag_link', array( $this, 'modify_tag_link' ) );

		// Modifies the author link.
		add_filter( 'author_link', array( $this, 'modify_author_link' ) );
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
		$url    = $this->theme_settings->get_frontend_url();
		$secret = $this->theme_settings->get_preview_secret();

		// Return the original link if the frontend URL or preview secret are not defined.
		if ( ! $url || ! $secret ) {
			return $link;
		}

		$post_type = $post->post_type;
		if ( 'post' === $post->post_type ) {
			$post_type = $this->theme_settings->get_blog_base();
		}

		// Update the preview link to point to the front-end.
		return add_query_arg(
			array(
				'id'     => $post->ID,
				'type'   => $post_type,
				'secret' => $secret,
			),
			esc_url_raw( "{$url}/api/preview" )
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
			return sprintf(
				'%s/%s/%s',
				$this->theme_settings->get_frontend_url(),
				$this->theme_settings->get_blog_base(),
				$post_slug
			);
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

	/**
	 * Modifies the category link.
	 *
	 * @param string $url The original URL.
	 *
	 * @return string The modified URL.
	 */
	public function modify_category_link( string $url ): string {
		$frontend_blog_cat = sprintf(
			'%s/%s',
			$this->theme_settings->get_frontend_url(),
			$this->theme_settings->get_blog_base(),
		);

		return str_replace( home_url(), $frontend_blog_cat, $url );
	}

	/**
	 * Modifies the tag link.
	 *
	 * @param string $url The original URL.
	 *
	 * @return string The modified URL.
	 */
	public function modify_tag_link( string $url ): string {
		$frontend_blog_tag = sprintf(
			'%s/%s',
			$this->theme_settings->get_frontend_url(),
			$this->theme_settings->get_blog_base(),
		);

		return str_replace( home_url(), $frontend_blog_tag, $url );
	}

	/**
	 * Modifies the author link.
	 *
	 * @param string $url The original URL.
	 *
	 * @return string The modified URL.
	 */
	public function modify_author_link( string $url ): string {
		$frontend_blog_tag = sprintf(
			'%s/%s',
			$this->theme_settings->get_frontend_url(),
			$this->theme_settings->get_blog_base(),
		);

		return str_replace( home_url(), $frontend_blog_tag, $url );
	}
}

new BBH_Link_Modifier();
