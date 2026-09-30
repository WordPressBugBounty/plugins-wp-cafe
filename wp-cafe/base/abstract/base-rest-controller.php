<?php
namespace WpCafe\Abstract;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;
use Exception;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Controller;

/**
 * BaseRest Controller
 *
 * @package WpCafe/Abstracts
 */
abstract class Base_Rest_Controller extends WP_REST_Controller implements Hookable_Service_Contract {
    /**
     * Register routes
     *
     * @return  void
     */
    public function register() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    /**
     * Register all routes
     *
     * @return  void
     */
    public function register_routes() {
        throw new Exception('Need to override register_routes method from child class');
    }

    /**
     * Send rest error
     *
     * @param   string  $message      Error message
     * @param   integer  $status_code  Error status code
     *
     * @return  WP_Error
     */
    public function error( $message, $status_code = 422, $type = '', $details = '' ) {
        $data = [
            'success'   => 0,
            'message'   => $message,
            'error'     => [
                'code'  => $status_code,
                'type'  => $type,
                'details'   => $details,
            ],
        ];

        return new WP_HTTP_Response( $data, $status_code );
    }

    /**
     * Send rest response
     *
     * @param   array  $data  Response data
     *
     * @return  WP_HTTP_Response
     */
    public function response( $data = [], $status_code = 200 ) {
        $message = is_array( $data ) && ! empty( $data['message'] ) ? $data['message'] : __( 'Request was successful', 'wp-cafe' );

        if ( is_array( $data ) && isset( $data['message'] ) ) {
            unset( $data['message'] );
        }

        $data = [
            'success'   => 1,
            'message'   => $message,
            'data'      => $data,
        ];

       return new WP_HTTP_Response( $data, $status_code );
    }

    /*
    * Verify REST API nonce.
    *
    * @param $request REST request object.
    * @return bool True if nonce is valid, false otherwise.
    */
    protected function verify_rest_nonce( $request ) : bool {
        $nonce = $request->get_header( 'X-WP-Nonce' );

        if ( empty( $nonce ) ) {
            return false;
        }

        return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
    }

    /**
     * Shared capability gate for read routes.
     *
     * The caller passes if it holds any one of $caps. A filter can then adjust
     * the decision, and its raw value is honored: a WP_Error is returned as-is
     * and only a strict `true` grants. We must not cast the filter result to
     * bool first, because WordPress reads any truthy value as "granted", so a
     * WP_Error (or any object) meant to deny would flip to allow.
     *
     * @param string[]         $caps    Capabilities; any one grants.
     * @param string           $filter  Filter applied to the boolean decision.
     * @param \WP_REST_Request $request Current request.
     * @param string           $message Message for the forbidden error.
     * @return true|WP_Error
     */
    protected function check_read_permission( array $caps, string $filter, $request, string $message ) {
        $can = false;
        foreach ( $caps as $cap ) {
            if ( current_user_can( $cap ) ) {
                $can = true;
                break;
            }
        }

        $result = apply_filters( $filter, $can, $request ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Controllers supply fixed wpcafe-prefixed permission hooks.

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        if ( true !== $result ) {
            return new WP_Error( 'wpcafe_forbidden', $message, [ 'status' => rest_authorization_required_code() ] );
        }

        if ( ! $this->verify_rest_nonce( $request ) ) {
            return new WP_Error( 'wpcafe_invalid_nonce', __( 'Invalid nonce.', 'wp-cafe' ), [ 'status' => 403 ] );
        }

        return true;
    }
}
