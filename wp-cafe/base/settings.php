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

        foreach ( $options as $name => $value ) {
            $settings[$name] = $options[$name];
        }

        return update_option( self::$option_name, $settings );
    }
}
