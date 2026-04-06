<?php
namespace WpCafe\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Upgrades\Install_Detector;
use WpCafe\Contracts\Hookable_Service_Contract;

/**
 * Migration_Runner class. Shows migration notice
 *
 * @package WpCafe/AdminMenu
 */
class Migration_Runner implements Hookable_Service_Contract {
    /**
     * Register hooks
     *
     * @return  void
     */
    public function register() {
        add_action( 'admin_notices', [ $this, 'show_migration_notice' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_migration_assets' ] );
    }

    /**
     * Enqueue migration notice assets
     *
     * @return  void
     */
    public function enqueue_migration_assets() {
        $db_migration = get_option( 'wpcafe_db_migration', false );

        // Only enqueue if migration is needed
        if ( $db_migration || ! Install_Detector::is_v2_upgrade() ) {
            return;
        }

        wp_enqueue_script( 'wpcafe-migration-notice' );
    }

    /**
     * Show migration notice if migration is needed
     *
     * @return  void
     */
    public function show_migration_notice() {
        $db_migration = get_option( 'wpcafe_db_migration', false );
        if ( $db_migration || ! Install_Detector::is_v2_upgrade() ) {
            return;
        }

        ?>
        <div class="notice notice-warning wpcafe-migration-notice is-dismissible"
             data-notice="migration"
             data-rest-url="<?php echo esc_attr( rest_url( 'wpcafe/v2/migration/run' ) ); ?>"
             data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>"
             data-success-message="<?php esc_attr_e( 'Migration completed successfully!', 'wp-cafe' ); ?>"
             data-error-message="<?php esc_attr_e( 'Migration failed. Please try again.', 'wp-cafe' ); ?>">
            <p>
                <strong><?php esc_html_e( 'WP Cafe Database Migration Required', 'wp-cafe' ); ?></strong>
            </p>
            <p>
                <?php esc_html_e( 'WP Cafe has been updated and requires a database migration to work properly. Please click the button below to run the migration.', 'wp-cafe' ); ?>
            </p>
            <p style="color: #dc3232; font-weight: 600;">
                <strong>⚠️ <?php esc_html_e( 'Important: Please backup your database before running the migration!', 'wp-cafe' ); ?></strong>
            </p>
            <p>
                <button type="button" class="button button-primary" id="wpcafe-run-migration">
                    <?php esc_html_e( 'Run Migration', 'wp-cafe' ); ?>
                </button>
                <span class="spinner" style="float: none; margin: 0 10px;"></span>
                <span class="wpcafe-migration-message"></span>
            </p>
        </div>
        <?php
    }
}
