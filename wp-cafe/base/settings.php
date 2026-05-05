<?php
namespace WpCafe;

/**
 * Settings class
 */
class Settings {
    /**
     * Store option name
     *
     * @var string
     */
    protected static $option_name = 'wpcafe_reservation_settings_options';

    /**
     * Get settings
     *
     * @param   string  $key
     *
     * @return  mixed
     */
    public static function get( $key = '' ) {
        $settings = get_option( self::$option_name, [] );

        if ( ! $key ) {
            return $settings;
        }

        $value = '';

        if ( ! empty( $settings[$key] ) ) {
            $value = $settings[$key];
        }

        return $value;
    }

    /**
     * Update settings
     *
     * @param   array  $options
     *
     * @return  void
     */
    public static function update( $options = [] ) {
        $settings = self::get();

        $options = self::sanitize( $options );

        foreach ( $options as $name => $value ) {
            $settings[$name] = $value;
        }

        return update_option( self::$option_name, $settings );
    }

    /**
     * Sanitize settings options by key schema.
     * Known keys get typed sanitization; unknown keys pass through unchanged
     * to avoid breaking pro plugin or third-party extensions.
     *
     * @param  array $options Raw options array.
     * @return array Sanitized options.
     */
    private static function sanitize( array $options ): array {
        $schema = self::get_sanitization_schema();

        $sanitized = [];

        foreach ( $options as $key => $value ) {
            $type = $schema[ $key ] ?? 'passthrough';

            $sanitized[ $key ] = self::sanitize_by_type( $type, $value );
        }

        return $sanitized;
    }

    /**
     * Schema mapping each known settings key to its sanitization type.
     *
     * @return array<string, string>
     */
    private static function get_sanitization_schema(): array {
        return [
            // Strings
            'onboarding_init'                         => 'string',
            'onboarding_completed'                    => 'string',
            'mini_cart_style'                         => 'string',
            'reservation_status'                      => 'string',
            'wc_status'                               => 'string',
            'currency'                                => 'string',
            'currency_symbol_position'                => 'string',
            'currency_price_separator'                => 'string',
            'currency_decimals'                       => 'string',
            'display_location_selector'               => 'string',
            'restaurant_name'                         => 'string',
            'restaurant_phone'                        => 'string',
            'pickup_message'                          => 'string',
            'override_pickup_schedule'                => 'string',
            'override_delivery_schedule'              => 'string',
            'calendar_language'                       => 'string',
            'reservation_form_button_text'            => 'string',
            'reservation_confirmation_button_text'    => 'string',
            'reservation_cancellation_button_text'    => 'string',
            'fluentcrm_webhook_url'                   => 'string',
            'whatsapp_facebook_app_id'                => 'string',
            'whatsapp_facebook_app_secret'            => 'string',
            'whatsapp_token'                          => 'string',
            'whatsapp_from_number_id'                 => 'string',
            'whatsapp_business_account_id'            => 'string',

            // Email
            'restaurant_email'                        => 'email',

            // Integers
            'reservation_maximum_guest'               => 'int',
            'reservation_total_seat_capacity'         => 'int',
            'reservation_minimum_guest'               => 'int',
            'default_receipt_layout_id'               => 'int',
            'reservation_booking_amount'              => 'int',
            'slot_interval'                           => 'int',

            // Booleans
            'automation_flow_added'                    => 'bool',
            'terms_agreed'                             => 'bool',
            'setup_progress_widget_visited'            => 'bool',
            'pickup_show_date_in_checkout_page'        => 'bool',
            'pickup_show_time_in_checkout_page'        => 'bool',
            'enable_pickup_message'                    => 'bool',
            'delivery_show_date_in_checkout_page'      => 'bool',
            'delivery_show_time_in_checkout_page'      => 'bool',
            'enable_custom_holiday'                    => 'bool',
            'require_location'                         => 'bool',
            'multiply_booking_amount_with_guests'      => 'bool',
            'enable_local_payment'                     => 'bool',
            'enable_woocommerce_payments'              => 'bool',
            'enable_order_notification'                => 'bool',
            'enable_order_tip'                         => 'bool',

            // Colors
            'primary_color'                            => 'color',
            'secondary_color'                          => 'color',

            // String arrays
            'block_timeslot_statuses'                  => 'string_array',
            'restaurant_type'                          => 'string_array',
            'custom_holidays'                          => 'string_array',

            // Schedules
            'restaurant_schedule'                      => 'schedule',
            'pickup_schedule'                          => 'schedule',
            'delivery_schedule'                        => 'schedule',

            // Complex structures
            'reservation_form_customization'           => 'recursive',
            'reservation_advanced'                     => 'reservation_advanced',
            'restaurant_location'                      => 'restaurant_location',
            'mini_cart_icon'                           => 'mini_cart_icon',
        ];
    }

