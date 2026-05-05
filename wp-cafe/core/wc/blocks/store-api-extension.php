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
                    $location_id = isset( $data['location_id'] ) ? intval( $data['location_id'] ) : 0;

                    if ( $location_id <= 0 ) {
                        return;
                    }

                    $location = Location_Model::find( $location_id );
                    if ( ! $location ) {
                        return;
                    }

                    Session::set( 'selected_location', $location_id );
                },
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
        ];
    }

    /**
     * Data returned to the client for the namespaced fields.
     *
     * @return array
     */
    public function data_callback() {
        return [
            'location_id' => function_exists( 'wpc_selected_location_id' ) ? wpc_selected_location_id() : null,
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
