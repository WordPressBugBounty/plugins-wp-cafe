<?php
namespace WpCafe\LiveAlert;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Models\Reservation_Model;

defined( 'ABSPATH' ) || exit;

/**
 * Notifier
 *
 * Keeps a short list of placed orders and reservations and hands the new ones
 * to open admin pages over the Heartbeat API.
 */
class Notifier implements Hookable_Service_Contract {
    /**
     * Option holding the alert list.
     */
    const LOG_OPTION = 'wpc_live_alert_log';

    /**
     * How many entries the list keeps.
     */
    const LOG_SIZE = 20;

    /**
     * Heartbeat data key, both directions.
     */
    const HEARTBEAT_KEY = 'wpc_live_alert';

    /**
     * Register hooks.
     *
     * @return void
     */
    public function register() {
        if ( wpc_is_module_enable( 'food_ordering' ) && function_exists( 'WC' ) ) {
            add_action( 'woocommerce_new_order', [ $this, 'record_order' ], 20, 2 );
        }

        add_action( 'wpcafe_reservation_placed', [ $this, 'record_reservation' ] );
        add_filter( 'heartbeat_received', [ $this, 'heartbeat_received' ], 10, 2 );
    }

    /**
     * Add a placed order to the list. WooCommerce fires this only when an order
     * leaves draft, so opening the checkout page never counts.
     *
     * @param int   $order_id Order id.
     * @param mixed $order    Order object.
     * @return void
     */
    public function record_order( $order_id, $order = null ) {
        if ( ! self::orders_enabled() ) {
            return;
        }

        if ( ! $order instanceof \WC_Order ) {
            $order = wc_get_order( $order_id );
        }

        if ( ! $order instanceof \WC_Order || 'shop_order' !== $order->get_type() ) {
            return;
        }

        // A booking's checkout order is announced once, as the reservation.
        if ( self::reservations_enabled() && wpc_get_order_online_reservation( $order ) ) {
            return;
        }

        self::add_to_log( 'order', $order->get_id() );
    }

    /**
     * Add a placed reservation to the list.
     *
     * @param mixed $reservation Placed reservation.
     * @return void
     */
    public function record_reservation( $reservation ) {
        if ( ! self::reservations_enabled() || ! $reservation instanceof Reservation_Model ) {
            return;
        }

        self::add_to_log( 'reservation', (int) $reservation->id );
    }

    /**
     * Order alerts: food ordering is on and its toggle is not saved off.
     *
     * @return bool
     */
    public static function orders_enabled() {
        return wpc_is_module_enable( 'food_ordering' )
            && function_exists( 'WC' )
            && wpc_is_option_on( 'enable_order_notification' );
    }

    /**
     * Reservation alerts are switched on by Pro and follow their toggle.
     *
     * @return bool
     */
    public static function reservations_enabled() {
        return wpc_is_module_enable( 'reservation' )
            && wpc_is_option_on( 'enable_reservation_notification' )
            && (bool) apply_filters( 'wpcafe_live_alert_reservations', false );
    }

    /**
     * Current alert list.
     *
     * @return array { seq: int, items: array }
     */
    public static function get_log() {
        $log = get_option( self::LOG_OPTION, [] );

        return [
            'seq'   => absint( $log['seq'] ?? 0 ),
            'items' => ( isset( $log['items'] ) && is_array( $log['items'] ) ) ? $log['items'] : [],
        ];
    }

    /**
     * Append an entry, keeping the last LOG_SIZE.
     *
     * TODO: read-modify-write, two placements in the same few ms can drop
     * one entry. A table (approach C) removes the race.
     *
     * @param string $type Entry type: order or reservation.
     * @param int    $id   Object id.
     * @return void
     */
    public static function add_to_log( $type, $id ) {
        $id = absint( $id );

        if ( ! $id ) {
            return;
        }

        $log = self::get_log();

        foreach ( $log['items'] as $entry ) {
            if ( ( $entry['type'] ?? '' ) === $type && absint( $entry['id'] ?? 0 ) === $id ) {
                return;
            }
        }

        $log['seq']++;
        $log['items'][] = [
            'seq'  => $log['seq'],
            'type' => $type,
            'id'   => $id,
        ];
        $log['items'] = array_slice( $log['items'], -self::LOG_SIZE );

        update_option( self::LOG_OPTION, $log, false );
    }

