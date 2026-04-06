<?php
/**
 * Blocks old WPCafe Pro v2 from being activated
 *
 * @package WpCafe/Base
 */

namespace WpCafe;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Version_Guard
 *
 * Prevents incompatible wpcafe-pro versions from being activated
 */
class Version_Guard {

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
	 * Initialize the activation blocker
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'pre_update_option_active_plugins', [ __CLASS__, 'check_pro_activation' ], 10, 2 );
	}

	/**
	 * Get the pro plugin slug dynamically
	 *
	 * @return string|null
	 */
	private static function get_pro_plugin_slug(): ?string {
		// Return cached value if already found
		if ( self::$pro_plugin_slug !== null ) {
			return self::$pro_plugin_slug;
		}

		// Load plugin.php if needed
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Get all installed plugins
		$all_plugins = get_plugins();

		// Search for the pro plugin by text domain
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
	 * Check and block activation of old pro plugin
	 *
	 * Intercepts plugin activation before the plugin loads
	 *
	 * @param mixed $value The new value.
	 * @param mixed $old_value The old value.
	 *
	 * @return mixed
	 */
	public static function check_pro_activation( $value, $old_value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		$pro_plugin_slug = self::get_pro_plugin_slug();

		if ( ! $pro_plugin_slug ) {
			return $value;
		}

		// Check if wpcafe-pro is being activated
		if ( in_array( $pro_plugin_slug, $value, true ) && ! in_array( $pro_plugin_slug, $old_value, true ) ) {
			if ( self::is_old_pro_version() ) {
				$value = array_filter( $value, function( $plugin ) use ( $pro_plugin_slug ) {
					return $pro_plugin_slug !== $plugin;
				});

				// Store the incompatibility flag to show notice on next page load
				set_transient( 'wpcafe_pro_incompatible', 'blocked', 30 );
			}
		}

		return $value;
	}

	/**
	 * Initialize notice display
	 *
	 * @return void
	 */
	public static function init_notice(): void {
		// Check if there's an incompatibility flag
		$incompatible = get_transient( 'wpcafe_pro_incompatible' );

		if ( ! $incompatible ) {
			return;
		}

		// Show the notice on admin_notices hook
		add_action( 'admin_notices', [ __CLASS__, 'show_incompatibility_notice' ], 10 );
	}

	/**
	 * Show incompatibility notice
	 *
	 * @return void
	 */
	public static function show_incompatibility_notice(): void {
		?>
		<div class="notice notice-error is-dismissible">
			<p>
				<?php
				echo '<strong>' . esc_html__( 'WPCafe Pro cannot be activated.', 'wp-cafe' ) . '</strong><br>';
				esc_html_e( 'Your current version (2.x) is not compatible with WPCafe 3.0 or higher.', 'wp-cafe' );
				esc_html_e( 'Please update WPCafe Pro to version 3.0.0 or higher to activate all Pro features.', 'wp-cafe' );
				?>
			</p>
		</div>
		<?php

		// Clear the transient after displaying
		delete_transient( 'wpcafe_pro_incompatible' );
	}

	/**
	 * Check if the pro plugin is an old version
	 *
	 * @return bool
	 */
	private static function is_old_pro_version(): bool {
		$pro_plugin_slug = self::get_pro_plugin_slug();

		if ( ! $pro_plugin_slug ) {
			return false;
		}

		$pro_plugin_file = WP_PLUGIN_DIR . '/' . $pro_plugin_slug;

		if ( ! file_exists( $pro_plugin_file ) ) {
			return false;
		}

		// Load plugin.php if needed
		if ( ! function_exists( 'get_file_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Get the version from the plugin file
		$plugin_data = get_file_data(
			$pro_plugin_file,
			array(
				'Version' => 'Version',
			)
		);

		$version = isset( $plugin_data['Version'] ) ? $plugin_data['Version'] : null;

		// If version is not found, it's old/broken
		if ( ! $version ) {
			return true;
		}

		// Check if version is less than 3.0.0
		return version_compare( $version, '3.0.0', '<' );
	}
}
