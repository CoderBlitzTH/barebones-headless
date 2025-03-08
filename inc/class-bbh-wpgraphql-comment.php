<?php

/**
 * WPGraphQL comment functions
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

final class BBH_WpGraphql_Comment {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'graphql_comment_insert_post_args', array( $this, 'adjust_comment_date' ) );
	}

	/**
	 * Filter WPGraphQL comment insert arguments to use local WordPress time.
	 *
	 * @param array $comment_args The arguments to be passed to wp_new_comment.
	 * @return array Modified comment arguments.
	 */
	public function adjust_comment_date( array $comment_args ): array {
		// Adjust comment_date to use local WordPress time based on the site's timezone (e.g. UTC+7)
		$comment_args['comment_date'] = current_time( 'mysql' );

		return $comment_args;
	}
}

new BBH_WpGraphql_Comment();
