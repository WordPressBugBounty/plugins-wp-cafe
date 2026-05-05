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
        'reservation_booking_amount',
        'multiply_booking_amount_with_guests',
        'restaurant_type',
        'reservation_status',
        'block_timeslot_statuses',
        'slot_interval',
        'restaurant_schedule',
        'enable_local_payment',
        'enable_woocommerce_payments',
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

        return $this->response( $settings );
    }

    /**
     * Check permissions for accessing settings.
     *
     * @return bool
     */
    public function get_settings_check_permissions() {
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        // Allow any user with a restaurant panel capability to read settings.
        // The reservation form (and other panel views) needs settings such as
        // reservation_form_customization to render correctly. Update is still
        // restricted to manage_options via update_settings_check_permissions().
        $panel_caps = [
            'manage_woocommerce',
            'wpcafe_view_own_orders',
            'wpcafe_view_all_orders',
            'wpcafe_manage_orders',
            'wpcafe_view_own_reservations',
            'wpcafe_view_all_reservations',
            'wpcafe_manage_reservations',
        ];

        foreach ( $panel_caps as $cap ) {
            if ( current_user_can( $cap ) ) {
                return true;
            }
        }

        return false;
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

        return $this->response( $settings );
    }
}