<?php
namespace WpCafe\Products\Controllers;

use WpCafe\Abstract\Base_Rest_Controller;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Server;
use WP_REST_Request;

/**
 * Product Category controller
 *
 * Handles all REST API endpoints for WooCommerce product categories.
 *
 * @package WpCafe/Products
 */
class Product_Category_Controller extends Base_Rest_Controller {
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
    protected $rest_base = 'product-categories';

    /**
     * Register all routes related to product categories
     *
     * @return void
     */
    public function register_routes(): void {
        
        register_rest_route( $this->namespace,
            '/' . $this->rest_base, [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_items'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
                'args'                => $this->get_collection_params(),
            ],
        ] );

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', 
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_item'],
                    'permission_callback' => [$this, 'get_item_permissions_check'],
                    'args'                => [
                        'context' => $this->get_context_param(['default' => 'view']),
                    ],
                ],
            ]
        );
    }

    /**
     * Get a collection of product categories
     *
     * @param WP_REST_Request $request
     * @return WP_HTTP_Response|WP_Error
     */
    public function get_items($request) {
        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page') ?: 1;

        $args = [
            'taxonomy'     => 'product_cat',
            'hide_empty'   => $request->get_param('hide_empty') ?: false,
            'orderby'      => $request->get_param('orderby') ?: 'name',
            'order'        => $request->get_param('order') ?: 'ASC',
            'number'       => empty($per_page) ? 0 : (int)$per_page,
            'offset'       => empty($per_page) ? 0 : ($page - 1) * (int)$per_page,
        ];

        // Handle parent filter
        if ($request->get_param('parent')) {
            $args['parent'] = (int) $request->get_param('parent');
        }

        // Handle search
        if ($request->get_param('search')) {
            $args['name__like'] = $request->get_param('search');
        }

        // Handle include/exclude
        if ($request->get_param('include')) {
            $args['include'] = $request->get_param('include');
        }

        if ($request->get_param('exclude')) {
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- admin/small-list usage, performance acceptable
            $args['exclude'] = $request->get_param('exclude');
        }
        $args = apply_filters('wpcafe_product_category_query_args', $args, $request);

        // Get categories using WordPress native function
        $categories = get_terms($args);
        
        if (is_wp_error($categories)) {
            return $this->error(__('Error retrieving categories', 'wp-cafe'), 500);
        }

        $data = [];
        foreach ($categories as $category) {
            $data[] = $this->prepare_category_data($category);
        }

        return $this->response($data);
    }

    /**
     * Get a single product category
     *
     * @param WP_REST_Request $request
     * @return WP_HTTP_Response|WP_Error
     */
    public function get_item($request) {
        $category_id = (int) $request->get_param('id');
        $category = get_term($category_id, 'product_cat');

        if (!$category || is_wp_error($category)) {
            return $this->error(__('Category not found', 'wp-cafe'), 404);
        }

        $data = $this->prepare_category_data($category);
        return $this->response($data);
    }

    /**
     * Prepare category data for response
     *
     * @param WP_Term $category
     * @return array
     */
    private function prepare_category_data($category) {
        // Get category display type
        $display_type = get_term_meta($category->term_id, 'display_type', true);
        
        // Get category order
        $menu_order = get_term_meta($category->term_id, 'order', true);
        
        // Get category image
        $image_id = get_term_meta($category->term_id, 'thumbnail_id', true);
        $image = null;
        
        if ($image_id) {
            $attachment = get_post($image_id);
            if ($attachment) {
                $image = [
                    'id'            => (int) $image_id,
                    'date_created'  => wc_rest_prepare_date_response($attachment->post_date_gmt),
                    'date_modified' => wc_rest_prepare_date_response($attachment->post_modified_gmt),
                    'src'           => wp_get_attachment_url($image_id),
                    'name'          => get_the_title($attachment),
                    'alt'           => get_post_meta($image_id, '_wp_attachment_image_alt', true),
                ];
            }
        }

        $data = [
            'id'          => (int) $category->term_id,
            'name'        => $category->name,
            'slug'        => $category->slug,
            'parent'      => (int) $category->parent,
            'description' => $category->description,
            'display'     => $display_type ? $display_type : 'default',
            'image'       => $image,
            'menu_order'  => (int) $menu_order,
            'count'       => (int) $category->count,
            'permalink'   => get_term_link($category, 'product_cat'),
        ];

        // Get child categories if requested
        $children = get_terms([
            'taxonomy'   => 'product_cat',
            'parent'     => $category->term_id,
            'hide_empty' => false,
        ]);

        if (!is_wp_error($children)) {
            $data['children'] = [];
            foreach ($children as $child) {
                $data['children'][] = [
                    'id'          => (int) $child->term_id,
                    'name'        => $child->name,
                    'slug'        => $child->slug,
                    'count'       => (int) $child->count,
                    'permalink'   => get_term_link($child, 'product_cat'),
                ];
            }
        }

        return $data;
    }

    /**
     * Get collection parameters
     *
     * @return array
     */
    public function get_collection_params() {
        return [
            'page'       => [
                'description'       => __('Current page of the collection.', 'wp-cafe'),
                'type'              => 'integer',
                'default'           => 1,
                'minimum'           => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'per_page'   => [
                'description'       => __('Maximum number of items to be returned in result set.', 'wp-cafe'),
                'type'              => 'integer',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'search'     => [
                'description'       => __('Limit results to those matching a string.', 'wp-cafe'),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'parent'     => [
                'description'       => __('Limit result set to categories assigned a specific parent.', 'wp-cafe'),
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'include'    => [
                'description'       => __('Limit result set to specific IDs.', 'wp-cafe'),
                'type'              => 'array',
                'items'             => [
                    'type' => 'integer',
                ],
                'default'           => [],
                'sanitize_callback' => 'wp_parse_id_list',
            ],
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- admin/small-list usage, performance acceptable
            'exclude'    => [
                'description'       => __('Ensure result set excludes specific IDs.', 'wp-cafe'),
                'type'              => 'array',
                'items'             => [
                    'type' => 'integer',
                ],
                'default'           => [],
                'sanitize_callback' => 'wp_parse_id_list',
            ],
            'hide_empty' => [
                'description'       => __('Whether to hide categories not assigned to any products.', 'wp-cafe'),
                'type'              => 'boolean',
                'default'           => false,
                'sanitize_callback' => 'wc_string_to_bool',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'orderby'    => [
                'description'       => __('Sort collection by object attribute.', 'wp-cafe'),
                'type'              => 'string',
                'default'           => 'name',
                'enum'              => ['id', 'include', 'name', 'slug', 'term_group', 'description', 'count'],
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'order'      => [
                'description'       => __('Order sort attribute ascending or descending.', 'wp-cafe'),
                'type'              => 'string',
                'default'           => 'ASC',
                'enum'              => ['ASC', 'DESC'],
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => 'rest_validate_request_arg',
            ],
        ];
    }

    /**
     * Check if a given request has permission to read product categories
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function get_items_permissions_check($request) {
        return apply_filters('wpcafe_product_category_read_permission', true, $request);
    }

    /**
     * Check if a given request has permission to read a product category
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function get_item_permissions_check($request) {
        return apply_filters('wpcafe_product_category_item_permission', true, $request);
    }
} 