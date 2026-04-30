<?php
namespace WpCafe\Products\Controllers;

use WpCafe\Abstract\Base_Rest_Controller;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Server;
use WP_REST_Request;

/**
 * Product controller
 *
 * Handles all REST API endpoints for WooCommerce products.
 *
 * @package WpCafe/Products
 */
class Product_Controller extends Base_Rest_Controller {
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
    protected $rest_base = 'products';

    /**
     * Register all routes related to products
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
     * Get a collection of products
     *
     * @param WP_REST_Request $request
     * @return WP_HTTP_Response|WP_Error
     */
    public function get_items($request) {
        $args = [
            'limit'   => $request->get_param('per_page') ?: -1,
            'page'    => $request->get_param('page') ?: 1,
            'orderby' => $request->get_param('orderby') ?: 'date',
            'order'   => $request->get_param('order') ?: 'DESC',
            'status'  => 'publish',
        ];

        // Handle category filter
        if ($request->get_param('category')) {
            $args['category'] = [$request->get_param('category')];
        }

        // Handle search
        if ($request->get_param('search')) {
            $args['s'] = $request->get_param('search');
        }

        // Handle include/exclude
        if ($request->get_param('include')) {
            $args['include'] = $request->get_param('include');
        }

        if ($request->get_param('exclude')) {
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- admin/small-list usage, performance acceptable
            $args['exclude'] = $request->get_param('exclude');
        }

        $args = apply_filters('wpcafe_product_query_args', $args, $request);

        // Get products using WooCommerce native function
        $products = wc_get_products($args);
        
        $data = [];
        foreach ($products as $product) {
            $data[] = $this->prepare_product_data_with_minimal_values($product);
        }

        return $this->response($data);
    }

    /**
     * Get a single product
     *
     * @param WP_REST_Request $request
     * @return WP_HTTP_Response|WP_Error
     */
    public function get_item($request) {
        $product_id = (int) $request->get_param('id');
        $product = wc_get_product($product_id);

        if (!$product || $product->get_status() !== 'publish') {
            return $this->error(__('Product not found', 'wp-cafe'), 404);
        }

        $data = $this->prepare_product_data($product);
        return $this->response($data);
    }

    /**
     * Prepare product data for response with minimal values
     *
     * @param WC_Product $product
     * @return array
     */
    private function prepare_product_data_with_minimal_values($product) {
        return [
            'id'        => $product->get_id(),
            'name'      => $product->get_name(),
            'images'    => $this->get_images($product),
            'slug'      => $product->get_slug(),
            'permalink' => $product->get_permalink(),
        ];
    }

