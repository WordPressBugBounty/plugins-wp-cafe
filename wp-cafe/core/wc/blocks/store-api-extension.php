<?php
namespace WpCafe\Wc\Blocks;

defined( 'ABSPATH' ) || exit;

use WpCafe\Models\Location_Model;
use WpCafe\Session;

/**
 * Store API Extension
 *
 * Adds a `wp-cafe` namespace to the Store API checkout endpoint so the block
 * checkout can push `location_id` (and future fields) via `extensionCartUpdate`,
 * and persists those values to order meta on submission. Mirrors the meta keys
 * written by Location_Selector::save_location_meta() on classic checkout.
 */
class Store_Api_Extension {

    const NAMESPACE_KEY = 'wp-cafe';

    /**
     * Hook in.
     *
     * @return void
     */
    public function register() {
        if ( did_action( 'woocommerce_blocks_loaded' ) ) {
            $this->register_endpoint_data();
            $this->register_update_callback();
        } else {
            add_action( 'woocommerce_blocks_loaded', [ $this, 'register_endpoint_data' ] );
            add_action( 'woocommerce_blocks_loaded', [ $this, 'register_update_callback' ] );
        }
        add_action( 'woocommerce_store_api_checkout_update_order_from_request', [ $this, 'persist_to_order' ], 10, 2 );
    }

    /**
     * Extend the Checkout endpoint schema with `location_id`.
     *
     * @return void
     */
    public function register_endpoint_data() {
        if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
            return;
        }

        woocommerce_store_api_register_endpoint_data(
            [
                'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema::IDENTIFIER,
                'namespace'       => self::NAMESPACE_KEY,
                'data_callback'   => [ $this, 'data_callback' ],
                'schema_callback' => [ $this, 'schema_callback' ],
                'schema_type'     => ARRAY_A,
            ]
        );
    }

    /**
     * Register an update callback so JS can write location_id into the
     * Store API session ahead of order submission.
     *
     * @return void
     */
    public function register_update_callback() {
        if ( ! function_exists( 'woocommerce_store_api_register_update_callback' ) ) {
            return;
        }

        woocommerce_store_api_register_update_callback(
            [
                'namespace' => self::NAMESPACE_KEY,
                'callback'  => function ( $data ) {
                    $this->handle_location_update( $data );
                    $this->handle_tip_update( $data );
                },
            ]
        );
    }

    /**
     * Persist a location selection pushed by the block-checkout UI.
     *
     * @param array $data
     * @return void
     */
    private function handle_location_update( $data ) {
        if ( ! array_key_exists( 'location_id', (array) $data ) ) {
            return;
        }

        $location_id = isset( $data['location_id'] ) ? intval( $data['location_id'] ) : 0;
        if ( $location_id <= 0 ) {
            return;
        }

        $location = Location_Model::find( $location_id );
        if ( ! $location ) {
            return;
        }

        Session::set( 'selected_location', $location_id );
    }

    /**
     * Persist a tip selection pushed by the block-checkout UI.
     *
     * @param array $data
     * @return void
     */
    private function handle_tip_update( $data ) {
        $has_type   = array_key_exists( 'tip_type', (array) $data );
        $has_amount = array_key_exists( 'tip_amount', (array) $data );
        if ( ! $has_type && ! $has_amount ) {
            return;
        }

        if ( ! function_exists( 'WC' ) || ! WC()->session ) {
            return;
        }

        $type   = isset( $data['tip_type'] ) ? sanitize_text_field( wp_unslash( (string) $data['tip_type'] ) ) : '';
        $amount = isset( $data['tip_amount'] ) ? floatval( $data['tip_amount'] ) : 0;

        $allowed_types = [ 'fixed_amount', 'percentage_amount', 'custom' ];
        $is_valid_type = in_array( $type, $allowed_types, true );

        if ( ! $is_valid_type || $amount <= 0 ) {
            WC()->session->__unset( 'wpc_pro_tip' );
            return;
        }

        WC()->session->set(
            'wpc_pro_tip',
            [
                'tip_added'         => 1,
                'tip_selected_type' => $type,
                'tip_amount'        => $amount,
            ]
        );
    }

    /**
     * Schema definition for the namespaced fields.
     *
     * @return array
     */
    public function schema_callback() {
        return [
            'location_id' => [
                'description' => __( 'Selected pickup location ID.', 'wp-cafe' ),
                'type'        => [ 'integer', 'null' ],
                'context'     => [ 'view', 'edit' ],
                'readonly'    => false,
            ],
            'tip_type'    => [
                'description' => __( 'Selected tip type (fixed_amount, percentage_amount, or custom).', 'wp-cafe' ),
                'type'        => [ 'string', 'null' ],
                'context'     => [ 'view', 'edit' ],
                'readonly'    => false,
            ],
            'tip_amount'  => [
                'description' => __( 'Selected tip amount (currency value for fixed/custom, percent for percentage).', 'wp-cafe' ),
                'type'        => [ 'number', 'null' ],
                'context'     => [ 'view', 'edit' ],
                'readonly'    => false,
            ],
        ];
    }

    /**
     * Data returned to the client for the namespaced fields.
     *
     * @return array
     */
    public function data_callback() {
        $tip = ( function_exists( 'WC' ) && WC()->session ) ? WC()->session->get( 'wpc_pro_tip' ) : null;
        $tip_type   = ( is_array( $tip ) && ! empty( $tip['tip_added'] ) ) ? (string) ( $tip['tip_selected_type'] ?? '' ) : '';
        $tip_amount = ( is_array( $tip ) && ! empty( $tip['tip_added'] ) ) ? floatval( $tip['tip_amount'] ?? 0 ) : 0;

        return [
            'location_id' => function_exists( 'wpc_selected_location_id' ) ? wpc_selected_location_id() : null,
            'tip_type'    => $tip_type,
            'tip_amount'  => $tip_amount,
        ];
    }

    /**
     * Persist namespaced extension data to order meta on submission.
     *
     * @param \WC_Order        $order
     * @param \WP_REST_Request $request
     * @return void
     */
    public function persist_to_order( $order, $request ) {
        $extensions = $request->get_param( 'extensions' );
        $params     = is_array( $extensions ) && ! empty( $extensions[ self::NAMESPACE_KEY ] ) ? $extensions[ self::NAMESPACE_KEY ] : [];

        if ( empty( $params['location_id'] ) ) {
            $params['location_id'] = function_exists( 'wpc_selected_location_id' ) ? wpc_selected_location_id() : 0;
        }

        $location_id = isset( $params['location_id'] ) ? intval( $params['location_id'] ) : 0;
        if ( $location_id <= 0 ) {
            return;
        }

        $location = Location_Model::find( $location_id );
        if ( ! $location ) {
            return;
        }

        $order->update_meta_data( 'wpc_location_id', $location->term_id );
        $order->update_meta_data( 'wpc_location_name', $location->location );

        Session::set( 'selected_location', $location->term_id );
    }
}
