<?php

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.1 404 Not Found' );
	exit;
}

if ( ! bbh_get_wpdi()->is_active( 'wp-graphql/wp-graphql.php' ) ) {
	return;
}

/**
 * Filter WPGraphQL comment insert arguments to use local WordPress time.
 *
 * @param array $comment_args The arguments to be passed to wp_new_comment.
 * @return array Modified comment arguments.
 */
function bbh_wpgraphql_adjust_comment_date( array $comment_args ): array {
	// Adjust comment_date to use local WordPress time based on the site's timezone (e.g. UTC+7)
	$comment_args['comment_date'] = current_time( 'mysql' );

	return $comment_args;
}
add_filter( 'graphql_comment_insert_post_args', 'bbh_wpgraphql_adjust_comment_date' );
