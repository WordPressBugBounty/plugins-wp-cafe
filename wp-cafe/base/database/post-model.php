<?php
namespace WpCafe\Database;

use WpCafe\Database;
use WP_Post;
use Exception;
use JsonSerializable;
use WP_Error;
use WP_Query;

/**
 * PostModel class
 */
abstract class Post_Model implements JsonSerializable {
    /**
     * Store post
     *
     * @var Object
     */
    protected $post;

    /**
     * Store meta property
     *
     * @var array
     */
    protected array $fillable = [];

    /**
     * Store post attributes
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Store origianl data
     *
     * @var array
     */
    protected $original = [];

    /**
     * Store post type
     *
     * @var string
     */
    protected $post_type;

    /**
     * Constructor for post model class
     *
     * @param   Object  $post  Post model
     *
     * @return  void
     */
    public function __construct( $post = null ) {
        if ( $post instanceof WP_Post ) {
            $this->post = $post;
        } elseif ( is_numeric( $post ) ) {
            $this->post = get_post( $post );
        }

        if ( $this->post && $this->post->post_type !== $this->get_post_type() ) {
            throw new Exception( esc_html__( 'Post type mismatch.', 'wp-cafe' ) );
        }

        if ( $this->post ) {
            $this->load_attributes();
        }
    }

    /**
     * Get post type
     *
     * @return  string  Get post type name
     */
    abstract protected function get_post_type();

    /**
     * Load attributes
     *
     * @return  void  Set all neccesary attributes
     */
    protected function load_attributes() {
        $this->attributes = [
            'id'        => $this->post->ID,
            'title'     => $this->post->post_title,
            'content'   => $this->post->post_content,
        ];

        $custom_meta = get_post_meta( $this->post->ID );

        foreach ( $this->fillable as $key => $value ) {
            $this->attributes[$key] = get_post_meta( $this->post->ID, $key, true );
        }

        $this->original = $this->attributes;
    }

    /**
     * Get attributes
     *
     * @param   string  $key  Property name
     *
     * @return  mixed   Meta data from any model
     */
    public function __get( $key ) {
        $core_fields = ['id', 'title', 'content'];

        if ( in_array( $key, $core_fields, true ) ) {
            return $this->attributes[$key] ?? null;
        }

        if ( isset( $this->fillable[$key] ) ) {
            return $this->attributes[$key];
        }

        // translators: %s is the name of the undefined property.
        throw new Exception( sprintf( esc_html__( 'Undefined property %s', 'wp-cafe' ), esc_html( $key ) ) );

    }

    /**
     * Set attributes
     *
     * @param   string  $key    Model meta property
     * @param   mixed  $value  Post meta data
     *
     * @return  void Set meta data
     */
    public function __set( $key, $value ) {

        if ( ! isset( $this->fillable[$key] ) ) {

            // translators: %s is the name of the undefined property.
            throw new Exception( sprintf( esc_html__( 'Undefined property %s', 'wp-cafe' ), esc_html( $key ) ) );
        }

        $this->attributes[$key] = $value;
    }

    /**
     * Find any model by using id
     *
     * @param   integer  $id  Post id
     *
     * @return  null | PostModel
     */
    public static function find( $id ) {
        $post = get_post( $id );
        if ( ! $post ) return null;

        $instance = new static( $post );

        return $instance;
    }

    /**
     * Get all posts
     *
     * @param   array  $args  Post arguments
     *
     * @return  array   collection of PostModel
     */
    public static function all( $args = [] ) {
        $defaults = [
            'post_type'     => (new static)->get_post_type(),
            'post_status'   => 'any',
            'numberposts'   => -1
        ];

        $args = wp_parse_args( $args, $defaults );
        $posts = get_posts( $args );

        return array_map( fn( $p ) => new static( $p ), $posts );
    }

    /**
     * Pagenate all collections
     *
     * @param   integer  $per_page  Number of items perpage
     * @param   integer  $page      Current page number
     * @param   array  $args      other args
     *
     * @return  array   collection of records
     */
    public static function paginate( $args = [] ) {
        $default_args = [
            'post_type'      => ( new static )->get_post_type(),
            'post_status'    => 'any',
            'posts_per_page' => 10, // Default items per page
            'paged'          => 1,   // Default to first page
        ];

        $args = wp_parse_args( $args, $default_args );

        if ( isset( $args['search'] ) ) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- required for report/filter functionality
            $args['meta_query'] = ( new static )->search_query( $args['search'] );
        }

        if ( isset( $args['filters'] ) ) {

            if ( isset( $args['filters']['status'] ) ) {
                $args['post_status'] = $args['filters']['status'];
                unset( $args['filters']['status'] );
            }

            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- required for report/filter functionality
            $args['meta_query'] = ( new static )->filter_query( $args['filters'] );
            unset( $args['filters'] );
        }

        $query = new WP_Query( $args );

