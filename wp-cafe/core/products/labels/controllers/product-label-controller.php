<?php
namespace WpCafe\Products\Labels\Controllers;

use WpCafe\Abstract\Base_Rest_Controller;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Server;
use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Product Label REST controller (read-only).
 *
 * Routes:
 *   GET /wpcafe/v2/product-labels
 *   GET /wpcafe/v2/product-labels/{id}
 *
 * @package WpCafe/Products/Labels
 */
class Product_Label_Controller extends Base_Rest_Controller {

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
    protected $rest_base = 'product-labels';

    /**
     * Taxonomy slug
     *
     * @var string
     */
    private $taxonomy = 'wpcafe_product_label';

    /**
     * Register all routes related to product labels.
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
                    'args'                => $this->get_collection_params(),
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
                    'permission_callback' => [ $this, 'get_item_permissions_check' ],
                    'args'                => [
                        'context' => $this->get_context_param( [ 'default' => 'view' ] ),
                    ],
                ],
            ]
        );
    }

    /**
     * Get a collection of product labels.
     *
     * @param WP_REST_Request $request
     * @return WP_HTTP_Response|WP_Error
     */
    public function get_items( $request ) {
        $per_page = $request->get_param( 'per_page' );
        $page     = $request->get_param( 'page' ) ?: 1;

        $args = [
            'taxonomy'   => $this->taxonomy,
            'hide_empty' => $request->get_param( 'hide_empty' ) ?: false,
            'orderby'    => $request->get_param( 'orderby' ) ?: 'name',
            'order'      => $request->get_param( 'order' ) ?: 'ASC',
            'number'     => empty( $per_page ) ? 0 : (int) $per_page,
            'offset'     => empty( $per_page ) ? 0 : ( $page - 1 ) * (int) $per_page,
        ];

        if ( $request->get_param( 'search' ) ) {
            $args['name__like'] = $request->get_param( 'search' );
        }

        if ( $request->get_param( 'include' ) ) {
            $args['include'] = $request->get_param( 'include' );
        }

        if ( $request->get_param( 'exclude' ) ) {
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- admin/small-list usage
            $args['exclude'] = $request->get_param( 'exclude' );
        }

        $args  = apply_filters( 'wpcafe_product_label_query_args', $args, $request );
        $terms = get_terms( $args );

        if ( is_wp_error( $terms ) ) {
            return $this->error( __( 'Error retrieving product labels', 'wp-cafe' ), 500 );
        }

        $data = [];
        foreach ( $terms as $term ) {
            $data[] = $this->prepare_label_data( $term );
        }

        return $this->response( $data );
    }

    /**
     * Get a single product label.
     *
     * @param WP_REST_Request $request
     * @return WP_HTTP_Response|WP_Error
     */
    public function get_item( $request ) {
        $term_id = (int) $request->get_param( 'id' );
        $term    = get_term( $term_id, $this->taxonomy );

        if ( ! $term || is_wp_error( $term ) ) {
            return $this->error( __( 'Product label not found', 'wp-cafe' ), 404 );
        }

        return $this->response( $this->prepare_label_data( $term ) );
    }

    /**
     * Shape a single label into the REST response payload.
     *
     * @param \WP_Term $term
     * @return array
     */
    private function prepare_label_data( $term ) {
        $meta = function_exists( 'wpc_product_label_meta' )
            ? wpc_product_label_meta( $term->term_id )
            : [
                'display'    => 'name',
                'bg'         => '#1F2937',
                'fg'         => '#FFFFFF',
                'icon_type'  => 'dashicons',
                'icon_value' => '',
            ];

        $icon_url = '';
        if ( 'svg' === $meta['icon_type'] && function_exists( 'wpc_product_label_icon_url' ) ) {
            $icon_url = wpc_product_label_icon_url( 'svg', $meta['icon_value'] );
        }

        return [
            'id'          => (int) $term->term_id,
            'name'        => $term->name,
            'slug'        => $term->slug,
            'description' => $term->description,
            'count'       => (int) $term->count,
            'display'     => $meta['display'],
            'bg'          => $meta['bg'],
            'fg'          => $meta['fg'],
            'icon_type'   => $meta['icon_type'],
            'icon_value'  => $meta['icon_value'],
            'icon_url'    => $icon_url,
        ];
    }

