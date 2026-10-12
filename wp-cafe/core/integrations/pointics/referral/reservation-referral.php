<?php
namespace WpCafe\Integrations\Pointics;

if ( ! defined( 'ABSPATH' ) ) exit;

use Pointics\Integrations\Wpcafe\Wpcafe_Trigger_Provider;
use Pointics\Referrals\Referral_Service;
use Pointics\Referrals\Referral_Trigger_Provider;
use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\CustomerAccounts\Booking_Account;
use WpCafe\Models\Reservation_Model;

/**
 * Pays an inviter when the friend they referred makes a first booking in an
 * earning status (the same statuses that pay the booking's own points).
 *
 * Only a friend who proved the account is theirs pays: signed in when they
 * booked, or the booking made the account and its owner has set a password.
 *
 * @package WpCafe/Integrations/Pointics
 */
class Reservation_Referral implements Hookable_Service_Contract {
    const META_REFERRER = '_wpc_pointics_referrer_id';

    /** Free, Pro and visual-table booking forms. */
    const BOOKING_SHORTCODES = [ 'wpc_reservation_form', 'wpc_reservation_form_pro', 'wpc_visual_reservation_form' ];

    /** Elementor widgets that render one of them (the Cafetics twin shares the free name). */
    const BOOKING_WIDGETS = [ 'wpc-reservation-form', 'wpc-pro-reservation-form', 'wpc-pro-visual-table-form' ];
    const META_PAID     = '_wpc_pointics_referral_paid';
    /** The friend a paid booking paid for, so "once per friend" is one lookup. */
    const META_FRIEND   = '_wpc_pointics_referral_friend';
    /** The account signed in when the booking was submitted. */
    const META_BOOKED_BY = '_wpc_pointics_booked_by';
    /** The account this booking created (Customer Accounts). */
    const META_MADE_ACCOUNT = '_wpc_pointics_made_account';

    /**
     * Booking being completed right now; read by the card's resolver.
     *
     * @var int
     */
    private static $current = 0;

    /**
     * @return void
     */
    public function register() {
        add_action( 'wpcafe_reservation_request_saved', [ $this, 'save_referrer' ], 10, 2 );
        // Fires before Customer Accounts can sign the visitor in (wpcafe_after_reservation_create).
        add_action( 'wpcafe_reservation_inserted', [ $this, 'save_booked_by' ] );
        add_action( 'wpcafe_customer_account_created', [ $this, 'save_made_account' ], 10, 2 );
        add_action( 'wpcafe_customer_account_verified', [ $this, 'on_account_verified' ], 10, 2 );
        add_filter( 'pointics_referral_award_triggers', [ $this, 'add_referral_trigger' ] );
        add_filter( 'pointics_referral_redirect_to_registration', [ $this, 'keep_on_booking_page' ], 10, 2 );

        // After the reconciler (10); a guest's account (Customer Accounts, priority 1) exists by then.
        foreach ( [ 'wpcafe_after_reservation_create', 'wpcafe_after_reservation_status_changed', 'wpcafe_after_reservation_cancelled', 'wpcafe_after_reservation_update' ] as $hook ) {
            add_action( $hook, [ $this, 'sync_model' ], 20 );
        }
        add_action( 'wpcafe_reservation_status_synced', [ $this, 'sync' ], 20 );
        add_action( 'before_delete_post', [ $this, 'on_deleted' ] );
    }

    /**
     * Deleting a paid booking takes the inviter's reward back, like a cancel.
     *
     * @param int $post_id Post being deleted.
     * @return void
     */
    public function on_deleted( $post_id ) {
        if ( 'wpc_reservation' === get_post_type( $post_id ) ) {
            $this->revoke( (int) $post_id );
        }
    }

    /**
     * @return int
     */
    public static function current_reservation_id(): int {
        return self::$current;
    }

    /**
     * Store who referred this visitor, from Pointics' attribution cookie.
     *
     * @param Reservation_Model $reservation Booking.
     * @param \WP_REST_Request  $request     Create request.
     * @return void
     */
    public function save_referrer( $reservation, $request ) {
        $referrer_id = (int) apply_filters(
            'wpcafe_pointics_attributed_referrer',
            (int) pointics_container()->make( Referral_Service::class )->attributed_referrer_id()
        );

        if ( $referrer_id <= 0 || get_current_user_id() === $referrer_id ) {
            return;
        }

        update_post_meta( $reservation->id, self::META_REFERRER, $referrer_id );
    }

