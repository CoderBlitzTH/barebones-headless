<?php

/**
 * Theme functionality.
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

final class BBH_Theme {
	private BBH_Theme_Settings $theme_settings;

	/**
	 * Private constructor to prevent instantiation from outside of the class.
	 */
	public function __construct() {
		$this->theme_settings = BBH_Theme_Settings::get_instance();

		add_action( 'after_setup_theme', array( $this, 'setup_theme' ) );

		/**
		 * Remove FSE
		 */
		add_action( 'after_setup_theme', array( $this, 'remove_site_editor_setup_theme' ), 999 );
		add_action( 'admin_menu', array( $this, 'remove_site_editor_menu' ), 999 );
		add_action( 'admin_init', array( $this, 'redirect_from_site_editor' ) );

		/**
		 * Remove Customizer
		*/
		add_action( 'admin_menu', array( $this, 'remove_customize_menu' ), 999 );
		add_action( 'admin_init', array( $this, 'disable_customize_manager' ) );
		add_action( 'load-customize.php', array( $this, 'redirect_theme_customizer' ) );
		add_action( 'init', array( $this, 'remove_map_meta_cap_customizer' ) );

		/**
		 * Based on https://gist.github.com/jasonbahl/5dd6c046cd5a5d39bda9eaaf7e32a09d
		 */
		add_action( 'parse_request', array( $this, 'disable_frontend' ), 999 );

		$this->disable_rest_api();
	}

	/**
	 * Sets up the theme.
	 */
	public function setup_theme(): void {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'menus' );
	}

	/**
	 * Remove FSE (Full Site Editing) support
	 */
	public function remove_site_editor_setup_theme(): void {
		remove_theme_support( 'block-templates' );
		remove_theme_support( 'block-template-parts' );
		remove_theme_support( 'widgets-block-editor' );
		remove_theme_support( 'core-block-patterns' );
		remove_theme_support( 'custom-spacing' );
		remove_theme_support( 'custom-units' );
		remove_theme_support( 'customize-selective-refresh' );
		remove_theme_support( 'customize-selective-refresh-widgets' );
	}

	/**
	 * Remove Site Editor menu items
	 */
	public function remove_site_editor_menu(): void {
		remove_menu_page( 'site-editor.php' );
		remove_submenu_page( 'themes.php', 'site-editor.php?path=/patterns' );
	}

	/**
	 * Redirect from site-editor
	 */
	public function redirect_from_site_editor(): void {
		global $pagenow;
		if ( 'site-editor.php' === $pagenow ) {
			wp_safe_redirect( admin_url( 'index.php' ) );
			exit;
		}
	}

	/**
	 * Remove Customize from the admin menu
	 */
	public function remove_customize_menu(): void {
		remove_submenu_page( 'themes.php', 'customize.php' );
		remove_submenu_page( 'themes.php', 'customize.php?return=' . rawurlencode( $_SERVER['REQUEST_URI'] ) );
	}

	/**
	 * Remove customize support and related functionality
	 */
	public function disable_customize_manager(): void {
		remove_action( 'plugins_loaded', '_wp_customize_include' );
		remove_action( 'admin_enqueue_scripts', '_wp_customize_loader_settings' );

		// Remove the customizer from the admin bar
		add_action(
			'wp_before_admin_bar_render',
			function (): void {
				global $wp_admin_bar;
				$wp_admin_bar->remove_menu( 'customize' );
			}
		);
	}

	/**
	 * Redirect away from the customizer if accessed directly
	 */
	public function redirect_theme_customizer(): void {
		wp_safe_redirect( admin_url( 'index.php' ) );
		exit;
	}

	/**
	 * Removes the capability to use the Customizer.
	 *
	 * This function modifies the capabilities map to disallow the 'customize' capability.
	 * It effectively prevents users from accessing the WordPress Customizer.
	 */
	public function remove_map_meta_cap_customizer(): void {
		add_filter(
			'map_meta_cap',
			static function ( $caps, $cap ) {
				// Check if the requested capability is 'customize'.
				if ( 'customize' === $cap ) {
					// Return an array that disallows the capability.
					return array( 'do_not_allow' );
				}
				// Return the original capabilities if not targeting 'customize'.
				return $caps;
			},
			10,
			2
		);
	}

	/**
	 * Redirects to the frontend application.
	 */
	public function disable_frontend(): void {
		/**
		 * If the request is not part of a CRON, REST Request, GraphQL Request or Admin request,
		 * output some basic, blank markup
		 */
		if (
			! defined( 'DOING_CRON' ) && ! defined( 'REST_REQUEST' ) && ! is_admin()
			&& ( empty( $wp->query_vars['rest_oauth1'] ) && ! defined( 'GRAPHQL_HTTP_REQUEST' ) )
		) {
			if ( strpos( home_url(), $this->theme_settings->get_frontend_url() ) === 0 ) {
				return;
			}

			global $wp;

			wp_redirect( trailingslashit( $this->theme_settings->get_frontend_url() ) . $wp->request, 301 ); // phpcs:ignore
			exit;
		}
	}

	/**
	 * Disables the WP REST API for visitors not logged into WordPress.
	 */
	public function disable_rest_api(): void {
		/**
		 * Disable REST API link in HTTP headers
		 * @link <https://example.com/wp-json/>; rel="https://api.w.org/"
		 */
		remove_action( 'template_redirect', 'rest_output_link_header', 11 );

		/**
		 * Disable REST API links in HTML <head>
		 * <link rel='https://api.w.org/' href='https://example.com/wp-json/' />
		 */
		remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
		remove_action( 'xmlrpc_rsd_apis', 'rest_output_rsd' );

		/**
		 * Disable REST API
		 *
		 * @link https://developer.wordpress.org/reference/hooks/rest_authentication_errors/
		 *
		 * @param WP_Error|null|true $access
		 * @return WP_Error|null|true
		 */
		add_filter(
			'rest_authentication_errors',
			static function ( $access ) {
				if ( ! is_user_logged_in()
				|| ! current_user_can( 'edit_posts' )
				|| ! current_user_can( 'manage_options' )
				) {
					return new WP_Error(
						'rest_forbidden',
						__( 'REST API is restricted to Admin and Editors only.', 'bbh' ),
						array( 'status' => rest_authorization_required_code() )
					);

				}

				return $access;
			}
		);
	}
}

new BBH_Theme();
