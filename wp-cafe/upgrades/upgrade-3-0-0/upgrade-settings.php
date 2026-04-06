<?php
namespace WpCafe\Upgrades\Upgrade_3_0_0;

use WpCafe\Models\Qrcode_Model;

/**
 * Class Upgrade_Settings
 *
 * Handles the upgrade process for version 3.0.0.
 */
class Upgrade_Settings {

    /**
     * Constructor for the Upgrade_Module class.
     *
     * Automatically triggers the upgrade process when an instance is created.
     */
    public function __construct() {
        $this->migrate_mini_cart();
        $this->migrate_tipping();
        $this->migrate_live_notification();
        $this->migrate_qr_code();
        $this->migrate_general_settings();
        $this->migrate_reservation_button_settings();
        $this->migrate_wc_layout();
        $this->migrate_location_settings();
        $this->migrate_integration_value();
        $this->migrate_reservation_settings();
    }

    /**
     * Migrate mini-cart
     *
     * @return  void
     */
    private function migrate_mini_cart() {
        $mini_cart_style = wpc_get_option('minicart_style');
        $mini_cart_empty_button_link = wpc_get_option('wpc_mini_empty_cart_link');

        if ( $mini_cart_style ) {
            wpc_update_option( 'mini_cart_style', $mini_cart_style );
        }

        if ( $mini_cart_empty_button_link ) {
            wpc_update_option( 'mini_cart_empty_button_link', $mini_cart_empty_button_link );
        }
    }

    /**
     * Migrate tipping
     *
     * @return  void
     */
    private function migrate_tipping() {
        $tipping_type           = wpc_get_option('wpc_pro_tip_allow_for');
        $tip_options            = wpc_get_option('wpc_pro_tip_percentage_data', []);

        $tip_options = array_map(function($item) {
            return intval($item);
        }, $tip_options);

        if ( 'tip_fixed' === $tipping_type ) {
            $tipping_type = 'fixed_amount';
        }elseif( 'tip_percentage' === $tipping_type ) {
            $tipping_type = 'percentage_amount';
        } else {
            $tipping_type = '';
        }

        if ( $tipping_type ) {
            wpc_update_option('tipping_calculation_method', $tipping_type);
            wpc_update_option('tip_options', $tip_options);
        }
    }

    /**
     * Migrate live notification
     *
     * @return  void
     */
    private function migrate_live_notification() {
        $order_notification = wpc_get_option('wpc_pro_order_notify');
        $sound_notification = wpc_get_option('wpc_pro_sound_notify');
        $sound_file         = wpc_get_option('sound_media_file');
        $sound_repeat       = wpc_get_option('wpc_pro_sound_repeat');
        $sound_interval     = wpc_get_option('repeat_interval_time');


        if ( 'on' === $order_notification && 'on' === $sound_notification ) {
            wpc_update_option('enable_sound_notification', true);

            if ( $sound_file ) {
                $sound_file_url = wp_get_attachment_url($sound_file);

                wpc_update_option('custom_notification_sound', $sound_file_url);
            }

            if ( 'on' === $sound_repeat ) {
                wpc_update_option('repeated_sound_minute', intval($sound_interval));
            }
        }

    }

    /**
     * Migrate QR code
     *
     * @return  void
     */
    private function migrate_qr_code() {
        $qrcode_ids  = wpc_get_option('wpc_pro_qrcode_id');
        $qrcode_urls = wpc_get_option('wpc_pro_qrcode_data');

        if ( empty( $qrcode_ids ) || empty( $qrcode_urls ) ) {
            return;
        }

        $qrcode_data = array_combine($qrcode_ids, $qrcode_urls);

        if ( ! empty( $qrcode_data ) ) {
            foreach ( $qrcode_data as $qrcode_id => $qrcode_url ) {
                Qrcode_Model::create([
                    'table_name' => $qrcode_id,
                    'table_id' => $qrcode_id,
                    'page_url' => $qrcode_url,
                ]);
            }
        }
    }

    /**
     * Migrate general settings
     *
     * @return  void
     */
    private function migrate_general_settings() {
        $primary_color     = wpc_get_option('wpc_primary_color');
        $secondary_color   = wpc_get_option('wpc_secondary_color');
        $calendar_language = wpc_get_option('reserv_form_local');

        if ( $primary_color ) {
            wpc_update_option('primary_color', $primary_color);
        }

        if ( $secondary_color ) {
            wpc_update_option('secondary_color', $secondary_color);
        }

        if ( $calendar_language ) {
            wpc_update_option('calendar_language', $calendar_language);
        }
    }

