<?php
namespace WpCafe\Validation\Rules;

/**
 * Interface for all validation rules.
 */
interface Validation_Rule_Interface {
    /**
     * Validate the rule.
     *
     * @param string $field
     * @param mixed $value
     * @param array $all_data
     * @return bool
     */
    public function validate(string $field, $value, array $all_data): bool;

    /**
     * Error message for the rule.
     *
     * @param string $field
     * @return string
     */
    public function message(string $field): string;
}

