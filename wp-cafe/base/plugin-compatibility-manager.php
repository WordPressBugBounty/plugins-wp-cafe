<?php
/**
 * Checks if wpcafe-pro plugin meets minimum version requirements
 * and deactivates it if it doesn't.
 *
 * @package WpCafe/Base
 */

namespace WpCafe;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Plugin_Compatibility_Manager
 *
 * Manages compatibility checks for the WP Cafe Pro plugin
 */
class Plugin_Compatibility_Manager {

	/**
	 * Minimum required version for wpcafe-pro
	 *
	 * @var string
	 */
	const MIN_PRO_VERSION = '3.0.5';

	/**
	 * Pro plugin text domain (unique identifier)
	 *
	 * @var string
	 */
	const PRO_TEXT_DOMAIN = 'wpcafe-pro';

	/**
	 * Cached pro plugin slug
	 *
	 * @var string|null
	 */
	private static $pro_plugin_slug = null;

	/**
	 * Flag to track if upgrade notice has been added
	 *
	 * @var bool
	 */
	private static $upgrade_notice_added = false;

	/**
	 * Check pro plugin compatibility
	 *
	 * @return void
	 */
	public static function check(): void {
		// Check if pro plugin is active
		if ( ! self::is_pro_plugin_active() ) {
			self::show_upgrade_notice_if_needed();
			return;
		}

		// Get pro plugin version
		$pro_version = self::get_pro_plugin_version();

		// If version is not found, deactivate (indicates old/broken installation)
		if ( ! $pro_version ) {
			self::deactivate_pro_plugin();
			self::show_incompatibility_notice();
			return;
		}

		// Compare versions
		if ( version_compare( $pro_version, self::MIN_PRO_VERSION, '<' ) ) {
			self::deactivate_pro_plugin();
			self::show_incompatibility_notice( $pro_version );
			return;
		}
	}

	/**
	 * Get the pro plugin slug dynamically
	 *
	 * @return string|null
	 */
	private static function get_pro_plugin_slug(): ?string {
		if ( self::$pro_plugin_slug !== null ) {
			return self::$pro_plugin_slug;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = get_plugins();

		foreach ( $all_plugins as $plugin_file => $plugin_data ) {
			if ( isset( $plugin_data['TextDomain'] ) && $plugin_data['TextDomain'] === self::PRO_TEXT_DOMAIN ) {
				self::$pro_plugin_slug = $plugin_file;
				return $plugin_file;
			}
		}

		// Fallback: search by plugin folder name
		foreach ( $all_plugins as $plugin_file => $plugin_data ) {
			if ( strpos( $plugin_file, self::PRO_TEXT_DOMAIN . '/' ) === 0 ) {
				self::$pro_plugin_slug = $plugin_file;
				return $plugin_file;
			}
		}

		return null;
	}

	/**
	 * Check if pro plugin is active
	 *
	 * @return bool
	 */
	private static function is_pro_plugin_active(): bool {
		$plugin_slug = self::get_pro_plugin_slug();
		return $plugin_slug && is_plugin_active( $plugin_slug );
	}

	/**
	 * Get pro plugin version
	 *
	 * @return string|null
	 */
	private static function get_pro_plugin_version(): ?string {
		$plugin_slug = self::get_pro_plugin_slug();

		if ( ! $plugin_slug ) {
			return null;
		}

		// Get plugin data
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_file = WP_PLUGIN_DIR . '/' . $plugin_slug;

		if ( ! file_exists( $plugin_file ) ) {
			return null;
		}

		$plugin_data = get_plugin_data( $plugin_file );

		return isset( $plugin_data['Version'] ) ? $plugin_data['Version'] : null;
	}

	/**
	 * Deactivate pro plugin
	 *
	 * @return void
	 */
	private static function deactivate_pro_plugin(): void {
		$plugin_slug = self::get_pro_plugin_slug();

		if ( ! $plugin_slug ) {
			return;
		}

		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		deactivate_plugins( $plugin_slug );
	}

	/**
	 * Show incompatibility notice
	 *
	 * @param string|null $current_version Current pro version.
	 *
	 * @return void
	 */
	private static function show_incompatibility_notice( ?string $current_version = null ): void {
		add_action(
			'admin_notices',
			function () use ( $current_version ) {
				self::render_incompatibility_notice( $current_version );
			}
		);
	}

	/**
	 * Render incompatibility notice HTML
	 *
	 * @param string|null $current_version Current pro version.
	 *
	 * @return void
	 */
	private static function render_incompatibility_notice( ?string $current_version = null ): void {
		?>
		<div class="notice notice-error is-dismissible">
			<p>
				<?php
				echo '<strong>' . esc_html__( 'WPCafe Pro has been deactivated.', 'wp-cafe' ) . '</strong><br>';
				if ( $current_version ) {
					printf(
						/* translators: %s: current WPCafe Pro version */
						esc_html__( 'Your current version (%s) is not compatible with this version of WPCafe.', 'wp-cafe' ),
						'<strong>' . esc_html( $current_version ) . '</strong>'
					);
				} else {
					esc_html_e( 'The installed version is not compatible with this version of WPCafe.', 'wp-cafe' );
				}
				printf(
					/* translators: %s: minimum WPCafe Pro version required */
					esc_html__( 'Please update WPCafe Pro to version %s or higher to activate all Pro features.', 'wp-cafe' ),
					'<strong>' . esc_html( self::MIN_PRO_VERSION ) . '</strong>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Show upgrade notice if pro plugin is deactivated and outdated
	 *
	 * @return void
	 */
	private static function show_upgrade_notice_if_needed(): void {
		if ( self::$upgrade_notice_added ) {
			return;
		}

		global $pagenow;
		if ( $pagenow !== 'plugins.php' ) {
			return;
		}

		$pro_version = self::get_pro_plugin_version();

		if ( ! $pro_version ) {
			return;
		}

		if ( version_compare( $pro_version, self::MIN_PRO_VERSION, '<' ) ) {
			self::$upgrade_notice_added = true;

			add_action(
				'admin_notices',
				function () use ( $pro_version ) {
					self::render_upgrade_notice( $pro_version );
				}
			);
		}
	}

	/**
	 * Render upgrade notice HTML
	 *
	 * @param string $pro_version Current pro version.
	 *
	 * @return void
	 */
	private static function render_upgrade_notice( string $pro_version ): void {
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<?php
				printf(
					/* translators: %s: current WPCafe Pro version */
					esc_html__( 'Your current WPCafe Pro version (%s) is not compatible with this version of WPCafe.', 'wp-cafe' ),
					'<strong>' . esc_html( $pro_version ) . '</strong>'
				);
				printf(
					/* translators: %s: minimum WPCafe Pro version required */
					esc_html__( 'Please update WPCafe Pro to version %s or higher to activate all Pro features.', 'wp-cafe' ),
					'<strong>' . esc_html( self::MIN_PRO_VERSION ) . '</strong>'
				);
				?>
			</p>
		</div>
		<?php
	}
}
