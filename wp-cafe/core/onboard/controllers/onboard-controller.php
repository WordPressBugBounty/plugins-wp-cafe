<?php
namespace WpCafe\Onboard\Controllers;

use WpCafe\Abstract\Base_Rest_Controller;
use WpCafe\Onboard\Onboarding;
use WP_REST_Server;

/**
 * Onboard controller
 *
 * @package WpCafe/Onboard
 */
class Onboard_Controller extends Base_Rest_Controller {
    /**
     * Endpoint namespace
     *
     * @var string
     */
    protected $namespace = 'wpcafe/v2';

    /**
     * Route name
     *
     * @var string
     */
    protected $base = 'onboard';

    /**
     * Register routes
     *
     * @return  void
     */
    public function register_routes(): void {
        register_rest_route( $this->namespace, $this->base, [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'setup_profile'],
                'permission_callback' => [$this, 'setup_profile_permissions_check'],
            ],
        ] );
    }

    /**
     * Setup bussinsess profile
     *
     * @param   WP_Rest_Request  $request  Request Object
     *
     * @return  WP_Rest_Response | WP_Error
     */
    public function setup_profile( $request ) {
        $data = json_decode( $request->get_body(), true );

        $result = Onboarding::update( $data );

        if ( is_wp_error( $result ) ) {
            return $this->error( $result->get_error_message() );
        }
        $message = __( 'Successfully updated business profile', 'wp-cafe' );

        $response = $this->prepare_item_for_response( $data, $request );

        do_action( 'wpcafe_onboard_setup_profile', $data );

        return $this->response( $response, $message );
    }

    /**
     * Check permissions for setup profile
     *
     * @param   WP_Rest_Request  $request
     *
     * @return  WP_Rest_Response | WP_Error
     */
    public function setup_profile_permissions_check( $request ): bool {
        return current_user_can( 'manage_options' );
    }

    /**
     * Prepare item for response
     *
     * @param   array  $item     Response item
     * @param   array  $request  Requested data
     *
     * @return  array            Collection of response data
     */
    public function prepare_item_for_response( $item, $request ): array {
        $data = [
            'restaurant_name'    => Onboarding::get('restaurant_name'),
            'restaurant_email'   => Onboarding::get('restaurant_email'),
            'restaurant_type'    => Onboarding::get('restaurant_type'),
            'order_type'         => Onboarding::get('order_type'),
            'enable_order_notification' => Onboarding::get('enable_order_notification'),
            'enable_order_tip'   => Onboarding::get('enable_order_tip'),
            'table_layout_configuaration'   => Onboarding::get('table_layout_configuaration'),
            'reservation_multi_slot'   => Onboarding::get('reservation_multi_slot'),
            'onboard_setup'            => Onboarding::get('onboard_setup'),
        ];

        return $data;
    }
}
