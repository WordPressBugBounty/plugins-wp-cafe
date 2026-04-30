<?php
namespace WpCafe\FoodOrder\Controllers;

use WpCafe\Abstract\Base_Rest_Controller;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Server;
use WpCafe\Models\Qrcode_Model;
use WpCafe\Resources\Qrcode_Resource;

/**
 * Qrcode controller
 *
 * Handles all REST API endpoints for Qrcode.
 *
 * @package WpCafe/Qrcode
 */
class Qrcode_Controller extends Base_Rest_Controller {
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
    protected $rest_base = 'qrcode';

    /**
     * Register all routes related to Qrcode
     *
     * @return void
     */
    public function register_routes(): void {
        
        register_rest_route( $this->namespace,
            '/' . $this->rest_base, [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'create_item'],
                'permission_callback' => [$this, 'create_item_permissions_check'],
            ],
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_items'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
            ],
            [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'bulk_delete_item'],
                'permission_callback' => [$this, 'delete_item_permissions_check'],
            ],
        ] );

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', 
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_item'],
                    'permission_callback' => [$this, 'get_item_permissions_check'],
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [$this, 'update_item'],
                    'permission_callback' => [$this, 'update_item_permissions_check'],
                ],
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [$this, 'delete_item'],
                    'permission_callback' => [$this, 'delete_item_permissions_check'],
                ],
            ]
        );
    }

    
    /**
     * Create a new Qrcode item
     *
     * @param \WP_REST_Request $request
     * @return WP_HTTP_Response|WP_Error
     */
    public function create_item($request) {
        $data = $this->prepare_item_for_database($request);

        if ( is_wp_error( $data ) ) {
            return $this->error( $data->get_error_message() );
        }

        $qrcode = Qrcode_Model::create( $data );


        if ( is_wp_error( $qrcode ) ) {
            return $this->error( $qrcode->get_error_message() );
        }

        $response = new Qrcode_Resource( $qrcode );

        return $this->response( $response );
    }

    /**
     * Permission check for creating a Qrcode
     *
     * @param \WP_REST_Request $request
     * @return bool
     */
    public function create_item_permissions_check($request): bool {
        return current_user_can('manage_options');
    }

    /**
     * Get a list of Qrcode items
     *
     * @param \WP_REST_Request $request
     * @return WP_HTTP_Response
     */
    public function get_items( $request ) {
        $per_page = ! empty( $request['per_page'] ) ? intval( $request['per_page'] ) : 10;
        $paged    = ! empty( $request['paged'] ) ? intval( $request['paged'] ) : 1;
        $search   = ! empty( $request['search'] ) ? sanitize_text_field( $request['search'] ) : '';
        $filter   = ! empty( $request['filter'] ) ? sanitize_text_field( $request['filter'] ) : '';
        $status   = ! empty( $request['status'] ) ? sanitize_text_field( $request['status'] ) : 'any';

        $args = [
            'post_status'    => $status,
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'search'         => $search,
            'filter'         => $filter,
        ];

        $response = Qrcode_Model::paginate( $args );
        $response['items'] = Qrcode_Resource::collection( $response['items'] );


        return $this->response( $response );
    }

    /**
     * Permission check for reading Qrcodes
     *
     * @param \WP_REST_Request $request
     * @return bool
     */
    public function get_items_permissions_check($request): bool {
        return current_user_can('manage_options');
    }

    /**
     * Get a single qrcide item
     *
     * @param \WP_REST_Request $request
     * @return WP_HTTP_Response|WP_Error
     */
    public function get_item($request) {
        $id = intval( $request['id'] );

        $qrcode = Qrcode_Model::find( $id );

        if ( ! $qrcode ) {
            return $this->error( __( 'Invalid qrcode id', 'wp-cafe' ) );
        }

        $response = new Qrcode_Resource( $qrcode );

        return $this->response( $response );
    }

    /**
     * Permission check for getting a single Qrcode
     *
     * @param \WP_REST_Request $request
     * @return bool
     */
    public function get_item_permissions_check($request): bool {
        return current_user_can( 'manage_options' );
    }

    /**
     * Update Qrcode.
     */
    public function update_item($request) {
        $id   = intval( $request['id'] );
        $data = $this->prepare_item_for_database( $request );

        if ( is_wp_error( $data ) ) {
            return $this->error( $data->get_error_message() );
        }

        $qrcode = Qrcode_Model::find( $id );

        if ( ! $qrcode ) {
            return $this->error( __( 'Invalid Qrcode id', 'wp-cafe' ) );
        }

        $qrcode->update( $data );

        $response = new Qrcode_Resource( $qrcode );

        return $this->response( $response );
    }

    /**
     * Permission check for updating a Qrcode
     *
     * @param \WP_REST_Request $request
     * @return bool
     */
    public function update_item_permissions_check($request): bool {
        return current_user_can('manage_options');
    }   

    /**
     * Delete Qrcode.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    public function delete_item($request) {
        $id = intval( $request['id'] );

        $qrcode = Qrcode_Model::find( $id );

        if ( ! $qrcode ) {
            return $this->error( __( 'Invalid Qrcode id', 'wp-cafe' ) );
        }

        $deleted = $qrcode->delete();

        if ( ! $deleted ) {
            return $this->error( __( 'Something went wrong. Qrcode could not be deleted', 'wp-cafe' ) );
        }

        $response = [
            'message' => __( 'Successfully deleted.', 'wp-cafe' )
        ];

        return $this->response( $response );
    }

    /**
     * Permission check for deleting a Qrcode
     *
     * @param \WP_REST_Request $request
     * @return bool
     */
    public function delete_item_permissions_check( $request ): bool {
        return current_user_can( 'manage_options' );
    }

    /**
     * Bulk delete Qrcodes.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    public function bulk_delete_item($request) {
        $ids = $request->get_param('ids');

        if ( ! is_array( $ids ) || empty( $ids )) {
            return $this->error(__('Invalid or empty Qrcodes IDs.', 'wp-cafe'), 400);
        }

        $deleted = [];

        foreach ( $ids as $id ) {
            $id = intval($id);
            $timed_product = Qrcode_Model::find( $id );

            if ( $timed_product ) {
                $timed_product->delete(); // Skip if Qrcode not found
            }

        }

        return $this->response( ['deleted' => $deleted], __( 'Selected Qrcode deleted.', 'wp-cafe' ) );
    }

    /**
     * Prepare item for database storage
     *
     * @param \WP_REST_Request $request
     * @return array|WP_Error
     */
    protected function prepare_item_for_database( $request ) {
        $data = json_decode( $request->get_body(), true );
        
        $table_id = ! empty( $data['table_id'] ) ? sanitize_text_field( $data['table_id'] ) : '';

        $table_name = ! empty( $data['table_name'] ) ? sanitize_text_field( $data['table_name'] ) : [];

        $page_url = ! empty( $data['page_url'] ) ? sanitize_text_field( $data['page_url'] ) : [];


        if ( ! $table_id ) {
            return new WP_Error( 'rules_error', __( 'Please enter table_id', 'wp-cafe' ) );
        }

        if ( ! $page_url ) {
            return new WP_Error( 'condition_error', __( 'Please enter products condition', 'wp-cafe' ) );
        }

        $prepare_data = [
            'table_id'    => $table_id,
            'table_name'  => $table_name,
            'page_url'    => $page_url
        ];

        return $prepare_data;
    }
}

