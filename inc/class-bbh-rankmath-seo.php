<?php

/**
 * Rank Math SEO functions.
 *
 * @author ColderBlitz
 * @package barebones-headless
 * @since 1.2.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.1 404 Not Found' );
	exit;
}

final class BBH_RankMath_SEO {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks(): void {
		add_filter( 'rank_math/sitemap/index/slug', array( $this, 'modify_sitemap_index_filename' ) );
	}

	/**
	 * Modify the sitemap filename.
	 *
	 * @see https://rankmath.com/kb/filters-hooks-api-developer/#modify-sitemap-index-slug
	 */
	public function modify_sitemap_index_filename(): string {
		return 'sitemap';
	}
}

new BBH_RankMath_SEO();
