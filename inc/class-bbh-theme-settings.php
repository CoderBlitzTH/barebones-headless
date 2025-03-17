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
		return $this->frontend_url;
	}

	/**
	 * Returns the blog base.
	 *
	 * @return string The blog base.
	 */
	public function get_blog_base(): string {
		return $this->blog_base;
	}

	/**
	 * Returns the preview secret.
	 *
	 * @return string The preview secret.
	 */
	public function get_preview_secret(): string {
		return $this->preview_secret;
	}

	/**
	 * Returns the revalidate token.
	 *
	 * @return string The revalidate token.
	 */
	public function get_revalidate_token(): string {
		return $this->revalidate_token;
	}

	/**
	 * Constructs the class.
	 */
	private function __construct() {
		// Check Define constants
		$frontend_url     = $this->get_constant_value( 'BBH_FRONTEND_URL' );
		$blog_base        = $this->get_constant_value( 'BBH_BLOG_BASE' );
		$preview_secret   = $this->get_constant_value( 'BBH_PREVIEW_SECRET' );
		$revalidate_token = $this->get_constant_value( 'BBH_REVALIDATION_SECRET' );

		$frontend_url     ??= get_option( 'bbh_frontend_url', 'http://localhost:3000' );
		$blog_base        ??= get_option( 'bbh_blog_base', 'blog' );
		$preview_secret   ??= get_option( 'bbh_preview_secret', 'preview' );
		$revalidate_token ??= get_option( 'bbh_revalidate_token', '' );

		// WordPress Options
		$this->frontend_url     = trim( $frontend_url, '/' );
		$this->blog_base        = trim( $blog_base, '/' );
		$this->preview_secret   = trim( $preview_secret, '/' );
		$this->revalidate_token = trim( $revalidate_token );

		if ( empty( $this->revalidate_token ) ) {
			$this->revalidate_token = wp_generate_password( 32, false );
			update_option( 'bbh_revalidate_token', $this->revalidate_token );
		}

		$this->hooks();
	}

	private function get_constant_value( string $constant_name ): ?string {
		if ( defined( $constant_name ) && constant( $constant_name ) ) {
			return (string) constant( $constant_name );
		}
		return null;
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
		// Define the SVG
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" color="currentColor" fill="none">
    <path fill-rule="evenodd" clip-rule="evenodd" d="M18 1C18.5523 1 19 1.44772 19 2V2.39864C19.7404 2.56884 20.4097 2.92409 20.9512 3.41399L21.4592 3.08741C21.9238 2.78874 22.5425 2.92323 22.8412 3.38779C23.1398 3.85236 23.0053 4.47107 22.5408 4.76974L22.0766 5.06817C22.2425 5.51495 22.3333 5.99727 22.3333 6.5C22.3333 7.00281 22.2425 7.48521 22.0765 7.93205L22.5404 8.23026C23.0049 8.52893 23.1394 9.14764 22.8407 9.61221C22.5421 10.0768 21.9234 10.2113 21.4588 9.91259L20.951 9.58617C20.4096 10.076 19.7403 10.4312 19 10.6014V11C19 11.5523 18.5523 12 18 12C17.4477 12 17 11.5523 17 11V10.6014C16.2597 10.4312 15.5904 10.076 15.049 9.58617L14.5412 9.91259C14.0766 10.2113 13.4579 10.0768 13.1593 9.61221C12.8606 9.14764 12.9951 8.52893 13.4596 8.23026L13.9235 7.93205C13.7575 7.48521 13.6667 7.00281 13.6667 6.5C13.6667 5.99727 13.7575 5.51495 13.9234 5.06817L13.4592 4.76974C12.9947 4.47107 12.8602 3.85236 13.1588 3.38779C13.4575 2.92323 14.0762 2.78874 14.5408 3.08741L15.0488 3.41399C15.5903 2.92409 16.2596 2.56884 17 2.39864V2C17 1.44772 17.4477 1 18 1ZM18 4.28571C17.1751 4.28571 16.4614 4.68984 16.0482 5.28631C15.8056 5.63659 15.6667 6.05259 15.6667 6.5C15.6667 6.94747 15.8056 7.36351 16.0483 7.71382C16.4615 8.31022 17.1752 8.71428 18 8.71428C18.8248 8.71428 19.5385 8.31022 19.9517 7.71382C20.1944 7.36351 20.3333 6.94747 20.3333 6.5C20.3333 6.05259 20.1944 5.6366 19.9518 5.28631C19.5386 4.68984 18.8249 4.28571 18 4.28571Z" fill="currentColor" />
    <path fill-rule="evenodd" clip-rule="evenodd" d="M10 14C10 13.4477 10.4477 13 11 13H13C13.5523 13 14 13.4477 14 14C14 14.5523 13.5523 15 13 15H11C10.4477 15 10 14.5523 10 14Z" fill="currentColor" />
    <path fill-rule="evenodd" clip-rule="evenodd" d="M9.94952 1L11.0029 1C11.5552 1 12.0029 1.44772 12.0029 2C12.0029 2.55229 11.5552 3 11.0029 3H10.0062C8.34371 3 7.17486 3.0013 6.27356 3.09623C5.39003 3.18929 4.87947 3.36325 4.49367 3.63318C4.15889 3.86741 3.86775 4.15834 3.63339 4.49278C3.36341 4.87806 3.18939 5.38789 3.09629 6.27049C3.0013 7.17094 3 8.33875 3 10C3 11.6613 3.0013 12.8291 3.09629 13.7295C3.18939 14.6121 3.36341 15.1219 3.63339 15.5072C3.86775 15.8417 4.15889 16.1326 4.49367 16.3668C4.87947 16.6368 5.39003 16.8107 6.27356 16.9038C7.17486 16.9987 8.34371 17 10.0062 17H14.0093C15.6718 17 16.8407 16.9987 17.7419 16.9038C18.6255 16.8107 19.136 16.6368 19.5218 16.3668C19.8566 16.1326 20.1478 15.8417 20.3821 15.5072C20.8194 14.8832 20.9679 14.0273 21.0005 12.9692C21.0175 12.4172 21.4787 11.9835 22.0308 12.0005C22.5828 12.0175 23.0165 12.4787 22.9995 13.0308C22.9647 14.1609 22.8117 15.5252 22.02 16.655C21.6517 17.1805 21.1943 17.6376 20.6684 18.0055C19.891 18.5494 18.9996 18.7824 17.9514 18.8928C16.9331 19 15.6588 19 14.066 19H13.4C13.0229 19 13 19.0229 13 19.4V20C13 20.8273 13.1727 21 14 21H16C16.5523 21 17 21.4477 17 22C17 22.5523 16.5523 23 16 23H8C7.44772 23 7 22.5523 7 22C7 21.4477 7.44772 21 8 21H10C10.8273 21 11 20.8273 11 20V19.4C11 19.0229 10.9771 19 10.6 19H9.9495C8.35672 19 7.08239 19 6.06407 18.8928C5.01593 18.7824 4.12447 18.5494 3.34712 18.0055C2.82123 17.6376 2.36379 17.1805 1.9955 16.655C1.45104 15.878 1.21783 14.9869 1.10732 13.9393C0.999973 12.9217 0.999985 11.6482 1 10.0568V9.94324C0.999985 8.35178 0.999973 7.07834 1.10732 6.06068C1.21783 5.01311 1.45104 4.12201 1.9955 3.34503C2.36379 2.81948 2.82123 2.3624 3.34712 1.99446C4.12447 1.45057 5.01593 1.21762 6.06407 1.10723C7.0824 0.999973 8.35673 0.999985 9.94952 1Z" fill="currentColor" />
</svg>';

		// Remove newlines and encode to base64
		$svg_base64 = base64_encode( preg_replace( '/\s+/', ' ', trim( $svg ) ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		// Create the data URI
		$icon_url = 'data:image/svg+xml;base64,' . $svg_base64;

		add_menu_page(
			'Theme Settings',
			'Theme Settings',
			'edit_theme_options',
			'bbh-theme-settings',
			array( $this, 'render_settings_page' ),
			$icon_url,
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
		$is_hidden_frontend_url     = null === $this->get_constant_value( 'BBH_FRONTEND_URL' );
		$is_hidden_blog_base        = null === $this->get_constant_value( 'BBH_BLOG_BASE' );
		$is_hidden_preview_secret   = null === $this->get_constant_value( 'BBH_PREVIEW_SECRET' );
		$is_hidden_revalidate_token = null === $this->get_constant_value( 'BBH_REVALIDATION_SECRET' );

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
							<input type="url" id="bbh_frontend_url" name="bbh_frontend_url" value="<?php echo esc_attr( $this->get_frontend_url() ); ?>" class="regular-text" readonly>
							<?php if ( $is_hidden_frontend_url ) : ?>
								<button type="button" class="button edit-toggle">แก้ไข</button>
							<?php endif; ?>
							<p class="description">URL Frontend ต้องตรงกับค่า <code>NEXT_PUBLIC_SITE_URL</code> ใน <code>.env</code> ของ Next.js</p>
						</td>
					</tr>

					<tr>
						<th scope="row">Blog Base</th>
						<td>
							<input type="text" id="bbh_blog_base" name="bbh_blog_base" value="<?php echo esc_attr( $this->get_blog_base() ); ?>" class="regular-text" readonly>
							<?php if ( $is_hidden_blog_base ) : ?>
								<button type="button" class="button edit-toggle">แก้ไข</button>
							<?php endif; ?>
							<p class="description">ตั้งค่า Slug ของบล็อก (ค่าเริ่มต้น: "blog").</p>
						</td>
					</tr>

					<tr>
						<th scope="row"><label for="bbh_preview_secret">Preview Secret</label></th>
						<td>
							<input type="text" id="bbh_preview_secret" name="bbh_preview_secret" value="<?php echo esc_attr( $this->get_preview_secret() ); ?>" class="regular-text" readonly>
							<?php if ( $is_hidden_preview_secret ) : ?>
								<button type="button" class="button edit-toggle">แก้ไข</button>
							<?php endif; ?>
							<p class="description">Preview Secret ต้องตรงกับค่า <code>NEXTJS_PREVIEW_SECRET</code> ใน <code>.env</code> ของ Next.js</p>
						</td>
					</tr>

					<tr>
						<th scope="row">Revalidation Token</th>
						<td>
							<input type="text" id="bbh_revalidate_token" name="bbh_revalidate_token" readonly value="<?php echo esc_attr( $this->get_revalidate_token() ); ?>" class="regular-text">
							<?php if ( $is_hidden_revalidate_token ) : ?>
								<button type="button" class="button" onclick="navigator.clipboard.writeText(this.previousElementSibling.value)">Copy</button>
								<button type="button" class="button" onclick="generateNewToken()">New Token</button>
							<?php endif; ?>
							<p class="description">Token Revalidation ต้องตรงกับค่า <code>NEXTJS_REVALIDATION_SECRET</code> ใน <code>.env</code> ของ Next.js</p>
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
