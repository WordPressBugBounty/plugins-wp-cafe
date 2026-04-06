<?php
namespace WpCafe\Validation\Rules;

/**
 * Maximum length rule.
 */
class Max implements Validation_Rule_Interface {
    /**
     * @var int The maximum allowed length.
     */
    protected $max;

    /**
     * Max constructor.
     *
     * @param int $max The maximum number of characters allowed.
     */
    public function __construct($max) {
        $this->max = (int) $max;
    }

    /**
     * Validate that the value does not exceed the maximum length.
     *
     * @param string $field     The field name.
     * @param mixed  $value     The value to validate.
     * @param array  $all_data  The complete set of data being validated.
     * @return bool             True if valid, false otherwise.
     */
    public function validate(string $field, $value, array $all_data): bool {
        return is_string($value) && mb_strlen($value) <= $this->max;
    }

    /**
     * Get the error message for this rule.
     *
     * @param string $field The field name.
     * @return string       The error message.
     */
    public function message(string $field): string {
        // Using sprintf to format the error message
        return sprintf('The %s field may not be greater than %d characters.', $field, $this->max);
    }
}