    /**
     * Migrate reservation button settings
     *
     * @return  void
     */
    private function migrate_reservation_button_settings() {
        $form_button_text         = wpc_get_option('first_booking_button');
        $confirmation_button_text = wpc_get_option('form_booking_button');
        $cancellation_button_text = wpc_get_option('form_cancell_button');

        if ( $form_button_text ) {
            wpc_update_option('reservation_form_button_text', $form_button_text);
        }

        if ( $confirmation_button_text ) {
            wpc_update_option('reservation_confirmation_button_text', $confirmation_button_text);
        }

        if ( $cancellation_button_text ) {
            wpc_update_option('reservation_cancellation_button_text', $cancellation_button_text);
        }
    }

    /**
     * Migrate WC layout
     *
     * @return  void
     */
    public function migrate_wc_layout() {
        $variation_layout = wpc_get_option('wpc_pro_woocommerce_variation_layout');
        $product_layout   = wpc_get_option('wpc_pro_woocommerce_override_css');

        if ( 'on' === $variation_layout ) {
            wpc_update_option('variation_layout', true);
        }

        if ( 'on' === $product_layout ) {
            wpc_update_option('product_layout', true);
        }
    }

    /**
     * Migrate location settings
     *
     * @return  void
     */
    public function migrate_location_settings() {
        $is_location_required = wpc_get_option('wpcafe_food_location');

        if ( 'on' === $is_location_required ) {
            wpc_update_option('require_location', true);
            wpc_update_option('display_location_selector', 'all_pages');
        }
    }

    /**
     * Migrate integration value
     *
     * @return  void
     */
    public function migrate_integration_value() {
        $google_maps_api_key = wpc_get_option('google_api_key');
        $pabbly_web_hook = wpc_get_option('pabbly_web_hooks');
        $zapier_web_hook = wpc_get_option('zapier_web_hooks');

        if ( $google_maps_api_key ) {
            wpc_update_option('google_map_api_key', $google_maps_api_key);
        }

        if ( $pabbly_web_hook ) {
            wpc_update_option('pabbly_webhook_url', $pabbly_web_hook);
        }

        if ( $zapier_web_hook ) {
            wpc_update_option('zapier_webhook_url', $zapier_web_hook);
        }
    }

    /**
     * Migrate reservation settings
     *
     * @return  void
     */
    public function migrate_reservation_settings() {
        $total_seat_capacity = wpc_get_option('rest_max_reservation');
        $minimum_guest       = wpc_get_option('wpc_min_guest_no');
        $maximum_guest       = wpc_get_option('wpc_max_guest_no');

        $reservation_close_state = wpc_get_option('rest_reservation_off');
        $reservation_pending_message  = wpc_get_option('wpc_pending_message');
        $reservation_confirmed_message  = wpc_get_option('wpc_booking_confirmed_message');
        $reservation_business_hour_label  = wpc_get_option('business_hour_label');
        $reservation_slot_interval = wpc_get_option('reserv_time_interval');
        $late_booking_type = wpc_get_option('wpc_late_booking_type');
        $late_booking_value = wpc_get_option('wpc_late_booking_value');

        if ( $total_seat_capacity ) {
            wpc_update_option('reservation_total_seat_capacity', intval($total_seat_capacity));
        }

        if ( $minimum_guest ) {
            wpc_update_option('reservation_minimum_guest', intval($minimum_guest));
        }

        if ( $maximum_guest ) {
            wpc_update_option('reservation_maximum_guest', intval($maximum_guest));
        }

        if ( $reservation_close_state ) {
            wpc_update_option('reservation_close_state', $reservation_close_state);
        }

        if ( $reservation_pending_message ) {
            wpc_update_option('enable_reservation_pending_message', true);
            wpc_update_option('reservation_pending_message', $reservation_pending_message);
        }

        if ( $reservation_confirmed_message ) {
            wpc_update_option('enable_reservation_confirmed_message', true);
            wpc_update_option('reservation_confirmed_message', $reservation_confirmed_message);
        }

        if ( $reservation_business_hour_label ) {   
            wpc_update_option('reservation_business_hour_label', $reservation_business_hour_label);
        }

        if ( $reservation_slot_interval ) {
            wpc_update_option('reservation_slot_interval', intval($reservation_slot_interval));
        }

        if ( $late_booking_type && $late_booking_value ) {
            wpc_update_option('reservation_advanced', [
                'value' => intval($late_booking_value),
                'unit'  => $late_booking_type
            ]);
        }
    }
}