    /**
     * Who was signed in when the booking was submitted, before any auto sign-in.
     *
     * @param Reservation_Model $reservation Booking.
     * @return void
     */
    public function save_booked_by( $reservation ) {
        $user_id = get_current_user_id();

        if ( $user_id > 0 ) {
            update_post_meta( $reservation->id, self::META_BOOKED_BY, $user_id );
        }
    }

    /**
     * @param int               $user_id     Account the booking made.
     * @param Reservation_Model $reservation Booking.
     * @return void
     */
    public function save_made_account( $user_id, $reservation ) {
        update_post_meta( $reservation->id, self::META_MADE_ACCOUNT, (int) $user_id );
    }

    /**
     * The owner of an account a booking made set a password: that booking may pay now.
     *
     * @param int $user_id        Account.
     * @param int $reservation_id Booking that made it.
     * @return void
     */
    public function on_account_verified( $user_id, $reservation_id ) {
        $this->sync( (int) $reservation_id );
    }

    /**
     * Whether the friend proved this booking is theirs: signed in when booking,
     * booked for them by staff, or the booking made their account. Either way the
     * account must have a password set: an auto sign-in proves nothing about who
     * typed the email.
     *
     * @param int $reservation_id Booking.
     * @param int $friend_id      Friend account.
     * @return bool
     */
    private function booked_by_friend( int $reservation_id, int $friend_id ): bool {
        $booked_by = (int) get_post_meta( $reservation_id, self::META_BOOKED_BY, true );

        // Staff booking a phone call for a customer vouch for who that customer is.
        $theirs = $booked_by === $friend_id
            || ( $booked_by > 0 && Booking_Account::is_staff( get_userdata( $booked_by ) ) )
            || (int) get_post_meta( $reservation_id, self::META_MADE_ACCOUNT, true ) === $friend_id;

        return $theirs && ! Booking_Account::is_unverified( $friend_id );
    }

    /**
     * @param array $triggers Referral award triggers.
     * @return array
     */
    public function add_referral_trigger( $triggers ) {
        $triggers[] = Wpcafe_Trigger_Provider::REFERRAL;

        return $triggers;
    }

    /**
     * A referral link that lands on a booking form stays there.
     *
     * @param bool $redirect    Pointics' choice.
     * @param int  $referrer_id Inviter.
     * @return bool
     */
    public function keep_on_booking_page( $redirect, $referrer_id ) {
        $post = get_queried_object();

        if ( $post instanceof \WP_Post && self::has_booking_form( $post ) ) {
            return false;
        }

        return (bool) $redirect;
    }