    /**
     * Answer a heartbeat with entries newer than the page's seq.
     *
     * @param array $response Heartbeat response.
     * @param array $data     Heartbeat data from the page.
     * @return array
     */
    public function heartbeat_received( $response, $data ) {
        if ( empty( $data[ self::HEARTBEAT_KEY ] ) || ! is_array( $data[ self::HEARTBEAT_KEY ] ) ) {
            return $response;
        }

        if ( ! self::is_legacy_enabled() || ! ( self::can_view_orders() || self::can_view_reservations() ) ) {
            return $response;
        }

        $since   = absint( $data[ self::HEARTBEAT_KEY ]['seq'] ?? 0 );
        $log     = self::get_log();
        $items   = [];
        // A type switched off after it was queued is dropped too.
        $enabled = [
            'order'       => self::orders_enabled(),
            'reservation' => self::reservations_enabled(),
        ];

        foreach ( $log['items'] as $entry ) {
            if ( absint( $entry['seq'] ?? 0 ) <= $since || empty( $enabled[ (string) ( $entry['type'] ?? '' ) ] ) ) {
                continue;
            }

            $item = $this->build_item( $entry );

            if ( $item ) {
                $items[] = $item;
            }
        }

        $response[ self::HEARTBEAT_KEY ] = [
            'seq'   => $log['seq'],
            'items' => $items,
            'sound' => Assets_Manager::sound_config(),
        ];

        return $response;
    }

    /**
     * Turn a list entry into box text the current user may see.
     *
     * @param array $entry List entry.
     * @return array|null { key, title, message, url }
     */
    protected function build_item( array $entry ) {
        $type = (string) ( $entry['type'] ?? '' );
        $id   = absint( $entry['id'] ?? 0 );
        $item = null;

        if ( 'order' === $type && self::can_view_orders() && function_exists( 'wc_get_order' ) ) {
            $order = wc_get_order( $id );

            if ( $order instanceof \WC_Order ) {
                $item = [
                    'title'   => __( 'New Order Placed!', 'wp-cafe' ),
                    'message' => sprintf(
                        /* translators: 1: order number, 2: order status label. */
                        __( 'Order #%1$s · %2$s', 'wp-cafe' ),
                        $order->get_order_number(),
                        wc_get_order_status_name( $order->get_status() )
                    ),
                    'url'     => add_query_arg( 'wpcafe', 'true', html_entity_decode( (string) $order->get_edit_order_url(), ENT_QUOTES, 'UTF-8' ) ),
                ];
            }
        } elseif ( 'reservation' === $type && self::can_view_reservations() && 'wpc_reservation' === get_post_type( $id ) ) {
            $item = [
                'title'   => __( 'New Reservation Placed!', 'wp-cafe' ),
                /* translators: %s: reservation id. */
                'message' => sprintf( __( 'Reservation #%s', 'wp-cafe' ), $id ),
                'url'     => admin_url( 'admin.php?page=wpcafe#/reservations' ),
            ];
        }

        if ( ! $item ) {
            return null;
        }

        $item = (array) apply_filters( 'wpcafe_live_alert_item', $item, $entry );

        return [
            'key'     => sanitize_key( $type . '-' . $id ),
            'title'   => wp_strip_all_tags( (string) ( $item['title'] ?? '' ) ),
            'message' => wp_strip_all_tags( (string) ( $item['message'] ?? '' ) ),
            'url'     => esc_url_raw( (string) ( $item['url'] ?? '' ) ),
        ];
    }

    /**
     * Whether the alert box is enabled. Kept as a filter so an add-on can
     * switch it off.
     *
     * @return bool
     */
    public static function is_legacy_enabled() {
        return (bool) apply_filters( 'wpcafe_live_order_legacy_enabled', true );
    }

    /**
     * Whether the current user may view order data.
     *
     * @return bool
     */
    public static function can_view_orders() {
        return function_exists( 'wpc_current_user_can_view_orders' )
            && wpc_current_user_can_view_orders();
    }

    /**
     * Whether the current user may view reservations.
     *
     * @return bool
     */
    public static function can_view_reservations() {
        return function_exists( 'wpc_current_user_can_view_reservations' )
            && wpc_current_user_can_view_reservations();
    }
}