        return [
            'items'        => array_map( fn($post) => new static($post), $query->posts ),
            'total'        => $query->found_posts,
            'per_page'     => $args['posts_per_page'],
            'current_page' => $args['paged'],
            'last_page'    => ceil($query->found_posts / $args['posts_per_page']),
        ];
    }


    /**
     * Filter post by using condition
     *
     * @param   string  $meta_key    Meta key for the post model
     * @param   mixed  $meta_value  Post meta value
     *
     * @return  array collection of post model
     */
    public static function where( $meta_key, $meta_value ) {
        $args = [
            'post_type'     => ( new static )->get_post_type(),
            'post_status'   => 'publish',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- required for report/filter functionality
            'meta_query'    => [
                [
                    'key'   => $meta_key,
                    'value' => $meta_value,
                ]
            ]
        ];

        $posts = get_posts( $args );

        return array_map( fn( $p ) => new static( $p ), $posts );
    }

    /**
     * Create a single row
     *
     * @param   array  $attributes  collection of attributes
     *
     * @return  null | PostModel | WP_Error
     */
    public static function create( array $attributes ) {
        if ( ! self::validate_attributes( $attributes ) ) {
            throw new Exception( esc_html__( 'Invalid property provided.', 'wp-cafe' ) );
        }

        $post_data = [
            'post_type'     => (new static)->get_post_type(),
            'post_status'   => $attributes['status'] ?? 'publish',
            'post_title'    => $attributes['title'] ?? '',
            'post_content'  => $attributes['content'] ?? '',
            'post_author'   => $attributes['post_author'] ?? get_current_user_id(),
        ];

        $post_id = wp_insert_post( $post_data );

        if ( is_wp_error( $post_id ) ) return null;

        $instance = new static( $post_id );

        if ( ! is_array( $attributes ) ) {
            return $instance;
        }

        foreach ( $attributes as $key => $value) {
            if ( ! in_array( $key, ['title', 'content', 'status'] ) ) {

                update_post_meta( $post_id, $key, $value );

                $instance->$key = $value;
            }
        }

        return $instance;
    }

    /**
     * Update a certain record
     *
     * @param   array  $attributes  Collection of attributes
     *
     * @return  null | PostModel
     */
    public function update( array $attributes ) {
        if ( ! $this->post ) {
            throw new Exception( esc_html__( 'Cannot update a non-existent post.', 'wp-cafe' ) );
        }

        if ( ! self::validate_attributes( $attributes ) ) {
            throw new Exception( esc_html__( 'Invalid property provided.', 'wp-cafe' ) );
        }

        // Update title/content if provided
        $post_data = ['ID' => $this->post->ID];

        if ( isset( $attributes['title'] ) ) {
            $this->title = $attributes['title'];
            $post_data['post_title'] = $attributes['title'];
        }

        if ( isset( $attributes['content'] ) ) {
            $this->content = $attributes['content'];
            $post_data['post_content'] = $attributes['content'];
        }

        if ( isset( $attributes['status'] ) ) {
            $this->status = $attributes['status'];
            $post_data['post_status'] = $attributes['status'];
        }

        wp_update_post( $post_data );

        // Update custom meta fields
        foreach ( $attributes as $key => $value ) {
            if ( ! in_array( $key, ['title', 'content', 'status'] ) ) {
                update_post_meta( $this->post->ID, $key, $value );
                $this->attributes[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * Save a single record
     *
     * @return  PostModel instance of the PostModel
     */
    public function save() {

        if ( ! $this->post ) {
            return static::create( $this->attributes );
        }

        $post_data = [
            'ID'            => $this->post->ID,
            'post_title'    => $this->title,
            'post_content'  => $this->content,
        ];

        wp_update_post( $post_data );

        foreach ( $this->attributes as $key => $value ) {
            if ( ! in_array( $key, ['ID', 'title', 'content'] ) && $value !== ( $this->original[$key] ?? null ) ) {
                update_post_meta( $this->post->ID, $key, $value );
            }
        }

        return $this;
    }

    /**
     * Delete a record
     *
     * @return  WP_Post|false|null Post data on success, false or null on failure.
     */
    public function delete() {
        if ( $this->post ) {
            return wp_delete_post( $this->post->ID, true );
        }

        return false;
    }

    /**
     * Query records from posts
     *
     * @return  Object
     */
    public static function query() {
        return new QueryBuilder(new static);
    }

    /**
     * Validate attributes
     *
     * @param   array  $attributes  Post data
     *
     * @return  bool
     */
    protected static function validate_attributes( $attributes = [] ) {
        if ( ! is_array( $attributes ) ) {
            return false;
        }

        foreach ( $attributes as $key => $value ) {
            if ( ! isset( (new static)->fillable[$key] ) ) {
                /* translators: %s: property name */
                throw new Exception( esc_html( sprintf( __( 'Invalid property provided: %s', 'wp-cafe' ), $key ) ) );
            }
        }

        return true;
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function to_array(): array {
        return $this->attributes ?? [];
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @return array
     */
    public function jsonSerialize(): array {
        return $this->to_array();
    }

    /**
     * Build a meta query array for searching qrcode posts by a given value.
     *
     * Searches the 'table_name', 'table_id', and 'page_url' meta fields for the provided value.
     *
     * @param string $search_value The value to search for in the qrcode meta fields.
     * @return array The meta query array for use in WP_Query or similar.
     */
    public function search_query( $search_value ) {
        // Implement this method on concreate model class
        return [];
    }

    /**
     * Build a meta query array for filtering posts by a given value.
     *
     * This method should be implemented in the concrete model class to return
     * a meta query array suitable for use in WP_Query or similar, based on the
     * provided filter value.
     *
     * @param mixed $filter_value The value to filter the posts by.
     * @return array The meta query array for use in WP_Query or similar.
     */
    public function filter_query( $filters ) {
        // Implement this method on concreate model class
        return [];
    }

    /**
     * Returns an array of the keys of the $fillable property.
     *
     * This array can be used to check if a key is fillable or not.
     *
     * @return array
     */
    public function get_fillable_keys() {
        return array_keys($this->fillable);
    }
}

