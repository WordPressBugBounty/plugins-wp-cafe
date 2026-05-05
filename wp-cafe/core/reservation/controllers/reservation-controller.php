<?php
namespace WpCafe\Reservation\Controllers;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Abstract\Base_Rest_Controller;
use WpCafe\Models\Reservation_Model;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Server;
use WpCafe\Resources\Reservation_Resource;
use WpCafe\Models\Reservation_Item_Model;
use WpCafe\Scheduler;
use WpCafe\Models\Location_Model;
use WpCafe\Session;

/**
 * Reservation controller
 *
 * Handles all REST API endpoints for reservations.
 *
 * @package WpCafe/Reservation
 */
class Reservation_Controller extends Base_Rest_Controller {
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
    protected $rest_base = 'reservations';

    /**
     * Register all routes related to reservation
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

        register_rest_route( $this->namespace,
            '/' . $this->rest_base . '/time-slots', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_slots'],
                'permission_callback' => [$this, 'get_slots_permissions_check'],
            ]
        ] );

        register_rest_route( $this->namespace,
            '/' . $this->rest_base . '/reservation-capacity', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_reservation_capacity'],
                'permission_callback' => [$this, 'get_reservation_capacity_permissions_check'],
            ]
        ] );

        register_rest_route( $this->namespace,
            '/' . $this->rest_base . '/reservation-cancel', [
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'cancel_reservation'],
                'permission_callback' => [$this, 'cancel_reservation_permissions_check'],
            ]
        ] );

        register_rest_route( $this->namespace,
            '/' . $this->rest_base . '/food-list', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_food_list'],
                'permission_callback' => [$this, 'get_food_list_permissions_check'],
            ]
        ] );

        register_rest_route( $this->namespace,
            '/' . $this->rest_base . '/cart-has-items', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'check_cart_has_items'],
                'permission_callback' => [$this, 'check_cart_has_items_permissions_check'],
            ]
        ] );
    }

    /**
     * Check whether user has legacy admin reservation access.
     *
     * @return bool
     */
    private function has_legacy_reservation_access(): bool {
        return current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' );
    }

    /**
     * Check whether user can view all reservations.
     *
     * @return bool
     */
    private function can_view_all_reservations(): bool {
        return $this->has_legacy_reservation_access()
            || current_user_can( 'wpcafe_view_all_reservations' )
            || current_user_can( 'wpcafe_manage_reservations' );
    }

    /**
     * Check whether user can view reservations.
     *
     * @return bool
     */
    private function can_view_reservations(): bool {
        return $this->can_view_all_reservations()
            || current_user_can( 'wpcafe_view_own_reservations' );
    }

    /**
     * Check whether user can manage reservations.
     *
     * @return bool
     */
    private function can_manage_reservations(): bool {
        return $this->has_legacy_reservation_access()
            || current_user_can( 'wpcafe_manage_reservations' );
    }

    /**
     * Check whether current user can only view own reservations.
     *
     * @return bool
     */
    private function view_own_reservations_only(): bool {
        return ! $this->can_view_all_reservations()
            && current_user_can( 'wpcafe_view_own_reservations' );
    }


    /**
     * Create a new reservation item
     *
     * @param \WP_REST_Request $request
     * @return WP_HTTP_Response|WP_Error
     */
    public function create_item($request) {
        $data = $this->prepare_item_for_database($request);

        if ( is_wp_error( $data ) ) {
            return $this->error( $data->get_error_message() );
        }

        // Validate guest capacity before creating reservation
        $capacity_validation = Reservation_Model::validate_guest_capacity(
            $data['total_guest'] ?? 1,
            $data['date'] ?? '',
            $data['start_time'] ?? '',
            $data['end_time'] ?? '',
            $data['branch_id'] ?? ''
        );

        if ( is_wp_error( $capacity_validation ) ) {
            return $this->error( $capacity_validation->get_error_message() );
        }

        $food_items = [];

        if ( ! empty( $data['food_items'] ) ) {
            $food_items = $data['food_items'];
            unset( $data['food_items'] );
        }

        $data['invoice'] = 'WPC' . wp_rand( 1000, 9999 );

        if ( empty( $data['status'] ) ) {
            $data['status'] = wpc_get_option( 'reservation_status', 'pending' );
        }

        $reservation = Reservation_Model::create( $data );

        if ( is_wp_error( $reservation ) ) {
            return $this->error( $reservation->get_error_message() );
        }

        if ( $this->food_menu_is_visible_in_reservation_form() ) {
            $food_items = $this->create_food_items_from_woocart( $reservation->id );
        }

        if ( ! empty( $food_items ) ) {
            $reservation->update( [ 'food_order' => 'yes' ] );
        }
        $this->set_reservation_data_in_woocommerce_session( $reservation );

        $payment_token = $this->issue_reservation_payment_token( $reservation->id );

        $response = new Reservation_Resource( $reservation );

        do_action( 'wpcafe_after_reservation_create', $reservation );

        $payload = array_merge( $response->to_array(), [ 'payment_token' => $payment_token ] );

        return $this->response( $payload, __( 'Reservation created successfully.', 'wp-cafe' ) );
    }

