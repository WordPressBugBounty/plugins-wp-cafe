<?php
namespace WpCafe\FoodOrder\Tip;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles AJAX actions for adding and removing tips.
 */
class Tipping_Ajax {

    /**
     * Register AJAX action hooks.
     *
     * @return void
     */
    public function __construct() {
        add_action( 'wp_ajax_add_tip', [ $this, 'add_tip' ] );
        add_action( 'wp_ajax_nopriv_add_tip', [ $this, 'add_tip' ] );

        add_action( 'wp_ajax_remove_tip', [ $this, 'remove_tip' ] );
        add_action( 'wp_ajax_nopriv_remove_tip', [ $this, 'remove_tip' ] );
    }

    /**
     * Handle adding tip to WooCommerce session.
     *
     * @return void
     */
    public function add_tip() {
        $response = [
            'status_code' => 0,
            'message'     => '',
        ];

        if ( ! wp_verify_nonce( $_POST['security'], 'add_tip_nonce_value' ) ) {
            $response['message'] = esc_html__( 'Nonce is not valid!', 'wp-cafe' );
        } else {
            $type     = sanitize_text_field( $_POST['tip_selected_type'] );

            $tip_amount = ! empty( $_POST['tip_amount'] ) ? floatval( $_POST['tip_amount'] ) : 0;

            $tip_types = [
                'fixed_amount',
                'percentage_amount',
                'custom'
            ];

            $is_valid = in_array( $type, $tip_types ) && $tip_amount > 0;

            if ( $is_valid ) {
                WC()->session->set( 'wpc_pro_tip', [
                    'tip_added'             => 1,
                    'tip_selected_type'     => $type,
                    'tip_amount'            => $tip_amount,
                ] );

                $response['status_code'] = 1;
                $response['message'] = esc_html__( 'Tip added successfully.', 'wp-cafe' );
            } else {
                $response['message'] = esc_html__( 'Invalid tip input.', 'wp-cafe' );
            }
        }

        wp_send_json( $response );
    }

    /**
     * Handle removing tip from WooCommerce session.
     *
     * @return void
     */
    public function remove_tip() {
        $response = [
            'status_code' => 0,
            'message'     => '',
        ];

        if ( ! wp_verify_nonce( $_POST['security'], 'remove_tip_nonce_value' ) ) {
            $response['message'] = esc_html__( 'Nonce is not valid!', 'wp-cafe' );
        } else {
            WC()->session->__unset( 'wpc_pro_tip' );
            $response['status_code'] = 1;
            $response['message'] = esc_html__( 'Tip removed successfully.', 'wp-cafe' );
        }

        wp_send_json( $response );
    }
}
