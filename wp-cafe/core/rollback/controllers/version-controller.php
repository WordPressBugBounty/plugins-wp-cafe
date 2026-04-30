<?php
namespace WpCafe\Onboard\Controllers;

use WpCafe\Abstract\Base_Rest_Controller;
use WP_REST_Server;

/**
 * Version controller
 *
 * @package WpCafe/Version
 */
class Version_Controller extends Base_Rest_Controller {
    /**
     * Endpoint namespace
     *
     * @var string
     */
    protected $namespace = 'wpcafe/v2';

    /**
     * Route name
     *
     * @var string
     */
    protected $base = 'versions';

    /**
     * Register routes
     *
     * @return  void
     */
    public function register_routes(): void {
        register_rest_route( $this->namespace, $this->base, [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_items'],
                'permission_callback' => [$this, 'get_items_permission'],
            ],
        ] );

        register_rest_route( $this->namespace, $this->base . '/rollback-url', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_rollback_url'],
                'permission_callback' => [$this, 'get_items_permission'],
            ],
        ] );
    }

    /**
     * Get version items
     *
     * @param   WP_Rest_Request  $request
     *
     * @return  WP_Rest_Response
     */
    public function get_items($request) {
        $rollback_versions = get_transient( 'wpcafe_rollback_versions_' . WPCAFE_VERSION );

        if ( false === $rollback_versions ) {
            $max_versions = 30;

            $versions = apply_filters( 'wpcafe_rollback_versions', [] );

            if ( empty( $versions ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

                $plugin_information = plugins_api(
                    'plugin_information', [
                        'slug' => 'wp-cafe',
                    ]
                );

                if ( empty( $plugin_information->versions ) || ! is_array( $plugin_information->versions ) ) {
                    return [];
                }

                uksort( $plugin_information->versions, 'version_compare' );
                $versions = array_keys( array_reverse( $plugin_information->versions ) );
            }

            $rollback_versions = [];

            $current_index = 0;
            foreach ( $versions as $version ) {
                if ( $max_versions <= $current_index ) {
                    break;
                }

                $lowercase_version = strtolower( $version );
                $is_valid_rollback_version = ! preg_match( '/(trunk|beta|rc|dev)/i', $lowercase_version );

                /**
                 * Is rollback version is valid.
                 *
                 * Filters the check whether the rollback version is valid.
                 *
                 * @param bool $is_valid_rollback_version Whether the rollback version is valid.
                 */
                $is_valid_rollback_version = apply_filters(
                    'wpcafe_rollback_is_valid_rollback_version',
                    $is_valid_rollback_version,
                    $lowercase_version
                );

                if ( ! $is_valid_rollback_version ) {
                    continue;
                }

                if ( version_compare( $version, WPCAFE_VERSION, '>=' ) ) {
                    continue;
                }

                $current_index++;
                $rollback_versions[] = $version;
            }

            set_transient( 'wpcafe_rollback_versions_' . WPCAFE_VERSION, $rollback_versions, WEEK_IN_SECONDS );
        }

        return $this->response([
            'current_version' => WPCAFE_VERSION,
            'versions'        => $rollback_versions
        ]);
    }

    /**
     * Get rollback url
     *
     * @return  WP_Rest_Response
     */
    public function get_rollback_url($request) {
        $version = $request['version'];

        if ( empty( $version ) ) {
            return $this->error(__( 'Please provide a version', 'wp-cafe' ));
        }

        $versions = get_transient( 'wpcafe_rollback_versions_' . WPCAFE_VERSION );

        if ( empty( $versions ) || ! in_array( $version, $versions, true ) ) {
            return $this->error(__( 'Invalid version provided', 'wp-cafe' ));
        }

        $url = add_query_arg([
            'action' => 'wpcafe_rollback',
            'version' => $version
        ], admin_url( 'admin-post.php') );

        $url = wp_nonce_url( $url, 'wpcafe_rollback' );

        return $this->response([
            'rollback_url' => html_entity_decode($url)
        ]);
    }

    /**
     * Get items permission
     *
     * @param   WP_Rest_Request  $request
     *
     * @return  bool
     */
    public function get_items_permission($request) {
        return current_user_can('manage_options');
    }
}