    /**
     * Issue a one-time, short-lived payment token for the reservation.
     *
     * Returns the raw token to the caller and stores only its SHA-256 hash
     * plus expiry as post meta so the payment endpoint can verify ownership
     * for guest flows without relying on WC session cookie continuity.
     *
     * @param int $reservation_id Reservation post ID.
     * @return string Raw token (64 hex chars).
     */
    private function issue_reservation_payment_token( int $reservation_id ): string {
        $token = bin2hex( random_bytes( 32 ) );

        update_post_meta( $reservation_id, '_wpc_payment_token', hash( 'sha256', $token ) );
        update_post_meta( $reservation_id, '_wpc_payment_token_expires', time() + ( 2 * HOUR_IN_SECONDS ) );

        return $token;
    }

    /**
     * Stores reservation data in WC session
     *
     * Stores reservation data in WC session to be used later
     * in the checkout process.
     *
     * @param Reservation_Model $reservation Reservation data
     */
    private function set_reservation_data_in_woocommerce_session( $reservation ) {
        if ( function_exists( 'WC' ) ) {
            // Initialize WooCommerce session if not already available
            if ( ! WC()->session ) {
                WC()->session = new \WC_Session_Handler();
                WC()->session->init();
            }
            
            if ( WC()->session ) {
                $session_data = [
                    'reservation_id' => $reservation->id,
                    'reservation_date' => $reservation->date ?? '',
                    'start_time'      => $reservation->start_time ?? '',
                    'end_time'        => $reservation->end_time ?? '',
                    'name'            => $reservation->name ?? '',
                    'email'           => $reservation->email ?? '',
                    'phone'           => $reservation->phone ?? '',
                    'total_guest'     => $reservation->total_guest ?? '',
                    'notes'           => $reservation->notes ?? '',
                    'branch_name'     => $reservation->branch_name ?? ''
                ];

                $custom_fields = $reservation->custom_fields ?? [];
                if ( ! empty( $custom_fields ) ) {
                    $session_data['custom_fields'] = $custom_fields;
                }

                WC()->session->set( 'wpc_reservation_data', $session_data );
            }
        }
    }