    /**
     * Prepare product data for response
     *
     * @param WC_Product $product
     * @return array
     */
    private function prepare_product_data($product) {
        $data = [
            'id'                  => $product->get_id(),
            'name'                => $product->get_name(),
            'slug'                => $product->get_slug(),
            'permalink'           => $product->get_permalink(),
            'date_created'        => wc_rest_prepare_date_response($product->get_date_created()),
            'date_modified'       => wc_rest_prepare_date_response($product->get_date_modified()),
            'type'                => $product->get_type(),
            'status'              => $product->get_status(),
            'featured'            => $product->get_featured(),
            'catalog_visibility'  => $product->get_catalog_visibility(),
            'description'         => $product->get_description(),
            'short_description'   => $product->get_short_description(),
            'sku'                 => $product->get_sku(),
            'price'               => $product->get_price(),
            'regular_price'       => $product->get_regular_price(),
            'sale_price'          => $product->get_sale_price(),
            'price_html'          => $product->get_price_html(),
            'on_sale'             => $product->is_on_sale(),
            'purchasable'         => $product->is_purchasable(),
            'total_sales'         => $product->get_total_sales(),
            'virtual'             => $product->is_virtual(),
            'downloadable'        => $product->is_downloadable(),
            'download_limit'      => $product->get_download_limit(),
            'download_expiry'     => $product->get_download_expiry(),
            'tax_status'          => $product->get_tax_status(),
            'tax_class'           => $product->get_tax_class(),
            'manage_stock'        => $product->managing_stock(),
            'stock_quantity'      => $product->get_stock_quantity(),
            'stock_status'        => $product->get_stock_status(),
            'backorders'          => $product->get_backorders(),
            'backorders_allowed'  => $product->backorders_allowed(),
            'backordered'         => $product->is_on_backorder(),
            'sold_individually'   => $product->is_sold_individually(),
            'weight'              => $product->get_weight(),
            'dimensions'          => [
                'length' => $product->get_length(),
                'width'  => $product->get_width(),
                'height' => $product->get_height(),
            ],
            'shipping_required'   => $product->needs_shipping(),
            'shipping_taxable'    => $product->is_shipping_taxable(),
            'shipping_class'      => $product->get_shipping_class(),
            'shipping_class_id'   => $product->get_shipping_class_id(),
            'reviews_allowed'     => $product->get_reviews_allowed(),
            'average_rating'      => $product->get_average_rating(),
            'rating_count'        => $product->get_rating_count(),
            'related_ids'         => array_map('absint', array_values(wc_get_related_products($product->get_id()))),
            'upsell_ids'          => array_map('absint', $product->get_upsell_ids()),
            'cross_sell_ids'      => array_map('absint', $product->get_cross_sell_ids()),
            'parent_id'           => $product->get_parent_id(),
            'purchase_note'       => $product->get_purchase_note(),
            'categories'          => $this->get_taxonomy_terms($product, 'product_cat'),
            'tags'                => $this->get_taxonomy_terms($product, 'product_tag'),
            'images'              => $this->get_images($product),
            'attributes'          => $this->get_attributes($product),
            'default_attributes'  => $this->get_default_attributes($product),
            'variations'          => [],
            'grouped_products'    => [],
            'menu_order'          => $product->get_menu_order(),
            'meta_data'           => $product->get_meta_data(),
        ];

        // Add variations for variable products
        if ($product->is_type('variable')) {
            $variations = $product->get_children();
            foreach ($variations as $variation_id) {
                $variation = wc_get_product($variation_id);
                if ($variation) {
                    $data['variations'][] = [
                        'id'                => $variation->get_id(),
                        'date_created'      => wc_rest_prepare_date_response($variation->get_date_created()),
                        'date_modified'     => wc_rest_prepare_date_response($variation->get_date_modified()),
                        'permalink'         => $variation->get_permalink(),
                        'sku'               => $variation->get_sku(),
                        'price'             => $variation->get_price(),
                        'regular_price'     => $variation->get_regular_price(),
                        'sale_price'        => $variation->get_sale_price(),
                        'on_sale'           => $variation->is_on_sale(),
                        'purchasable'       => $variation->is_purchasable(),
                        'virtual'           => $variation->is_virtual(),
                        'downloadable'      => $variation->is_downloadable(),
                        'download_limit'    => $variation->get_download_limit(),
                        'download_expiry'   => $variation->get_download_expiry(),
                        'tax_status'        => $variation->get_tax_status(),
                        'tax_class'         => $variation->get_tax_class(),
                        'manage_stock'      => $variation->managing_stock(),
                        'stock_quantity'    => $variation->get_stock_quantity(),
                        'stock_status'      => $variation->get_stock_status(),
                        'backorders'        => $variation->get_backorders(),
                        'backorders_allowed'=> $variation->backorders_allowed(),
                        'backordered'       => $variation->is_on_backorder(),
                        'weight'            => $variation->get_weight(),
                        'dimensions'        => [
                            'length' => $variation->get_length(),
                            'width'  => $variation->get_width(),
                            'height' => $variation->get_height(),
                        ],
                        'shipping_class'    => $variation->get_shipping_class(),
                        'shipping_class_id' => $variation->get_shipping_class_id(),
                        'image'             => $this->get_images($variation),
                        'attributes'        => $this->get_variation_attributes($variation),
                    ];
                }
            }
        }

        // Add grouped products for grouped products
        if ($product->is_type('grouped')) {
            $grouped_products = $product->get_children();
            foreach ($grouped_products as $grouped_product_id) {
                $grouped_product = wc_get_product($grouped_product_id);
                if ($grouped_product) {
                    $data['grouped_products'][] = [
                        'id'    => $grouped_product->get_id(),
                        'name'  => $grouped_product->get_name(),
                        'slug'  => $grouped_product->get_slug(),
                        'price' => $grouped_product->get_price(),
                    ];
                }
            }
        }

        return $data;
    }

    /**
     * Get taxonomy terms for a product
     *
     * @param WC_Product $product
     * @param string $taxonomy
     * @return array
     */
    private function get_taxonomy_terms($product, $taxonomy) {
        $terms = get_the_terms($product->get_id(), $taxonomy);
        if (!$terms || is_wp_error($terms)) {
            return [];
        }

        $data = [];
        foreach ($terms as $term) {
            $data[] = [
                'id'          => $term->term_id,
                'name'        => $term->name,
                'slug'        => $term->slug,
                'description' => $term->description,
                'count'       => $term->count,
            ];
        }

        return $data;
    }

