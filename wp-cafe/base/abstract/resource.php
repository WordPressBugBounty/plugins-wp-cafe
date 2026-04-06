<?php
namespace WpCafe\Abstract;

use WP_REST_Response;

/**
 * Class Resource
 * Base class for API response resources.
 */
abstract class Resource implements \JsonSerializable {
    /**
     * The original data to be transformed.
     *
     * @var mixed
     */
    protected mixed $data;

    /**
     * Additional data to merge into the response.
     *
     * @var array
     */
    protected $with = [];

    /**
     * Resource constructor.
     *
     * @param mixed $data The data to transform.
     */
    public function __construct(mixed $data) {
        $this->data = $data;
    }

    /**
     * Transform the resource into an array.
     * You must implement this method in subclasses.
     *
     * @return array
     */
    abstract public function to_array();

    /**
     * Add additional data to the response.
     *
     * @param array $extra Extra key-value pairs to merge.
     * @return static
     */
    public function with($extra) {
        $this->with = $extra;
        return $this;
    }

    /**
     * Get the resolved array (main data + extra).
     *
     * @return array
     */
    public function resolve() {
        return array_merge($this->to_array(), $this->with);
    }

    /**
     * Handle JSON serialization.
     *
     * @return array
     */
    public function jsonSerialize(): array {
        return $this->resolve();
    }

    /**
     * Automatically convert to WP_REST_Response when returned.
     *
     * @return WP_REST_Response
     */
    public function to_response() {
        return new WP_REST_Response( $this->resolve() );
    }

    /**
     * Create a collection of this resource.
     *
     * @param array $items
     * @return array
     */
    public static function collection(array $items) {
        $collection = array_map(function ($item) {
            return (new static($item))->resolve();
        }, $items);

        return $collection;
    }
}
