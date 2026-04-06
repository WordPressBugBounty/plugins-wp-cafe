<?php
namespace WpCafe\Upgrades;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;

/**
 * Detects fresh installs vs upgrades from v2.x
 *
 * @package WpCafe/Upgrades
 */
class Install_Detector implements Hookable_Service_Contract {
	/**
	 * Stores the version at which the plugin was first installed
	 */
	const FIRST_INSTALLED_VERSION_OPTION = 'wpcafe_install_fingerprint';

	/**
	 * Option key for v2.x upgrade detection flag. Set to true when a v2.x to v3.x upgrade is detected
	 */
	const V2_DETECTED_OPTION = 'wpcafe_v2_upgrade_detected';

	/**
	 * Register hooks
	 *
	 * @return void
	 */
	public function register() {
		if ( did_action( 'plugins_loaded' ) ) {
			$this->detect_installation_type();
		} else {
			add_action( 'plugins_loaded', [ $this, 'detect_installation_type' ], 5 );
		}
	}

	/**
	 * Detect installation type: fresh install vs upgrade
	 *
	 * @return void
	 */
	public function detect_installation_type() {
		$first_installed_version = get_option( self::FIRST_INSTALLED_VERSION_OPTION );
		$stored_version          = get_option( 'wpc_cafe_version' );

		if ( $first_installed_version && version_compare( $first_installed_version, '3.0.0', '<' ) ) {
			$this->mark_as_v2_upgrade();
			return;
		}

		if ( ! $first_installed_version ) {
			if ( $stored_version && version_compare( $stored_version, '3.0.0', '<' ) ) {
				$this->mark_as_v2_upgrade();
				$this->set_first_installed_version( $stored_version );
			} elseif ( $this->has_v2_data() ) {
				$this->mark_as_v2_upgrade();
				$this->set_first_installed_version( '2.x' );
			}
		}
	}

	/**
	 * Check if any v2.x data exists in the database
	 *
	 * @return bool True if v2.x data found
	 */
	private function has_v2_data() {
		// Check for v2-specific options that would only exist in v2 installations
		$v2_options = [
			// Reservation settings
			'wpc_weekly_schedule',
			'reser_multi_schedule',
			'wpc_all_day_start_time',
			'multi_start_time',
			'weekly_multi_diff_times',
			// Pickup/Delivery
			'wpc_pickup_weekly_schedule',
			'wpc_delivery_schedule',
			'wpc_pro_pickup_message',
			'wpc_pro_allow_pickup_date',
			'wpc_pro_allow_delivery_date',
			// Module settings
			'wpc_pro_discount_enable',
			'wpc_pro_tip_enable',
			'allow_mini_cart',
			// Table layout
			'wpc_table_layout',
		];

		foreach ( $v2_options as $option ) {
			if ( wpc_get_option( $option ) !== false ) {
				return true;
			}
		}

		// Check if any reservations exist in the custom post type
		$reservations = get_posts( [
			'post_type'      => 'wpc_reservation',
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		] );

		if ( ! empty( $reservations ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Mark this installation as a v2.x upgrade
	 *
	 * @return void
	 */
	private function mark_as_v2_upgrade() {
		update_option( self::V2_DETECTED_OPTION, true );
	}

	/**
	 * Set the first installed version
	 *
	 * @param string $version Version to store as first installed version
	 * @return void
	 */
	private function set_first_installed_version( $version ) {
		update_option( self::FIRST_INSTALLED_VERSION_OPTION, $version );
	}

	/**
	 * Check if this is a v2.x upgrade
	 *
	 * @return bool True if this is a v2.x to v3.x upgrade
	 */
	public static function is_v2_upgrade() {
		return (bool) get_option( self::V2_DETECTED_OPTION, false );
	}
}