    /**
     * Get product images
     *
     * @param WC_Product $product
     * @return array
     */
    private function get_images($product) {
        $images = [];
        $attachment_ids = array_merge(
            [$product->get_image_id()],
            $product->get_gallery_image_ids()
        );

        foreach ($attachment_ids as $attachment_id) {
            if (!$attachment_id) {
                continue;
            }

            $attachment = get_post($attachment_id);
            if (!$attachment) {
                continue;
            }

            $images[] = [
                'id'            => (int) $attachment_id,
                'date_created'  => wc_rest_prepare_date_response($attachment->post_date_gmt),
                'date_modified' => wc_rest_prepare_date_response($attachment->post_modified_gmt),
                'src'           => wp_get_attachment_url($attachment_id),
                'name'          => get_the_title($attachment),
                'alt'           => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
            ];
        }

        return $images;
    }

    /**
     * Get product attributes
     *
     * @param WC_Product $product
     * @return array
     */
    private function get_attributes($product) {
        $attributes = [];
        $product_attributes = $product->get_attributes();

        foreach ($product_attributes as $attribute) {
            $attributes[] = [
                'id'          => $attribute->get_id(),
                'name'        => $attribute->get_name(),
                'position'    => $attribute->get_position(),
                'visible'     => $attribute->get_visible(),
                'variation'   => $attribute->get_variation(),
                'options'     => $attribute->get_options(),
            ];
        }

        return $attributes;
    }

    /**
     * Get default attributes
     *
     * @param WC_Product $product
     * @return array
     */
    private function get_default_attributes($product) {
        $default_attributes = [];
        $product_default_attributes = $product->get_default_attributes();

        foreach ($product_default_attributes as $attribute) {
            if ( ! is_array( $attribute ) ) {
                continue;
            }

            $default_attributes[] = [
                'id'     => $attribute['id'] ?? '',
                'name'   => $attribute['name'] ?? '',
                'option' => $attribute['option'] ?? '',
            ];
        }

        return $default_attributes;
    }

    /**
     * Get variation attributes
     *
     * @param WC_Product_Variation $variation
     * @return array
     */
    private function get_variation_attributes($variation) {
        $attributes = [];
        $variation_attributes = $variation->get_variation_attributes();

        foreach ($variation_attributes as $attribute_name => $attribute_value) {
            $attributes[] = [
                'name'  => $attribute_name,
                'value' => $attribute_value,
            ];
        }

        return $attributes;
    }

    /**
     * Get collection parameters
     *
     * @return array
     */
    public function get_collection_params() {
        return [
            'page'     => [
                'description'       => __('Current page of the collection.', 'wp-cafe'),
                'type'              => 'integer',
                'default'           => 1,
                'minimum'           => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'per_page' => [
                'description'       => __('Maximum number of items to be returned in result set.', 'wp-cafe'),
                'type'              => 'integer',
                'default'           => -1,
                'minimum'           => -1,
                'maximum'           => 100,
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'search'   => [
                'description'       => __('Limit results to those matching a string.', 'wp-cafe'),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'category' => [
                'description'       => __('Limit result set to products assigned a specific category ID.', 'wp-cafe'),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'include'  => [
                'description'       => __('Limit result set to specific IDs.', 'wp-cafe'),
                'type'              => 'array',
                'items'             => [
                    'type' => 'integer',
                ],
                'default'           => [],
                'sanitize_callback' => 'wp_parse_id_list',
            ],
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- admin/small-list usage, performance acceptable
            'exclude'  => [
                'description'       => __('Ensure result set excludes specific IDs.', 'wp-cafe'),
                'type'              => 'array',
                'items'             => [
                    'type' => 'integer',
                ],
                'default'           => [],
                'sanitize_callback' => 'wp_parse_id_list',
            ],
            'orderby'  => [
                'description'       => __('Sort collection by object attribute.', 'wp-cafe'),
                'type'              => 'string',
                'default'           => 'date',
                'enum'              => ['date', 'id', 'include', 'title', 'slug', 'price', 'popularity', 'rating', 'menu_order'],
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'order'    => [
                'description'       => __('Order sort attribute ascending or descending.', 'wp-cafe'),
                'type'              => 'string',
                'default'           => 'DESC',
                'enum'              => ['ASC', 'DESC'],
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => 'rest_validate_request_arg',
            ],
        ];
    }

    /**
     * Check if a given request has permission to read products
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function get_items_permissions_check($request) {
        return apply_filters('wpcafe_product_read_permission', true, $request);
    }

    /**
     * Check if a given request has permission to read a product
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function get_item_permissions_check($request) {
        return apply_filters('wpcafe_product_item_permission', true, $request);
    }
} 