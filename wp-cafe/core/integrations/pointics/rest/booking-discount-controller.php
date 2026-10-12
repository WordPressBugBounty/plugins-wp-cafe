<?php
namespace WpCafe\Integrations\Pointics;

if ( ! defined( 'ABSPATH' ) ) exit;

use WP_REST_Server;
use WpCafe\Abstract\Base_Rest_Controller;

/**
 * What a points code takes off the booking being filled in, for the live total.
 *
 * Prices against the same server total the booking is saved at, so the figure
 * shown is the figure charged.
 *
 * @package WpCafe/Integrations/Pointics
 */
class Booking_Discount_Controller extends Base_Rest_Controller {
    /** @var string */
    protected $namespace = 'wpcafe/v2';

    /** @var string */
    protected $rest_base = 'pointics/booking-discount';

    /**
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'quote' ],
                'permission_callback' => [ $this, 'permissions_check' ],
                'args'                => [
                    'code'        => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'total_guest' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'minimum'           => 1,
                        'maximum'           => 500,
                        'validate_callback' => 'rest_validate_request_arg',
                        'sanitize_callback' => 'absint',
                    ],
                    'branch_id'   => [
                        'required'          => false,
                        'type'              => 'integer',
                        'default'           => 0,
                        'minimum'           => 0,
                        'validate_callback' => 'rest_validate_request_arg',
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ]
        );
    }

    /**
     * The customer's own held code: signed in, and the request is theirs.
     *
     * @param \WP_REST_Request $request Request.
     * @return true|\WP_Error
     */
    public function permissions_check( $request ) {
        if ( ! is_user_logged_in() ) {
            return new \WP_Error( 'wpcafe_forbidden', __( 'Sign in to use your points.', 'wp-cafe' ), [ 'status' => 401 ] );
        }

        if ( ! $this->verify_rest_nonce( $request ) ) {
            return new \WP_Error( 'wpcafe_invalid_nonce', __( 'Invalid security token.', 'wp-cafe' ), [ 'status' => 403 ] );
        }

        return true;
    }

    /**
     * @param \WP_REST_Request $request Request.
     * @return \WP_HTTP_Response|\WP_Error
     */
    public function quote( $request ) {
        $input  = Reservation_Points_Calculator::input_for_cart( (int) $request['total_guest'], (int) $request['branch_id'] );
        $total  = (float) $input['booking_amount'] + (float) $input['food_amount'];
        $priced = ( new Booking_Points_Discount() )->price( (string) $request['code'], get_current_user_id(), $total );

        if ( is_wp_error( $priced ) ) {
            return $priced;
        }

        return $this->response(
            [
                'code'            => $priced['code'],
                'discount_amount' => $priced['discount'],
                // The full value, so the form can say how much of a capped reward goes unused.
                'value'           => $priced['value'],
                'method'          => $priced['method'],
                'new_total'       => max( 0.0, $total - $priced['discount'] ),
                'points'          => $priced['points'],
            ]
        );
    }
}
