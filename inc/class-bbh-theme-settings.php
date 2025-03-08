<?php

/**
 * Theme settings page.
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

final class BBH_Theme_Settings {
	/**
	 * The instance of the class.
	 *
	 * @var BBH_Theme_Settings|null
	 */
	private static ?BBH_Theme_Settings $instance = null;

	/**
	 * The frontend URL.
	 *
	 * @var string
	 */
	private string $frontend_url;

	/**
	 * The blog base.
	 *
	 * @var string
	 */
	private string $blog_base;

	/**
	 * The preview secret.
	 *
	 * @var string
	 */
	private string $preview_secret;

	/**
	 * The revalidate token.
	 *
	 * @var string
	 */
	private string $revalidate_token;

	/**
	 * Gets the instance of the class.
	 *
	 * @return BBH_Theme_Settings The instance of the class.
	 */
	public static function get_instance(): BBH_Theme_Settings {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Returns the frontend URL.
	 *
	 * @return string The frontend URL.
	 */
	public function get_frontend_url(): string {
		if ( defined( 'BBH_FRONTEND_URL' ) && BBH_FRONTEND_URL ) {
			return BBH_FRONTEND_URL;
		}
		return $this->frontend_url;
	}

	/**
	 * Returns the blog base.
	 *
	 * @return string The blog base.
	 */
	public function get_blog_base(): string {
		if ( defined( 'BBH_BLOG_BASE' ) && BBH_BLOG_BASE ) {
			return BBH_BLOG_BASE;
		}
		return $this->blog_base;
	}

	/**
	 * Returns the preview secret.
	 *
	 * @return string The preview secret.
	 */
	public function get_preview_secret(): string {
		if ( defined( 'BBH_PREVIEW_SECRET' ) && BBH_PREVIEW_SECRET ) {
			return BBH_PREVIEW_SECRET;
		}
		return $this->preview_secret;
	}

	/**
	 * Returns the revalidate token.
	 *
	 * @return string The revalidate token.
	 */
	public function get_revalidate_token(): string {
		if ( defined( 'BBH_REVALIDATION_SECRET' ) && BBH_REVALIDATION_SECRET ) {
			return BBH_REVALIDATION_SECRET;
		}
		return $this->revalidate_token;
	}

	/**
	 * Constructs the class.
	 */
	private function __construct() {
		// WordPress Options
		$this->frontend_url     = rtrim( get_option( 'bbh_frontend_url', 'http://localhost:3000' ), '/' );
		$this->blog_base        = trim( get_option( 'bbh_blog_base', 'blog' ), '/' );
		$this->preview_secret   = trim( get_option( 'bbh_preview_secret', 'preview' ), '/' );
		$this->revalidate_token = trim( get_option( 'bbh_revalidate_token', '' ) );

		if ( empty( $this->revalidate_token ) ) {
			$this->revalidate_token = wp_generate_password( 32, false );
			update_option( 'bbh_revalidate_token', $this->revalidate_token );
		}

		$this->hooks();
	}

	/**
	 * Hooks the class to the WordPress hooks.
	 */
	private function hooks(): void {
		// Register Hooks
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Adds the admin menu.
	 */
	public function add_admin_menu(): void {
		add_menu_page(
			'Theme Settings',
			'Theme Settings',
			'manage_options',
			'bbh-theme-settings',
			array( $this, 'render_settings_page' ),
			'dashicons-admin-generic',
			100
		);
	}

	/**
	 * Registers the settings.
	 */
	public function register_settings(): void {
		register_setting(
			'bbh_theme_settings',
			'bbh_frontend_url',
			array( 'sanitize_callback' => 'esc_url_raw' )
		);

		register_setting(
			'bbh_theme_settings',
			'bbh_blog_base',
			array( 'sanitize_callback' => 'sanitize_text_field' )
		);

		register_setting(
			'bbh_theme_settings',
			'bbh_preview_secret',
			array( 'sanitize_callback' => 'sanitize_text_field' )
		);

		register_setting( 'bbh_theme_settings', 'bbh_revalidate_token' );
	}

	/**
	 * Renders the settings page.
	 */
	public function render_settings_page(): void {
		?>

		<div class="wrap">
			<h1>Theme Settings</h1>
			<form
				method="post"
				action="options.php"
				onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการบันทึกการตั้งค่านี้?')"
			>
				<?php
				settings_fields( 'bbh_theme_settings' );
				do_settings_sections( 'bbh_theme_settings' );
				?>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="bbh_frontend_url">Frontend URL</label></th>
						<td>
							<input type="url" id="bbh_frontend_url" name="bbh_frontend_url" value="<?php echo esc_attr( $this->frontend_url ); ?>" class="regular-text" readonly>
							<button type="button" class="button edit-toggle">แก้ไข</button>
							<p class="description">URL ของ Frontend เช่น Next.js</p>
						</td>
					</tr>

					<tr>
						<th scope="row">Blog Base</th>
						<td>
							<input type="text" id="bbh_blog_base" name="bbh_blog_base" value="<?php echo esc_attr( $this->blog_base ); ?>" class="regular-text" readonly>
							<button type="button" class="button edit-toggle">แก้ไข</button>
							<p class="description">Set the base slug for blog posts (default: "blog").</p>
						</td>
					</tr>

					<tr>
						<th scope="row"><label for="bbh_preview_secret">Preview Secret</label></th>
						<td>
							<input type="text" id="bbh_preview_secret" name="bbh_preview_secret" value="<?php echo esc_attr( $this->preview_secret ); ?>" class="regular-text" readonly>
							<button type="button" class="button edit-toggle">แก้ไข</button>
							<p class="description">Preview Secret ต้องตรงกับค่าใน .env ของ Next.js</p>
						</td>
					</tr>

					<tr>
						<th scope="row">Revalidation Token</th>
						<td>
							<input type="text" id="bbh_revalidate_token" name="bbh_revalidate_token" readonly value="<?php echo esc_attr( $this->revalidate_token ); ?>" class="regular-text">
							<button type="button" class="button" onclick="navigator.clipboard.writeText(this.previousElementSibling.value)">Copy</button>
							<button type="button" class="button" onclick="generateNewToken()">New Token</button>
							<p class="description">ใช้ Token นี้ในการทำ Revalidation</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>

		<script>
			document.addEventListener("DOMContentLoaded", function () {
				const editButtons = document.querySelectorAll(".edit-toggle");

				editButtons.forEach(button => {
					button.addEventListener("click", function () {
						const input = this.previousElementSibling;
						const isEditing = !input.readOnly;

						if (isEditing) {
							input.setAttribute("readonly", true);
						} else {
							input.removeAttribute("readonly");
							this.remove();
						}
					});
				});
			});

			function generateNewToken() {
				if (confirm('คุณต้องการสร้าง Token ใหม่หรือไม่?')) {
					const length = 32;
					const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
					let token = '';
					for (let i = 0; i < length; i++) {
						token += chars.charAt(Math.floor(Math.random() * chars.length));
					}
					document.getElementById('bbh_revalidate_token').value = token;
				}
			}
		</script>

		<?php
	}
}

BBH_Theme_Settings::get_instance();
