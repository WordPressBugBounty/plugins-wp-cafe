<?php
namespace WpCafe\Settings\Controllers;

use WP_REST_Server;
use WpCafe\Abstract\Base_Rest_Controller;
use WpCafe\Settings;

/**
 * Settings_Controller class. Handles settings related REST API requests.
 *
 * @package WpCafe/Settings/Controllers
 */
class Settings_Controller extends Base_Rest_Controller {
    /**
     * Setting keys safe to expose publicly (no webhook URLs, credentials, etc.).
     */
    private const PUBLIC_SETTING_KEYS = [
        'reservation_form_customization',
        'reservation_maximum_guest',
        'reservation_minimum_guest',
        'enable_custom_holiday',
        'custom_holidays',
        'calendar_language',
        'wc_status',
        'reservation_form_button_text',
        'reservation_confirmation_button_text',
        'reservation_cancellation_button_text',
        'primary_color',
        'secondary_color',
        'require_location',
        'display_location_selector',
        'location_selector_pages',
        'enable_floating_location_widget',
        'reservation_booking_amount',
        'multiply_booking_amount_with_guests',
        'reservation_partial_payment',
        'restaurant_type',
        'reservation_status',
        'enable_reservation_pending_message',
        'reservation_pending_message',
        'enable_reservation_confirmed_message',
        'reservation_confirmed_message',
        'block_timeslot_statuses',
        'slot_interval',
        'restaurant_schedule',
        'enable_local_payment',
        'enable_woocommerce_payments',

        // Currency display formatting — non-sensitive; required by the front-end
        // price renderer (reservation form + restaurant-management panel).
        'currency',
        'currency_symbol_position',
        'currency_price_separator',
        'currency_decimals',
    ];

    /**
     * Integration credentials / webhook URLs. Stripped from the response for
     * any caller without `manage_options`.
     */
    private const SENSITIVE_SETTING_KEYS = [
        'whatsapp_token',
        'whatsapp_facebook_app_id',
        'whatsapp_facebook_app_secret',
        'whatsapp_from_number_id',
        'whatsapp_business_account_id',
        'whatsapp_admin_number',
        'fluentcrm_webhook_url',
    ];

    /**
     * Store the namespace for the REST API.
     *
     * @var string
     */
    protected $namespace = 'wpcafe/v2';

    /**
     * Store the REST base for the API.
     *
     * @var string
     */
    protected $rest_base = 'settings';

    /**
     * Register the REST routes for settings.
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => [ $this, 'get_settings' ],
                    'permission_callback' => [ $this, 'get_settings_check_permissions' ],
                ],
                [
                    'methods'  => WP_REST_Server::EDITABLE,
                    'callback' => [ $this, 'update_settings' ],
                    'permission_callback' => [ $this, 'update_settings_check_permissions' ],
                ]
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/public',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_public_settings' ],
                'permission_callback' => [ $this, 'get_public_settings_permissions_check' ],
            ]
        );
    }

    /**
     * Get settings.
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function get_settings( $request ) {
        $settings = Settings::get();

        $settings = apply_filters( 'wpcafe_settings', $settings );
        $settings = is_array( $settings ) ? $settings : [];

        $settings = $this->filter_sensitive_settings( $settings );

        return $this->response( $settings );
    }

    /**
     * Gate the full settings payload (which includes credentials) to store
     * admins. The per-user panel caps (`wpcafe_view_own_*`) are deliberately
     * excluded: they are granted to every subscriber/customer on activation, so
     * trusting them would expose credentials. Panel views use `/settings/public`.
     *
     * Must return a strict bool — WordPress treats any non-`WP_Error`/non-false
     * return (e.g. `$this->error()`) as "granted", so do not return that here.
     *
     * @param \WP_REST_Request|null $request The request object.
     * @return bool
     */
    public function get_settings_check_permissions( $request = null ) {
        $can_read = current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' );
        $can_read = (bool) apply_filters( 'wpcafe_settings_read_permission', $can_read, $request );

        if ( ! $can_read ) {
            return false;
        }

        if ( $request instanceof \WP_REST_Request && ! $this->verify_rest_nonce( $request ) ) {
            return false;
        }

        return true;
    }

    /**
     * Remove credential keys from the payload unless the caller is a full
     * administrator. Defense-in-depth so secrets never reach non-admins.
     *
     * @param  array $settings Full settings payload.
     * @return array
     */
    private function filter_sensitive_settings( array $settings ): array {
        $expose_secrets = (bool) apply_filters( 'wpcafe_settings_expose_sensitive', current_user_can( 'manage_options' ) );

        if ( $expose_secrets ) {
            return $settings;
        }

        $sensitive_keys = apply_filters( 'wpcafe_sensitive_setting_keys', self::SENSITIVE_SETTING_KEYS );
        $sensitive_keys = is_array( $sensitive_keys ) ? $sensitive_keys : self::SENSITIVE_SETTING_KEYS;

        return array_diff_key( $settings, array_flip( $sensitive_keys ) );
    }

    /**
     * Permission check for public settings endpoint.
     * Intentionally public — only keys in PUBLIC_SETTING_KEYS are exposed.
     *
     * @return bool
     */
    public function get_public_settings_permissions_check(): bool {
        return true;
    }

    /**
     * Get public (whitelisted) settings safe for unauthenticated access.
     *
     * @param \WP_REST_Request $request
     * @return \WP_HTTP_Response
     */
    public function get_public_settings( $request ) {
        $all_settings = Settings::get();
        $public_keys  = apply_filters( 'wpcafe_public_setting_keys', self::PUBLIC_SETTING_KEYS );
        $public       = array_intersect_key( $all_settings, array_flip( $public_keys ) );

        /*
         * Hand the reservation form just enough to show the deposit: whether it is
         * on, and Deposet's rate and type. The form does the same sum itself so the
         * amount updates live as the guest count changes, without asking the server
         * each time. The real deposit is still worked out on the server when the
         * reservation is created, so these values are only for display.
         */
        if ( wpc_is_deposet_active() && ! empty( $all_settings['reservation_partial_payment'] ) ) {
            $public['wpc_deposit'] = [
                'enabled' => true,
                'type'    => get_option( 'deposet_type', 'percentage' ),
                'amount'  => (float) get_option( 'deposet_amount', '50' ),
            ];
        } else {
            $public['wpc_deposit'] = [ 'enabled' => false ];
        }

        return $this->response( $public );
    }

    /**
     * Update settings.
     *
     * @return \WP_REST_Response
     */
    public function update_settings( $request ) {
        $params = $request->get_params();

        // Strip WP REST API internal parameters — not settings data.
        foreach ( [ '_locale', '_fields', '_embed', '_jsonp', '_method' ] as $internal ) {
            unset( $params[ $internal ] );
        }

        Settings::update( $params );

        return $this->get_item( $request );
    }

    /**
     * Check permissions for updating settings.
     *
     * @return bool
     */
    public function update_settings_check_permissions( $request ) {
        return current_user_can( 'manage_options' );
    }

    /**
     * Get a collection of items.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_item( $request ) {
        $settings = Settings::get();

        $settings = apply_filters( 'wpcafe_settings', $settings );
        $settings = is_array( $settings ) ? $settings : [];

        $settings = $this->filter_sensitive_settings( $settings );

        return $this->response( $settings );
    }
}