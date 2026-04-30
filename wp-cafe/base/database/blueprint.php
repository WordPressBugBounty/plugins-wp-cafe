<?php
namespace WpCafe\Database;

/**
 * Blueprint class to define database table schema.
 */
class Blueprint {
    /**
     * The table name (without prefix).
     *
     * @var string
     */
    protected $table_name;

    /**
     * The list of columns and settings.
     *
     * @var array
     */
    protected $columns = [];

    /**
     * The list of foreign key constraints.
     *
     * @var array
     */
    protected $foreign_keys = [];

    /**
     * Constructor.
     *
     * @param string $table_name
     */
    public function __construct( $table_name ) {
        $this->table_name = $table_name;
    }

    /**
     * Add an auto-incrementing ID column.
     *
     * @return void
     */
    public function id() {
        $this->columns[] = '`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
    }

    /**
     * Add a foreign ID column.
     *
     * @param string $column
     * @return void
     */
    public function foreign_id($column) {
        $this->columns[] = "`{$column}` BIGINT(20) UNSIGNED";
    }

    /**
     * Add a string (VARCHAR) column.
     *
     * @param string $column
     * @param int $length
     * @return void
     */
    public function string($column, $length = 255) {
        $this->columns[] = "`{$column}` VARCHAR({$length})";

        return $this;
    }

    /**
     * Add an integer column.
     *
     * @param string $column
     * @return void
     */
    public function integer($column) {
        $this->columns[] = "`{$column}` INT(11)";

        return $this;
    }

    /**
     * Add a float column.
     *
     * @param string $column
     * @return void
     */
    public function float($column) {
        $this->columns[] = "`{$column}` FLOAT";

        return $this;
    }

    /**
     * Add a boolean column.
     *
     * @param string $column
     * @return void
     */
    public function boolean($column) {
        $this->columns[] = "`{$column}` TINYINT(1)";

        return $this;
    }

    /**
     * Add a text column.
     *
     * @param string $column
     * @return void
     */
    public function text($column) {
        $this->columns[] = "`{$column}` TEXT";

        return $this;
    }

    /**
     * Add a time column.
     *
     * @param string $column
     * @return void
     */
    public function time($column) {
        $this->columns[] = "`{$column}` TIME";

        return $this;
    }

    /**
     * Add a datetime column.
     *
     * @param string $column
     * @return void
     */
    public function datetime($column) {
        $this->columns[] = "`{$column}` DATETIME";

        return $this;
    }

    /**
     * Add an enum column with predefined values.
     *
     * @param string $column
     * @param array $values
     * @return void
     */
    public function enum($column, array $values) {
        $escaped_values = array_map( 'esc_sql', $values );
        $values_string = "'" . implode("','", $escaped_values) . "'";
        $this->columns[] = "`{$column}` ENUM({$values_string})";

        return $this;
    }

    /**
     * Add a foreign key constraint.
     *
     * @param string $column
     * @param string $referenced_table
     * @param string $referenced_column
     * @return void
     */
    public function foreign($column, $referenced_table, $referenced_column = 'id') {
        $this->foreign_keys[] = [
            'column'            => $column,
            'referenced_table'  => $referenced_table,
            'referenced_column' => $referenced_column,
        ];

        return $this;
    }

    /**
     * Add created_at and updated_at timestamp columns.
     *
     * @return void
     */
    public function timestamps() {
        $this->columns[] = "`created_at` DATETIME DEFAULT CURRENT_TIMESTAMP";
        $this->columns[] = "`updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

        return $this;
    }

    /**
     * Add a default value to the last added column.
     *
     * @param mixed $value
     * @return void
     */
    public function default($value) {
        $last_index = count($this->columns) - 1;
        if ($last_index >= 0) {
            if (is_string($value)) {
                $value = "'" . esc_sql($value) . "'";
            }
            $this->columns[$last_index] .= " DEFAULT {$value}";
        }

        return $this;
    }

    /**
     * Execute the table creation using dbDelta and apply foreign keys.
     *
     * @return void
     */
    public function create() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $full_table_name = $wpdb->prefix . $this->table_name;
        $columns_sql     = implode(',', $this->columns);

        $sql = "CREATE TABLE {$full_table_name} ({$columns_sql}) {$wpdb->get_charset_collate()};";
        dbDelta($sql);

        // Apply foreign key constraints manually
        if ( is_array( $this->foreign_keys ) ) {
            foreach ( $this->foreign_keys as $fk ) {
                $fk_sql = "ALTER TABLE {$full_table_name} 
                    ADD CONSTRAINT fk_{$this->table_name}_{$fk['column']}
                    FOREIGN KEY (`{$fk['column']}`)
                    REFERENCES `{$wpdb->prefix}{$fk['referenced_table']}`(`{$fk['referenced_column']}`)
                    ON DELETE CASCADE ON UPDATE CASCADE;";
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- DDL schema creation with developer-defined table/column names
                $wpdb->query($fk_sql);
            }
        }
    }
}


