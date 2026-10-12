<?php
namespace WpCafe\Integrations\Pointics;

if ( ! defined( 'ABSPATH' ) ) exit;

use WP_REST_Server;
use WpCafe\Abstract\Base_Rest_Controller;
use WpCafe\Models\Reservation_Model;

/**
 * "Reward points" block in the admin booking dialog.
 *
 * @package WpCafe/Integrations/Pointics
 */
class Reservation_Points_Admin_Controller extends Base_Rest_Controller {
    /** @var string */
    protected $namespace = 'wpcafe/v2';

    /** @var string */
    protected $rest_base = 'pointics/reservations';

    /**
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)/points',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_points' ],
                'permission_callback' => [ $this, 'get_points_permissions_check' ],
            ]
        );
    }

    /**
     * Staff only. Returns true or WP_Error; never a WP_HTTP_Response, which WordPress would read as "granted".
     *
     * @param \WP_REST_Request $request Request.
     * @return true|\WP_Error
     */
    public function get_points_permissions_check( $request ) {
        $can = current_user_can( 'manage_options' )
            || current_user_can( 'manage_woocommerce' )
            || current_user_can( 'wpcafe_view_all_reservations' )
            || current_user_can( 'wpcafe_manage_reservations' );

        // Cast: a non-WP_Error object from a filter would otherwise read as "granted".
        $can = true === apply_filters( 'wpcafe_pointics_admin_points_permission', $can, $request );

        if ( ! $can ) {
            return new \WP_Error( 'wpcafe_forbidden', __( 'You do not have permission to view booking points.', 'wp-cafe' ), [ 'status' => 403 ] );
        }

        if ( ! $this->verify_rest_nonce( $request ) ) {
            return new \WP_Error( 'wpcafe_invalid_nonce', __( 'Invalid nonce.', 'wp-cafe' ), [ 'status' => 403 ] );
        }

        return true;
    }

    /**
     * @param \WP_REST_Request $request Request.
     * @return \WP_HTTP_Response
     */
    public function get_points( $request ) {
        $id = absint( $request['id'] );

        if ( 'wpc_reservation' !== get_post_type( $id ) ) {
            return $this->error( __( 'Reservation not found.', 'wp-cafe' ), 404 );
        }

        $reservation = Reservation_Model::find( $id );
        $status      = (string) get_post_status( $id );
        $user_id     = ( new Reservation_Account_Resolver() )->resolve( $reservation );
        // Booking card only: a referral earn on the same object belongs to the inviter, not this booking.
        $totals      = ( new Reservation_Points_Reconciler() )->booking_totals( $id );
        $total       = $totals['net'];
        // Same rule as the reconciler: eligibility only decides a booking that holds nothing yet.
        $lines       = $user_id > 0 ? $this->lines( $reservation, $user_id, 0 === $totals['earned'] ) : [];

        $effect = Reservation_Points_Reconciler::status_effect( $status );

        if ( $user_id <= 0 && 0 === $total ) {
            $state = 'guest';
        } elseif ( 'revoke' === $effect ) {
            $state = 'cancelled';
        } elseif ( 'earn' === $effect || $total > 0 ) {
            // A status in neither list keeps what it earned, so it still reads as earned.
            $state = 'earned';
        } else {
            $state = 'pending';
        }

        $expected = array_sum( wp_list_pluck( $lines, 'points' ) );

        return $this->response(
            [
                'state'    => $state,
                'total'    => $total,
                'lines'    => $lines,
                // Only an earning status is kept in step with the rules; one on hold may legitimately differ.
                'mismatch' => 'earn' === $effect && 'earned' === $state && $expected !== $total,
            ]
        );
    }

    /**
     * Breakdown lines of every active booking rule for the booking as it is now.
     *
     * @param Reservation_Model $reservation Booking.
     * @param int               $user_id     Account.
     * @param bool              $qualifying  Whether rule conditions still decide (nothing earned yet).
     * @return array<int, array{label: string, points: int}>
     */
    private function lines( $reservation, $user_id, $qualifying ) {
        $input      = Reservation_Points_Calculator::input_for_reservation( $reservation );
        $calculator = new Reservation_Points_Calculator();
        $lines      = [];

        foreach ( Reservation_Points_Calculator::open_rules() as $channel ) {
            if ( $qualifying && ! $calculator->qualifies( $channel, $user_id, $input ) ) {
                continue;
            }

            $calc = $calculator->price( $channel, $user_id, $input );

            foreach ( $calc ? $calc->breakdown() : [] as $row ) {
                $points = $this->delta_points( (string) $row['delta'] );

                if ( 0 !== $points ) {
                    $lines[] = [
                        'label'  => (string) $row['label'],
                        'points' => $points,
                    ];
                }
            }
        }

        return $lines;
    }

    /**
     * "+50" gives 50; "x2.00 (+190)" gives 190; "capped at 0" gives 0.
     *
     * @param string $delta Breakdown delta.
     * @return int
     */
    private function delta_points( $delta ) {
        if ( preg_match( '/\(([+-]?\d+)\)/', $delta, $match ) ) {
            return (int) $match[1];
        }

        return preg_match( '/^[+-]?\d+$/', $delta ) ? (int) $delta : 0;
    }
}
