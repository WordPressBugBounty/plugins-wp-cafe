<?php
namespace WpCafe\Validation;

use WpCafe\Validation\Rules;

/**
 * Factory class to parse string rules.
 */
class Rule_Factory {
    /**
     * Rule name to class map.
     *
     * @var array
     */
    protected static $rule_map = [
        'required' => Rules\Required::class,
        'email'    => Rules\Email::class,
        'min'      => Rules\Min::class,
        'max'      => Rules\Max::class,
        'in'       => Rules\In::class,
        'same'     => Rules\Same::class,
    ];

    /**
     * Make rule instances from string or array.
     *
     * @param string|array $rules
     * @return array
     */
    public static function make($rules) {
        $rule_objects = [];

        if ( is_string( $rules ) ) {
            $rules = explode('|', $rules);
        }

        foreach ( $rules as $rule ) {
            [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
            $class = self::$rule_map[$name] ?? null;

            if ( $class && class_exists( $class ) ) {
                $rule_objects[] = $param ? new $class($param) : new $class();
            }
        }

        return $rule_objects;
    }
}


