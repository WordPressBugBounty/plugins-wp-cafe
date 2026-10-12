<?php
namespace WpCafe\Integrations\Pointics;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;

/**
 * Starts WP Cafe's Pointics features.
 *
 * Runs on `pointics_booted` (Pointics boot, plugins_loaded priority 5), so no
 * class in this folder is loaded on a site without Pointics. Everything this
 * folder does is listed in SERVICES.
 *
 * @package WpCafe/Integrations/Pointics
 */
class Pointics_Bridge {
    /**
     * Pointics function added with the order screens: the oldest Pointics
     * build this folder runs on. Later additions are checked where they are used.
     */
    const REQUIRED_FUNCTION = 'pointics_order_points_summary';

    /**
     * The WP Cafe cards and tabs live in Pointics. A build with the order
     * screens but not these would fatal where this folder names them.
     */
    const REQUIRED_CLASS = '\\Pointics\\Integrations\\Wpcafe\\Wpcafe_Trigger_Provider';

    /**
     * Services to register, in order.
     *
     * @var string[]
     */
    const SERVICES = [
        Pointics_Cards::class,
        Menu_Badge::class,
        Reservation_Events_Listener::class,
        Booking_Order_Points_Line::class,
        Booking_Payment_Coupon_Keeper::class,
        Reservation_Points_Preview_Controller::class,
        Booking_Discount_Controller::class,
        Booking_Points_Discount::class,
        Reservation_Points_Admin_Controller::class,
        Pointics_Assets::class,
    ];

    /**
     * Whether boot() already registered the services.
     *
     * @var bool
     */
    private static $running = false;

    /**
     * Register every service once.
     *
     * @return void
     */
    public static function boot() {
        if ( self::$running || ! self::is_supported() ) {
            return;
        }

        self::$running = true;

        foreach ( self::SERVICES as $class ) {
            $service = new $class();

            if ( $service instanceof Hookable_Service_Contract ) {
                $service->register();
            }
        }

        // Needs Pointics' referral extension points and its referrals feature.
        if ( method_exists( '\Pointics\Referrals\Referral_Service', 'complete_for_hook' )
            && pointics_container()->make( \Pointics\Support\Features::class )->enabled( 'referrals' ) ) {
            ( new Reservation_Referral() )->register();
        }
    }

    /**
     * Whether the loaded Pointics has what this integration needs.
     *
     * @param string $required_function Function name to look for.
     * @param string $required_class    Class name to look for.
     * @return bool
     */
    public static function is_supported( string $required_function = self::REQUIRED_FUNCTION, string $required_class = self::REQUIRED_CLASS ): bool {
        return function_exists( $required_function ) && class_exists( $required_class );
    }

    /**
     * Whether the integration is live in this request.
     *
     * @return bool
     */
    public static function is_running(): bool {
        return self::$running;
    }
}