    /**
     * Checks if food menu is visible in reservation form
     *
     * @return bool
     */
    private function food_menu_is_visible_in_reservation_form() : bool {
        $reservation_form_customization = wpc_get_option('reservation_form_customization');

        if ( ! is_array($reservation_form_customization ) ) {
            return false;
        }

        foreach ( $reservation_form_customization as $reservation_step ) {
            if ( ! isset( $reservation_step['fields'] ) || ! is_array( $reservation_step['fields'] ) ) {
                continue;
            }

            foreach ( $reservation_step['fields'] as $field ) {
                if ( 'food_menu' === $field['type']  && $field['visible'] == true ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Permission check for creating a reservation
     *
     * @param \WP_REST_Request $request
     * @return bool
     */
    public function create_item_permissions_check($request): bool {
        return $this->verify_rest_nonce( $request );
    }

    /**
     * Get a list of reservation items
     *
     * @param \WP_REST_Request $request
     * @return WP_HTTP_Response
     */
    public function get_items( $request ) {
        $per_page = ! empty( $request['per_page'] ) ? intval( $request['per_page'] ) : 10;
        $paged    = ! empty( $request['paged'] ) ? intval( $request['paged'] ) : 1;
        $search   = ! empty( $request['search'] ) ? sanitize_text_field( $request['search'] ) : '';
        $status   = ! empty( $request['status'] ) ? sanitize_text_field( $request['status'] ) : 'any';


        $filter = [];

        if ( isset( $request['status'] ) ) {
            $filter['status'] = sanitize_text_field( $request['status'] );
        }

        if ( isset( $request['branch'] ) ) {
            $filter['branch'] = sanitize_text_field( $request['branch'] );
        }

        if ( isset( $request['food_order'] ) ) {
            $filter['food_order'] = sanitize_text_field( $request['food_order'] );
        }

        if ( isset( $request['date_range'] ) ) {
            $filter['date_range'] = sanitize_text_field( $request['date_range'] );
        }

        $args = [
            'post_status'    => $status,
            'posts_per_page' => $per_page,
            'paged'          => $paged,
        ];

        if ( $this->view_own_reservations_only() ) {
            $filter['email'] = wp_get_current_user()->user_email;
        }

        if ( ! empty( $search ) ) {
            $args['search'] = $search;
        }

        if ( ! empty( $filter ) ) {
            $args['filters'] = $filter;
        }

        $data = Reservation_Model::paginate( $args );

        if ( ! $data ) {
            return $this->error( __( 'No reservations found', 'wp-cafe' ), 404 );
        }

        $response = Reservation_Resource::collection( $data['items'] );

        $data['items'] = $response;

        return $this->response( $data );
    }

    /**
     * Permission check for reading reservations
     *
     * @param \WP_REST_Request $request
     * @return bool
     */
    public function get_items_permissions_check($request): bool {
        return $this->can_view_reservations();
    }

    /**
     * Get a single reservation item
     *
     * @param \WP_REST_Request $request
     * @return WP_HTTP_Response|WP_Error
     */
    public function get_item($request) {
        $id = intval( $request['id'] );

        $reservation = Reservation_Model::find($id);

        if ( ! $reservation ) {
            return $this->error(__('Reservation not found', 'wp-cafe'), 404);
        }

        if ( $this->view_own_reservations_only() ) {
            $reservation_email  = (string) get_post_meta( $id, 'email', true );
            $current_user_email = (string) wp_get_current_user()->user_email;

            if ( '' === $current_user_email || strcasecmp( $reservation_email, $current_user_email ) !== 0 ) {
                return $this->error( __( 'You do not have permission to view this reservation.', 'wp-cafe' ), 403 );
            }
        }
        
        $response = new Reservation_Resource( $reservation );

        return $this->response( $response );
    }

    /**
     * Permission check for getting a single reservation
     *
     * @param \WP_REST_Request $request
     * @return bool
     */
    public function get_item_permissions_check($request): bool {
        return $this->can_view_reservations();
    }

    /**
     * Update reservation.
     */
    public function update_item($request) {
        $id = intval($request['id']);
        $reservation = Reservation_Model::find($id);

        if ( ! $reservation ) {
            return $this->error( __('Reservation not found.', 'wp-cafe'), 404 );
        }

        $data = $this->prepare_item_for_database($request);

        if ( is_wp_error( $data ) ) {
            return $this->error($data->get_error_message());
        }

        $old_status = $reservation->status;
        $old_reservation_data = [
            'name'          => $reservation->name,
            'email'         => $reservation->email,
            'phone'         => $reservation->phone,
            'date'          => $reservation->date,
            'start_time'    => $reservation->start_time,
            'end_time'      => $reservation->end_time,
            'total_guest'   => $reservation->total_guest,
            'table_name'    => $reservation->table_name,
            'branch_id'     => $reservation->branch_id,
            'branch_name'   => $reservation->branch_name,
            'status'        => $reservation->status,
            'notes'         => $reservation->notes,
            'booking_amount'=> $reservation->booking_amount,
            'total_price'   => $reservation->total_price,
            'currency'      => $reservation->currency,
            'payment_method'=> $reservation->payment_method,
            'food_order'    => $reservation->food_order,
            'invoice'       => $reservation->invoice,
        ];
        $updated = $reservation->update($data);

        if ( ! $updated ) {
            return $this->error( __('Failed to update reservation.', 'wp-cafe'), 500 );
        }

        // Trigger cancellation hook if status changed to 'cancelled'
        if ( 'cancelled' !== $old_status  &&  'cancelled' === $reservation->status ) {
            do_action( 'wpcafe_after_reservation_cancelled', $reservation );
        }

        // Trigger status change hook for non-cancelled status transitions
        if ( isset( $data['status'] ) && $old_status !== $reservation->status && 'cancelled' !== $reservation->status ) {
            do_action( 'wpcafe_after_reservation_status_changed', $reservation, $old_status );
        }

        // Trigger updated hook only when non-status fields have actually changed
        if ( $this->has_reservation_field_changes( $old_reservation_data, $data ) ) {
            do_action( 'wpcafe_after_reservation_update', $reservation, $old_reservation_data );
        }

        $response = new Reservation_Resource( $reservation );

        return $this->response( $response, __('Reservation updated successfully.', 'wp-cafe') );
    }

    /**
     * Permission check for updating a reservation
     *
     * @param \WP_REST_Request $request
     * @return bool
     */
    public function update_item_permissions_check($request): bool {
        return $this->can_manage_reservations();
    }   

    /**
     * Delete reservation.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    public function delete_item($request) {
        if ( ! $this->verify_rest_nonce( $request ) ) {
            return $this->error( __('Invalid security token', 'wp-cafe'), 403, 'invalid_nonce' );
        }

        $id = intval( $request['id'] );
        $reservation = Reservation_Model::find($id);

        if ( ! $reservation ) {
            return $this->error( __('Reservation not found.', 'wp-cafe'), 404 );
        }

        $deleted = $reservation->delete();

        if ( ! $deleted ) {
            return $this->error( __('Failed to delete reservation.', 'wp-cafe'), 500 );
        }

        do_action('wpcafe_after_reservation_delete', $deleted );

        return $this->response( ['deleted' => true], __('Reservation deleted.', 'wp-cafe') );
    }

    /**
     * Permission check for deleting a reservation
     *
     * @param \WP_REST_Request $request
     * @return bool
     */
    public function delete_item_permissions_check( $request ): bool {
        return $this->can_manage_reservations();
    }

    /**
     * Bulk delete reservations.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    public function bulk_delete_item($request) {
        if ( ! $this->verify_rest_nonce( $request ) ) {
            return $this->error( __('Invalid security token', 'wp-cafe'), 403, 'invalid_nonce' );
        }

        $ids = $request->get_param('ids');

        if ( ! is_array( $ids ) || empty( $ids )) {
            return $this->error(__('Invalid or empty reservation IDs.', 'wp-cafe'), 400);
        }

        $deleted = [];

        foreach ( $ids as $id ) {
            $id = intval($id);
            $reservation = Reservation_Model::find( $id );

            if ( $reservation ) {
                $deleted_item = $reservation->delete(); // Skip if reservation not found

                do_action('wpcafe_after_reservation_delete', $deleted_item);

                $deleted[] = $deleted_item; 
            }

        }

        return $this->response( ['deleted' => $deleted], __( 'Selected reservations deleted.', 'wp-cafe' ) );
    }

    /**
     * Prepare item for database storage
     *
     * @param \WP_REST_Request $request
     * @return array|WP_Error
     */
    protected function prepare_item_for_database( $request ) {
        $body = $request->get_body();
        $data = json_decode( $body, true );

        if ( ! is_array( $data ) ) {
            return [];
        }

        $has_date       = ! empty( $data['date'] );
        $has_start_time = ! empty( $data['start_time'] );
        $has_end_time   = ! empty( $data['end_time'] );

        if ( $has_date && ( $has_start_time || $has_end_time ) ) {
            $date = $data['date'];

            if ( $has_start_time ) {
                $data['start_time'] = $this->parse_time_to_timestamp( $date, $data['start_time'] );
            }
            if ( $has_end_time ) {
                $data['end_time'] = $this->parse_time_to_timestamp( $date, $data['end_time'] );
            }
        }

        if ( ! $has_date ) {
            unset( $data['date'] );
        }
        if ( ! $has_start_time ) {
            unset( $data['start_time'] );
        }
        if ( ! $has_end_time ) {
            unset( $data['end_time'] );
        }

        if ( empty( $data['table_name'] ) ) {
            $table_id_from_session = wpc_get_table_id_from_session();
            if ( ! empty( $table_id_from_session ) ) {
                $data['table_name'] = $table_id_from_session;
            }
        }

        // Sanitize user-supplied fields before validation and storage.
        if ( isset( $data['name'] ) ) {
            $data['name'] = sanitize_text_field( $data['name'] );
        }
        if ( isset( $data['phone'] ) ) {
            $data['phone'] = sanitize_text_field( $data['phone'] );
        }
        if ( isset( $data['email'] ) ) {
            $data['email'] = sanitize_email( $data['email'] );
        }
        if ( isset( $data['notes'] ) ) {
            $data['notes'] = sanitize_textarea_field( $data['notes'] );
        }
        if ( isset( $data['table_name'] ) ) {
            $data['table_name'] = sanitize_text_field( $data['table_name'] );
        }
        if ( isset( $data['date'] ) ) {
            $data['date'] = sanitize_text_field( $data['date'] );
        }
        if ( isset( $data['branch_name'] ) ) {
            $data['branch_name'] = sanitize_text_field( $data['branch_name'] );
        }
        if ( isset( $data['status'] ) ) {
            $data['status'] = sanitize_text_field( $data['status'] );
        }
        if ( isset( $data['total_guest'] ) ) {
            $data['total_guest'] = intval( $data['total_guest'] );
        }
        if ( isset( $data['branch_id'] ) ) {
            $data['branch_id'] = absint( $data['branch_id'] );
        }
        if ( isset( $data['booking_amount'] ) ) {
            $data['booking_amount'] = floatval( $data['booking_amount'] );
        }
        if ( isset( $data['total_price'] ) ) {
            $data['total_price'] = floatval( $data['total_price'] );
        }
        if ( isset( $data['seats'] ) && is_array( $data['seats'] ) ) {
            $data['seats'] = array_values( array_filter( array_map(
                'sanitize_text_field',
                array_map( 'wp_unslash', $data['seats'] )
            ) ) );
        }

        $validate = wpcafe_validate( $data , [
            'name' => [
                'required',
                'string',
            ],
            'email' => [
                'required',
                'email',
            ],
        ]);



        if ( is_wp_error( $validate ) ) {
            return $validate;
        }

        $data = $this->separate_custom_fields_from_data( $data );

        return $data;
    }

    /**
     * Separates custom fields from reservation data.
     *
     * Extracts fields that are defined in form customization settings
     * but are not part of the Reservation_Model into a separate 
     * custom_fields array.
     *
     * @param array $data The reservation data from request.
     *
     * @return array Modified data with custom_fields key added.
     */
    private function separate_custom_fields_from_data( array $data ): array {
        $fillable_keys = $this->get_fillable_keys(); // Get fillable keys from Reservation_Model
        $custom_field_types = $this->get_custom_field_types(); // Get custom field ID => type map

        $custom_fields = [];

        foreach ( $data as $key => $value ) {
            if ( $this->is_custom_field( $key, $fillable_keys, array_keys( $custom_field_types ) ) ) {
                $field_type = $custom_field_types[ $key ] ?? 'text';
                $custom_fields[ $key ] = $this->sanitize_custom_field_value( $value, $field_type );
                unset( $data[ $key ] );
            }
        }

        if ( ! empty( $custom_fields ) ) {
            $data['custom_fields'] = $custom_fields;
        }

        return $data;
    }


    /**
     * Checks if a key represents a custom field.
     *
     * A field is considered custom if it's not a core fillable field
     * but exists in the form customization settings.
     *
     * @param string $key              The field key to check.
     * @param array  $fillable_keys    Core model fillable keys.
     * @param array  $custom_field_ids Custom field IDs from settings.
     *
     * @return bool True if the key is a custom field.
     */
    private function is_custom_field( string $key, array $fillable_keys, array $custom_field_ids ): bool {
        // If it's a core fillable field, it's not a custom field
        if ( in_array( $key, $fillable_keys, true ) ) {
            return false;
        }

        // Check if it exists in custom field definitions
        return in_array( $key, $custom_field_ids, true );
    }

    /**
     * Sanitizes a custom field value based on its type.
     *
     * @param mixed  $value The field value to sanitize.
     * @param string $type  The field type (text, select, textarea, radio, checkbox).
     *
     * @return mixed Sanitized value.
     */
    private function sanitize_custom_field_value( $value, string $type ) {
        switch ( $type ) {
            case 'textarea':
                return is_string( $value ) ? sanitize_textarea_field( $value ) : '';

            case 'checkbox':
                return is_array( $value ) ? array_map( 'sanitize_text_field', $value ) : [];

            case 'text':
            case 'select':
            case 'radio':
            default:
                return is_string( $value ) ? sanitize_text_field( $value ) : sanitize_text_field( (string) $value );
        }
    }

    /**
     * Gets fillable keys from the Reservation Model.
     *
     * @return array Array of fillable key names.
     */
    private function get_fillable_keys(): array {
        $model = new Reservation_Model();
        return $model->get_fillable_keys();
    }

    /**
     * Gets custom field IDs from customization settings.
     *
     * @return array Array of custom field IDs.
     */
    private function get_custom_field_ids(): array {
        return array_keys( $this->get_custom_field_types() );
    }

    /**
     * Gets custom field ID-to-type map from customization settings.
     *
     * Extracts all field IDs and their types defined in the reservation
     * form customization settings across all steps.
     *
     * @return array Associative array of field ID => field type.
     */
    private function get_custom_field_types(): array {
        $custom_field_types = [];
        $customization_settings = wpc_get_option( 'reservation_form_customization', [] );

        if ( empty( $customization_settings ) ) {
            return $custom_field_types;
        }

        foreach ( $customization_settings as $step ) {
            if ( empty( $step['fields'] ) ) {
                continue;
            }

            foreach ( $step['fields'] as $field ) {
                if ( ! empty( $field['id'] ) ) {
                    $custom_field_types[ $field['id'] ] = $field['type'] ?? 'text';
                }
            }
        }

        return $custom_field_types;
    }

    /**
     * Create food items from woocommerce cart items
     * 
     * @param int $reservation_id
     * 
     * @return array Array of Reservation_Item_Model instances
     */
    public function create_food_items_from_woocart( $reservation_id ) {
        if ( function_exists('wc_load_cart') && is_null( WC()->cart ) ) {
            wc_load_cart();
        }

        $cart_available = WC()->cart ? true : false; // Check if WooCommerce is active and cart is available

        if ( ! class_exists('WooCommerce') || ! $cart_available ) {
            return [];
        }

        $cart = WC()->cart;
        if ( $cart->is_empty() ) {
            return [];
        }

        $reservation_items = [];

        foreach ( $cart->get_cart() as $cart_item ) {
            $product = $cart_item['data'];
            
            if ( ! ( $product instanceof \WC_Product ) ) {
                continue;
            }

            // Create reservation item data
            $item = [
                'reservation_id' => $reservation_id,
                'product_id'     => $cart_item['product_id'],
                'product_name'   => $product->get_name(),
                'quantity'       => $cart_item['quantity'],
                'price'          => $product->get_price(),
            ];

            $reservation_item = Reservation_Item_Model::create( $item );

            if ( ! is_wp_error( $reservation_item ) ) {
                $reservation_items[] = $reservation_item;
            }
        }

        return $reservation_items;
    }

    /**
     * Get time slots
     *
     * @param \WP_REST_Request $request
     * @return WP_HTTP_Response
     */
    public function get_slots($request) {
        $start_date     = $request->get_param('start_date');
        $end_date       = $request->get_param('end_date');
        $location_id    = $request->get_param('location_id') ?? null;
        $schedules      = wpc_get_reservation_schedule( $location_id );
        $total_capacity = wpc_get_reservation_capacity( $location_id );

        if ( ! $start_date ) {
            return $this->error( __( 'Please start date', 'wp-cafe' ) );
        }

        if ( ! $end_date ) {
            return $this->error( __( 'Please end date', 'wp-cafe' ) );
        }

        if ( ! $schedules ) {
            return $this->error(__('Schedules did not set', 'wp-cafe'), 409);
        }

        // Get reservation settings for date range validation
        $reservation_advanced = wpc_get_reservation_advanced( $location_id );
        $early_booking_time  = wpc_get_reservation_early_booking_time( $location_id );

        // Validate and adjust date range based on settings
        $date_range = $this->validate_and_adjust_date_range(
            $start_date,
            $end_date,
            $reservation_advanced,
            $early_booking_time
        );

        // If no valid dates in range, return empty response
        if ( empty( $date_range ) ) {
            return $this->response( [] );
        }

        $scheduler = new Scheduler(
            $schedules,
            $date_range['start_date'],
            $date_range['end_date'],
            $total_capacity,
            $location_id
        );

        $slots = $scheduler->generate();

        return $this->response($slots);
    }

    /**
     * Validate and adjust date range based on reservation settings
     *
     * @param string $start_date Requested start date (Y-m-d)
     * @param string $end_date Requested end date (Y-m-d)
     * @param array $reservation_advanced Minimum lead time setting
     * @param string|array $early_booking_time Maximum booking horizon setting
     * @return array Adjusted date range or empty array if no valid dates
     */
    private function validate_and_adjust_date_range( $start_date, $end_date, $reservation_advanced, $early_booking_time ) {
        $timezone = wp_timezone();
        $now = new \DateTime( 'now', $timezone );
        $now->setTime( 0, 0, 0 );

        $start = new \DateTime( $start_date, $timezone );
        $end   = new \DateTime( $end_date, $timezone );

        // Calculate minimum allowed datetime based on reservation_advanced
        $minimum_datetime = $this->calculate_minimum_booking_datetime( $reservation_advanced, $now );

        // Adjust start_date if before minimum allowed
        if ( $start < $minimum_datetime ) {
            $start = clone $minimum_datetime;
        }

        // Calculate maximum allowed date based on early_booking_time
        $maximum_date = $this->calculate_maximum_booking_date( $early_booking_time, $now );

        // Adjust end_date if after maximum allowed
        if ( $end > $maximum_date ) {
            $end = clone $maximum_date;
        }

        // Ensure start is before end after adjustments
        if ( $start >= $end ) {
            return [];
        }

        return [
            'start_date' => $start->format( 'Y-m-d' ),
            'end_date'   => $end->format( 'Y-m-d' ),
        ];
    }

    /**
     * Calculate minimum booking datetime from advance reservation setting
     *
     * @param array $reservation_advanced Advance reservation setting
     * @param DateTime $now Current datetime (start of day in WP timezone)
     * @return DateTime Minimum allowed booking datetime
     */
    private function calculate_minimum_booking_datetime( $reservation_advanced, $now ) {
        $minimum = clone $now;

        if ( empty( $reservation_advanced ) || ! is_array( $reservation_advanced ) ) {
            return $minimum;
        }

        $value = intval( $reservation_advanced['value'] ?? 0 );
        $unit  = $reservation_advanced['unit'] ?? 'minutes';

        switch ( $unit ) {
            case 'minutes':
                $minimum->add( new \DateInterval( "PT{$value}M" ) );
                break;
            case 'hours':
                $minimum->add( new \DateInterval( "PT{$value}H" ) );
                break;
            case 'days':
                $minimum->add( new \DateInterval( "P{$value}D" ) );
                break;
        }

        return $minimum;
    }

    /**
     * Calculate maximum booking date from early booking time limit
     *
     * @param string|array $early_booking_time Early booking time setting
     * @param DateTime $now Current datetime (start of day in WP timezone)
     * @return DateTime Maximum allowed booking date
     */
    private function calculate_maximum_booking_date( $early_booking_time, $now ) {
        $maximum = clone $now;

        // "any_time" means no restriction - use 1 year ahead as practical limit
        if ( $early_booking_time === 'any_time' || empty( $early_booking_time ) ) {
            $maximum->add( new \DateInterval( "P1Y" ) );
            return $maximum;
        }

        if ( ! is_array( $early_booking_time ) ) {
            return $maximum;
        }

        $value = intval( $early_booking_time['value'] ?? 0 );
        $unit  = $early_booking_time['unit'] ?? 'days';

        switch ( $unit ) {
            case 'days':
                $maximum->add( new \DateInterval( "P{$value}D" ) );
                break;
            case 'weeks':
                $maximum->add( new \DateInterval( "P" . ( $value * 7 ) . "D" ) );
                break;
            case 'months':
                $maximum->add( new \DateInterval( "P{$value}M" ) );
                break;
        }

        return $maximum;
    }

    /**
     * Transient-based IP rate limiter for public endpoints.
     * Returns true if the request is within the allowed limit, false otherwise.
     *
     * @param int $limit  Max requests allowed within $window seconds.
     * @param int $window Time window in seconds.
     * @return bool
     */
    private function check_rate_limit( int $limit = 30, int $window = 60 ): bool {
        $ip    = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
        $key   = 'wpc_rate_' . md5( $ip );
        $count = (int) get_transient( $key );

        if ( $count >= $limit ) {
            return false;
        }

        set_transient( $key, $count + 1, $window );
        return true;
    }

    /**
     * Permission check for getting time slots
     *
     * @param \WP_REST_Request $request
     * @return bool|\WP_Error
     */
    public function get_slots_permissions_check($request) {
        if ( ! $this->check_rate_limit() ) {
            return new \WP_Error( 'rate_limited', __( 'Too many requests. Please try again later.', 'wp-cafe' ), [ 'status' => 429 ] );
        }
        return true;
    }

    /**
     * Get reservation capacity
     *
     * @param \WP_REST_Request $request
     * @return WP_HTTP_Response
     */
    public function get_reservation_capacity($request) {
        $date       = $request->get_param('date');
        $start_time = $request->get_param('start_time');
        $end_time   = $request->get_param('end_time');
        $branch_id  = $request->get_param('branch_id');

        if ( $branch_id === "undefined" || $branch_id === "null" ) {
            $branch_id = null;
        }

        if ( empty( $date ) ) {
            return $this->error( __( 'Please enter date', 'wp-cafe' ) );
        }

        if ( empty( $start_time ) ) {
            return $this->error( __( 'Please enter start time', 'wp-cafe' ) );
        }

        if ( empty( $end_time ) ) {
            return $this->error( __( 'Please enter end time', 'wp-cafe' ) );
        }

        $booked_capacity = Reservation_Model::get_total_guest_by_date_time($date, $start_time, $end_time, $branch_id );

        $total_capacity = wpc_get_reservation_capacity( $branch_id );

        $available_capacity = $total_capacity - $booked_capacity;

        // Get booked seat IDs for seat-plan integration
        $booked_seat_ids = $this->get_booked_seats( $date, $start_time, $end_time, $branch_id );

        return $this->response([
            'available_capacity' => $available_capacity,
            'booked_capacity'    => $booked_capacity,
            'total_capacity'     => $total_capacity,
            'booked_seat_ids'    => $booked_seat_ids,
        ]);
    }

    /**
     * Permission check for getting reservation capacity
     *
     * @param \WP_REST_Request $request
     * @return bool|\WP_Error
     */
    public function get_reservation_capacity_permissions_check($request) {
        if ( ! $this->check_rate_limit() ) {
            return new \WP_Error( 'rate_limited', __( 'Too many requests. Please try again later.', 'wp-cafe' ), [ 'status' => 429 ] );
        }
        return true;
    }

    /**
     * Helper method to find reservation by invoice and email
     *
     * @param string $invoice
     * @param string $email
     * @return Reservation_Model|null
     */
    private function find_reservation_by_invoice_and_email($invoice, $email) {
        if (empty($invoice) || empty($email)) {
            return null;
        }

        $args = [
            'post_type' => 'wpc_reservation',
            'post_status' => ['confirmed', 'pending', 'cancelled'],
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- required for report/filter functionality
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'invoice',
                    'value' => $invoice,
                    'compare' => '=',
                ],
            ]
        ];

        $posts = get_posts($args);

        if (empty($posts)) {
            return null;
        }

        $reservation = new Reservation_Model($posts[0]);
        
        // Verify email matches
        if ($reservation->email !== $email) {
            return null;
        }

        return $reservation;
    }

    /**
     * Cancel reservation
     *
     * @param \WP_REST_Request $request
     * @return WP_HTTP_Response
     */
    public function cancel_reservation($request) {
        if ( ! $this->verify_rest_nonce( $request ) ) {
            return $this->error( __( 'Security check failed. Please try again.', 'wp-cafe' ), 403 );
        }

        $invoice = sanitize_text_field( $request->get_param('invoice') ?? '' );
        $email   = sanitize_email( $request->get_param('email') ?? '' );
        $notes   = sanitize_textarea_field( $request->get_param( 'notes' ) ?? '' );
        $phone   = sanitize_text_field( $request->get_param('phone') ?? '' );

        if ( empty( $invoice ) ) {
            return $this->error( __( 'Please enter invoice', 'wp-cafe' ) );
        }

        if ( empty( $email ) ) {
            return $this->error( __( 'Please enter email', 'wp-cafe' ) );
        }

        $reservation = $this->find_reservation_by_invoice_and_email($invoice, $email);

        if ( ! $reservation ) {
            return $this->error( __( 'Reservation not found', 'wp-cafe' ) );
        }

        $status = get_post_status( $reservation->id );

        if ( 'cancelled' === $status ) {
            return $this->error( __( 'Reservation already cancelled', 'wp-cafe' ) );
        }

        $reservation->update([
            'status' => 'cancelled',
            'notes'  => $notes,
        ]);

        do_action( 'wpcafe_after_reservation_cancelled', $reservation );

        return $this->response( __( 'Reservation cancelled successfully', 'wp-cafe' ) );
    }

    /**
     * Permission check for canceling a reservation
     *
     * @param \WP_REST_Request $request
     * @return bool
     */
    public function cancel_reservation_permissions_check($request): bool {
        if ( ! $this->verify_rest_nonce( $request ) ) {
            return false;
        }
        $invoice     = $request->get_param( 'invoice' );
        $email       = $request->get_param( 'email' );
        $reservation = $this->find_reservation_by_invoice_and_email( $invoice, $email );
        return (bool) $reservation;
    }

    /**
     * Get food list
     *
     * @param \WP_REST_Request $request
     * @return WP_HTTP_Response
     */
    public function get_food_list($request) {
        $content = "";

        $branch_id = $request->get_param('branch_id');

        if ( ! empty($branch_id) ) {
            $selected_location = ! empty($branch_id) ? intval($branch_id) : '';
            Session::set( 'selected_location', $selected_location );
        }

        if ( wpc_is_module_enable('food_ordering') ) {
            $shortcode_attributes = $this->get_food_menu_attributes_from_settings();
            $content = do_shortcode("[wpc_reservation_with_food {$shortcode_attributes}]");
        }

        // Return empty string if content has no food menu items
        if ( ! empty($content) && strpos($content, 'wpc-food-menu-item') === false ) {
            $content = "";
        }

        return $this->response($content);
    }

    /**
     * Get food menu attributes from reservation form customization settings
     *
     * @return string Formatted shortcode attributes string
     */
    private function get_food_menu_attributes_from_settings(): string {
        $reservation_form_customization = wpc_get_option('reservation_form_customization', []);

        if ( ! is_array($reservation_form_customization) ) {
            return '';
        }

        $food_menu_fields = [];

        foreach ( $reservation_form_customization as $step ) {
            if ( ! isset($step['fields']) || ! is_array($step['fields']) ) {
                continue;
            }

            foreach ( $step['fields'] as $field ) {
                if ( isset($field['type']) && $field['type'] === 'food_menu' && isset($field['food_menu_fields']) && is_array($field['food_menu_fields']) ) {
                    $food_menu_fields = $field['food_menu_fields'];
                    break 2;
                }
            }
        }

        if ( empty( $food_menu_fields ) ) {
            return '';
        }

        // Map the settings to shortcode attributes
        $attributes = [];

        if ( isset($food_menu_fields['wpc_food_categories']) && is_array($food_menu_fields['wpc_food_categories']) ) {
            $categories_csv = implode(',', $food_menu_fields['wpc_food_categories']);
            $attributes[] = "wpc_food_categories=\"{$categories_csv}\"";
        }

        if ( isset($food_menu_fields['style']) ) {
            $attributes[] = "style=\"{$food_menu_fields['style']}\"";
        }

        if ( isset($food_menu_fields['template']) ) {
            $attributes[] = "template=\"{$food_menu_fields['template']}\"";
        }

        if ( isset($food_menu_fields['wpc_show_desc']) ) {
            $attributes[] = "wpc_show_desc=\"{$food_menu_fields['wpc_show_desc']}\"";
        }

        if ( isset($food_menu_fields['show_thumbnail']) ) {
            $attributes[] = "show_thumbnail=\"{$food_menu_fields['show_thumbnail']}\"";
        }

        if ( isset($food_menu_fields['wpc_cart_button']) ) {
            $attributes[] = "wpc_cart_button=\"{$food_menu_fields['wpc_cart_button']}\"";
        }

        if ( isset($food_menu_fields['no_of_product']) ) {
            $attributes[] = "no_of_product=\"{$food_menu_fields['no_of_product']}\"";
        }

        return implode(' ', $attributes);
    }

    /**
     * Permission check for getting food list
     *
     * @return bool|\WP_Error
     */
    public function get_food_list_permissions_check() {
        if ( ! $this->check_rate_limit() ) {
            return new \WP_Error( 'rate_limited', __( 'Too many requests. Please try again later.', 'wp-cafe' ), [ 'status' => 429 ] );
        }
        return true;
    }

    /**
     * Get booked seat IDs for a specific time slot
     *
     * @param string $date
     * @param string $start_time
     * @param string $end_time
     * @param int $branch_id
     * @return array Array of booked seat IDs
     */
    protected function get_booked_seats( $date, $start_time, $end_time, $branch_id ) {
        return Reservation_Model::get_booked_seats_for_time_slot( $date, $start_time, $end_time, $branch_id );
    }

    /**
     * Check if WooCommerce cart has items
     *
     * @return WP_HTTP_Response
     */
    public function check_cart_has_items() {
        // Check if WooCommerce is available
        if ( ! class_exists( 'WooCommerce' ) ) {
            return $this->response( [ 'has_items' => false ] );
        }

        if ( function_exists( 'wc_load_cart' ) && is_null( WC()->cart ) ) {
            wc_load_cart();
        }

        if ( ! WC()->cart ) {
            return $this->response( [ 'has_items' => false ] );
        }

        $has_items = ! WC()->cart->is_empty();

        return $this->response( [ 'has_items' => (bool) $has_items ] );
    }

    /**
     * Permission check for checking cart items
     *
     * @return bool|\WP_Error
     */
    public function check_cart_has_items_permissions_check() {
        if ( ! $this->check_rate_limit() ) {
            return new \WP_Error( 'rate_limited', __( 'Too many requests. Please try again later.', 'wp-cafe' ), [ 'status' => 429 ] );
        }
        return true;
    }

    /**
     * Parse time string to timestamp, handling WordPress and standard formats
     *
     * @param string $date Date in various formats (Y-m-d, d/m/Y, m/d/Y, etc.)
     * @param string $time_string Time string in various formats
     * @return int|false Timestamp or false on failure
     */
    private function parse_time_to_timestamp( $date, $time_string ) {
        $normalized_date = $this->normalize_date( $date );
        if ( ! $normalized_date ) {
            return false;
        }

        $wp_time_format = get_option( 'time_format' );
        if ( $wp_time_format ) {
            $datetime = \DateTime::createFromFormat( 'Y-m-d ' . $wp_time_format, $normalized_date . ' ' . $time_string );
            if ( $datetime !== false ) {
                return $datetime->getTimestamp();
            }
        }

        $timestamp = strtotime( $normalized_date . ' ' . $time_string );
        return $timestamp !== false ? $timestamp : false;
    }

    /**
     * Normalize date string to Y-m-d format
     *
     * @param string $date_string Date in various formats
     * @return string|false Normalized date in Y-m-d format or false on failure
     */
    private function normalize_date( $date_string ) {
        $wp_date_format = get_option( 'date_format' );
        if ( $wp_date_format ) {
            $datetime = \DateTime::createFromFormat( $wp_date_format, $date_string );
            if ( $datetime !== false ) {
                return $datetime->format( 'Y-m-d' );
            }
        }

        $timestamp = strtotime( $date_string );
        if ( $timestamp !== false ) {
            return gmdate( 'Y-m-d', $timestamp );
        }

        return false;
    }

    /**
     * Check if any non-status fields have actually changed
     *
     * @param array $old_data Old reservation data
     * @param array $new_data New data being updated
     * @return bool True if any field value is different
     */
    private function has_reservation_field_changes( $old_data, $new_data ) {
        $fields_to_check = ['name', 'email', 'phone', 'date', 'start_time', 'end_time',
            'total_guest', 'table_name', 'branch_id', 'branch_name',
            'notes', 'booking_amount', 'total_price', 'currency',
            'payment_method', 'food_order', 'invoice'];

        foreach ( $fields_to_check as $field ) {
            if ( isset( $new_data[ $field ] ) ) {
                $old_value = $old_data[ $field ] ?? '';
                $new_value = $new_data[ $field ];

                if ( is_array( $new_value ) ) {
                    if ( json_encode( $old_value ) !== json_encode( $new_value ) ) {
                        return true;
                    }
                } elseif ( (string) $old_value !== (string) $new_value ) {
                    return true;
                }
            }
        }

        return false;
    }
}