    /**
     * Get collection parameters.
     *
     * @return array
     */
    public function get_collection_params() {
        return [
            'page'       => [
                'description'       => __( 'Current page of the collection.', 'wp-cafe' ),
                'type'              => 'integer',
                'default'           => 1,
                'minimum'           => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'per_page'   => [
                'description'       => __( 'Maximum number of items to be returned in result set.', 'wp-cafe' ),
                'type'              => 'integer',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'search'     => [
                'description'       => __( 'Limit results to those matching a string.', 'wp-cafe' ),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'include'    => [
                'description'       => __( 'Limit result set to specific IDs.', 'wp-cafe' ),
                'type'              => 'array',
                'items'             => [ 'type' => 'integer' ],
                'default'           => [],
                'sanitize_callback' => 'wp_parse_id_list',
            ],
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- admin/small-list usage
            'exclude'    => [
                'description'       => __( 'Ensure result set excludes specific IDs.', 'wp-cafe' ),
                'type'              => 'array',
                'items'             => [ 'type' => 'integer' ],
                'default'           => [],
                'sanitize_callback' => 'wp_parse_id_list',
            ],
            'hide_empty' => [
                'description'       => __( 'Whether to hide labels not assigned to any products.', 'wp-cafe' ),
                'type'              => 'boolean',
                'default'           => false,
                'sanitize_callback' => 'wc_string_to_bool',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'orderby'    => [
                'description'       => __( 'Sort collection by object attribute.', 'wp-cafe' ),
                'type'              => 'string',
                'default'           => 'name',
                'enum'              => [ 'id', 'include', 'name', 'slug', 'term_group', 'description', 'count' ],
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'order'      => [
                'description'       => __( 'Order sort attribute ascending or descending.', 'wp-cafe' ),
                'type'              => 'string',
                'default'           => 'ASC',
                'enum'              => [ 'ASC', 'DESC' ],
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => 'rest_validate_request_arg',
            ],
        ];
    }

    /**
     * Permission check for list endpoint.
     *
     * @param WP_REST_Request $request
     * @return bool|\WP_HTTP_Response
     */
    public function get_items_permissions_check( $request ) {
        $can_read = current_user_can( 'manage_woocommerce' )
            || current_user_can( 'edit_products' )
            || current_user_can( 'manage_categories' );

        $can_read = apply_filters( 'wpcafe_product_label_read_permission', $can_read, $request );

        if ( ! $can_read ) {
            return $this->error( __( 'You do not have permission to access product labels.', 'wp-cafe' ), 403 );
        }

        if ( ! $this->verify_rest_nonce( $request ) ) {
            return $this->error( __( 'Invalid nonce.', 'wp-cafe' ), 403 );
        }

        return true;
    }

    /**
     * Permission check for single endpoint.
     *
     * @param WP_REST_Request $request
     * @return bool|\WP_HTTP_Response
     */
    public function get_item_permissions_check( $request ) {
        $can_read = current_user_can( 'manage_woocommerce' )
            || current_user_can( 'edit_products' )
            || current_user_can( 'manage_categories' );

        $can_read = apply_filters( 'wpcafe_product_label_item_permission', $can_read, $request );

        if ( ! $can_read ) {
            return $this->error( __( 'You do not have permission to access this product label.', 'wp-cafe' ), 403 );
        }

        if ( ! $this->verify_rest_nonce( $request ) ) {
            return $this->error( __( 'Invalid nonce.', 'wp-cafe' ), 403 );
        }

        return true;
    }
}
