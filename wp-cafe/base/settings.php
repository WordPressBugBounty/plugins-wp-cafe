<?php
namespace WpCafe;

defined( 'ABSPATH' ) || exit;

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
    /**
     * Positive-integer settings that must be > 0 to be meaningful.
     * A value of 0 (which absint() produces for null/empty inputs) means
     * "not configured" — skip the update so the previous DB value is preserved.
     */
    private const POSITIVE_INT_KEYS = [
        'reservation_maximum_guest',
        'reservation_minimum_guest',
        'reservation_total_seat_capacity',
        'slot_interval',
        'reservation_booking_amount',
    ];

    /**
     * Allowed values for the presence-widget enum fields.
     *
     * Kept in the class so the option can never hold a corner or a display
     * mode the templates have no CSS or branch for.
     */
    private const PRESENCE_POSITIONS  = [ 'bottom-right', 'bottom-left', 'top-right', 'top-left' ];
    private const PRESENCE_DISPLAY    = [ 'all_pages', 'specific_pages', 'dont_show' ];
    private const PRESENCE_SOURCES    = [ 'upload', 'url' ];
    private const PRESENCE_LINK_TYPES = [ 'page', 'url' ];
    private const PRESENCE_TRIGGERS   = [ 'load', 'delay', 'scroll' ];

    /**
     * Ceilings on what one presence save may store.
     *
     * This option is autoloaded on every request, so nothing reaching the
     * sanitizer may grow it without bound. The contact card's UI still has no
     * row limit: its list scrolls, and 50 rows is far past any real menu.
     */
    private const PRESENCE_TEXT_MAX  = 200;
    private const PRESENCE_MAX_ROWS  = 50;
    private const PRESENCE_MAX_PAGES = 200;

    public static function update( $options = [] ) {
        $settings = self::get();

        $options = self::sanitize( $options );

        foreach ( $options as $name => $value ) {
            // Don't overwrite a positive-integer field with 0 (sent as null/empty from the form).
            if ( in_array( $name, self::POSITIVE_INT_KEYS, true ) && $value === 0 ) {
                continue;
            }

            // The presence sanitizer returns only the widgets and fields the
            // payload named, so the previous value has to be folded back in
            // here, where it is already loaded.
            if ( 'presence_widgets' === $name ) {
                $value = self::merge_presence_widgets( $settings[ $name ] ?? [], $value );
            }

            $settings[$name] = $value;
        }

        return update_option( self::$option_name, $settings );
    }

    /**
     * Remove keys from the settings option entirely.
     *
     * update() only merges, so a key written by an old version stays forever
     * once nothing sends it any more. This is the only way to retire one.
     *
     * @param  array $keys Setting keys to drop.
     * @return bool  True when something was removed and the option was saved.
     */
    public static function delete_keys( array $keys ) {
        $settings = self::get();

        if ( ! is_array( $settings ) ) {
            return false;
        }

        $removed = false;

        foreach ( $keys as $key ) {
            if ( array_key_exists( $key, $settings ) ) {
                unset( $settings[ $key ] );
                $removed = true;
            }
        }

        if ( ! $removed ) {
            return false;
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
            'funnelkit_webhook_url'                   => 'string',
            'uncanny_automator_webhook_url'           => 'string',
            'mailmint_webhook_url'                    => 'string',
            'zoho_flow_webhook_url'                   => 'string',
            'flowmattic_webhook_url'                  => 'string',
            'whatsapp_facebook_app_id'                => 'string',
            'whatsapp_facebook_app_secret'            => 'string',
            'whatsapp_token'                          => 'string',
            'whatsapp_from_number_id'                 => 'string',
            'whatsapp_business_account_id'            => 'string',
            'whatsapp_admin_number'                   => 'string',

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
            'pickup_enable_asap'                       => 'bool',
            'enable_pickup_message'                    => 'bool',
            'delivery_show_date_in_checkout_page'      => 'bool',
            'delivery_show_time_in_checkout_page'      => 'bool',
            'delivery_enable_asap'                     => 'bool',
            'enable_custom_holiday'                    => 'bool',
            'require_location'                         => 'bool',
            'enable_floating_location_widget'          => 'bool',
            'multiply_booking_amount_with_guests'      => 'bool',
            'reservation_partial_payment'              => 'bool',
            'enable_local_payment'                     => 'bool',
            'enable_woocommerce_payments'              => 'bool',
            'enable_order_notification'                => 'bool',
            'enable_reservation_notification'          => 'bool',
            'enable_order_tip'                         => 'bool',
            'mini_cart_show_per_item_tax'              => 'bool',
            'qr_show_pickup_delivery'                  => 'bool',

            // Colors
            'primary_color'                            => 'color',
            'secondary_color'                          => 'color',

            // String arrays
            'block_timeslot_statuses'                  => 'string_array',
            'restaurant_type'                          => 'string_array',
            'custom_holidays'                          => 'string_array',
            'mailpoet_list_ids'                        => 'string_array',
            'order_limit_statuses'                     => 'string_array',

            // Schedules
            'restaurant_schedule'                      => 'schedule',
            'pickup_schedule'                          => 'schedule',
            'delivery_schedule'                        => 'schedule',

            // Complex structures
            'reservation_form_customization'           => 'recursive',
            'reservation_advanced'                     => 'reservation_advanced',
            'restaurant_location'                      => 'restaurant_location',
            'mini_cart_icon'                           => 'mini_cart_icon',
            'presence_widgets'                         => 'presence_widgets',
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

            case 'presence_widgets':
                return self::sanitize_presence_widgets( $value );

            default: // 'passthrough' — unknown keys: sanitize defensively
                if ( is_string( $value ) ) return sanitize_text_field( $value );
                if ( is_array( $value ) )  return self::sanitize_value_recursive( $value );
                if ( is_int( $value ) || is_float( $value ) || is_bool( $value ) ) return $value;
                return '';
        }
    }

    /**
     * Sanitize the nested presence_widgets structure.
     *
     * Input only. The merge with what is already saved happens in update(),
     * where the previous settings are loaded anyway, so running this twice in
     * one request gives the same answer both times.
     *
     * @param  mixed $value Raw value from the REST payload.
     * @return array        Only the widgets the payload actually named.
     */
    private static function sanitize_presence_widgets( $value ): array {
        if ( ! is_array( $value ) ) {
            return [];
        }

        $clean = [];

        foreach ( [ 'video', 'contact' ] as $widget ) {
            if ( is_array( $value[ $widget ] ?? null ) ) {
                $clean[ $widget ] = self::presence_widget( $widget, $value[ $widget ] );
            }
        }

        return $clean;
    }

    /**
     * Sanitize one widget, keeping only the keys the payload named.
     *
     * array_intersect_key is what makes a partial write safe: send one field
     * and the rest of the widget is left for update() to fill from what is
     * already saved, instead of resetting to a hard-coded default.
     *
     * @param  string $widget 'video' or 'contact'.
     * @param  array  $raw    That widget's raw payload.
     * @return array
     */
    private static function presence_widget( string $widget, array $raw ): array {
        $clean = 'video' === $widget
            ? self::presence_video( $raw )
            : self::presence_contact( $raw );

        $clean = array_intersect_key( $clean, $raw );

        // A page list only means anything for "specific pages". Keeping it
        // otherwise leaves IDs behind that nothing reads.
        if ( isset( $clean['display'] ) && 'specific_pages' !== $clean['display'] ) {
            $clean['display_pages'] = [];
        }

        return $clean;
    }

    /**
     * Fold a sanitized presence payload into what is already saved.
     *
     * Each widget has its own settings tab, so a payload naming one widget
     * must not touch the other, and a payload naming one field must not reset
     * the rest. Defaults only fill a key the site has never saved, so a first
     * partial write still stores a complete config.
     *
     * @param  mixed $stored   Previously saved presence_widgets value.
     * @param  array $incoming Sanitized payload.
     * @return array
     */
    private static function merge_presence_widgets( $stored, array $incoming ): array {
        $stored = is_array( $stored ) ? $stored : [];

        // A payload that named no widget is not an instruction to erase both.
        if ( ! $incoming ) {
            return $stored;
        }

        $merged = $stored;

        foreach ( $incoming as $widget => $config ) {
            $merged[ $widget ] = array_merge(
                self::presence_defaults( $widget ),
                is_array( $stored[ $widget ] ?? null ) ? $stored[ $widget ] : [],
                $config
            );
        }

        return $merged;
    }

    /**
     * Default config for one presence widget.
     *
     * Only fills a key the site has never saved. Keep in step with
     * Presence_Settings::defaults() in wpcafe-pro.
     *
     * @param  string $widget 'video' or 'contact'.
     * @return array          Empty for an unknown widget name.
     */
    private static function presence_defaults( string $widget ): array {
        $defaults = [
            'video'   => [
                'enabled'         => false,
                'source_type'     => 'upload',
                'video_id'        => 0,
                'video_url'       => '',
                'show_controls'   => true,
                'start_muted'     => true,
                'allow_unmute'    => true,
                'autoplay'        => false,
                'start_minimized' => true,
                'position'        => 'bottom-right',
                'offset_x'        => 24,
                'offset_y'        => 190,
                'display'         => 'all_pages',
                'display_pages'   => [],
                'cta_label'       => '',
                'cta_link_type'   => 'page',
                'cta_page_id'     => 0,
                'cta_url'         => '',
                'trigger'         => 'load',
                'trigger_delay'   => 3,
                'trigger_scroll'  => 20,
            ],
            'contact' => [
                'enabled'        => false,
                'title'          => '',
                'subtitle'       => '',
                'items'          => [],
                'primary'        => [ 'label' => '', 'url' => '' ],
                'secondary'      => [ 'label' => '', 'url' => '' ],
                'position'       => 'bottom-left',
                'offset_x'       => 24,
                'offset_y'       => 24,
                'display'        => 'all_pages',
                'display_pages'  => [],
                'trigger'        => 'load',
                'trigger_delay'  => 3,
                'trigger_scroll' => 20,
                'remember_close' => true,
            ],
        ];

        return $defaults[ $widget ] ?? [];
    }

    /**
     * Sanitize every key of the video widget.
     *
     * @param  array $raw Raw video config.
     * @return array
     */
    private static function presence_video( array $raw ): array {
        return [
            'enabled'         => ! empty( $raw['enabled'] ),
            'source_type'     => self::presence_enum( $raw['source_type'] ?? '', self::PRESENCE_SOURCES, 'upload' ),
            'video_id'        => absint( $raw['video_id'] ?? 0 ),
            'video_url'       => sanitize_url( $raw['video_url'] ?? '' ),
            'show_controls'   => ! empty( $raw['show_controls'] ),
            'start_muted'     => ! empty( $raw['start_muted'] ),
            'allow_unmute'    => ! empty( $raw['allow_unmute'] ),
            'autoplay'        => ! empty( $raw['autoplay'] ),
            'start_minimized' => ! empty( $raw['start_minimized'] ),
            'position'        => self::presence_enum( $raw['position'] ?? '', self::PRESENCE_POSITIONS, 'bottom-right' ),
            'offset_x'        => self::presence_offset( $raw['offset_x'] ?? 24 ),
            'offset_y'        => self::presence_offset( $raw['offset_y'] ?? 190 ),
            'display'         => self::presence_enum( $raw['display'] ?? '', self::PRESENCE_DISPLAY, 'all_pages' ),
            'display_pages'   => self::presence_page_ids( $raw['display_pages'] ?? [] ),
            'cta_label'       => self::presence_text( $raw['cta_label'] ?? '' ),
            'cta_link_type'   => self::presence_enum( $raw['cta_link_type'] ?? '', self::PRESENCE_LINK_TYPES, 'page' ),
            'cta_page_id'     => absint( $raw['cta_page_id'] ?? 0 ),
            'cta_url'         => sanitize_url( $raw['cta_url'] ?? '' ),
            'trigger'         => self::presence_enum( $raw['trigger'] ?? '', self::PRESENCE_TRIGGERS, 'load' ),
            'trigger_delay'   => self::presence_range( $raw['trigger_delay'] ?? 3, 1, 60 ),
            'trigger_scroll'  => self::presence_range( $raw['trigger_scroll'] ?? 20, 5, 90 ),
        ];
    }

    /**
     * Sanitize every key of the contact card.
     *
     * @param  array $raw Raw contact config.
     * @return array
     */
    private static function presence_contact( array $raw ): array {
        return [
            'enabled'        => ! empty( $raw['enabled'] ),
            'title'          => self::presence_text( $raw['title'] ?? '' ),
            'subtitle'       => self::presence_text( $raw['subtitle'] ?? '' ),
            'items'          => self::presence_items( $raw['items'] ?? [] ),
            'primary'        => self::presence_button( $raw['primary'] ?? [] ),
            'secondary'      => self::presence_button( $raw['secondary'] ?? [] ),
            'position'       => self::presence_enum( $raw['position'] ?? '', self::PRESENCE_POSITIONS, 'bottom-left' ),
            'offset_x'       => self::presence_offset( $raw['offset_x'] ?? 24 ),
            'offset_y'       => self::presence_offset( $raw['offset_y'] ?? 24 ),
            'display'        => self::presence_enum( $raw['display'] ?? '', self::PRESENCE_DISPLAY, 'all_pages' ),
            'display_pages'  => self::presence_page_ids( $raw['display_pages'] ?? [] ),
            'trigger'        => self::presence_enum( $raw['trigger'] ?? '', self::PRESENCE_TRIGGERS, 'load' ),
            'trigger_delay'  => self::presence_range( $raw['trigger_delay'] ?? 3, 1, 60 ),
            'trigger_scroll' => self::presence_range( $raw['trigger_scroll'] ?? 20, 5, 90 ),
            'remember_close' => ! empty( $raw['remember_close'] ),
        ];
    }

    /**
     * Return $value when it is in $allowed, otherwise $fallback.
     *
     * @param  mixed  $value    Raw value.
     * @param  array  $allowed  Allowed values.
     * @param  string $fallback Value to use when the input is not allowed.
     * @return string
     */
    private static function presence_enum( $value, array $allowed, string $fallback ): string {
        $value = is_string( $value ) ? $value : '';

        return in_array( $value, $allowed, true ) ? $value : $fallback;
    }

    /**
     * Sanitize a short free-text field and cap its length.
     *
     * @param  mixed $value Raw text.
     * @param  int   $max   Characters to keep.
     * @return string
     */
    private static function presence_text( $value, int $max = self::PRESENCE_TEXT_MAX ): string {
        $text = sanitize_text_field( is_scalar( $value ) ? (string) $value : '' );

        return function_exists( 'mb_substr' ) ? mb_substr( $text, 0, $max ) : substr( $text, 0, $max );
    }

    /**
     * Clamp a raw number into an inclusive range.
     *
     * (int), not absint: absint( -5 ) is 5, which drops an out-of-range
     * negative somewhere inside the range instead of at $min.
     *
     * @param  mixed $value Raw value.
     * @param  int   $min   Lowest allowed value.
     * @param  int   $max   Highest allowed value.
     * @return int
     */
    private static function presence_range( $value, int $min, int $max ): int {
        return max( $min, min( $max, (int) $value ) );
    }

    /**
     * Clamp a pixel nudge to somewhere the owner can still see the widget.
     *
     * @param  mixed $value Raw offset.
     * @return int          0-500.
     */
    private static function presence_offset( $value ): int {
        return self::presence_range( $value, 0, 500 );
    }

    /**
     * Sanitize the list of pages a widget is limited to.
     *
     * @param  mixed $value Raw page ID list.
     * @return int[]        Unique positive IDs, capped.
     */
    private static function presence_page_ids( $value ): array {
        if ( ! is_array( $value ) ) {
            return [];
        }

        // intval, not absint: absint( -3 ) is 3, which would silently turn a
        // malformed ID into a different real page.
        $ids = array_filter(
            array_map( 'intval', $value ),
            static function ( $id ) {
                return $id > 0;
            }
        );

        return array_values( array_slice( array_unique( $ids ), 0, self::PRESENCE_MAX_PAGES ) );
    }

    /**
     * Sanitize one of the contact card's two buttons.
     *
     * @param  mixed $value Raw button.
     * @return array
     */
    private static function presence_button( $value ): array {
        $value = is_array( $value ) ? $value : [];

        return [
            'label' => self::presence_text( $value['label'] ?? '' ),
            'url'   => sanitize_url( $value['url'] ?? '' ),
        ];
    }

    /**
     * Sanitize the contact info rows.
     *
     * The card scrolls its own list, so the UI has no row limit. The stored
     * cap is a different thing: this option is autoloaded on every request,
     * and 50 rows is already far past any real list.
     *
     * @param  mixed $value Raw rows.
     * @return array
     */
    private static function presence_items( $value ): array {
        if ( ! is_array( $value ) ) {
            return [];
        }

        $icons = array_merge( array_keys( wpc_presence_icon_slugs() ), [ 'custom' ] );
        $rows  = [];

        foreach ( array_slice( $value, 0, self::PRESENCE_MAX_ROWS ) as $row ) {
            if ( ! is_array( $row ) ) {
                continue;
            }

            $rows[] = [
                'icon'    => self::presence_enum( $row['icon'] ?? '', $icons, 'email' ),
                'icon_id' => absint( $row['icon_id'] ?? 0 ),
                'text'    => self::presence_text( $row['text'] ?? '' ),
                'url'     => sanitize_url( $row['url'] ?? '' ),
            ];
        }

        return $rows;
    }

    /**
     * Sanitize a schedule array (restaurant_schedule, pickup_schedule, delivery_schedule,
     * reservation_schedule). Whitelists day keys and status values; sanitizes time slot
     * strings; validates max_orders.
     *
     * Public so other save paths that carry a schedule-shaped value — e.g. the
     * per-location controller — can reuse the same rules instead of re-implementing them.
     *
     * @param  mixed $schedule Raw schedule value.
     * @return array           Sanitized schedule with status, slots, and max_orders per day.
     */
    public static function sanitize_schedule( $schedule ): array {
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

            // Empty string / missing / negative all mean "unlimited" (null) —
            // only an explicit non-negative number sets a real cap, so a bad
            // input can never silently turn into "accept 0 orders today".
            // round() rather than an (int) cast: the FE already blocks
            // fractional input, but a direct API call could still send one
            // (e.g. 1.5) — round it to the nearest whole order instead of
            // truncating it down to a smaller cap than what was sent.
            $max_orders = null;
            if ( isset( $config['max_orders'] ) && is_numeric( $config['max_orders'] ) ) {
                $candidate = (int) round( (float) $config['max_orders'] );
                if ( $candidate >= 0 ) {
                    $max_orders = $candidate;
                }
            }

            $sanitized[ $day ] = [ 'status' => $status, 'slots' => $slots, 'max_orders' => $max_orders ];
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
