<?php
namespace WpCafe\LiveAlert;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Assets Manager
 *
 * Loads the alert box script and style on admin pages for users who may see
 * orders or reservations.
 */
class Assets_Manager implements Hookable_Service_Contract {
    /**
     * Register hooks.
     *
     * @return void
     */
    public function register() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Enqueue the alert script, style and config.
     *
     * @return void
     */
    public function enqueue_assets() {
        if ( ! Notifier::is_legacy_enabled() || ! ( Notifier::can_view_orders() || Notifier::can_view_reservations() ) ) {
            return;
        }

        // Both toggles off: nothing to alert about, so skip the script and its heartbeat work.
        if ( ! Notifier::orders_enabled() && ! Notifier::reservations_enabled() ) {
            return;
        }

        $version = defined( 'WPCAFE_VERSION' ) ? WPCAFE_VERSION : false;

        wp_enqueue_script( 'wpc-live-order-notify', wpcafe()->assets_url . '/build/js/live-order-notify.js', [ 'heartbeat' ], $version, true );
        wp_enqueue_style( 'wpc-live-order-notify', wpcafe()->assets_url . '/build/css/live-order.css', [], $version );

        wp_add_inline_script(
            'wpc-live-order-notify',
            'window.wpcLiveOrder = ' . wp_json_encode( $this->get_config() ) . ';',
            'before'
        );
    }

    /**
     * Config for the script. Built with wp_json_encode so booleans and numbers
     * keep their types.
     *
     * @return array
     */
    public function get_config() {
        return [
            'seq'            => Notifier::get_log()['seq'],
            'is_orders_list' => $this->is_orders_list(),
            // Site wall-clock now, same frame as the orders list <time datetime>.
            'site_now'       => current_time( 'timestamp' ) * 1000,
            'sound'          => self::sound_config(),
            'i18n'           => [
                'close'    => __( 'Close', 'wp-cafe' ),
                'view'     => __( 'View', 'wp-cafe' ),
                'just_now' => __( 'Just Now', 'wp-cafe' ),
                'recent'   => __( 'Recent', 'wp-cafe' ),
                'today'    => __( 'Today', 'wp-cafe' ),
            ],
        ];
    }

    /**
     * Sound settings. Also sent with every heartbeat, so open pages follow
     * changes without a reload.
     *
     * @return array { enabled, url, repeat_minutes }
     */
    public static function sound_config() {
        $default_sound = wpcafe()->assets_url . '/music/ding_dong.mp3';
        $sound_url     = esc_url_raw( (string) wpc_get_option( 'custom_notification_sound', $default_sound ) );

        return [
            'enabled'        => filter_var( wpc_get_option( 'enable_sound_notification', false ), FILTER_VALIDATE_BOOLEAN ),
            'url'            => $sound_url ? $sound_url : $default_sound,
            'repeat_minutes' => self::repeat_minutes(),
        ];
    }

    /**
     * Saved repeat minutes. Read raw: wpc_get_option() turns a saved 0
     * ("Play Once") into the default 1.
     *
     * @return int
     */
    protected static function repeat_minutes() {
        $settings = (array) Settings::get();

        if ( ! isset( $settings['repeated_sound_minute'] ) || '' === $settings['repeated_sound_minute'] ) {
            return 1;
        }

        return absint( $settings['repeated_sound_minute'] );
    }

    /**
     * Whether the current screen is the WooCommerce orders list (HPOS or legacy).
     *
     * @return bool
     */
    protected function is_orders_list() {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

        if ( ! $screen || ! in_array( $screen->id, [ 'woocommerce_page_wc-orders', 'edit-shop_order' ], true ) ) {
            return false;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen check
        $action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';

        return ! in_array( $action, [ 'edit', 'new' ], true );
    }
}
