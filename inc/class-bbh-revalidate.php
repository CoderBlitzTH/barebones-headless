<?php

/**
 * ฟังก์ชันสำหรับการ Revalidate ในระบบ Headless WordPress
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

final class BBH_Revalidate {
	/**
	 * อินสแตนซ์ของการตั้งค่าธีม
	 *
	 * @var BBH_Theme_Settings
	 */
	private BBH_Theme_Settings $theme_settings;

	/**
	 * URL สำหรับการ revalidate ฝั่ง frontend
	 *
	 * @var string
	 */
	private string $frontend_revalidate_url;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->theme_settings = BBH_Theme_Settings::get_instance();

		// URL สำหรับ revalidate (สามารถกำหนดผ่าน filter)
		$this->frontend_revalidate_url = (string) apply_filters(
			'bbh_frontend_revalidate_url',
			$this->theme_settings->get_frontend_url() . '/api/revalidate'
		);

		// เพิ่ม Hooks สำหรับการทำ Revalidate
		add_action( 'save_post', array( $this, 'trigger_revalidate_on_post_update' ), 10, 2 );
		add_action( 'transition_post_status', array( $this, 'trigger_revalidate_on_status_change' ), 10, 3 );

		new BBH_Manual_Revalidate( $this );
	}

	/**
	 * ทริกเกอร์การ revalidate เมื่ออัปเดตโพสต์
	 *
	 * @param int     $post_id ไอดีของโพสต์
	 * @param WP_Post $post    ออบเจกต์ของโพสต์
	 * @return void
	 */
	public function trigger_revalidate_on_post_update( int $post_id, WP_Post $post ): void {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( 'publish' !== $post->post_status ) {
			return;
		}

		$paths = $this->get_paths_to_revalidate( $post );
		$this->send_revalidate_request( $paths );
	}

	/**
	 * ทริกเกอร์การ revalidate เมื่อสถานะโพสต์เปลี่ยน
	 *
	 * @param string  $new_status สถานะใหม่ของโพสต์
	 * @param string  $old_status สถานะเดิมของโพสต์
	 * @param WP_Post $post       ออบเจกต์ของโพสต์
	 * @return void
	 */
	public function trigger_revalidate_on_status_change( string $new_status, string $old_status, WP_Post $post ): void {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			return;
		}

		if ( 'publish' === $new_status || 'publish' === $old_status ) {
			$paths = $this->get_paths_to_revalidate( $post );
			$this->send_revalidate_request( $paths );
		}
	}

	/**
	 * รับเส้นทางที่ต้องการ revalidate
	 *
	 * @param WP_Post $post ออบเจกต์ของโพสต์
	 * @return array<string> อาร์เรย์ของเส้นทาง
	 */
	public function get_paths_to_revalidate( WP_Post $post ): array {
		$paths = array();

		// เพิ่มหน้าแรก (เผื่อมีการแสดงโพสต์ล่าสุดบนหน้าแรก)
		$paths[] = '/';

		$post_type = get_post_type( $post );

		if ( 'post' === $post_type ) {
			$blog_base = $this->theme_settings->get_blog_base();

			// หน้าบล็อก/บทความทั้งหมด
			$paths[] = "/{$blog_base}";

			// หน้าบทความเฉพาะ
			$paths[] = "/{$blog_base}/{$post->post_name}";

			$cache_key  = "post_categories_{$post->ID}";
			$categories = wp_cache_get( $cache_key );

			if ( false === $categories ) {
				$categories = get_the_category( $post->ID );
				wp_cache_set( $cache_key, $categories );
			}

			// เพิ่มหมวดหมู่ที่เกี่ยวข้อง
			if ( ! empty( $categories ) ) {
				foreach ( array_slice( $categories, 0, 3 ) as $category ) { // จำกัด 3 หมวดหมู่
					$paths[] = "/{$blog_base}/category/{$category->slug}";
				}
			}
		} elseif ( 'page' === $post_type ) {
			// หน้าเพจ
			$paths[] = '/' . $post->post_name;
		} else {
			// Custom post types
			$paths[] = '/' . $post_type;
			$paths[] = '/' . $post_type . '/' . $post->post_name;

			// เพิ่ม taxonomy ที่เกี่ยวข้องกับ custom post type
			$taxonomies = get_object_taxonomies( $post_type, 'objects' );
			if ( ! empty( $taxonomies ) ) {
				foreach ( $taxonomies as $taxonomy ) {
					$terms = get_the_terms( $post->ID, $taxonomy->name );
					if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
						foreach ( array_slice( $terms, 0, 3 ) as $term ) { // จำกัด 3 Terms
							$paths[] = '/' . $post_type . '/' . $taxonomy->rewrite['slug'] . '/' . $term->slug;
						}
					}
				}
			}
		}

		$paths = (array) apply_filters( 'bbh_revalidate_paths', array_unique( $paths ), $post );

		return $paths;
	}

	/**
	 * รับเส้นทางที่ต้องการ revalidate สำหรับ Taxonomy Term
	 *
	 * @param WP_Term $term Term object
	 * @return array<string> Array of paths
	 */
	public function get_term_paths_to_revalidate( WP_Term $term ): array {
		$paths     = array();
		$blog_base = $this->theme_settings->get_blog_base();

		// เส้นทางสำหรับ Taxonomy
		if ( 'category' === $term->taxonomy ) {
			$paths[] = "/{$blog_base}/category/{$term->slug}";
		} elseif ( 'post_tag' === $term->taxonomy ) {
			$paths[] = "/{$blog_base}/tag/{$term->slug}";
		}

		$paths = (array) apply_filters( 'bbh_revalidation_term_paths', array_unique( $paths ), $term );
		return $paths;
	}

	/**
	 * ส่งคำขอไปยัง FrontEnd API เพื่อทำ revalidate
	 *
	 * @param array<string> $paths รายการเส้นทางที่ต้องการ revalidate
	 * @return bool|WP_Error ผลลัพธ์ของการส่งคำขอ (true หากสำเร็จ, WP_Error หากมีข้อผิดพลาด)
	 */
	public function send_revalidate_request( array $paths ) {
		$token = $this->theme_settings->get_revalidate_token();
		if ( empty( $token ) ) {
			return new WP_Error( 'missing_token', 'Revalidate token หายไป' );
		}

		if ( ! $this->is_valid_request() ) {
			return new WP_Error( 'invalid_request', 'คำขอไม่ถูกต้อง' );
		}

		if ( empty( $paths ) ) {
			return new WP_Error( 'no_paths', 'ไม่มีเส้นทางที่ต้องการ revalidate' );
		}

		if ( ! filter_var( $this->frontend_revalidate_url, FILTER_VALIDATE_URL ) ) {
			return new WP_Error( 'invalid_url', 'URL สำหรับ revalidate ไม่ถูกต้อง' );
		}

		// Sanitize paths
		$sanitized_paths = array_map( 'sanitize_text_field', $paths );
		$sanitized_paths = array_filter( $sanitized_paths ); // ลบค่าว่าง
		$sanitized_paths = array_values( $sanitized_paths );

		if ( empty( $sanitized_paths ) ) {
			return new WP_Error( 'invalid_paths', 'เส้นทางไม่ถูกต้องหลังการ sanitize' );
		}

		$args = array(
			'method'      => 'POST',
			'timeout'     => 10,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => defined( 'WP_DEBUG' ) && WP_DEBUG,
			'headers'     => array(
				'Content-Type'       => 'application/json',
				'X-Revalidate-Token' => esc_attr( $this->theme_settings->get_revalidate_token() ),
			),
			'body'        => wp_json_encode( array( 'paths' => $sanitized_paths ) ),
			'cookies'     => array(),
		);

		$response = wp_remote_post( esc_url_raw( $this->frontend_revalidate_url ), $args );

		if ( is_wp_error( $response ) ) {
			error_log( 'FrontEnd Revalidate Error: ' . $response->get_error_message() ); //phpcs:ignore
			return $response;
		}

		if ( $args['blocking'] && 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'revalidate_failed', 'Revalidate failed' );
		}

		// Trigger Hook หลังจาก revalidate เสร็จ
		do_action( 'bbh_after_revalidate', $sanitized_paths, $response );

		return true;
	}

	private function is_valid_request(): bool {
		$allowed_domains = (array) apply_filters( 'bbh_allowed_revalidate_domains', array( home_url() ) );

		// ตรวจสอบ Referer
		$referer = wp_unslash( $_SERVER['HTTP_REFERER'] ?? '' );
		$host    = wp_parse_url( $referer, PHP_URL_HOST );
		if ( ! empty( $referer ) && $host ) {
			foreach ( $allowed_domains as $domain ) {
				if ( $host === $domain ) {
					return true;
				}
			}
		}

		// ตรวจสอบ Origin
		$origin = wp_unslash( $_SERVER['HTTP_ORIGIN'] ?? '' );
		$host   = wp_parse_url( $origin, PHP_URL_HOST );
		if ( ! empty( $origin ) && $host ) {
			foreach ( $allowed_domains as $domain ) {
				if ( $host === $domain ) {
					return true;
				}
			}
		}

		return false;
	}
}

new BBH_Revalidate();
