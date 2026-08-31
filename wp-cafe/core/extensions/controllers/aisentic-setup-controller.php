<?php
namespace WpCafe\Extensions\Controllers;

if ( ! defined( 'ABSPATH' ) ) exit;

use Arraytics\ToolsSdk\PluginManager;
use WP_REST_Server;
use WpCafe\Abstract\Base_Rest_Controller;

/**
 * Aisentic_Setup_Controller class. One-call install + activate + register for the
 * Aisentic AI assistant.
 *
 * WP Cafe shows its AI prompt inputs whether or not Aisentic is installed, so someone
 * typing a question on a site without it is offered the install. This endpoint is the
 * other half of that offer: it turns a consented dialog into a working assistant
 * without dropping the user into a second setup wizard.
 *
 * All three steps fit in one request because Aisentic boots its service providers at
 * include time (aisentic.php calls aisentic() at file scope), so activate_plugin()
 * attaches its `wpcafe/aisentic/register_site` listener before we fire the action.
 *
 * @package WpCafe/Extensions/Controllers
 */
class Aisentic_Setup_Controller extends Base_Rest_Controller {
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
    protected $rest_base = 'aisentic/setup';

    /**
     * Plugin slug installed by this endpoint.
     */
    const PLUGIN_SLUG = 'aisentic';

    /**
     * Register routes
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route( $this->namespace, $this->rest_base, [
            [
                /*
                 * No `args` schema on purpose. ApiBase::sendRequest() hands apiFetch a
                 * pre-stringified `body` with no JSON content type, so WP never parses
                 * it into request params and every registered arg arrives empty. The
                 * body is decoded in the callback instead — same approach as
                 * Plugin_Controller, which is why that route works.
                 */
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'create_item' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
        ] );
    }

    /**
     * Only users who could perform this install by hand. WordPress already denies
     * install_plugins under DISALLOW_FILE_MODS, so locked-down sites are covered too.
     *
     * @return bool
     */
    public function check_permissions() {
        return current_user_can( 'install_plugins' ) && current_user_can( 'manage_options' );
    }

    /**
     * Install Aisentic, activate it, and register the site.
     *
     * @param   \WP_REST_Request  $request  Request object.
     *
     * @return  \WP_HTTP_Response
     */
    public function create_item( $request ) {
        $input = json_decode( $request->get_body(), true );
        $input = is_array( $input ) ? $input : [];

        /*
         * The consent checkbox is not the gate — this is. Personal data leaves the
         * site in this request, so the request itself has to carry the agreement,
         * and nothing is installed before it is checked. Compared strictly, so a
         * truthy "0"/"false" string can never stand in for agreement.
         */
        if ( true !== ( $input['consent'] ?? null ) ) {
            return $this->error(
                __( 'Consent is required before an account can be created.', 'wp-cafe' ),
                400
            );
        }

        $name     = isset( $input['name'] ) ? sanitize_text_field( $input['name'] ) : '';
        $email    = isset( $input['email'] ) ? sanitize_email( $input['email'] ) : '';
        $site_url = isset( $input['site_url'] ) ? esc_url_raw( $input['site_url'] ) : '';

        if ( ! is_email( $email ) ) {
            return $this->error( __( 'Please enter a valid email address.', 'wp-cafe' ), 400 );
        }

        if ( empty( $site_url ) ) {
            return $this->error( __( 'Please enter your site address.', 'wp-cafe' ), 400 );
        }

        $installed = $this->install_and_activate();

        if ( is_wp_error( $installed ) ) {
            return $this->error( $installed->get_error_message() );
        }

        /*
         * Aisentic's own handshake for this: it registers with the provider, stores
         * the API key, and marks the wpcafe integration connected — which is what
         * gates its chat bundle onto WP Cafe screens. Nothing here duplicates that.
         */
        do_action( 'wpcafe/aisentic/register_site', $name, $email, $site_url );

        /*
         * Read the key back rather than trusting the action. The listener swallows
         * provider errors so a bad network never breaks activation, which means a
         * silent failure is possible and the dialog has to be able to say so.
         */
        $registered = function_exists( 'aisentic_get_option' )
            && '' !== (string) aisentic_get_option( 'llmProviders.aisentic.apiKey', '' );

        return $this->response( [
            'installed'  => true,
            'registered' => $registered,
            'message'    => $registered
                ? __( 'Aisentic is ready.', 'wp-cafe' )
                : __( 'Aisentic was installed, but the account could not be created. You can finish setup in Aisentic.', 'wp-cafe' ),
        ] );
    }

    /**
     * Install the plugin from wordpress.org if missing, then activate it.
     *
     * @return  true|\WP_Error
     */
    private function install_and_activate() {
        if ( ! PluginManager::is_installed( self::PLUGIN_SLUG ) ) {
            $result = PluginManager::install_plugin( self::PLUGIN_SLUG );

            if ( is_wp_error( $result ) ) {
                return $result;
            }

            if ( ! $result ) {
                return new \WP_Error(
                    'wpcafe_aisentic_install_failed',
                    __( 'Aisentic could not be installed. You can install it from Plugins → Add New instead.', 'wp-cafe' )
                );
            }
        }

        if ( PluginManager::is_activated( self::PLUGIN_SLUG ) ) {
            return true;
        }

        $activated = PluginManager::activate_plugin( self::PLUGIN_SLUG );

        if ( is_wp_error( $activated ) ) {
            return $activated;
        }

        if ( ! $activated ) {
            return new \WP_Error(
                'wpcafe_aisentic_activate_failed',
                __( 'Aisentic was installed but could not be activated.', 'wp-cafe' )
            );
        }

        return true;
    }
}
