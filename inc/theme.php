<?php

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.1 404 Not Found' );
	exit;
}

if ( ! function_exists( 'bbh_theme_setup' ) ) {
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * @since 1.0.0
	 */
	function bbh_theme_setup(): void {
		// Add support for featured images and custom post types.
		add_theme_support( 'post-thumbnails' );

		// Add excerpts to pages.
		add_post_type_support( 'page', 'excerpt' );

		/**
		 * Disable "BIG Image" functionality.
		 *
		 * @see https://developer.wordpress.org/reference/hooks/big_image_size_threshold/
		 */
		add_filter( 'big_image_size_threshold', '__return_false' );
	}
	add_action( 'after_setup_theme', 'bbh_theme_setup' );
}

if ( ! function_exists( 'bbh_embed_wrapper' ) ) {
	/**
	 * Wrap WYSIWYG embed in a div wrapper for responsive
	 *
	 * @since 1.0.0
	 *
	 * @param string $html HTML string.
	 * @param string $url  Current URL.
	 * @param string $attr Embed attributes.
	 * @param string $id   Post ID.
	 * @return string
	 */
	function bbh_embed_wrapper( $html ): string {
		return '<div class="iframe-wrapper">' . $html . '</div>';
	}
	add_filter( 'embed_oembed_html', 'bbh_embed_wrapper' );
}
