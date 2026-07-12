<?php
namespace WpCafe\Integrations\Controllers;

if ( ! defined( 'ABSPATH' ) ) exit;

use Arraytics\ToolsSdk\PluginManager;
use WpCafe\Abstract\Base_Rest_Controller;
use WP_REST_Server;

/**
 * MailPoet controller.
 *
 * Feeds the integration config modal: reports whether the MailPoet plugin
 * needs install/activate, and (once active) returns the account's lists for
 * the list picker. Install/activate itself reuses the shared
 * `wpcafe/v2/plugins` endpoint on the client.
 *
 * @package WpCafe/Integrations/Controllers
 */
class Mailpoet_Controller extends Base_Rest_Controller {

    /**
     * REST namespace.
     *
     * @var string
     */
    protected $namespace = 'wpcafe/v2';

    /**
     * REST base.
     *
     * @var string
     */
    protected $rest_base = 'mailpoet';

    /**
     * Register routes.
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route( $this->namespace, $this->rest_base, [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_mailpoet_data' ],
                'permission_callback' => function () {
                    return current_user_can( 'manage_options' );
                },
            ],
        ] );
    }

    /**
     * Return MailPoet plugin status and, when active, the available lists.
     *
     * @return \WP_HTTP_Response
     */
    public function get_mailpoet_data() {
        $status = $this->get_plugin_status();
        $lists  = 'active' === $status ? $this->get_lists() : [];

        return $this->response( [
            'plugin_status' => $status,
            'lists'         => $lists,
        ] );
    }

    /**
     * Resolve the MailPoet plugin lifecycle stage.
     *
     * @return string One of 'install', 'activate', 'active'.
     */
    private function get_plugin_status() {
        if ( ! PluginManager::is_installed( 'mailpoet' ) ) {
            return 'install';
        }

        if ( ! PluginManager::is_activated( 'mailpoet' ) ) {
            return 'activate';
        }

        return 'active';
    }

    /**
     * Fetch MailPoet lists as select options.
     *
     * @return array<int, array{value:string,label:string}>
     */
    private function get_lists() {
        $options = [];

        if ( ! class_exists( '\MailPoet\API\API' ) ) {
            return $options;
        }

        try {
            $lists = \MailPoet\API\API::MP( 'v1' )->getLists();
            foreach ( (array) $lists as $list ) {
                if ( empty( $list['id'] ) ) {
                    continue;
                }
                $options[] = [
                    'value' => (string) $list['id'],
                    'label' => sanitize_text_field( $list['name'] ?? '' ),
                ];
            }
        } catch ( \Throwable $e ) {
            return [];
        }

        return $options;
    }
}