    /**
     * Dispatch sanitization based on type string.
     *
     * @param  string $type  Sanitization type from schema.
     * @param  mixed  $value Raw value.
     * @return mixed         Sanitized value.
     */
    private static function sanitize_by_type( string $type, $value ) {
        switch ( $type ) {
            case 'string':
                return sanitize_text_field( $value );

            case 'email':
                return sanitize_email( $value );

            case 'int':
                return absint( $value );

            case 'bool':
                return (bool) $value;

            case 'color':
                return sanitize_hex_color( $value ) ?? '';

            case 'string_array':
                return is_array( $value ) ? array_map( 'sanitize_text_field', $value ) : [];

            case 'schedule':
                return self::sanitize_schedule( $value );

            case 'recursive':
                return is_array( $value ) ? self::sanitize_value_recursive( $value ) : [];

            case 'reservation_advanced':
                return is_array( $value ) ? [
                    'value' => absint( $value['value'] ?? 30 ),
                    'unit'  => sanitize_text_field( $value['unit'] ?? 'minutes' ),
                ] : $value;

            case 'restaurant_location':
                return is_array( $value ) ? [
                    'address' => sanitize_text_field( $value['address'] ?? '' ),
                    'lat'     => isset( $value['lat'] ) ? (float) $value['lat'] : 0.0,
                    'lng'     => isset( $value['lng'] ) ? (float) $value['lng'] : 0.0,
                ] : $value;

            case 'mini_cart_icon':
                return is_array( $value ) ? [
                    'type'  => sanitize_text_field( $value['type'] ?? '' ),
                    'value' => sanitize_text_field( $value['value'] ?? '' ),
                ] : $value;

            default: // 'passthrough' — unknown keys: sanitize defensively
                if ( is_string( $value ) ) return sanitize_text_field( $value );
                if ( is_array( $value ) )  return self::sanitize_value_recursive( $value );
                if ( is_int( $value ) || is_float( $value ) || is_bool( $value ) ) return $value;
                return '';
        }
    }

    /**
     * Sanitize a schedule array (restaurant_schedule, pickup_schedule, delivery_schedule).
     * Whitelists day keys and status values; sanitizes time slot strings.
     *
     * @param  mixed $schedule Raw schedule value.
     * @return array           Sanitized schedule.
     */
    private static function sanitize_schedule( $schedule ): array {
        if ( ! is_array( $schedule ) ) {
            return [];
        }

        $allowed_days     = [ 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun' ];
        $allowed_statuses = [ 'on', 'off' ];
        $sanitized        = [];

        foreach ( $schedule as $day => $config ) {
            if ( ! in_array( $day, $allowed_days, true ) || ! is_array( $config ) ) {
                continue;
            }

            $status = ( isset( $config['status'] ) && in_array( $config['status'], $allowed_statuses, true ) )
                ? $config['status']
                : 'off';

            $slots = [];
            if ( ! empty( $config['slots'] ) && is_array( $config['slots'] ) ) {
                foreach ( $config['slots'] as $slot ) {
                    if ( ! is_array( $slot ) ) {
                        continue;
                    }
                    $slots[] = [
                        'start' => sanitize_text_field( $slot['start'] ?? '' ),
                        'end'   => sanitize_text_field( $slot['end'] ?? '' ),
                    ];
                }
            }

            $sanitized[ $day ] = [ 'status' => $status, 'slots' => $slots ];
        }

        return $sanitized;
    }

    /**
     * Recursively sanitize a nested array value.
     * arrays are recursed. Used for reservation_form_customization.
     *
     * @param  mixed $value Raw value.
     * @return mixed        Sanitized value.
     */
    private static function sanitize_value_recursive( $value ) {
        if ( is_array( $value ) ) {
            return array_map( [ self::class, 'sanitize_value_recursive' ], $value );
        }
        if ( is_string( $value ) ) {
            return sanitize_text_field( $value );
        }
        if ( is_int( $value ) || is_float( $value ) || is_bool( $value ) ) {
            return $value;
        }

        return '';
    }
}
