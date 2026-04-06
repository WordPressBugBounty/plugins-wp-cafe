<?php
namespace WpCafe\Database;

/**
 * Schema builder class.
 */
class Schema {
    /**
     * Create a table.
     *
     * @param string $table_name
     * @param callable $callback
     * @return void
     */
    public static function create($table_name, callable $callback) {
        $blueprint = new Blueprint($table_name);
        $callback($blueprint);
        $blueprint->create();
    }

    /**
     * Drop a table.
     *
     * @param string $table_name
     * @return void
     */
    public static function drop_if_exists($table_name) {
        global $wpdb;
        $full_table_name = $wpdb->prefix . $table_name;
        $wpdb->query("DROP TABLE IF EXISTS {$full_table_name}");
    }
}
