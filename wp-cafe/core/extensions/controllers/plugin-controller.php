<?php
namespace WpCafe\Extensions\Controllers;
 
use Arraytics\ToolsSdk\PluginManager;
use WP_REST_Server;
use WpCafe\Abstract\Base_Rest_Controller;
use WP_Error;
 
/**
* Plugin_Controller class. Handles extension related REST API requests.
*
* @package WpCafe/Settings/Controllers
*/
class Plugin_Controller extends Base_Rest_Controller {
    /**
     * Store the namespace for the REST API.
     *
     * @var string
     */
    protected $namespace = 'wpcafe/v2';
 
    /**
     * Store the REST base for the API.
     *
     * @var string
     */
    protected $rest_base = 'plugins';
 
    /**
     * Register routes
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route( $this->namespace, $this->rest_base, [
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'update_item'],
                'permission_callback' => function () {
                    return current_user_can( 'manage_options' );
                },
            ],
        ] );
 
    }
 
    /**
     * Enable or disable plugin
     *
     * @param   WP_Rest_Request  $request  [$request description]
     *
     * @return  WP_Response | WP_Error
     */
    public function update_item( $request ) {
        $input_data = json_decode( $request->get_body(), true );
 
        $name   = ! empty( $input_data['name'] ) ? sanitize_text_field( $input_data['name'] ) : '';
        $status = ! empty( $input_data['status'] ) ? sanitize_text_field( $input_data['status'] ) : '';
 
        $statuses = ['install', 'activate', 'deactivate'];
 
        if ( ! $name ) {
            return $this->error( __( 'Please enter plugin name', 'wp-cafe' ) );
        }
 
        if (  ! $status ) {
            return $this->error( __( 'Please plugin enter status', 'wp-cafe' ) );
        }
 
        if ( ! in_array( $status, $statuses ) ) {
            return $this->error( __( 'Invalid status', 'wp-cafe' ) );
        }
 
        $plugin = wpcafe_extension()->find( $name );
        $deps = ! empty( $plugin['deps'] ) ? $plugin['deps'] : [];

        if ( $deps ) {
            foreach ( $deps as $dep ) {
                if ( ! PluginManager::is_installed( $dep ) ) {
                    /* translators: %s: plugin name */
                    return $this->error( sprintf( __( 'Dependency plugin %s is not installed', 'wp-cafe' ), $dep ) );
                }
            }
        }
        
        $our_plugins  = function_exists( 'wpcafe_our_plugins_list' ) ? wpcafe_our_plugins_list() : [];
        $slug         = isset( $our_plugins[ $name ]['slug'] ) ? $our_plugins[ $name ]['slug'] : $name;
        $download_url = ! empty( $plugin['download_url'] ) ? $plugin['download_url'] : null;

        $update = false;

        if ( $status === 'install' ) {
            if ( $download_url ) {
                // Validate download URL scheme and host before installing.
                $parsed          = wp_parse_url( $download_url );
                $allowed_domains = [ 'wordpress.org', 'arraytics.com', 'themewinter.com', 'github.com' ];
                if ( ( $parsed['scheme'] ?? '' ) !== 'https' || ! in_array( $parsed['host'] ?? '', $allowed_domains, true ) ) {
                    return $this->error( __( 'Download URL must use HTTPS from a trusted domain.', 'wp-cafe' ) );
                }

                // Plugin is not on WordPress.org — install directly from the provided URL.
                if ( PluginManager::is_installed( $slug ) ) {
                    $update = true;
                } else {
                    include_once ABSPATH . 'wp-admin/includes/file.php';
                    include_once ABSPATH . 'wp-admin/includes/misc.php';
                    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

                    $skin    = new \Automatic_Upgrader_Skin();
                    $upgrader = new \Plugin_Upgrader( $skin );
                    $result  = $upgrader->install( $download_url );
                    $update  = $result ? true : false;
                }
            } else {
                $update = PluginManager::install_plugin( $slug );
            }
        }

        if ( $status === 'activate' ) {
            if ( ! PluginManager::is_installed( $slug ) ) {
                $install_result = PluginManager::install_plugin( $slug );
                if ( ! $install_result ) {
                    return $this->error( __( 'Plugin installation failed.', 'wp-cafe' ) );
                }
            }

            $update = PluginManager::activate_plugin( $slug );
        }

        if ( $status === 'deactivate' && PluginManager::is_activated( $slug ) ) {
            $update = PluginManager::deactivate_plugin( $slug );
        }
        
        if ( ! $update ) {
            /* translators: %s: action status (install, activate, or deactivate) */
            return $this->error( sprintf( __( 'Plugin couldn\'t %s', 'wp-cafe' ), $status ) );
        }
 
        $response = [
            'message' => __( 'Successfully updated', 'wp-cafe' ),
        ];
 
        return $this->response( $response );
    }
}