<?php
namespace WpCafe\FoodOrder\LiveOrder;

defined('ABSPATH') || exit;

/**
 * Assets Manager
 * 
 * Manages CSS and other frontend assets for live order notifications.
 */
class Assets_Manager {
    /**
     * Constructor
     * 
     * Initializes the assets manager.
     */
    public function __construct() {
        add_action( 'admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action( 'admin_footer', [$this, 'inject_popup_markup']);
    }

    /**
     * Enqueue assets
     *
     * Enqueues the necessary assets for live order notifications.
     * Only loads assets in WordPress admin panel.
     *
     * @param string $hook The current admin page hook.
     *
     * @return void
     */
    public function enqueue_assets( $hook ) {
        wp_enqueue_script( 'wpc-live-order-notify', wpcafe()->assets_url . '/build/js/live-order-notify.js', ['jquery'], '1.0', true );

        wp_enqueue_style( 'wpc-live-order-notify', wpcafe()->assets_url . '/build/css/live-order.css', [], '1.0', 'all' );

        $order_page_url = admin_url( 'admin.php?page=wc-orders&wpcafe=true' );

        wp_localize_script('wpc-live-order-notify', 'wpcLiveOrder', [
            'text_notify'  => wpc_get_option('live_order_text_notify', 'on'),
            'sound_notify' => wpc_get_option('enable_sound_notification', false),
            'ajax_url'     => admin_url('admin-ajax.php'),
            'sound_url'    => wpc_get_option('custom_notification_sound', wpcafe()->assets_url . '/music/ding_dong.mp3'),
            'last_order_id' => wpc_get_last_order_id(),
            'nonce'         => wp_create_nonce('wpc_live_order_notify'),
            'admin_url'     => admin_url(),
            'sound_duration' => wpc_get_option('repeated_sound_minute', 1),
            'order_page_url' => $order_page_url,
        ]);
    }

    /**
     * Inject popup markup
     *
     * Injects the popup markup for live order notifications.
     *
     * @return void
     */
    public function inject_popup_markup() {
        $screen = get_current_screen();
        ?>
        <div class="wpc-live-notice-list"></div>
        <?php
    }
} 

