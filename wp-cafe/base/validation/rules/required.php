<?php
namespace WpCafe\Validation\Rules;

/**
 * Rule to ensure a field is present and not empty.
 */
class Required implements Validation_Rule_Interface {

    /**
     * Validate that the field is present and not empty.
     *
     * @param string $field     The name of the field being validated.
     * @param mixed  $value     The value to validate.
     * @param array  $all_data  The complete set of data being validated.
     * @return bool             True if the value is not null or empty string, false otherwise.
     */
    public function validate(string $field, $value, array $all_data): bool {
        return !is_null($value) && $value !== '';
    }

    /**
     * Get the error message for this rule.
     *
     * @param string $field The field name.
     * @return string       The error message.
     */
    public function message(string $field): string {
        return sprintf('The %s field is required.', $field);
    }
}
