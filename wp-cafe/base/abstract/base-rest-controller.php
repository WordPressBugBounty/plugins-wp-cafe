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
    public function response( $data = [], $status_code = 200, ) {
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
}
