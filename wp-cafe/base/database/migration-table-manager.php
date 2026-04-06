<?php
namespace WpCafe\Database;
/**
 * Class MigrationTableManager
 *
 * Responsible for managing the migrations history table in the database.
 */
class Migration_Table_Manager {
    /**
     * Table name for storing migration history.
     *
     * @var string
     */
    protected static $table_name = 'wpcafe_migrations';

    /**
     * Ensure the migration table exists.
     *
     * @return void
     */
    public static function ensure_table_exists() {
        global $wpdb;

        $table = $wpdb->prefix . self::$table_name;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            migrated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Check if a given migration has already been applied.
     *
     * @param string $migration_name
     * @return bool
     */
    public static function is_migrated($migration_name) {
        global $wpdb;
        $table = $wpdb->prefix . self::$table_name;

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE migration = %s",
            $migration_name
        ));
    }

    /**
     * Record a migration as applied.
     *
     * @param string $migration_name
     * @param int $batch
     * @return void
     */
    public static function mark_as_migrated($migration_name, $batch = 1) {
        global $wpdb;
        $table = $wpdb->prefix . self::$table_name;

        $wpdb->insert($table, [
            'migration'   => $migration_name,
            'batch'       => $batch,
            'migrated_at' => current_time('mysql'),
        ]);
    }

    /**
     * Remove a migration record (used for rollback).
     *
     * @param string $migration_name
     * @return void
     */
    public static function remove_migration($migration_name) {
        global $wpdb;
        $table = $wpdb->prefix . self::$table_name;

        $wpdb->delete($table, ['migration' => $migration_name]);
    }

    /**
     * Get the latest batch number.
     *
     * @return int
     */
    public static function get_latest_batch() {
        global $wpdb;
        $table = $wpdb->prefix . self::$table_name;

        return (int) $wpdb->get_var("SELECT MAX(batch) FROM {$table}") ?: 1;
    }

    /**
     * Get all migrations in the latest batch.
     *
     * @return array
     */
    public static function get_latest_batch_migrations() {
        global $wpdb;
        $table = $wpdb->prefix . self::$table_name;
        $latest_batch = self::get_latest_batch();

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE batch = %d ORDER BY id DESC",
            $latest_batch
        ));
    }
}
