<?php
namespace WpCafe\Extensions\Controllers;

use Arraytics\ToolsSdk\PluginManager;
use WP_REST_Server;
use WpCafe\Abstract\Base_Rest_Controller;
use WpCafe\Settings;
use WP_Error;

/**
 * Extension_Controller class. Handles extension related REST API requests.
 *
 * @package WpCafe/Settings/Controllers
 */
class Extension_Controller extends Base_Rest_Controller {
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
    protected $rest_base = 'extentions';

    /**
     * Register routes
     *
     * @return void
     */
    public function register_routes() {
        /*
         * Register route
         */
        register_rest_route( $this->namespace, $this->rest_base, [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_items'],
                'permission_callback' => [ $this, 'read_permission_check' ],
            ],
        ] );

        register_rest_route( $this->namespace, $this->rest_base, [
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'update_item'],
                'permission_callback' => function () {
                    return current_user_can( 'manage_options' );
                },
            ],
        ] );

    }

    /**
     * Permission check for read endpoint. Allow admins, dokan vendors, and any
     * user with restaurant panel access capabilities (manager, staff, customer).
     *
     * @return bool
     */
    public function read_permission_check(): bool {
        if ( current_user_can( 'manage_options' ) || wpc_user_is_dokan_vendor() ) {
            return true;
        }

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
     * Get all extensions
     *
     * @param   WP_Rest_Request  $request
     *
     * @return  WP_Rest_Response
     */
    public function get_items( $request ) {
        $type = ! empty( $request['type'] ) ? $request['type'] : 'all';

        $types = [
            'module'      => wpcafe_extension()->get_modules(),
            'addon'       => wpcafe_extension()->get_addons(),
            'plugin'      => wpcafe_extension()->get_plugins(),
            'all'         => wpcafe_extension()->get(),
            'our-plugins' => $this->get_our_plugins_with_status(),
        ];

        if ( ! array_key_exists( $type, $types ) ) {
            return $this->error( __( 'Invalid type provided', 'wp-cafe' ) );
        }

        return $this->response( $types[$type] );
    }

    /**
     * Returns sibling Arraytics plugins with live install/activate status resolved via PluginManager.
     *
     * @return array
     */
    private function get_our_plugins_with_status(): array {
        $plugins = wpcafe_our_plugins_list();

        foreach ( $plugins as $key => &$plugin ) {
            if ( PluginManager::is_activated( $key ) ) {
                $plugin['status'] = 'deactivate';
            } elseif ( PluginManager::is_installed( $key ) ) {
                $plugin['status'] = 'activate';
            } else {
                $plugin['status'] = 'install';
            }
        }
        unset( $plugin );

        return array_values( $plugins );
    }

    /**
     * Enable or disable extension
     *
     * @param   WP_Rest_Request  $request  [$request description]
     *
     * @return  WP_Response | WP_Error
     */
    public function update_item( $request ) {
        $input_data = json_decode( $request->get_body(), true );

        $name   = ! empty( $input_data['name'] ) ? sanitize_text_field( $input_data['name'] ) : '';
        $status = ! empty( $input_data['status'] ) ? sanitize_text_field( $input_data['status'] ) : '';

        $statuses = ['off', 'on', 'install', 'activate', 'deactivate'];

        if ( ! $name ) {
            return $this->error( __( 'Please enter extension name', 'wp-cafe' ) );
        }

        if (  ! $status ) {
            return $this->error( __( 'Please enter status', 'wp-cafe' ) );
        }

        if ( ! in_array( $status, $statuses ) ) {
            return $this->error( __( 'Please enter status on/off', 'wp-cafe' ) );
        }

        if ( ! wpcafe_extension()->find( $name ) ) {
            return $this->error( __( 'Invalid extension.', 'wp-cafe' ) );
        }

        $update = wpcafe_extension()->update( $name, $status );

        $parent = wpcafe_extension()->find_parent( $name );

        if ( $parent ) {
            $parent_status = wpcafe_extension()->find( $parent )['status'];

            if ( $status === 'on' ) {
                wpcafe_extension()->update( $parent, 'on' );
            }
        }

        if ( is_wp_error( $update ) ) {
            return $this->error( wp_strip_all_tags( $update->get_error_message() ) );
        }

        if ( ! $update ) {
            /* translators: %s: action status (on, off, install, activate, or deactivate) */
            return $this->error( sprintf( __( 'Extension couldn\'t %s', 'wp-cafe' ), $status ) );
        }
        $this->handle_special_cases_for_module_enabling($name, $status);

        $response = wpcafe_extension()->get();

        $response['message'] = __( 'Successfully updated', 'wp-cafe' );

        return $this->response( $response );
    }

    /**
     * On enabling pickup and delivery module auto enable their related settings .
     */
    private function handle_special_cases_for_module_enabling($name, $status) {
        if ( $name == 'pickup' && $status == 'on') {
            wpc_update_option( 'pickup_show_date_in_checkout_page',true );
            wpc_update_option( 'pickup_show_time_in_checkout_page',true );
        }

        if ( $name == 'delivery' && $status == 'on' ) {
            wpc_update_option( 'delivery_show_date_in_checkout_page', true );
            wpc_update_option( 'delivery_show_time_in_checkout_page', true );    
        }
    }
}

