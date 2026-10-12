<?php
namespace WpCafe\Integrations\Pointics;

if ( ! defined( 'ABSPATH' ) ) exit;

use Pointics\Database\Repositories\Ledger_Repository;
use Pointics\Database\Repositories\Redemption_Repository;
use Pointics\Integrations\Wpcafe\Wpcafe_Hold_Redeemer;
use Pointics\Points\Expiration\Expiry_Calculator;
use Pointics\Points\Ledger_Service;
use Pointics\Points\Redemption_Engine;
use Pointics\Points\Values\Redemption;
use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\CustomerAccounts\Booking_Account;
use WpCafe\Models\Reservation_Model;

/**
 * Points spent on a booking paid at the restaurant.
 *
 * Pointics' booking card holds the points for a WP Cafe tab reward and hands
 * its code to the booking form. This prices that code against the booking,
 * saves the discount, spends the points for good, and gives them back if the
 * booking is cancelled. No WooCommerce coupon is involved.
 *
 * @package WpCafe/Integrations/Pointics
 */
class Booking_Points_Discount implements Hookable_Service_Contract {
    const REQUEST_KEY     = 'pointics_coupon';
    const META_REDEMPTION = '_wpc_pointics_redemption_id';
    const META_POINTS     = '_wpc_pointics_points_used';
    const META_CODE       = '_wpc_pointics_coupon';
    const META_RETURNED   = '_wpc_pointics_points_returned';

    /**
     * The priced code waiting for the booking it was checked for to be saved.
     *
     * @var array|null
     */
    private static $pending = null;

    /**
     * @return void
     */
    public function register() {
        add_filter( 'wpcafe_reservation_before_save', [ $this, 'before_save' ], 10, 3 );
        // The first hook after the insert: nothing in the request can fail before the link is written.
        add_action( 'wpcafe_reservation_inserted', [ $this, 'after_save' ], 5 );
        add_action( 'wpcafe_reservation_create_failed', [ $this, 'on_create_failed' ] );
        add_action( 'wpcafe_after_reservation_cancelled', [ $this, 'on_model_change' ] );
        add_action( 'wpcafe_after_reservation_status_changed', [ $this, 'on_model_change' ] );
        add_action( 'wpcafe_reservation_status_synced', [ $this, 'on_status_synced' ] );
        // Before, not after: the booking's meta still names the redemption.
        add_action( 'before_delete_post', [ $this, 'on_deleted' ] );
        add_filter( 'pointics_user_can_redeem', [ $this, 'can_redeem' ], 10, 2 );
        add_filter( 'pointics_redeem_not_allowed_message', [ $this, 'not_allowed_message' ], 10, 2 );
    }

    /**
     * An account a booking made from a typed email spends nothing until its owner sets a password.
     *
     * @param bool $allowed Pointics' answer.
     * @param int  $user_id Customer.
     * @return bool
     */
    public function can_redeem( $allowed, $user_id ) {
        return (bool) $allowed && ! Booking_Account::is_unverified( (int) $user_id );
    }

    /**
     * @param string $message Pointics' message.
     * @param int    $user_id Customer.
     * @return string
     */
    public function not_allowed_message( $message, $user_id ) {
        return Booking_Account::is_unverified( (int) $user_id ) ? self::unverified_message() : $message;
    }

    /**
     * @return string
     */
    private static function unverified_message() {
        return __( 'Set your password from the email we sent you to use your points.', 'wp-cafe' );
    }

