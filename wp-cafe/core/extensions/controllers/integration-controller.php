<?php
namespace WpCafe\Extensions\Controllers;

use WP_REST_Server;
use WpCafe\Abstract\Base_Rest_Controller;
use WpCafe\Settings;
use WP_Error;

/**
 * Integration_Controller class. Handles integration related REST API requests.
 *
 * @package WpCafe/Settings/Controllers
 */
class Integration_Controller extends Base_Rest_Controller {
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
    protected $rest_base = 'integrations';

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
                'permission_callback' => function () {
                    return current_user_can( 'manage_options' );
                },
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
     * Get all extensions
     *
     * @param   WP_Rest_Request  $request
     *
     * @return  WP_Rest_Response
     */
    public function get_items( $request ) {
        $integration_list = wpc_integration()->get();

        return $this->response( $integration_list );
    }

    /**
     * Enable or disable integration
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
            return $this->error( __( 'Please enter integration name', 'wp-cafe' ) );
        }

        if (  ! $status ) {
            return $this->error( __( 'Please enter status', 'wp-cafe' ) );
        }

        if ( ! in_array( $status, $statuses ) ) {
            return $this->error( __( 'Please enter status on/off', 'wp-cafe' ) );
        }

        if ( ! wpc_integration()->find( $name ) ) {
            return $this->error( __( 'Invalid integration.', 'wp-cafe' ) );
        }

        $update = wpc_integration()->update( $name, $status );

        if ( ! $update ) {
            /* translators: %s: action status (on, off, install, activate, or deactivate) */
            return $this->error( sprintf( __( 'Integration couldn\'t %s', 'wp-cafe' ), $status ) );
        }

        $response = wpc_integration()->get();

        $response['message'] = __( 'Successfully updated', 'wp-cafe' );

        return $this->response( $response );
    }
}

