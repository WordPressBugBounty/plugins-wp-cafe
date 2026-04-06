<?php
namespace WpCafe\Admin\Controllers;

use WP_REST_Server;
use WpCafe\Abstract\Base_Rest_Controller;
use WpCafe\Upgrades\Upgrader;

/**
 * Migration_Controller class. Handles migration related REST API requests.
 *
 * @package WpCafe/Admin/Controllers
 */
class Migration_Controller extends Base_Rest_Controller {
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
    protected $rest_base = 'migration';

    /**
     * Register the REST routes for migration.
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/run',
            [
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => [ $this, 'run_migration' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ]
        );
    }

    /**
     * Run migration.
     *
     * @return \WP_REST_Response
     */
    public function run_migration() {
        $db_migration = get_option( 'wpcafe_db_migration', false );
        $db_migration_pro = get_option( 'wpcafe_db_migration_pro', false );
        $old_settings = get_option( 'wpcafe_reservation_settings_options' );

        if ( empty( $old_settings ) ) {
            return $this->error( __( 'No old settings found. Migration not required.', 'wp-cafe' ), 400 );
        }

        try {
            update_option( 'wpcafe_old_settings', $old_settings );

            $upgrader = new Upgrader();
            $results = [];

            if ( ! $db_migration ) {
                $upgrader->run();
                update_option( 'wpcafe_db_migration', true );
                $results['free'] = true;
            }

            if ( ! $db_migration_pro && function_exists( 'wpcafe_pro' ) ) {
                $upgrader->run_pro();
                update_option( 'wpcafe_db_migration_pro', true );
                $results['pro'] = true;
            }
            delete_option( 'wpcafe_v2_upgrade_detected' );

            return $this->response( [
                'message' => __( 'Migration completed successfully.', 'wp-cafe' ),
                'results' => $results,
            ] );
        } catch ( \Exception $e ) {
            return $this->error(
                /* translators: %s: error message */
                sprintf( __( 'Migration failed: %s', 'wp-cafe' ), $e->getMessage() ), 500, 'migration_error', $e->getMessage()
            );
        }
    }

    /**
     * Check permissions for migration operations.
     *
     * @return bool
     */
    public function check_permissions() {
        return current_user_can( 'manage_options' );
    }
}
