<?php
/**
 * QueryBulder Class
 *
 * @package WpCafe/Database
 */
namespace WpCafe\Database;

use WP_Query;

class Query_Builder {
    /**
     * Store post model object
     *
     * @var Object
     */
    protected $model;

    /**
     * Store meta query
     *
     * @var array
     */
    protected $meta_query = [];

    /**
     * Store or meta query data
     *
     * @var array
     */
    protected $meta_query_or = [];

    /**
     * Store tax query
     *
     * @var array
     */
    protected $tax_query = [];

    /**
     * Store all args
     *
     * @var array
     */
    protected $args = [];

    /**
     * Constructor
     *
     * @param   Object  $model  The model class name
     */
    public function __construct( $model ) {
        $this->model = $model;
        $this->args['post_type'] = $model->get_post_type();
        $this->args['post_status'] = 'publish';
    }

    /**
     * Add a meta query condition (AND)
     *
     * @param   string|array  $key      Meta key or array of conditions
     * @param   string        $compare  Comparison operator
     * @param   mixed         $value    Meta value
     *
     * @return  $this
     */
    public function where( $key, $value = null, $compare = '=' )
    {
        if ( is_array( $key ) ) {
            foreach ( $key as $condition ) {
                $this->where(...$condition);
            }
            return $this;
        }

        $this->meta_query[] = compact('key', 'value', 'compare');
        return $this;
    }

    /**
     * Add an OR meta query condition
     *
     * @param   string  $key
     * @param   string  $compare
     * @param   mixed   $value
     *
     * @return  $this
     */
    public function orWhere( $key, $value = null, $compare = '=') {
        $this->meta_query_or[] = compact('key', 'value', 'compare');
        return $this;
    }

    /**
     * Add a WHERE IN meta query condition
     *
     * @param   string  $key
     * @param   array   $values
     *
     * @return  $this
     */
    public function whereIn( $key, array $values ) {
        $this->meta_query[] = [
            'key' => $key,
            'value' => $values,
            'compare' => 'IN',
        ];
        return $this;
    }

    /**
     * Add a meta query condition for NULL value
     *
     * @param   string  $key
     *
     * @return  $this
     */
    public function whereNull( $key ) {
        $this->meta_query[] = [
            'key'     => $key,
            'compare' => 'NOT EXISTS',
        ];

        return $this;
    }

    /**
     * Add a taxonomy query (generic)
     *
     * @param   string       $taxonomy
     * @param   string|array $terms
     * @param   string       $field
     *
     * @return  $this
     */
    public function taxonomy( $taxonomy, $terms, $field = 'slug' ) {
        $this->tax_query[] = [
            'taxonomy'  => $taxonomy,
            'field'     => $field,
            'terms'     => (array) $terms,
        ];

        return $this;
    }

    /**
     * Add a category query
     *
     * @param   string|int  $slugOrId
     *
     * @return  $this
     */
    public function category( $slugOrId ) {
        return $this->taxonomy( 'category', $slugOrId, is_numeric( $slugOrId ) ? 'term_id' : 'slug' );
    }

    /**
     * Add a tag query
     *
     * @param   string|int  $slugOrId
     *
     * @return  $this
     */
    public function tag( $slugOrId ) {
        return $this->taxonomy('post_tag', $slugOrId, is_numeric( $slugOrId ) ? 'term_id' : 'slug');
    }

    /**
     * Set order by custom field
     *
     * @param   string  $field
     * @param   string  $direction
     *
     * @return  $this
     */
    public function orderBy( $field, $direction = 'ASC' ) {
        $this->args['orderby']  = 'meta_value';
        $this->args['meta_key'] = $field;
        $this->args['order']    = $direction;

        return $this;
    }

    /**
     * Execute query and return all matched model instances
     *
     * @return  array
     */
    public function get() {
        $this->args['numberposts'] = -1;

        if ( $meta = $this->buildMetaQuery() ) {
            $this->args['meta_query'] = $meta;
        }

        if ( $this->tax_query ) {
            $this->args['tax_query'] = count( $this->tax_query ) > 1
                ? array_merge(['relation' => 'AND'], $this->tax_query)
                : $this->tax_query;
        }

        $posts = get_posts( $this->args );

        return array_map(fn($post) => new $this->model($post), $posts);
    }

    /**
     * Paginate the query results
     *
     * @param   int  $per_page
     * @param   int  $page
     *
     * @return  array
     */
    public function paginate( $per_page = 10, $page = 1 ) {
        $this->args['posts_per_page'] = $per_page;
        $this->args['paged'] = $page;

        if ( $meta = $this->buildMetaQuery() ) {
            $this->args['meta_query'] = $meta;
        }

        if ( $this->tax_query ) {
            $this->args['tax_query'] = count($this->tax_query) > 1
                ? array_merge(['relation' => 'AND'], $this->tax_query)
                : $this->tax_query;
        }

        $query = new WP_Query( $this->args );

        return [
            'items'         => array_map(fn($p) => new $this->model($p), $query->posts),
            'total'         => $query->found_posts,
            'per_page'      => $per_page,
            'current_page'  => $page,
            'last_page'     => ceil($query->found_posts / $per_page),
        ];
    }

    /**
     * Return first matched model or null
     *
     * @return  null|PostModel
     */
    public function first() {
        $this->args['numberposts'] = 1;

        if ( $meta = $this->buildMetaQuery() ) {
            $this->args['meta_query'] = $meta;
        }

        if ( $this->tax_query ) {
            $this->args['tax_query'] = $this->tax_query;
        }

        $posts = get_posts( $this->args );

        return $posts ? new $this->model( $posts[0] ) : null;
    }

    /**
     * Count number of matched posts
     *
     * @return  int
     */
    public function count() {
        $this->args['posts_per_page'] = 1;
        $this->args['fields'] = 'ids';

        if ( $meta = $this->buildMetaQuery() ) {
            $this->args['meta_query'] = $meta;
        }

        if ( $this->tax_query ) {
            $this->args['tax_query'] = $this->tax_query;
        }

        $query = new WP_Query( $this->args );

        return $query->found_posts;
    }

    /**
     * Check if any record exists
     *
     * @return  bool
     */
    public function exists() {
        return $this->count() > 0;
    }

    /**
     * Build meta_query with AND/OR relations
     *
     * @return  array
     */
    protected function buildMetaQuery() {
        $meta = [];

        if ( $this->meta_query ) {
            $meta[] = count( $this->meta_query ) > 0
                ? array_merge( ['relation' => 'AND'], $this->meta_query )
                : $this->meta_query[0];
        }

        if ( $this->meta_query_or ) {
            $meta[] = count( $this->meta_query_or ) > 0
                ? array_merge( ['relation' => 'OR'], $this->meta_query_or )
                : $this->meta_query_or[0];
        }

        if ( count( $meta ) === 1) {
            return $meta[0];
        } elseif ( count( $meta ) > 0 ) {
            return array_merge( ['relation' => 'AND'], $meta );
        }

        return [];
    }
}