    /**
     * What a code takes off a total, or why it cannot be used.
     *
     * @param string $code    Coupon code.
     * @param int    $user_id Customer.
     * @param float  $total   Booking and food together.
     * @return array{discount: float, value: float, method: string, points: int, redemption_id: int, code: string}|\WP_Error
     */
    public function price( string $code, int $user_id, float $total ) {
        $invalid = self::invalid();

        if ( $user_id <= 0 || '' === $code || ! class_exists( Wpcafe_Hold_Redeemer::class ) ) {
            return $invalid;
        }

        if ( Booking_Account::is_unverified( $user_id ) ) {
            return new \WP_Error( 'pointics_redeem_not_allowed', self::unverified_message(), [ 'status' => 403 ] );
        }

        $row        = pointics_container()->make( Redemption_Repository::class )->find_by_handle( $code, Wpcafe_Hold_Redeemer::INTEGRATION );
        $redemption = $row ? Redemption::from_row( $row ) : null;
        $details    = $redemption ? pointics_container()->make( Wpcafe_Hold_Redeemer::class )->details( $redemption ) : null;

        // Held for this customer, not yet spent, money off, and not lapsed.
        if ( ! $details || $redemption->user_id !== $user_id
            || ! in_array( $redemption->status, Redemption::OPEN_STATUSES, true )
            || ( null !== $details['expires_ts'] && $details['expires_ts'] < time() )
            || $total < $details['min_spend'] ) {
            return $invalid;
        }

        $amount = $details['value'];

        /*
         * Points turned into money were picked for a bigger total (fewer guests or
         * less food since). Refuse rather than spend points the booking cannot use;
         * a fixed reward is capped below instead, like a WooCommerce coupon.
         */
        if ( 'points-conversion' === $details['method'] && $amount > $total ) {
            return new \WP_Error(
                'wpcafe_points_total_changed',
                __( 'Your booking total is now lower than this points code. Return the points and apply again.', 'wp-cafe' ),
                [ 'status' => 409 ]
            );
        }

        $discount = min( max( 0.0, 'percent' === $details['discount_type'] ? round( $total * $amount / 100, 2 ) : $amount ), $total );

        // Nothing to take off (a free booking): refuse rather than spend the points for nothing.
        if ( $discount <= 0 ) {
            return $invalid;
        }

        return [
            'discount'      => $discount,
            // What the reward is worth before the cap (a percent for a percentage reward).
            'value'         => (float) $amount,
            'method'        => (string) $details['method'],
            'points'        => (int) $row->points,
            'redemption_id' => $redemption->id,
            'code'          => $redemption->handle,
        ];
    }

    /**
     * @return \WP_Error
     */
    private static function invalid() {
        return new \WP_Error(
            'wpcafe_points_discount_invalid',
            __( 'Your points discount is no longer valid. Please review your summary.', 'wp-cafe' ),
            [ 'status' => 409 ]
        );
    }

    /**
     * Lower a booking paid at the restaurant by the points code it carries.
     *
     * @param array|\WP_Error  $data          Booking data, priced on the server.
     * @param \WP_REST_Request $request       Create request.
     * @param float            $food_subtotal Food in the cart for this booking.
     * @return array|\WP_Error
     */
    public function before_save( $data, $request, $food_subtotal ) {
        self::$pending = null;
        $code          = sanitize_text_field( (string) $this->code_from( $request ) );

        // Paying online, WooCommerce checkout takes its own rewards instead.
        if ( is_wp_error( $data ) || '' === $code || 'wc' === ( $data['payment_method'] ?? '' ) ) {
            return $data;
        }

        $priced = $this->price( $code, get_current_user_id(), (float) ( $data['total_price'] ?? 0 ) + (float) $food_subtotal );

        if ( is_wp_error( $priced ) ) {
            return $priced;
        }

        /*
         * Spend the points before the booking exists. A code that another booking
         * or a "Return points" click got to first then refuses this booking,
         * instead of saving it at full price after the customer saw the discount.
         */
        if ( ! pointics_container()->make( Redemption_Engine::class )->commit( $priced['redemption_id'] ) ) {
            return self::invalid();
        }

        $data['points_discount'] = $priced['discount'];
        self::$pending           = $priced;

        return $data;
    }

