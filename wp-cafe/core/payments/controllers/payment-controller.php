<?php
namespace WpCafe\Payments\Controllers;

use WpCafe\Abstract\Base_Rest_Controller;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Server;
use WpCafe\Payments\Payment_Processor;
use WpCafe\Models\Reservation_Model;

/**
 * Payment controller
 *
 * Handles all REST API endpoints for payments.
 *
 * @package WpCafe/Payments
 */
class Payment_Controller extends Base_Rest_Controller {
    /**
     * Endpoint namespace
     *
     * @var string
     */
    protected $namespace = 'wpcafe/v2';

    /**
     * Route base
     *
     * @var string
     */
    protected $rest_base = 'payments';

    /**
     * Register all routes related to payment
     *
     * @return void
     */
    public function register_routes(): void {

        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'create_item'],
                'permission_callback' => [$this, 'create_item_permissions_check'],
            ],
        ]);
    }

    /**
     * Check user permissions
     *
     * @param \WP_REST_Request $request
     * @return bool
     */
    public function permissions_check($request): bool {
        return true;
    }

    /**
     * Create a new payment
     *
     * @param \WP_REST_Request $request
     * @return WP_HTTP_Response|WP_Error
     */
    public function create_item($request) {
        $data           = json_decode( $request->get_body(), true );
        $payment_method = sanitize_text_field( $data['payment_method'] ?? '' );
        $reservation_id = absint( $data['reservation_id'] ?? 0 );

        if ( empty( $payment_method ) ) {
            return $this->error( __( 'Payment method is required', 'wp-cafe' ) );
        }

        if ( empty( $reservation_id ) ) {
            return $this->error( __( 'Reservation ID is required', 'wp-cafe' ) );
        }

        $reservation = Reservation_Model::find( $reservation_id );
        if ( ! $reservation ) {
            return $this->error( __( 'Reservation not found.', 'wp-cafe' ), 404 );
        }

        $current_user_id = get_current_user_id();
        if ( $current_user_id ) {
            if ( (int) $reservation->user_id !== $current_user_id ) {
                return $this->error( __( 'You do not have permission to pay for this reservation.', 'wp-cafe' ), 403 );
            }
        } else {
            $session_data        = ( function_exists( 'WC' ) && WC()->session )
                ? WC()->session->get( 'wpc_reservation_data' )
                : null;
            $session_reservation = isset( $session_data['reservation_id'] )
                ? (int) $session_data['reservation_id']
                : 0;

            if ( $session_reservation !== $reservation_id ) {
                return $this->error( __( 'You do not have permission to pay for this reservation.', 'wp-cafe' ), 403 );
            }
        }

        $payment_procce = new Payment_Processor( $payment_method );
        $response       = $payment_procce->process_payment( [
            'reservation_id' => $reservation_id,
        ] );

        return $this->response( $response );
    }

    /**
     * Check if a given request has access to create items.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     */
    public function create_item_permissions_check( $request ) {
        return $this->verify_rest_nonce( $request );
    }
}
