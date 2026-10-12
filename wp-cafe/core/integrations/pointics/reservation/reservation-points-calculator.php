<?php
namespace WpCafe\Integrations\Pointics;

if ( ! defined( 'ABSPATH' ) ) exit;

use Pointics\Integrations\Wpcafe\Wpcafe_Trigger_Provider;
use Pointics\Points\Earn_Pricer;
use Pointics\Points\Values\Earn_Event;
use WpCafe\Models\Reservation_Model;

/**
 * What a booking is worth under one "Book a table" rule.
 *
 * Pointics does the whole sum; this class only supplies the amount a rate rule
 * multiplies, which is the booking price plus its food.
 *
 * @package WpCafe/Integrations/Pointics
 */
class Reservation_Points_Calculator {

    /**
     * Price a booking under one rule.
     *
     * @param \Pointics\Channels\Values\Channel $channel Rule.
     * @param int                               $user_id Account, 0 for a guest preview.
     * @param array                             $input   Booking input.
     * @return \Pointics\Points\Values\Point_Calculation|null
     */
    public function price( $channel, int $user_id, array $input ) {
        return pointics_container()->make( Earn_Pricer::class )->price( $channel, $this->event( $channel, $user_id, $input ) );
    }

    /**
     * Booking rules that can pay today: active and inside their run window.
     *
     * @return \Pointics\Channels\Values\Channel[]
     */
    public static function open_rules() {
        $index = pointics_container()->make( \Pointics\Channels\Active_Channel_Index::class );

        // Older Pointics has no run windows, so every active rule is open.
        return method_exists( $index, 'open_for_trigger' )
            ? $index->open_for_trigger( Wpcafe_Trigger_Provider::RESERVATION )
            : $index->for_trigger( Wpcafe_Trigger_Provider::RESERVATION );
    }

    /**
     * Points one rule pays this booking.
     *
     * Roles and conditions decide only whether the booking qualifies at first.
     * Once it holds points, a later edit re-prices the amount alone: a "first
     * booking" condition would otherwise fail on the booking's own earn and
     * take the points back.
     *
     * @param \Pointics\Channels\Values\Channel $channel    Rule.
     * @param int                               $user_id    Account, 0 for a guest quote.
     * @param array                             $input      Booking input.
     * @param bool                              $qualifying Check roles and conditions (no points held yet).
     * @return int
     */
    public function points_for( $channel, int $user_id, array $input, bool $qualifying = true ) {
        if ( $qualifying && ! $this->qualifies( $channel, $user_id, $input ) ) {
            return 0;
        }

        $calc = $this->price( $channel, $user_id, $input );

        return $calc ? $calc->points() : 0;
    }

    /**
     * Every open rule's points for this booking, as quoted before it earns.
     *
     * @param int   $user_id Account, 0 for a guest quote.
     * @param array $input   Booking input.
     * @return int
     */
    public function total_for( int $user_id, array $input ) {
        $points = 0;

        foreach ( self::open_rules() as $channel ) {
            $points += $this->points_for( $channel, $user_id, $input );
        }

        return $points;
    }

    /**
     * The rule's role list and conditions let this booking earn.
     *
     * @param \Pointics\Channels\Values\Channel $channel Rule.
     * @param int                               $user_id Account, 0 for a guest quote.
     * @param array                             $input   Booking input.
     * @return bool
     */
    public function qualifies( $channel, int $user_id, array $input ) {
        $user  = $user_id > 0 ? get_userdata( $user_id ) : false;
        $roles = $user ? (array) $user->roles : [];

        if ( [] !== $channel->user_roles && ! array_intersect( $channel->user_roles, $roles ) ) {
            return false;
        }

        return pointics_container()->make( \Pointics\Channels\Conditions\Condition_Evaluator::class )
            ->evaluate( $channel->conditions, $this->event( $channel, $user_id, $input ), $channel );
    }

    /**
     * The earn event a booking prices and awards with.
     *
     * @param \Pointics\Channels\Values\Channel $channel Rule.
     * @param int                               $user_id Account.
     * @param array                             $input   Booking input.
     * @return Earn_Event
     */
    public function event( $channel, int $user_id, array $input ) {
        $user     = $user_id > 0 ? get_userdata( $user_id ) : false;
        $currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : (string) wpc_get_option( 'currency', '' );

        return new Earn_Event(
            $channel->trigger,
            $channel->integration,
            $user_id,
            self::amount( $input ),
            $currency,
            [
                'object_type' => Wpcafe_Trigger_Provider::RESERVATION_OBJECT,
                'object_id'   => (int) $input['reservation_id'],
                'currency'    => $currency,
                'user_roles'  => $user ? (array) $user->roles : [],
            ],
            '',
            time()
        );
    }

    /**
     * What a rate rule multiplies: booking price plus food, less any points
     * spent on it. The whole of what the customer pays.
     *
     * @param array $input Booking input.
     * @return float
     */
    public static function amount( array $input ) {
        return max( 0.0, (float) ( $input['booking_amount'] ?? 0 ) + (float) ( $input['food_amount'] ?? 0 ) - (float) ( $input['points_discount'] ?? 0 ) );
    }

