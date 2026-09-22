<?php
namespace WpCafe\LiveAlert;

use WpCafe\Contracts\Switchable_Provider_Contract;
use WpCafe\Providers\Base_Service_Provider;

defined( 'ABSPATH' ) || exit;

/**
 * Admin alerts (green box and sound) for new orders and reservations.
 *
 * @package WpCafe/LiveAlert
 */
class Live_Alert_Service_Provider extends Base_Service_Provider implements Switchable_Provider_Contract {
    /**
     * Store services
     *
     * @var array
     */
    protected $services = [
        Notifier::class,
        Assets_Manager::class,
    ];

    /**
     * Register services
     *
     * @return array
     */
    public function get_services() {
        return apply_filters( 'wpcafe_live_alert_services', $this->services );
    }

    /**
     * Own switch, so reservation-only sites still get alerts.
     *
     * @return bool
     */
    public function is_enable() {
        return ( wpc_is_module_enable( 'food_ordering' ) && function_exists( 'WC' ) )
            || wpc_is_module_enable( 'reservation' );
    }
}