    /**
     * Tie the spent points to the booking, so a cancel can give them back.
     *
     * @param Reservation_Model $reservation Booking.
     * @return void
     */
    public function after_save( $reservation ) {
        $pending       = self::$pending;
        self::$pending = null;

        if ( ! $pending ) {
            return;
        }

        update_post_meta( $reservation->id, self::META_REDEMPTION, $pending['redemption_id'] );
        update_post_meta( $reservation->id, self::META_POINTS, $pending['points'] );
        update_post_meta( $reservation->id, self::META_CODE, $pending['code'] );
    }

    /**
     * The points were spent in before_save, so a booking that then fails to
     * save gives them straight back.
     *
     * @return void
     */
    public function on_create_failed() {
        $pending       = self::$pending;
        self::$pending = null;

        if ( ! $pending ) {
            return;
        }

        $row = pointics_container()->make( Redemption_Repository::class )->find( $pending['redemption_id'] );

        if ( $row ) {
            $this->credit_back( (int) $row->user_id, (int) $pending['points'], (int) $pending['redemption_id'], __( 'Points returned: booking not saved', 'wp-cafe' ), 'wpcafe:booking_discount_returned:unsaved:' . $pending['redemption_id'], null );
        }
    }

    /**
     * @param Reservation_Model $reservation Booking.
     * @return void
     */
    public function on_model_change( $reservation ) {
        if ( is_object( $reservation ) ) {
            $this->give_back_if_cancelled( (int) $reservation->id );
        }
    }

    /**
     * @param int $reservation_id Booking.
     * @return void
     */
    public function on_status_synced( $reservation_id ) {
        $this->give_back_if_cancelled( (int) $reservation_id );
    }

    /**
     * Deleting a booking is permanent, so the points spent on it come back.
     *
     * @param int $post_id Post being deleted.
     * @return void
     */
    public function on_deleted( $post_id ) {
        if ( 'wpc_reservation' === get_post_type( $post_id ) ) {
            $this->give_back( (int) $post_id, true );
        }
    }

    /**
     * A booking in a revoking status (cancelled, refunded, as Pointics' settings
     * say) returns the points spent on it, once, and drops the discount, so
     * confirming it again charges the full price.
     *
     * A customer's cancel after the table time returns nothing: the discount was
     * likely used at the table. Staff may still.
     *
     * @param int $reservation_id Booking.
     * @return void
     */
    public function give_back_if_cancelled( int $reservation_id ) {
        if ( 'revoke' !== Reservation_Points_Reconciler::status_effect( (string) get_post_status( $reservation_id ) ) ) {
            return;
        }

        $starts = self::starts_at( $reservation_id );

        if ( null !== $starts && time() >= $starts && ! Booking_Account::is_staff( wp_get_current_user() ) ) {
            return;
        }

        $this->give_back( $reservation_id, false );
    }

    /**
     * Credit back the points a booking spent, once.
     *
     * @param int  $reservation_id Booking.
     * @param bool $deleted        The booking is being deleted, so it is not updated.
     * @return void
     */
    private function give_back( int $reservation_id, bool $deleted ) {
        $redemption_id = (int) get_post_meta( $reservation_id, self::META_REDEMPTION, true );
        $points        = (int) get_post_meta( $reservation_id, self::META_POINTS, true );

        if ( $redemption_id <= 0 || $points <= 0 || get_post_meta( $reservation_id, self::META_RETURNED, true ) ) {
            return;
        }

        $row = pointics_container()->make( Redemption_Repository::class )->find( $redemption_id );

        if ( ! $row ) {
            return;
        }

        $reservation = Reservation_Model::find( $reservation_id );
        $invoice     = $reservation ? $reservation->invoice : $reservation_id;

        $this->credit_back(
            (int) $row->user_id,
            $points,
            $redemption_id,
            $deleted
                /* translators: %s: reservation invoice number. */
                ? sprintf( __( 'Points returned: booking #%s deleted', 'wp-cafe' ), $invoice )
                /* translators: %s: reservation invoice number. */
                : sprintf( __( 'Points returned: booking #%s cancelled', 'wp-cafe' ), $invoice ),
            'wpcafe:booking_discount_returned:' . $reservation_id . ':' . $redemption_id,
            $reservation_id
        );

        update_post_meta( $reservation_id, self::META_RETURNED, 1 );

        if ( $reservation && ! $deleted ) {
            $reservation->update( [ 'points_discount' => 0 ] );
        }
    }

