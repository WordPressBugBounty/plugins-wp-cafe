<?php
namespace WpCafe\Validation;

use Exception;

/**
 * Custom exception for validation errors.
 */
class Validation_Exception extends Exception {
    /**
     * @var array
     */
    protected $errors;

    /**
     * @param array $errors
     */
    public function __construct(array $errors) {
        parent::__construct("Validation failed");
        $this->errors = $errors;
    }

    /**
     * Get all validation errors.
     *
     * @return array
     */
    public function get_errors(): array {
        return $this->errors;
    }
}


