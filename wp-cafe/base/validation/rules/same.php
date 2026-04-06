<?php
namespace WpCafe\Validation\Rules;

/**
 * Rule to validate that one field's value matches another field's value.
 */
class Same implements Validation_Rule_Interface {
    /**
     * The name of the other field to compare with.
     *
     * @var string
     */
    protected $other_field;

    /**
     * Constructor to initialize the other field name.
     *
     * @param string $other_field The name of the field to compare against.
     */
    public function __construct($other_field) {
        $this->other_field = $other_field;
    }

    /**
     * Validate that the field value matches the value of the other field.
     *
     * @param string $field     The name of the field being validated.
     * @param mixed  $value     The value to validate.
     * @param array  $all_data  The complete set of data being validated.
     * @return bool             True if the values match, false otherwise.
     */
    public function validate(string $field, $value, array $all_data): bool {
        return isset($all_data[$this->other_field]) && $all_data[$this->other_field] === $value;
    }

    /**
     * Get the error message for this rule.
     *
     * @param string $field The field name.
     * @return string       The error message.
     */
    public function message(string $field): string {
        return sprintf('The %s field must match %s.', $field, $this->other_field);
    }
}