    /**
     * Whether a page shows a booking form: a shortcode in its content, or a
     * form widget in a page builder's own meta (Elementor, Bricks, Oxygen, Breakdance).
     *
     * @param \WP_Post $post Page.
     * @return bool
     */
    public static function has_booking_form( $post ) {
        foreach ( self::BOOKING_SHORTCODES as $tag ) {
            if ( has_shortcode( $post->post_content, $tag ) ) {
                return true;
            }
        }

        // Builders keep the layout out of post_content: Elementor saves widget
        // names, the others save the shortcode text.
        $needles = array_merge(
            array_map( static fn( $tag ) => '[' . $tag, self::BOOKING_SHORTCODES ),
            array_map( static fn( $widget ) => '"' . $widget . '"', self::BOOKING_WIDGETS )
        );

        foreach ( [ '_elementor_data', '_bricks_page_content_2', '_ct_builder_json', '_ct_builder_shortcodes', 'ct_builder_shortcodes', 'breakdance_data' ] as $meta_key ) {
            $data = get_post_meta( $post->ID, $meta_key, true );

            if ( ! is_string( $data ) || '' === $data ) {
                continue;
            }

            foreach ( $needles as $needle ) {
                if ( false !== strpos( $data, $needle ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param Reservation_Model $reservation Booking.
     * @return void
     */
    public function sync_model( $reservation ) {
        // Not empty(): the model's magic properties always read as unset there.
        $reservation_id = is_object( $reservation ) ? (int) $reservation->id : 0;

        if ( $reservation_id > 0 ) {
            $this->sync( $reservation_id );
        }
    }

    /**
     * Pay or reverse the inviter for one booking. Follows the same status
     * settings as the booking's own points.
     *
     * @param int $reservation_id Reservation id.
     * @return void
     */
    public function sync( $reservation_id ) {
        $reservation_id = (int) $reservation_id;

        if ( 'wpc_reservation' !== get_post_type( $reservation_id ) ) {
            return;
        }

        $referrer_id = (int) get_post_meta( $reservation_id, self::META_REFERRER, true );
        $paid        = 'yes' === get_post_meta( $reservation_id, self::META_PAID, true );
        $effect      = Reservation_Points_Reconciler::status_effect( (string) get_post_status( $reservation_id ) );

        if ( 'earn' !== $effect ) {
            if ( 'revoke' === $effect ) {
                $this->revoke( $reservation_id );
            }
            return;
        }

        if ( $paid ) {
            return;
        }

        $friend_id = ( new Reservation_Account_Resolver() )->resolve( Reservation_Model::find( $reservation_id ) );

        // A guest typing someone's email must not hand that customer to an inviter.
        if ( $friend_id <= 0 || ! $this->booked_by_friend( $reservation_id, $friend_id ) ) {
            return;
        }

        // Signing up through the link uses up the cookie, so a friend who joined
        // first and books later is found through their completed referral.
        if ( $referrer_id <= 0 ) {
            $referrer_id = $this->signup_referrer( $friend_id );
        }

        if ( $referrer_id <= 0 || ! $this->can_pay( $referrer_id, $friend_id, $reservation_id ) ) {
            return;
        }

        // The cancel take-back reads the inviter from here.
        update_post_meta( $reservation_id, self::META_REFERRER, $referrer_id );

        self::$current = $reservation_id;

        try {
            pointics_container()->make( Referral_Service::class )->complete_for_hook( Wpcafe_Trigger_Provider::REFERRAL_HOOK, $friend_id, 0.0, $referrer_id );
        } finally {
            self::$current = 0;
        }

        // Mark paid only when the inviter actually holds referral points on this
        // booking now (a bound rule paid), so a program with no referral rule can
        // still pay a later booking once the admin adds one.
        if ( $this->inviter_holds_points( $referrer_id, $reservation_id ) ) {
            update_post_meta( $reservation_id, self::META_FRIEND, $friend_id );
            update_post_meta( $reservation_id, self::META_PAID, 'yes' );
            delete_transient( self::pending_key( $friend_id ) );
        }
    }

    /**
     * Take the inviter's reward for this booking back, if they hold one.
     *
     * @param int $reservation_id Booking.
     * @return void
     */
    private function revoke( int $reservation_id ) {
        $referrer_id = (int) get_post_meta( $reservation_id, self::META_REFERRER, true );
        $paid        = 'yes' === get_post_meta( $reservation_id, self::META_PAID, true );

        // The ledger decides, not only the flag: an inviter can hold points with no flag written.
        if ( $referrer_id > 0 && ( $paid || $this->inviter_holds_points( $referrer_id, $reservation_id ) ) ) {
            do_action( Wpcafe_Trigger_Provider::REFERRAL_CANCEL_HOOK, $reservation_id );
            delete_post_meta( $reservation_id, self::META_PAID );
        }
    }

    /**
     * The inviter a booking by this visitor would pay now, or 0. Read by the
     * booking form, so a friend only sees a bonus their booking can earn.
     *
     * @param int $user_id Signed-in visitor, 0 for a guest.
     * @return int
     */
    public function pending_referrer( int $user_id ): int {
        $referrer_id = (int) apply_filters(
            'wpcafe_pointics_attributed_referrer',
            (int) pointics_container()->make( Referral_Service::class )->attributed_referrer_id()
        );

        if ( $user_id <= 0 ) {
            return $referrer_id;
        }

        // The form asks on every guest or location change; the answer holds for a minute.
        $key    = self::pending_key( $user_id );
        $cached = get_transient( $key );

        if ( is_array( $cached ) && ( $cached['cookie'] ?? null ) === $referrer_id ) {
            return (int) $cached['referrer'];
        }

        $cookie = $referrer_id;

        if ( $referrer_id <= 0 ) {
            $referrer_id = $this->signup_referrer( $user_id );
        }

        $pending = $referrer_id > 0 && $this->can_pay( $referrer_id, $user_id, 0 ) ? $referrer_id : 0;

        set_transient( $key, [ 'cookie' => $cookie, 'referrer' => $pending ], MINUTE_IN_SECONDS );

        return $pending;
    }

    /**
     * @param int $user_id Friend account.
     * @return string
     */
    private static function pending_key( int $user_id ): string {
        return 'wpc_pts_pending_ref_' . $user_id;
    }

    /**
     * @param int $referrer_id    Inviter.
     * @param int $friend_id      Friend account.
     * @param int $reservation_id This booking, 0 before it exists.
     * @return bool
     */
    private function can_pay( $referrer_id, $friend_id, $reservation_id ) {
        return $friend_id !== $referrer_id
            && ! $this->friend_already_paid( $reservation_id, $referrer_id, $friend_id )
            && ! $this->already_customer( $friend_id, $reservation_id );
    }

    /**
     * Who referred this account at signup, 0 if nobody (or an older Pointics).
     *
     * @param int $friend_id Friend account.
     * @return int
     */
    private function signup_referrer( $friend_id ) {
        $service = pointics_container()->make( Referral_Service::class );

        return method_exists( $service, 'referrer_of' ) ? (int) $service->referrer_of( (int) $friend_id ) : 0;
    }

    /**
     * An earlier confirmed booking or a paid WooCommerce order means the
     * friend did not come because of the invite.
     *
     * @param int $friend_id      Friend account.
     * @param int $reservation_id This booking.
     * @return bool
     */
    private function already_customer( $friend_id, $reservation_id ) {
        if ( Reservation_Points_Calculator::is_returning( $friend_id, $reservation_id ) ) {
            return true;
        }

        return function_exists( 'wc_get_customer_order_count' ) && wc_get_customer_order_count( $friend_id ) > 0
            && ! $this->only_booking_orders( $friend_id );
    }

    /**
     * Orders created for bookings do not make someone a customer before the booking.
     *
     * @param int $friend_id Friend account.
     * @return bool
     */
    private function only_booking_orders( $friend_id ) {
        foreach ( wc_get_orders( [ 'customer_id' => $friend_id, 'limit' => 20, 'status' => [ 'wc-processing', 'wc-completed' ] ] ) as $order ) {
            if ( '' === (string) $order->get_meta( 'reservation_id' ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether another booking by this friend already paid this inviter. One
     * reward per friend: other friends of the same inviter still pay.
     *
     * @param int $reservation_id This booking, 0 before it exists.
     * @param int $referrer_id    Inviter.
     * @param int $friend_id      Friend account.
     * @return bool
     */
    private function friend_already_paid( $reservation_id, $referrer_id, $friend_id ) {
        $query = new \WP_Query(
            [
                'post_type'      => 'wpc_reservation',
                'post_status'    => 'any',
                'posts_per_page' => 2,
                'fields'         => 'ids',
                'no_found_rows'  => true,
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- three keys, only on an earning status.
                'meta_query'     => [
                    [ 'key' => self::META_REFERRER, 'value' => $referrer_id ],
                    [ 'key' => self::META_FRIEND, 'value' => $friend_id ],
                    [ 'key' => self::META_PAID, 'value' => 'yes' ],
                ],
            ]
        );

        // Include at most this booking and one other paid referral.
        return ! empty( array_diff( array_map( 'intval', $query->posts ), [ (int) $reservation_id ] ) );
    }

    /**
     * @param int $referrer_id    Inviter.
     * @param int $reservation_id Booking.
     * @return bool
     */
    private function inviter_holds_points( $referrer_id, $reservation_id ) {
        // An earn's source is the rule's stored integration, and older rules store
        // 'wpcafe' instead of 'referrals': check both. The trigger match keeps it precise.
        $ledger = pointics_container()->make( \Pointics\Database\Repositories\Ledger_Repository::class );

        foreach ( [ Referral_Trigger_Provider::PROVIDER, Wpcafe_Trigger_Provider::ID ] as $source ) {
            $earns = $ledger->find_completed_earns_for_object(
                $referrer_id,
                $source,
                $reservation_id,
                Wpcafe_Trigger_Provider::RESERVATION_OBJECT
            );

            foreach ( $earns as $earn ) {
                // Net, not the row: a reversed earn still exists but pays nothing.
                if ( Wpcafe_Trigger_Provider::REFERRAL === $earn->trigger && $ledger->net_points_for_earn( $earn->id ) > 0 ) {
                    return true;
                }
            }
        }

        return false;
    }
}
