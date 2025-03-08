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

final class BBH_Manual_Revalidate {
	private BBH_Revalidate $revalidator;

	public function __construct( BBH_Revalidate $revalidator ) {
		$this->revalidator = $revalidator;

		add_filter( 'post_row_actions', array( $this, 'add_revalidate_action' ), 10, 2 );
		add_filter( 'bulk_actions-edit-post', array( $this, 'add_bulk_revalidate_action' ) );

		add_filter( 'page_row_actions', array( $this, 'add_revalidate_action' ), 10, 2 );
		add_filter( 'bulk_actions-edit-page', array( $this, 'add_bulk_revalidate_action' ) );

		add_filter( 'category_row_actions', array( $this, 'add_term_revalidate_action' ), 10, 2 );
		add_filter( 'tag_row_actions', array( $this, 'add_term_revalidate_action' ), 10, 2 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'wp_ajax_bbh_manual_revalidate', array( $this, 'handle_manual_revalidate' ) );
	}

	/**
	 * เพิ่มปุ่ม Revalidate ใน Row Actions
	 *
	 * @param array<string> $actions Existing actions
	 * @param WP_Post       $post    Post object
	 * @return array<string> Updated actions
	 */
	public function add_revalidate_action( array $actions, WP_Post $post ): array {
		if ( current_user_can( 'edit_posts', $post->ID ) && 'publish' === $post->post_status ) {
			$nonce                 = wp_create_nonce( "bbh_revalidate_{$post->ID}" );
			$actions['revalidate'] = sprintf(
				'<a href="#" class="bbh-revalidate" data-post-id="%d" data-nonce="%s">%s</a>',
				$post->ID,
				esc_attr( $nonce ),
				__( 'Revalidate', 'bbh' )
			);
		}
		return $actions;
	}

	/**
	 * เพิ่มปุ่ม Revalidate ใน Row Actions สำหรับ Terms
	 *
	 * @param array<string> $actions Existing actions
	 * @param WP_Term       $term    Term object
	 * @return array<string> Updated actions
	 */
	public function add_term_revalidate_action( array $actions, WP_Term $term ): array {
		if ( current_user_can( 'manage_categories', $term->term_id ) ) {
			$nonce                 = wp_create_nonce( "bbh_revalidate_term_{$term->term_id}" );
			$actions['revalidate'] = sprintf(
				'<a href="#" class="bbh-revalidate" data-id="%d" data-type="term" data-taxonomy="%s" data-nonce="%s">%s</a>',
				$term->term_id,
				esc_attr( $term->taxonomy ),
				esc_attr( $nonce ),
				__( 'Revalidate', 'bbh' )
			);
		}
		return $actions;
	}

	/**
	 * เพิ่มตัวเลือก Revalidate ใน Bulk Actions
	 *
	 * @param array<string> $actions Existing bulk actions
	 * @return array<string> Updated bulk actions
	 */
	public function add_bulk_revalidate_action( array $actions ): array {
		$actions['bbh_revalidate'] = __( 'Revalidate', 'bbh' );
		return $actions;
	}

	/**
	 * โหลด JavaScript สำหรับ AJAX
	 *
	 * @param string $hook Current admin page
	 */
	public function enqueue_admin_scripts( string $hook ): void {
		if ( ! in_array( $hook, array( 'edit.php', 'edit-tags.php' ), true ) ) {
			return;
		}

		$version = filemtime( BBH_THEME_DIR . 'js/revalidate.js' );

		wp_enqueue_script(
			'bbh-revalidate-script',
			BBH_THEME_URL . 'js/revalidate.js',
			array( 'jquery' ),
			$version,
			true
		);

		wp_localize_script(
			'bbh-revalidate-script',
			'bbhRevalidate',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'bbh_bulk_revalidate' ),
			)
		);
	}

	/**
	 * จัดการ AJAX Request สำหรับ Manual Revalidation
	 */
	public function handle_manual_revalidate(): void {
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			wp_send_json_error( array( 'message' => 'Method ไม่ถูกต้อง' ) );
		}

		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'manage_categories' ) ) {
			wp_send_json_error( array( 'message' => 'ไม่มีสิทธิ์ในการดำเนินการ' ) );
		}

		// ตรวจสอบว่าผู้ใช้มีการส่งคำขอซ้ำหรือไม่ (Rate Limiting)
		if ( get_transient( 'bbh_revalidate_lock_' . get_current_user_id() ) ) {
			wp_send_json_error( array( 'message' => 'โปรดรอสักครู่ก่อนลองอีกครั้ง' ) );
		}

		// ตั้งค่า Rate Limit (ล็อก 10 วินาที)
		set_transient( 'bbh_revalidate_lock_' . get_current_user_id(), true, 10 );

		$paths = array();

		if ( isset( $_POST['id'] ) && isset( $_POST['type'] ) ) {
			// Revalidate เดี่ยว (Post หรือ Term)
			$id   = intval( $_POST['id'] );
			$type = sanitize_text_field( $_POST['type'] );

			if ( ! in_array( $type, array( 'post', 'term' ), true ) ) {
				wp_send_json_error( array( 'message' => 'ประเภทไม่ถูกต้อง' ) );
			}

			if ( 'post' === $type ) {
				check_ajax_referer( "bbh_revalidate_{$id}", 'nonce' );

				$post = get_post( $id );
				if ( $post && 'publish' === $post->post_status ) {
					$paths = $this->revalidator->get_paths_to_revalidate( $post );
				}
			} elseif ( 'term' === $type ) {
				check_ajax_referer( "bbh_revalidate_term_{$id}", 'nonce' );

				$taxonomy = sanitize_key( $_POST['taxonomy'] ?? '' );
				if ( ! taxonomy_exists( $taxonomy ) ) {
					wp_send_json_error( array( 'message' => 'Taxonomy ไม่ถูกต้อง' ) );
				}

				$term = get_term( $id, $taxonomy );
				if ( $term && ! is_wp_error( $term ) ) {
					$paths = $this->revalidator->get_term_paths_to_revalidate( $term );
				}
			}
		} elseif ( isset( $_POST['post_ids'] ) && is_array( $_POST['post_ids'] ) ) {
			// Bulk Revalidate จาก Posts
			check_ajax_referer( 'bbh_bulk_revalidate', 'nonce' );

			$post_ids = array_filter( array_map( 'intval', $_POST['post_ids'] ) );
			if ( empty( $post_ids ) ) {
				wp_send_json_error( array( 'message' => 'ไม่มี Post IDs ที่ถูกต้อง' ) );
			}

			foreach ( $post_ids as $post_id ) {
				$post = get_post( $post_id );
				if ( $post && 'publish' === $post->post_status ) {
					$paths = array_merge( $paths, $this->revalidator->get_paths_to_revalidate( $post ) );
				}
			}
		}

		if ( empty( $paths ) ) {
			wp_send_json_error( array( 'message' => 'ไม่มีข้อมูลที่สามารถ Revalidate ได้' ) );
		}

		$result = $this->revalidator->send_revalidate_request( array_unique( $paths ) );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => 'Revalidate สำเร็จ' ) );
	}
}
