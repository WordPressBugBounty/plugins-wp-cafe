<?php
namespace WpCafe\Validation\Rules;

/**
 * Minimum length rule.
 */
class Min implements Validation_Rule_Interface {
    /**
     * @var int The minimum required length.
     */
    protected $min;

    /**
     * Min constructor.
     *
     * @param int $min The minimum number of characters required.
     */
    public function __construct($min) {
        $this->min = (int) $min;
    }

    /**
     * Validate that the value meets the minimum length.
     *
     * @param string $field     The field name.
     * @param mixed  $value     The value to validate.
     * @param array  $all_data  The complete set of data being validated.
     * @return bool             True if valid, false otherwise.
     */
    public function validate(string $field, $value, array $all_data): bool {
        return is_string($value) && mb_strlen($value) >= $this->min;
    }

    /**
     * Get the error message for this rule.
     *
     * @param string $field The field name.
     * @return string       The error message.
     */
    public function message(string $field): string {
        return sprintf('The %s field must be at least %d characters.', $field, $this->min);
    }
}
