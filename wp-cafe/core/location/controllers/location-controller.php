<?php
namespace WpCafe\Location\Controllers;

use WpCafe\Abstract\Base_Rest_Controller;
use WpCafe\Models\Location_Model;
use WpCafe\Resources\Location_Resource;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Server;

/**
 * Location controller
 *
 * Handles all REST API endpoints for locations.
 *
 * @package WpCafe/Location
 */
class Location_Controller extends Base_Rest_Controller {
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
    protected $rest_base = 'locations';

    /**
     * Register all routes related to location
     *
     * @return void
     */
    public function register_routes(): void {

        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'create_item'],
                'permission_callback' => [$this, 'create_item_permissions_check'],
            ],
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_items'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'delete_items'],
                'permission_callback' => [$this, 'delete_item_permissions_check'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
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
        ]);
    }

    /**
     * Check user permissions
     *
     * @param \WP_REST_Request $request
     * @return bool
     */
    public function permissions_check($request): bool {
        return current_user_can('manage_options');
    }

    /**
     * Create a new location
     *
     * @param \WP_REST_Request $request
     * @return WP_HTTP_Response|WP_Error
     */
    public function create_item($request) {
        $data = $this->prepare_item_for_database($request);
        if (is_wp_error($data)) {
            return $this->error($data->get_error_message());
        }

        $location = Location_Model::create( $data['restaurant_name'], $data );
        if ( is_wp_error( $location ) ) {
            return $this->error( $location->get_error_message() );
        }

        return $this->response($location);
    }

    /**
	 * Check if a given request has access to create items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}

    /**
     * Get a list of locations
     *
     * @param \WP_REST_Request $request
     * @return WP_HTTP_Response
     */
    public function get_items($request) {
        $page      = $request->get_param( 'paged' ) ?: 1;
        $per_page  = $request->get_param( 'per_page' ) ?: 20;
        $search    = $request->get_param( 'search' ) ?: '';

        $result = Location_Model::paginated( $page, $per_page, $search );

        $result['items'] = Location_Resource::collection( $result['items'] );

        return $this->response( $result );
    }

    /**
     * Get a single location
     *
     * @param \WP_REST_Request $request
     * @return WP_HTTP_Response|WP_Error
     */
    public function get_item($request) {
        $id = intval( $request['id'] );
        $location = Location_Model::find( $id );
        if ( ! $location ) {
            return $this->error(__('Location not found', 'wp-cafe'), 404);
        }

        $location = new Location_Resource( $location );

        return $this->response( $location );
    }

    /**
	 * Check if a given request has access to get a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! $this->check_rate_limit() ) {
			return new \WP_Error( 'rate_limited', __( 'Too many requests.', 'wp-cafe' ), [ 'status' => 429 ] );
		}
		return true;
	}

	private function check_rate_limit( int $limit = 60, int $window = 60 ): bool {
		$ip    = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$key   = 'wpc_rate_loc_' . md5( $ip );
		$count = (int) get_transient( $key );
		if ( $count >= $limit ) {
			return false;
		}
		set_transient( $key, $count + 1, $window );
		return true;
	}

    /**
     * Update an existing location
     *
     * @param \WP_REST_Request $request
     * @return WP_HTTP_Response|WP_Error
     */
    public function update_item( $request ) {
        $id = intval($request['id']);
        $location = Location_Model::find($id);

        if ( ! $location ) {
            return $this->error(__('Location not found.', 'wp-cafe'), 404);
        }

        $data = $this->prepare_item_for_database( $request );

        if ( is_wp_error( $data ) ) {
            return $this->error( $data->get_error_message() );
        }

        $location->update( $data );

        return $this->response($location);
    }

    /**
	 * Check if a given request has access to update a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}

    /**
     * Delete a location
     *
     * @param \WP_REST_Request $request
     * @return WP_HTTP_Response
     */
    public function delete_item($request) {
        $id       = intval( $request['id'] );
        $location = Location_Model::find( $id );

        if ( ! $location ) {
            return $this->error( __('Location not found.', 'wp-cafe'), 404 );
        }

        $location->delete();

        return $this->response([
            'message'   => __('Location deleted.', 'wp-cafe')
        ]);
    }

    /**
	 * Check if a given request has access to delete a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|boolean
	 */
	public function delete_item_permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}

    /**
     * Bulk delete locations
     *
     * @param \WP_REST_Request $request
     * @return WP_HTTP_Response|WP_Error
     */
    public function delete_items( $request ) {
        $body = $request->get_body();
        $data = json_decode( $body, true );

        if ( ! isset( $data['ids'] ) || ! is_array( $data['ids'] ) ) {
            return $this->error( __( 'Invalid request. Expecting array of IDs.', 'wp-cafe' ), 400 );
        }

        $deleted = [];
        $failed  = [];

        foreach ( $data['ids'] as $id ) {
            $id = intval( $id );
            $location = Location_Model::find( $id );

            if ( $location ) {
                $location->delete();
                $deleted[] = $id;
            } else {
                $failed[] = $id;
            }
        }

        return $this->response([
            'message' => __( 'Bulk delete completed.', 'wp-cafe' ),
            'deleted' => $deleted,
            'failed'  => $failed,
        ]);
    }

    /**
     * Prepare location data for storage
     *
     * @param \WP_REST_Request $request
     * @return array|WP_Error
     */
    protected function prepare_item_for_database( $request ) {
        $body = $request->get_body();
        $data = json_decode( $body, true );

        $validate = wpcafe_validate( $data, [
            'restaurant_name' => [
                'required'
            ],
            'location'         => [
                'required'
            ]
        ] );

        if ( is_wp_error( $validate ) ) {
            return $validate;
        }

        return $data;
    }
}
