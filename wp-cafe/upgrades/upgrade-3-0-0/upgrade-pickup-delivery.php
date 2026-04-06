<?php
namespace WpCafe\Upgrades\Upgrade_3_0_0;

use WpCafe\Models\Qrcode_Model;

/**
 * Class Upgrade_Pickup_Delivery
 *
 * Handles the upgrade process for version 3.0.0.
 */
class Upgrade_Pickup_Delivery {

    /**
     * Constructor for the Upgrade_Module class.
     *
     * Automatically triggers the upgrade process when an instance is created.
     */
    public function __construct() {
        $this->migrate_pickup_schedule();
        $this->migrate_delivery_schedule();
        $this->migrate_pickup_delivery_settings();
    }

    /**
     * Migrate pickup delivery
     *
     * @return  void
     */
    private function migrate_pickup_delivery_settings() {
        $pickup_message         = wpc_get_option('wpc_pro_pickup_message');
        $enable_pickup_date     = wpc_get_option('wpc_pro_allow_pickup_date');
        $enable_pickup_time     = wpc_get_option('wpc_pro_allow_pickup_time');
        $enable_delivery_date   = wpc_get_option('wpc_pro_allow_delivery_date');
        $enable_delivery_time   = wpc_get_option('wpc_pro_allow_delivery_time');
        $delivery_message       = wpc_get_option('wpc_pro_delivery_message');
        $minimum_order_amount   = wpc_get_option('min_order_amount');
        $prepare_time           = wpc_get_option('order_prepare_days');

        if ( 'on' === $enable_pickup_date ) {
            wpc_update_option('pickup_show_date_in_checkout_page', true);
        }

        if ( 'on' === $enable_pickup_time ) {
            wpc_update_option('pickup_show_time_in_checkout_page', true);
        }

        if ( 'on' === $enable_delivery_date ) {
            wpc_update_option('delivery_show_date_in_checkout_page', true);
        }

        if ( 'on' === $enable_delivery_time ) {
            wpc_update_option('delivery_show_time_in_checkout_page', true);
        }

        if ( $pickup_message ) {
            wpc_update_option('enable_pickup_message', true);
            wpc_update_option('pickup_message', $pickup_message);
        }

        if ( $delivery_message ) {
            wpc_update_option('enable_delivery_message', true);
            wpc_update_option('delivery_message', $delivery_message);
        }

        if ( $minimum_order_amount ) {
            wpc_update_option('pickup_minimum_order_amount', intval($minimum_order_amount) );
            wpc_update_option('delivery_minimum_order_amount', intval($minimum_order_amount) );
        }

        if ( $prepare_time ) {
            wpc_update_option('pickup_prepare_time', $prepare_time);
            wpc_update_option('delivery_prepare_time', $prepare_time);
        }
    }

    /**
     * Convert schedule data
     *
     * @return  array
     */
    private function convert_schedule_data($schedule_days, $schedule_start_time, $schedule_end_time) {
        
        if ( empty( $schedule_days ) || empty( $schedule_start_time ) || empty( $schedule_end_time ) ) {
            return [];
        }

        // Initialize all days
        $days = [
            "Mon" => ["status" => "off", "slots" => []],
            "Tue" => ["status" => "off", "slots" => []],
            "Wed" => ["status" => "off", "slots" => []],
            "Thu" => ["status" => "off", "slots" => []],
            "Fri" => ["status" => "off", "slots" => []],
            "Sat" => ["status" => "off", "slots" => []],
            "Sun" => ["status" => "off", "slots" => []],
        ];
        
        
        // Merge logic
        foreach ($schedule_days as $index => $dayRow) {
            foreach ($dayRow as $day => $status) {
        
                // Set status to ON
                $days[$day]['status'] = $status;
        
                // Add slot for that day
                $days[$day]['slots'][] = [
                    'start' => $schedule_start_time[$index],
                    'end'   => $schedule_end_time[$index],
                ];
            }
        }

        return $days;
    }

    /**
     * Migrate pickup schedule
     *
     * @return  void
     */
    private function migrate_pickup_schedule() {
        $wpc_pickup_weekly_schedule = wpc_get_option('wpc_pickup_weekly_schedule', []);
        $wpc_pickup_weekly_schedule_start_time = wpc_get_option('wpc_pickup_weekly_schedule_start_time', []);
        $wpc_pickup_weekly_schedule_end_time = wpc_get_option('wpc_pickup_weekly_schedule_end_time', []);

        $pickup_time_interval = wpc_get_option('pickup_time_interval');
        $schedule_data = $this->convert_schedule_data($wpc_pickup_weekly_schedule, $wpc_pickup_weekly_schedule_start_time, $wpc_pickup_weekly_schedule_end_time);

        if ( $schedule_data ) {
            wpc_update_option('override_pickup_schedule', "1");
            wpc_update_option('pickup_schedule', $schedule_data);
            wpc_update_option('pickup_slot_interval', intval($pickup_time_interval));
        }
    }

    /**
     * Migrate delivery schedule
     *
     * @return  void
     */
    private function migrate_delivery_schedule() {
        $wpc_delivery_weekly_schedule = wpc_get_option('wpc_delivery_schedule', []);
        $wpc_delivery_weekly_schedule_start_time = wpc_get_option('wpc_delivery_weekly_schedule_start_time', []);
        $wpc_delivery_weekly_schedule_end_time = wpc_get_option('wpc_delivery_weekly_schedule_end_time', []);

        $delivery_slot_interval = wpc_get_option('delivery_time_interval', 30);

        $schedule_data = $this->convert_schedule_data($wpc_delivery_weekly_schedule, $wpc_delivery_weekly_schedule_start_time, $wpc_delivery_weekly_schedule_end_time);

        if ( $schedule_data ) {
            wpc_update_option('override_delivery_schedule', "1");
            wpc_update_option('delivery_schedule', $schedule_data);
            wpc_update_option('delivery_slot_interval', intval($delivery_slot_interval));
        }
    }
}
