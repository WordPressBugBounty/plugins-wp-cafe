<?php
/**
 * Manages WPCafe Pro plugin compatibility and activation
 *
 * @package WpCafe/Base
 */

namespace WpCafe;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Compatibility_Handler
 *
 * Coordinates compatibility checks and version guards for dependent plugins
 */
class Compatibility_Handler {

	/**
	 * Initialize the pro plugin manager
	 *
	 * @return void
	 */
	public static function init(): void {
		self::check_compatibility();
		self::block_old_pro_activation();
	}

	/**
	 * Check pro plugin compatibility and deactivate if needed
	 *
	 * @return void
	 */
	public static function check_compatibility(): void {
		// Load plugin.php if needed
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		Plugin_Compatibility_Manager::check();
	}

	/**
	 * Initialize the pro activation blocker
	 *
	 * @return void
	 */
	private static function block_old_pro_activation(): void {
		Version_Guard::init();
	}

	/**
	 * Register hooks for pro plugin checks
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_action( 'admin_init', [ __CLASS__, 'show_notices' ], 1 );
		add_action( 'init', [ __CLASS__, 'check_compatibility' ], 0 );
	}

	/**
	 * Show any pending notices
	 *
	 * @return void
	 */
	public static function show_notices(): void {
		Version_Guard::init_notice();
	}
}