    /**
     * Input for a saved booking.
     *
     * @param Reservation_Model $reservation Booking.
     * @return array
     */
    public static function input_for_reservation( $reservation ) {
        return [
            'reservation_id' => (int) $reservation->id,
            // The figure the booking was actually priced at, not today's setting.
            'booking_amount' => max( 0.0, (float) $reservation->total_price ),
            'food_amount'    => self::food_for_reservation( $reservation ),
            'points_discount' => (float) $reservation->points_discount,
        ];
    }

    /**
     * Input for the booking form preview. Prices the booking with the same
     * helper the controller saves with, so the quote and the award agree.
     *
     * @param int   $guests    Guests typed in the form.
     * @param int   $branch_id Location picked in the form, 0 for the site default.
     * @param float $discount  Points money off already applied in the form.
     * @return array
     */
    public static function input_for_cart( int $guests, int $branch_id = 0, float $discount = 0.0 ) {
        $config = wpc_get_reservation_booking_config( $branch_id > 0 ? $branch_id : null );
        $amount = (float) $config['amount'];

        return [
            'reservation_id' => 0,
            'booking_amount' => $config['multiply'] ? $amount * max( 1, $guests ) : $amount,
            'food_amount'    => self::food_in_cart(),
            'points_discount' => max( 0.0, $discount ),
        ];
    }

    /**
     * Whether the account already has another confirmed booking, matched by
     * author or by email. Bookings made before Pointics was installed count.
     *
     * @param int $user_id                Account; 0 is never returning.
     * @param int $exclude_reservation_id The booking being priced.
     * @return bool
     */
    public static function is_returning( int $user_id, int $exclude_reservation_id ) {
        $user = $user_id > 0 ? get_userdata( $user_id ) : false;

        if ( ! $user ) {
            return false;
        }

        $base = [
            'post_type'      => 'wpc_reservation',
            'post_status'    => 'confirmed',
            'posts_per_page' => 2,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ];

        $by_author = new \WP_Query( array_merge( $base, [ 'author' => $user_id ] ) );

        // Two matches suffice even when one is the booking being priced.
        if ( array_diff( array_map( 'intval', $by_author->posts ), [ $exclude_reservation_id ] ) ) {
            return true;
        }

        // Emails are stored as typed; compare case-insensitively.
        $by_email = new \WP_Query(
            array_merge(
                $base,
                [
                    // Fetch one extra match because the current booking is skipped below.
                    'posts_per_page' => 21,
                    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- legacy guest bookings store email in post meta; bounded lookup needed for first-booking eligibility.
                    'meta_query'     => [
                        [
                            'key'     => 'email',
                            'value'   => $user->user_email,
                            'compare' => 'LIKE',
                        ],
                    ],
                ]
            )
        );

        foreach ( $by_email->posts as $id ) {
            if ( (int) $id === $exclude_reservation_id ) {
                continue;
            }

            if ( 0 === strcasecmp( (string) get_post_meta( $id, 'email', true ), $user->user_email ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Food on a booking, from its order when it has one (refunds taken off),
     * else from the saved booking items.
     *
     * @param Reservation_Model $reservation Booking.
     * @return float
     */
    private static function food_for_reservation( $reservation ) {
        // Read into a local first: empty() on the model's magic property is always true.
        $order_id = (int) $reservation->woo_order_id;
        $order    = $order_id > 0 && function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : null;

        if ( $order ) {
            $booking_product = (int) wpc_get_option( 'woocommerce_generic_product_id', 0 );
            $amount          = 0.0;

            foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
                if ( (int) $item->get_product_id() === $booking_product ) {
                    continue;
                }

                $amount += (float) $item->get_total() - abs( (float) $order->get_total_refunded_for_item( $item_id ) );
            }

            return max( 0.0, $amount );
        }

        $amount = 0.0;

        foreach ( $reservation->get_items() as $item ) {
            $amount += (float) $item->price * (int) $item->quantity;
        }

        return max( 0.0, $amount );
    }

    /**
     * Food in the current cart, when the booking form shows a food menu.
     *
     * @return float
     */
    private static function food_in_cart() {
        if ( ! self::form_has_food_menu() || ! function_exists( 'WC' ) ) {
            return 0.0;
        }

        if ( function_exists( 'wc_load_cart' ) && null === WC()->cart ) {
            wc_load_cart();
        }

        if ( ! WC()->cart || WC()->cart->is_empty() ) {
            return 0.0;
        }

        WC()->cart->calculate_totals();

        $booking_product = (int) wpc_get_option( 'woocommerce_generic_product_id', 0 );
        $amount          = 0.0;

        foreach ( WC()->cart->get_cart() as $item ) {
            if ( (int) ( $item['product_id'] ?? 0 ) === $booking_product ) {
                continue;
            }

            $amount += (float) ( $item['line_subtotal'] ?? 0 );
        }

        return max( 0.0, $amount );
    }

    /**
     * Same check Reservation_Controller uses before it treats the cart as the booking's food.
     *
     * @return bool
     */
    private static function form_has_food_menu() {
        $steps = wpc_get_option( 'reservation_form_customization' );

        if ( ! is_array( $steps ) ) {
            return false;
        }

        foreach ( $steps as $step ) {
            foreach ( (array) ( $step['fields'] ?? [] ) as $field ) {
                if ( 'food_menu' === ( $field['type'] ?? '' ) && ! empty( $field['visible'] ) ) {
                    return true;
                }
            }
        }

        return false;
    }
}
