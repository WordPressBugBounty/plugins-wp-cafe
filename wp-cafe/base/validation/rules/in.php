<?php
namespace WpCafe\Validation\Rules;

/**
 * Rule to ensure a value is in a predefined list.
 */
class In implements Validation_Rule_Interface {
    /**
     * @var array
     */
    protected $values;

    /**
     * Constructor.
     *
     * @param string $list A comma-separated string of valid values.
     */
    public function __construct($list) {
        $this->values = array_map('trim', explode(',', $list));
    }

    /**
     * Validate that the value exists in the predefined list.
     *
     * @param string $field     The name of the field being validated.
     * @param mixed  $value     The value to validate.
     * @param array  $all_data  The full dataset being validated.
     * @return bool             True if valid, false otherwise.
     */
    public function validate(string $field, $value, array $all_data): bool {
        return in_array($value, $this->values, true);
    }

    /**
     * Get the error message for this rule.
     *
     * @param string $field The field name.
     * @return string       The error message.
     */
    public function message(string $field): string {
        return sprintf(
            'The %s field must be one of: %s.',
            $field,
            implode(', ', $this->values)
        );
    }
}
