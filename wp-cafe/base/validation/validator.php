<?php
namespace WpCafe\Validation;
/**
 * Validator class to validate data against rules.
 */
class Validator {
    /**
     * @var array
     */
    protected $data;

    /**
     * @var array<string, ValidationRuleInterface[]>
     */
    protected $rules;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @param array $data
     * @param array $rules
     */
    public function __construct(array $data, array $rules) {
        $this->data = $data;
        $this->rules = $rules;
    }

    /**
     * Run the validator.
     *
     * @throws Validation_Exception
     */
    public function validate(): void {
        foreach ($this->rules as $field => $field_rules) {
            $value = $this->data[$field] ?? null;

            foreach ( $field_rules as $rule ) {
                if ( ! $rule->validate( $field, $value, $this->data ) ) {
                    $this->errors[$field][] = $rule->message( $field );
                }
            }
        }

        if ( ! empty( $this->errors ) ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Errors are stored as array and escaped when displayed
            throw new Validation_Exception( $this->errors );
        }
    }
    
    /**
     * Check if the validation passed.
     *
     * @return bool True if validation passed.
     */
    public function passes(): bool {
        return empty($this->errors);
    }

    /**
     * Retrieve validation error messages.
     *
     * @return array Associative array of validation errors.
     */
    public function errors(): array {
        return $this->errors;
    }

}

