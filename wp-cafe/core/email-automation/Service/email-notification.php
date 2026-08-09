<?php
namespace WpCafe\Email_Automation\Service;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Models\Reservation_Model;
use WpCafe\Reservation\Email\Handlers\Reservation_Email_Handler;
use Ens\Core\SDK;

/**
 * Email Notification Service for WP Cafe
 *
 * Handles email notifications for cafe orders and reservations
 */
class Email_Notification implements Hookable_Service_Contract {

	/**
	 * REST route prefix the bundled email-notification SDK registers its flow
	 * endpoints under: plugin_slug ('wp-cafe') . '/v1/' . rest_base
	 * ('notification-flow'). Kept here so the permission lock-down below targets
	 * exactly those routes and nothing else.
	 */
	private const FLOW_ROUTE_PREFIX = '/wp-cafe/v1/notification-flow';

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
			// The SDK applies its body filter prefixed with the configured hook_prefix
			// ('wpcafe'), i.e. `wpcafe_notification_sdk_email_body`. Hooking the bare
			// name silently never fired, so no automation email got the branded
			// wrapper. Match the prefixed name so wrapping actually applies.
			add_filter( 'wpcafe_notification_sdk_email_body', [ $this, 'wrap_email_body' ], 10, 1 );
			add_filter( 'wpcafe_notification_sdk_email_message', [ $this, 'replace_reservation_custom_fields_placeholder' ], 10, 5 );
			add_filter( 'wpcafe_ens_whatsapp_credentials', [ $this, 'map_whatsapp_credentials' ] );
			add_action( 'wpcafe_ens_whatsapp_send_error', [ $this, 'log_whatsapp_error' ], 10, 4 );
			add_action( 'wpcafe_ens_whatsapp_request',    [ $this, 'log_whatsapp_request' ], 10, 4 );
			add_action( 'wpcafe_ens_whatsapp_send_success', [ $this, 'log_whatsapp_success' ], 10, 4 );

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
			add_filter( 'rest_endpoints', [ $this, 'secure_notification_flow_endpoints' ] );
		}
	}

	/**
	 * Restrict the SDK's notification-flow REST routes to administrators.
	 *
	 * @param array $endpoints Map of route regex => one handler or a list of handlers.
	 * @return array
	 */
	public function secure_notification_flow_endpoints( $endpoints ) {
		if ( ! is_array( $endpoints ) ) {
			return $endpoints;
		}

		foreach ( $endpoints as $route => $handlers ) {
			if ( strpos( $route, self::FLOW_ROUTE_PREFIX ) !== 0 || ! is_array( $handlers ) ) {
				continue;
			}

			if ( isset( $handlers['callback'] ) || isset( $handlers['permission_callback'] ) ) {
				$endpoints[ $route ]['permission_callback'] = [ self::class, 'check_notification_flow_permission' ];
				continue;
			}

			foreach ( $handlers as $index => $handler ) {
				if ( is_array( $handler ) && ( isset( $handler['callback'] ) || isset( $handler['permission_callback'] ) ) ) {
					$endpoints[ $route ][ $index ]['permission_callback'] = [ self::class, 'check_notification_flow_permission' ];
				}
			}
		}

		return $endpoints;
	}

	/**
	 * Permission gate for the notification-flow REST routes.
	 *
	 * @param \WP_REST_Request|null $request Current request (unused; kept for the callback signature).
	 * @return true|\WP_Error True when allowed, WP_Error otherwise.
	 */
	public static function check_notification_flow_permission( $request = null ) {
		$can = current_user_can( 'manage_options' );
		$can = (bool) apply_filters( 'wpcafe_notification_flow_permission', $can, $request );

		if ( ! $can ) {
			return new \WP_Error(
				'wpcafe_rest_forbidden',
				__( 'You do not have permission to manage email notification flows.', 'wp-cafe' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
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
	 * Map wp-cafe's WhatsApp options into the SDK credentials shape.
	 *
	 * Iterates each `whatsapp_*` option via `wpc_get_option()` and maps it to
	 * the credential key the SDK's MetaCloudProvider expects.
	 *
	 * @param array $creds Default credentials from SDK option store.
	 * @return array
	 */
	public function map_whatsapp_credentials( $creds ) {
		if ( ! is_array( $creds ) ) {
			$creds = [];
		}

		$option_map = [
			'whatsapp_token'              => 'access_token',
			'whatsapp_from_number_id'     => 'phone_number_id',
			'whatsapp_business_account_id' => 'business_id',
		];

		$mapped = [];
		foreach ( $option_map as $option_key => $cred_key ) {
			$value = wpc_get_option( $option_key, '' );
			if ( ! empty( $value ) ) {
				$mapped[ $cred_key ] = $value;
			}
		}

		return array_merge( $creds, $mapped );
	}

	/**
	 * Log WhatsApp transport errors so admins can diagnose Meta-side failures.
	 *
	 * @param mixed  $error_body Raw response body or error message.
	 * @param string $to         Normalized recipient.
	 * @param array  $payload    Request payload sent to Meta.
	 * @param int    $http_code  HTTP status (0 for transport/setup errors).
	 */
	public function log_whatsapp_error( $error_body, $to, $payload, $http_code ) {
		$error_string = is_string( $error_body ) ? $error_body : wp_json_encode( $error_body );

		error_log( sprintf(
			'[wpcafe whatsapp] send failed (http=%d, to=%s): %s',
			(int) $http_code,
			$to,
			$error_string
		) );

		$recent = get_transient( 'wpcafe_whatsapp_recent_errors' );
		if ( ! is_array( $recent ) ) {
			$recent = [];
		}
		array_unshift( $recent, [
			'time'  => time(),
			'to'    => $to,
			'code'  => (int) $http_code,
			'error' => $error_string,
		] );
		$recent = array_slice( $recent, 0, 20 );
		set_transient( 'wpcafe_whatsapp_recent_errors', $recent, DAY_IN_SECONDS );
	}

	/**
	 * Debug-only log of the outbound Meta request so the payload can be
	 *
	 * @param string $endpoint     Full Graph API URL.
	 * @param array  $payload      Payload that will be sent (already filtered).
	 * @param string $message_type `text` or `template`.
	 * @param string $to           Normalized recipient.
	 */
	public function log_whatsapp_request( $endpoint, $payload, $message_type, $to ) {
		if ( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
			return;
		}
		error_log( sprintf(
			'[wpcafe whatsapp] request type=%s to=%s payload=%s',
			$message_type,
			$to,
			wp_json_encode( $payload )
		) );
	}

	/**
	 * Debug-only log of a successful Meta response so the message id
	 *
	 * @param string $response_body Meta API response body.
	 * @param string $to            Normalized recipient.
	 * @param array  $payload       Request payload sent to Meta.
	 * @param int    $http_code     HTTP status.
	 */
	public function log_whatsapp_success( $response_body, $to, $payload, $http_code ) {
		if ( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
			return;
		}
		error_log( sprintf(
			'[wpcafe whatsapp] sent ok (http=%d, to=%s) response=%s',
			(int) $http_code,
			$to,
			is_string( $response_body ) ? $response_body : wp_json_encode( $response_body )
		) );
	}

	/**
	 * Reservation trigger names that may carry the custom-fields placeholder.
	 *
	 * @return array<string>
	 */
	private function get_reservation_trigger_actions() {
		return array(
			'reservation_created',
			'reservation_confirmed',
			'reservation_pending',
			'reservation_updated',
			'reservation_cancelled',
		);
	}

	/**
	 * Replace the reservation custom-fields placeholder with a receiver-specific table.
	 *
	 * The placeholder is left untouched by the SDK's scalar placeholder engine.
	 * This filter runs once per email recipient and swaps it for the formatted
	 * HTML table, using different empty-state text for admin vs customer.
	 *
	 * @param string $message      The message content after SDK placeholder replacement.
	 * @param string $receiver_type Receiver type (e.g. customer_email, admin_email).
	 * @param string $action_name  The trigger action name.
	 * @param array  $action_data  The notification data array.
	 * @param int    $count        Email index.
	 * @return string
	 */
	public function replace_reservation_custom_fields_placeholder( $message, $receiver_type, $action_name, $action_data, $count ) {
		if ( ! is_string( $message ) || '' === $message ) {
			return $message;
		}

		$placeholder = Reservation_Email_Handler::CUSTOM_FIELDS_PLACEHOLDER;
		if ( strpos( $message, $placeholder ) === false ) {
			return $message;
		}

		if ( ! in_array( $action_name, $this->get_reservation_trigger_actions(), true ) ) {
			return $message;
		}

		$reservation_id = absint( $action_data['reservation_id'] ?? 0 );
		$formatted      = '';

		if ( $reservation_id ) {
			try {
				$reservation = Reservation_Model::find( $reservation_id );
				if ( $reservation instanceof Reservation_Model ) {
					$handler   = new Reservation_Email_Handler();
					$formatted = $handler->format_custom_fields_for_email_html( $reservation );
				}
			} catch ( \Exception $e ) {
				$formatted = '';
			}
		}

		$is_admin = 'admin_email' === $receiver_type;

		if ( '' === $formatted ) {
			$replacement = $is_admin
				? esc_html__( 'No extra field added', 'wp-cafe' )
				: '';
		} else {
			$replacement = $formatted;
		}

		return str_replace( $placeholder, $replacement, $message );
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
