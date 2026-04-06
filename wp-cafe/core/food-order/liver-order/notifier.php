<?php
namespace WpCafe\FoodOrder\LiveOrder;

defined('ABSPATH') || exit;

/**
 * Notifier Class
 * 
 * Handles live order notifications for new orders.
 */
class Notifier {
    /**
     * Constructor
     * 
     * Initializes the notifier.
     */
    public function __construct() {
        add_filter( 'heartbeat_received', array( $this, 'get_notification' ), 10, 2 );
        add_action( 'wp_ajax_wpc_check_latest_order', array( $this, 'check_latest_order_ajax' ) );
    }

    /**
     * Get notification
     * 
     * Retrieves the notification data from the transient.
     * 
     * @return void
     */
    public function get_notification($response, $data) {
        $live_notify = ! empty( $data['wpc_pro_heart'] ) ? $data['wpc_pro_heart'] : '';

        if ( 'live_notify' !==  $live_notify ) {
            return $response;
        }

        $response['new_order_id'] = wpc_get_last_order_id();

        return $response;
    }

    /**
     * AJAX handler to check for latest orders
     *
     * @return void
     */
    public function check_latest_order_ajax() {
        check_ajax_referer( 'wpc_live_order_notify', 'nonce' );

        $last_order_id = isset( $_POST['last_order_id'] ) ? intval( $_POST['last_order_id'] ) : 0;
        $latest_order_id = wpc_get_last_order_id();

        wp_send_json_success([
            'new_order_id' => $latest_order_id,
            'has_new' => ( $latest_order_id && $latest_order_id !== $last_order_id ),
        ]);
    }
}
