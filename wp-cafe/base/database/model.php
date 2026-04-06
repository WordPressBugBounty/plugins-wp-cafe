<?php

namespace WpCafe\Database;

use wpdb;

/**
 * Class Model
 *
 * A base model class for WordPress plugins using $wpdb.
 */
class Model {
    /**
     * The WordPress $wpdb instance.
     *
     * @var wpdb
     */
    protected static $db;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected static $table;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected static $primary_key = 'id';

    /**
     * The attributes of the model.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The query conditions.
     *
     * @var array
     */
    protected $wheres = [];

    /**
     * Relationships to eager load.
     *
     * @var array
     */
    protected $with = [];

    /**
     * Constructor.
     *
     * @param array $attributes
     */
    public function __construct( array $attributes = [] ) {
        $this->attributes = $attributes;
        global $wpdb;
        static::$db = $wpdb;
    }

    /**
     * Get the table name.
     *
     * @return string
     */
    protected static function get_table() {
        if ( isset( static::$table ) ) {
            return static::$db->prefix . static::$table;
        }
        return static::$db->prefix . strtolower( static::class ) . 's';
    }

    /**
     * Create a new record.
     *
     * @param array $data
     * @return static|null
     */
    public static function create( array $data ) {
        $table    = static::get_table();
        $inserted = static::$db->insert( $table, $data );

        if ( $inserted ) {
            $id = static::$db->insert_id;
            return static::find( $id );
        }

        return null;
    }

    /**
     * Update the current model instance in the database.
     *
     * @param array $data
     * @return bool True on success, false on failure.
     */
    public function update( array $data ) {
        global $wpdb;

        if ( ! isset( $this->attributes[$this->primary_key] ) ) {
            return false;
        }

        $updated = $wpdb->update(
            $this->table,
            $data,
            [ $this->primary_key => $this->attributes[$this->primary_key] ]
        );

        if ( $updated !== false ) {
            foreach ( $data as $key => $value ) {
                $this->attributes[$key] = $value;
            }

            return true;
        }

        return false;
    }

    /**
     * Find a record by ID.
     *
     * @param int|string $id
     * @return static|null
     */
    public static function find( $id ) {
        $instance    = new static();
        $table       = static::get_table();
        $primary_key = static::$primary_key;

        $sql = static::$db->prepare( "SELECT * FROM {$table} WHERE {$primary_key} = %d LIMIT 1", $id );
        $result = static::$db->get_row( $sql, ARRAY_A );

        if ( ! $result ) {
            return null;
        }

        $model = new static( $result );

        if ( ! empty( $instance->with ) ) {
            $model->load_relations();
        }

        return $model;
    }

    /**
     * Get all records.
     *
     * @return array
     */
    public static function all() {
        $instance = new static();
        $table    = static::get_table();
        $results  = static::$db->get_results("SELECT * FROM {$table}", ARRAY_A);

        return array_map(fn($row) => new static($row), $results);
    }

    /**
     * Save the model.
     *
     * @return bool
     */
    public function save() {
        $table = static::get_table();
        $primary_key = static::$primary_key;

        if ( ! empty( $this->attributes[$primary_key] ) ) {
            // Update
            $id     = $this->attributes[$primary_key];
            $data   = $this->attributes;
            unset( $data[$primary_key] );

            $updated = static::$db->update(
                $table,
                $data,
                [$primary_key => $id]
            );

            return $updated !== false;
        } else {
            // Insert
            $inserted = static::$db->insert(
                $table,
                $this->attributes
            );

            if ($inserted) {
                $this->attributes[$primary_key] = static::$db->insert_id;
                return true;
            }
        }

        return false;
    }

    /**
     * Delete the model.
     *
     * @return bool
     */
    public function delete() {
        $table       = static::get_table();
        $primary_key = static::$primary_key;

        if ( empty( $this->attributes[$primary_key] ) ) {
            return false;
        }

        $deleted = static::$db->delete(
            $table,
            [$primary_key => $this->attributes[$primary_key]]
        );

        return $deleted !== false;
    }

    /**
     * Where clause.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return static
     */
    public static function where( $column, $operator, $value ) {
        $instance = new static();
        $instance->wheres[] = compact('column', 'operator', 'value');
        return $instance;
    }

    /**
     * Eager load relationships.
     *
     * @param array|string $relations
     * @return static
     */
    public function with( $relations ) {
        $this->with = is_array( $relations ) ? $relations : [$relations];
        return $this;
    }

    /**
     * Get the results.
     *
     * @return array
     */
    public function get() {
        $table = static::get_table();
        $sql   = "SELECT * FROM {$table}";

        if ( ! empty( $this->wheres ) ) {
            $conditions = [];
            foreach ($this->wheres as $where) {
                $conditions[] = "{$where['column']} {$where['operator']} '" . esc_sql($where['value']) . "'";
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $results = static::$db->get_results( $sql, ARRAY_A );

        return array_map( fn( $row ) => new static( $row ), $results );
    }

    /**
     * Get the first record.
     *
     * @return static|null
     */
    public function first() {
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Belongs to relation.
     *
     * @param string $related
     * @param string|null $foreign_key
     * @return mixed|null
     */
    public function belongs_to( $related, $foreign_key = null ) {
        $foreign_key = $foreign_key ?? strtolower( $related ) . '_id';
        $id = $this->__get( $foreign_key );

        if ( ! $id ) {
            return null;
        }

        return $related::find( $id );
    }

    /**
     * Has one relation.
     *
     * @param string $related
     * @param string|null $foreign_key
     * @param string|null $local_key
     * @return mixed|null
     */
    public function has_one( $related, $foreign_key = null, $local_key = null ) {
        $local_key   = $local_key ?? static::$primary_key;
        $foreign_key = $foreign_key ?? strtolower( static::class ) . '_id';

        return $related::where( $foreign_key, '=', $this->__get( $local_key ) )->first();
    }

    /**
     * Has many relation.
     *
     * @param string $related
     * @param string|null $foreign_key
     * @param string|null $local_key
     * @return array
     */
    public function has_many( $related, $foreign_key = null, $local_key = null ) {
        $local_key   = $local_key ?? static::$primary_key;
        $foreign_key = $foreign_key ?? strtolower( static::class ) . '_id';

        $data = [
            'local' => $local_key,
            'foreign_key'   => $foreign_key,
            'related'       => $related
        ];
        
        return $related::where( $foreign_key, '=', $this->__get( $local_key ) )->get();
    }

    /**
     * Magic getter.
     *
     * @param string $key
     * @return mixed|null
     */
    public function __get( $key ) {

        // If already loaded
        if ( isset( $this->attributes[$key]) ) {
            return $this->attributes[$key];
        }

        // If method exists, and is a relationship
        if ( method_exists( $this, $key ) ) {
            $relation = $this->$key();

            // Cache the relation to avoid multiple queries
            $this->attributes[$key] = $relation;

            return $relation;
        }

        return null;
    }

    /**
     * Magic setter.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set( $key, $value ) {
        $this->attributes[$key] = $value;
    }


    /**
     * Load relations if with() was called.
     */
    protected function load_relations() {
        foreach ( $this->with as $relation ) {
            if ( method_exists( $this, $relation ) ) {
                $this->$relation = $this->$relation();
            }
        }
    }

}
