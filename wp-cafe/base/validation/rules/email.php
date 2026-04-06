<?php
namespace WpCafe\Validation\Rules;

/**
 * Rule to validate an email address.
 */
class Email implements Validation_Rule_Interface {
    /**
     * Validate that the given value is a valid email address.
     *
     * @param string $field     The name of the field being validated.
     * @param mixed  $value     The value to validate.
     * @param array  $all_data  The complete set of data being validated.
     * @return bool             True if the value is a valid email, false otherwise.
     */
    public function validate(string $field, $value, array $all_data): bool {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Get the error message for this rule.
     *
     * @param string $field The field name.
     * @return string       The error message.
     */
    public function message(string $field): string {
        return sprintf('The %s must be a valid email address.', $field);
    }
}
