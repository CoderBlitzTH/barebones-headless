<?php

/**
 * Next.js WordPress: revalidation functionality
 *
 * Handles the revalidation of Next.js pages when WordPress content changes.
 * This class manages the transition of post status and triggers revalidation
 * on the Next.js frontend.
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

if ( ! class_exists( 'BBH_Revalidation' ) ) {
	/**
	 * Manages the revalidation of Next.js pages in response to WordPress post updates.
	 */
	class BBH_Revalidation {
		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->hooks();
		}

		/**
		 * Registers hooks for the class.
		 */
		public function hooks(): void {
			add_action( 'transition_post_status', array( $this, 'transition_handler' ), 10, 3 );
		}

		/**
		 * Handles the post status transition for revalidation purposes.
		 *
		 * This method is triggered when a post's status transitions. It determines
		 * the appropriate slug for revalidation based on the post type and initiates
		 * the revalidation process.
		 *
		 * @param string    $new_status New status of the post.
		 * @param string    $old_status Old status of the post.
		 * @param WP_Post   $post       The post object.
		 */
		public function transition_handler( string $new_status, string $old_status, WP_Post $post ): void {
			// Do not run on autosave or cron.
			if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
				return;
			}

			// Ignore drafts and inherited posts.
			if ( ( 'draft' === $new_status && 'draft' === $old_status ) || 'inherit' === $new_status ) {
				return;
			}

			// Determine the slug based on post type.
			$slug = $this->get_slug( $post->post_type, $post->post_name );

			// Trigger revalidation.
			if ( $slug ) {
				$this->on_demand_revalidation( $slug );
			}
		}

		/**
		 * Gets the slug for a given post type and post name.
		 *
		 * @param string $post_type The post type.
		 * @param string $post_name The post name.
		 *
		 * @return string
		 */
		protected function get_slug( string $post_type, string $post_name ): string {
			switch ( $post_type ) {
				case 'post':
					return "/videos/{$post_name}";
				default:
					return $post_name;
			}
		}

		/**
		 * Performs on-demand revalidation of a Next.js page.
		 *
		 * Sends a request to the Next.js revalidation endpoint to update the static
		 * content for a given slug.
		 *
		 * @param string $slug The slug of the post to revalidate.
		 */
		public function on_demand_revalidation( string $slug ): void {
			// Check necessary constants and slug.
			if ( ! defined( 'BBH_FRONTEND_URL' ) || ! defined( 'BBH_REVALIDATION_SECRET' ) || ! $slug ) {
				return;
			}

			// Construct the revalidation URL.
			$revalidation_url = add_query_arg(
				'slug',
				$slug,
				esc_url_raw( rtrim( BBH_FRONTEND_URL, '/' ) . '/api/revalidate' )
			);

			// Make a GET request to the revalidation endpoint.
			$response = wp_remote_get(
				$revalidation_url,
				array(
					'headers' => array(
						'x-revalidation-secret' => BBH_REVALIDATION_SECRET,
					),
				)
			);

			// Handle response errors.
			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				return;
			}
		}
	}

	new BBH_Revalidation();
}
