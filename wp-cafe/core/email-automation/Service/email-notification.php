<?php
namespace WpCafe\Email_Automation\Service;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;
use Ens\Core\SDK;

/**
 * Email Notification Service for WP Cafe
 *
 * Handles email notifications for cafe orders and reservations
 */
class Email_Notification implements Hookable_Service_Contract {

	/**
	 * Trigger registry instance
	 *
	 * @var Trigger_Registry
	 */
	private $trigger_registry;

	/**
	 * Constructor
	 *
	 * @param Trigger_Registry|null $trigger_registry The trigger registry instance
	 */
	public function __construct( $trigger_registry = null ) {
		$this->trigger_registry = $trigger_registry ?? new Trigger_Registry();
	}

	/**
	 * Register email notification service
	 *
	 * Sets up the SDK, registers filters, and initializes email automation
	 *
	 * @return void
	 */
	public function register() {
		if ( class_exists( SDK::class ) ) {
			add_filter( 'notification_sdk_email_body', [ $this, 'wrap_email_body' ], 10, 2 );

			SDK::get_instance()
                ->setup(
                    array(
						'plugin_name'          => 'Wp Cafe',
						'plugin_slug'          => 'wp-cafe',
						'general_prefix'       => 'wpc',
						'hook_prefix'          => 'wpcafe',
						'text_domain'          => 'wp-cafe',
						'admin_script_handler' => 'wpcafe-dashboard-scripts',
						'sub_menu_filter_hook' => 'wpcafe_menu',
						'sub_menu_details'     =>
							array(
								'id'         => 'wpcafe-automation',
								'title'      => __( 'Automation', 'wp-cafe' ),
								'link'       => '/automation',
								'capability' => apply_filters( 'wpcafe_menu_permission_', 'manage_options' ),
								'position'   => apply_filters( 'wpcafe_menu_permission_', 11 ),
							),
                    )
                )
                ->init();

			add_filter( 'ens_wpc_available_actions', [ $this, 'get_available_actions' ] );
		}
	}

	/**
	 * Get available actions for the email automation SDK
	 *
	 * Retrieves all registered trigger configurations from the registry
	 *
	 * @return array
	 */
	public function get_available_actions() {
		return $this->trigger_registry->get_all_configurations();
	}

	/**
	 * Wrap email body with custom template
	 *
	 * @param string $message The email message content
	 * @param array  $data    The notification data with all dynamic values
	 * @return string The wrapped email with template
	 */
	public function wrap_email_body( $message ) {
		// Path to the email template file
		$template_path = WPCAFE_DIR . '/templates/email/reservation-created.html';

		// If template file doesn't exist, return the message as is
		if ( ! file_exists( $template_path ) ) {
			return $message;
		}
		// Get the template content
		$template = file_get_contents( $template_path );

		// Extract dynamic values from notification data
		$restaurant_name =  wpc_get_option('restaurant_name', "") ;
		$restaurant_location =  wpc_get_option('restaurant_location', array()) ;
		$restaurant_address = isset( $restaurant_location['address'] ) ? $restaurant_location['address'] : '';
		$restaurant_phone = wpc_get_option('restaurant_phone', '');
		$restaurant_email = wpc_get_option('restaurant_email', '');
		$plugin_name  = apply_filters(
			'wpcafe_plugin_name',
			WPCAFE_PLUGIN_NAME
		);
		$show_powered_by = apply_filters( 'wpcafe_show_email_powered_by', true );

		// Build powered-by HTML if enabled
		$powered_by_html = '';
		if ( $show_powered_by ) {
			$powered_by_html = '<p class="wpc-powered-by">Powered by ' . esc_html( $plugin_name ) . '</p>';
		}

		// Prepare variables for replacement
		$variables = array(
			'{{MESSAGE}}'                     => wp_kses_post( $message ),
			'{%reservation_branch_name%}'     => esc_html( $restaurant_name ),
			'{%reservation_branch_address%}'  => esc_html( $restaurant_address ),
			'{%restaurant_phone%}'    => esc_html( $restaurant_phone ),
			'{%restaurant_email%}'    => esc_html( $restaurant_email ),
			'{%powered_by_section%}'  => $powered_by_html,
		);

		// Replace placeholders with actual values
		return str_replace( array_keys( $variables ), array_values( $variables ), $template );
	}
}
