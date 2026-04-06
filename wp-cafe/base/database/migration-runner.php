<?php
namespace WpCafe\Database;
/**
 * MigrationRunner class.
 *
 * Responsible for running and rolling back all migration files.
 */
class Migration_Runner {
    /**
     * Run all migration files to apply the database schema changes.
     *
     * This method loads all PHP files inside the migrations directory,
     * instantiates the migration class, and calls the up() method.
     *
     * @return void
     */
    public static function migrate() {
        $migration_files = glob( wpcafe()->plugin_directory . '/migrations/*.php' );

        if ( ! is_array( $migration_files ) ) {
            return;
        }

        foreach ( $migration_files as $file ) {
            $migration = require_once $file;

            if ( $migration instanceof Migration ) {
                $migration->up();
            }
        }
    }

    /**
     * Rollback all migration files to revert the database schema changes.
     *
     * This method loads all PHP files inside the migrations directory in reverse order,
     * instantiates the migration class, and calls the down() method.
     *
     * @return void
     */
    public static function rollback() {
        $migration_files = glob( wpcafe()->plugin_directory . '/migrations/*.php' );

        if ( ! is_array( $migration_files ) ) {
            return;
        }

        foreach ( array_reverse( $migration_files ) as $file ) {
            $migration = require_once $file;

            if ( $migration instanceof Migration ) {
                $migration->down();
            }
        }
    }
}