    /**
     * When the table time starts, as a real timestamp; null without a date.
     *
     * A stored start time is the wall-clock time read as UTC (or an older "19:00"
     * string), so it is read back in the site's time zone. No time: end of the day.
     *
     * @param int $reservation_id Booking.
     * @return int|null
     */
    private static function starts_at( int $reservation_id ) {
        $date  = (string) get_post_meta( $reservation_id, 'date', true );
        $start = get_post_meta( $reservation_id, 'start_time', true );

        if ( '' === $date ) {
            return null;
        }

        // Ten digits or more is a timestamp; "0930" is still a time.
        if ( is_int( $start ) || ( is_string( $start ) && ctype_digit( $start ) && strlen( $start ) >= 10 ) ) {
            $wall = gmdate( 'Y-m-d H:i:s', (int) $start );
        } else {
            $wall = $date . ' ' . ( '' !== (string) $start ? (string) $start : '23:59:59' );
        }

        $moment = date_create_immutable( $wall, wp_timezone() );

        return $moment ? $moment->getTimestamp() : null;
    }

    /**
     * A committed redemption cannot be reversed, so spent points come back as a credit of their own.
     *
     * @param int      $user_id        Customer.
     * @param int      $points         Points spent.
     * @param int      $redemption_id  Redemption.
     * @param string   $description    Ledger line.
     * @param string   $dedupe_key     Keeps a retry from paying twice.
     * @param int|null $reservation_id Booking, null when none was saved.
     * @return void
     */
    private function credit_back( int $user_id, int $points, int $redemption_id, string $description, string $dedupe_key, ?int $reservation_id ) {
        pointics_container()->make( Ledger_Service::class )->credit(
            $user_id,
            $points,
            'adjust',
            'wpcafe',
            'wpcafe.booking_discount_returned',
            $this->original_expiry( $redemption_id ),
            $description,
            [ 'redemption_id' => $redemption_id ],
            $dedupe_key,
            // Spent points coming back are not new earnings, so no tier moves.
            false,
            null !== $reservation_id ? 'wpcafe_reservation' : null,
            $reservation_id
        );
    }

    /**
     * When the points a redemption spent were due to expire: the earliest of the
     * lots it used, so returning them cannot make old points new again.
     *
     * @param int $redemption_id Redemption.
     * @return string|null Null when they never expire.
     */
    private function original_expiry( int $redemption_id ) {
        $row    = pointics_container()->make( Redemption_Repository::class )->find( $redemption_id );
        $ledger = pointics_container()->make( Ledger_Repository::class );
        $spend  = $row ? $ledger->find( (int) $row->ledger_id ) : null;
        $lots   = $spend ? (array) ( $spend->meta['consumed'] ?? [] ) : [];

        if ( [] === $lots ) {
            return pointics_container()->make( Expiry_Calculator::class )->for_now();
        }

        $earliest = null;

        foreach ( $lots as $used ) {
            $lot = $ledger->find( (int) ( $used['id'] ?? 0 ) );

            if ( $lot && null !== $lot->expires_at && ( null === $earliest || $lot->expires_at < $earliest ) ) {
                $earliest = $lot->expires_at;
            }
        }

        return $earliest;
    }

    /**
     * The code from the create request. The form posts JSON, so a body read
     * backs up a param WordPress did not parse.
     *
     * @param \WP_REST_Request $request Create request.
     * @return mixed
     */
    private function code_from( $request ) {
        $param = $request->get_param( self::REQUEST_KEY );

        if ( null !== $param ) {
            return $param;
        }

        $body = json_decode( (string) $request->get_body(), true );

        return is_array( $body ) ? ( $body[ self::REQUEST_KEY ] ?? '' ) : '';
    }
}
