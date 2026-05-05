<?php
namespace WpCafe\FoodOrder\Controllers;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Abstract\Base_Rest_Controller;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Food Order Controller
 *
 * Read-only REST endpoints serving WooCommerce orders to the frontend
 * restaurant management panel.
 */
class Food_Order_Controller extends Base_Rest_Controller {
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
    protected $rest_base = 'food-orders';

    /**
     * Register all routes related to food orders
     *
     * @return void
     */
    public function register_routes(): void {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_items' ],
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_item' ],
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'update_item_status' ],
                    'permission_callback' => [ $this, 'manage_items_permissions_check' ],
                ],
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [ $this, 'delete_item' ],
                    'permission_callback' => [ $this, 'manage_items_permissions_check' ],
                ],
            ]
        );
    }

    /**
     * Permission check shared by all routes.
     *
     * @param WP_REST_Request $request Request.
     * @return bool|\WP_HTTP_Response
     */
    public function get_items_permissions_check( $request ) {
        $can_read = current_user_can( 'manage_woocommerce' )
            || current_user_can( 'wpcafe_view_all_orders' )
            || current_user_can( 'wpcafe_view_own_orders' )
            || current_user_can( 'wpcafe_manage_orders' );

        if ( ! $can_read ) {
            return $this->error( __( 'You do not have permission to access orders.', 'wp-cafe' ), 403 );
        }

        if ( ! $this->verify_rest_nonce( $request ) ) {
            return $this->error( __( 'Invalid nonce.', 'wp-cafe' ), 403 );
        }

        if ( ! function_exists( 'wc_get_orders' ) ) {
            return $this->error( __( 'WooCommerce is not active.', 'wp-cafe' ), 500 );
        }

        return true;
    }

    /**
     * Permission check for mutating order routes.
     *
     * @param WP_REST_Request $request Request.
     * @return bool|\WP_HTTP_Response
     */
    public function manage_items_permissions_check( $request ) {
        $can_manage = current_user_can( 'manage_woocommerce' )
            || current_user_can( 'wpcafe_manage_orders' );

        if ( ! $can_manage ) {
            return $this->error( __( 'You do not have permission to manage orders.', 'wp-cafe' ), 403 );
        }

        if ( ! $this->verify_rest_nonce( $request ) ) {
            return $this->error( __( 'Invalid nonce.', 'wp-cafe' ), 403 );
        }

        if ( ! function_exists( 'wc_get_orders' ) ) {
            return $this->error( __( 'WooCommerce is not active.', 'wp-cafe' ), 500 );
        }

        return true;
    }

    /**
     * GET /food-orders — list orders.
     *
     * @param WP_REST_Request $request Request.
     * @return \WP_HTTP_Response
     */
    public function get_items( $request ) {
        $paged    = max( 1, absint( $request->get_param( 'paged' ) ?: 1 ) );
        $per_page = min( 100, max( 1, absint( $request->get_param( 'per_page' ) ?: 10 ) ) );
        $search   = sanitize_text_field( (string) $request->get_param( 'search' ) );
        $status   = $request->get_param( 'status' );
        $date_from = sanitize_text_field( (string) $request->get_param( 'date_from' ) );
        $date_to   = sanitize_text_field( (string) $request->get_param( 'date_to' ) );
        $location  = absint( $request->get_param( 'location' ) );

        $args = [
            'limit'    => $per_page,
            'page'     => $paged,
            'paginate' => true,
            'orderby'  => 'date',
            'order'    => 'DESC',
        ];

        $view_own_only = ! current_user_can( 'manage_woocommerce' )
            && ! current_user_can( 'wpcafe_view_all_orders' )
            && ! current_user_can( 'wpcafe_manage_orders' );

        if ( $view_own_only ) {
            $args['customer_id'] = get_current_user_id();
        }

        if ( $location > 0 ) {
            $args['meta_query'] = [
                [
                    'key'     => 'wpc_location_id',
                    'value'   => $location,
                    'compare' => '=',
                ],
            ];
        }

        if ( ! empty( $status ) ) {
            $valid_statuses = array_keys( wc_get_order_statuses() );
            if ( is_array( $status ) ) {
                $args['status'] = array_values( array_intersect( $status, $valid_statuses ) );
            } else {
                $clean = 'wc-' === substr( $status, 0, 3 ) ? $status : 'wc-' . $status;
                if ( in_array( $clean, $valid_statuses, true ) ) {
                    $args['status'] = $clean;
                }
            }
        }

        if ( ! empty( $search ) ) {
            $args['s'] = $search;
        }

        if ( ! empty( $date_from ) || ! empty( $date_to ) ) {
            $from = $date_from ?: '';
            $to   = $date_to ?: '';
            $args['date_created'] = trim( $from . '...' . $to, '.' );
        }

        $results = wc_get_orders( $args );

        $items = [];
        foreach ( $results->orders as $order ) {
            $items[] = $this->format_order_summary( $order );
        }

        return $this->response( [
            'items'       => $items,
            'total'       => (int) $results->total,
            'total_pages' => (int) $results->max_num_pages,
            'paged'       => $paged,
            'per_page'    => $per_page,
        ] );
    }

    /**
     * PATCH /food-orders/{id} — update order status.
     *
     * @param WP_REST_Request $request Request.
     * @return \WP_HTTP_Response
     */
    public function update_item_status( $request ) {
        $id     = absint( $request['id'] );
        $order  = wc_get_order( $id );

        if ( ! $order ) {
            return $this->error( __( 'Order not found.', 'wp-cafe' ), 404 );
        }

        $status = sanitize_text_field( (string) $request->get_param( 'status' ) );
        if ( empty( $status ) ) {
            return $this->error( __( 'Status is required.', 'wp-cafe' ), 400 );
        }

        $valid_statuses = array_keys( wc_get_order_statuses() );
        $prefixed = 'wc-' === substr( $status, 0, 3 ) ? $status : 'wc-' . $status;
        if ( ! in_array( $prefixed, $valid_statuses, true ) ) {
            return $this->error( __( 'Invalid status.', 'wp-cafe' ), 400 );
        }

        $order->update_status( $status, '', true );

        return $this->response( $this->format_order_summary( $order ) );
    }

    /**
     * DELETE /food-orders/{id} — trash an order.
     *
     * @param WP_REST_Request $request Request.
     * @return \WP_HTTP_Response
     */
    public function delete_item( $request ) {
        $id    = absint( $request['id'] );
        $order = wc_get_order( $id );

        if ( ! $order ) {
            return $this->error( __( 'Order not found.', 'wp-cafe' ), 404 );
        }

        $order->delete( false );

        return $this->response( [ 'deleted' => true, 'id' => $id ] );
    }

    /**
     * GET /food-orders/{id} — single order detail.
     *
     * @param WP_REST_Request $request Request.
     * @return \WP_HTTP_Response
     */
    public function get_item( $request ) {
        $id    = absint( $request['id'] );
        $order = wc_get_order( $id );

        if ( ! $order ) {
            return $this->error( __( 'Order not found.', 'wp-cafe' ), 404 );
        }

        $view_own_only = ! current_user_can( 'manage_woocommerce' )
            && ! current_user_can( 'wpcafe_view_all_orders' )
            && ! current_user_can( 'wpcafe_manage_orders' );

        if ( $view_own_only && (int) $order->get_customer_id() !== get_current_user_id() ) {
            return $this->error( __( 'You do not have permission to view this order.', 'wp-cafe' ), 403 );
        }

        return $this->response( $this->format_order_detail( $order ) );
    }

    /**
     * Format an order for the list response.
     *
     * @param \WC_Order $order Order.
     * @return array
     */
    protected function format_order_summary( $order ) {
        $items_count = 0;
        foreach ( $order->get_items() as $item ) {
            $items_count += (int) $item->get_quantity();
        }

        return [
            'id'              => $order->get_id(),
            'number'          => $order->get_order_number(),
            'status'          => $order->get_status(),
            'status_label'    => wc_get_order_status_name( $order->get_status() ),
            'date_created'    => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '',
            'customer_name'   => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ) ?: __( 'Guest', 'wp-cafe' ),
            'customer_email'  => $order->get_billing_email(),
            'items_count'     => $items_count,
            'total'           => $order->get_total(),
            'total_formatted' => wp_strip_all_tags( wc_price( $order->get_total() ) ),
            'currency'        => $order->get_currency(),
            'payment_method'  => $order->get_payment_method_title(),
            'edit_url'        => $order->get_edit_order_url(),
        ];
    }

    /**
     * Format an order for the detail response.
     *
     * @param \WC_Order $order Order.
     * @return array
     */
    protected function format_order_detail( $order ) {
        $line_items = [];
        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            $line_items[] = [
                'id'       => $item->get_id(),
                'name'     => $item->get_name(),
                'quantity' => (int) $item->get_quantity(),
                'subtotal' => $order->get_item_subtotal( $item, false, false ),
                'total'    => $order->get_line_total( $item, false, false ),
                'sku'      => $product ? $product->get_sku() : '',
            ];
        }

        return array_merge( $this->format_order_summary( $order ), [
            'billing_address'   => $order->get_formatted_billing_address(),
            'shipping_address'  => $order->get_formatted_shipping_address(),
            'customer_note'     => $order->get_customer_note(),
            'line_items'        => $line_items,
            'subtotal'          => $order->get_subtotal(),
            'subtotal_formatted'=> wp_strip_all_tags( wc_price( $order->get_subtotal() ) ),
            'total_tax'         => $order->get_total_tax(),
            'shipping_total'    => $order->get_shipping_total(),
        ] );
    }
}
