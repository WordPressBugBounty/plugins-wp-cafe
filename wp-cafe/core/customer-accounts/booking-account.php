<?php
namespace WpCafe\CustomerAccounts;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Models\Reservation_Model;

/**
 * Gives each booking customer a WordPress account, when the admin turns it on.
 *
 * A new email makes an account; a known one is reused. Either way the booking's
 * author becomes that customer, so "my bookings" and integrations find it.
 * A new account made from the public booking form can also be signed in.
 *
 * @package WpCafe/CustomerAccounts
 */
class Booking_Account implements Hookable_Service_Contract {
    const SETTING_CREATE     = 'reservation_create_customer_account';
    const SETTING_AUTO_LOGIN = 'reservation_customer_auto_login';

    /** User meta: the booking that made the account, until its owner sets a password or logs in with one. */
    const META_UNVERIFIED = '_wpcafe_unverified';

    /** Capabilities of people who book for others; they are never the customer. */
    const STAFF_CAPS = [ 'manage_options', 'manage_woocommerce', 'wpcafe_manage_reservations', 'wpcafe_view_all_reservations' ];

    /**
     * What this request did, by booking id: `created` or `existing`, and whether it signed in.
     * The response says only `linked`, or `created` when it signed in.
     *
     * @var array<int, array{status: string, signed_in: bool}>
     */
    private static $results = [];

    /**
     * Bookings that came through the create route from a signed-out browser.
     *
     * @var int[]
     */
    private static $from_form = [];

    /**
     * The logged-in cookie set during this request; see sign_in().
     *
     * @var string
     */
    private static $logged_in_cookie = '';

    /**
     * @return void
     */
    public function register() {
        add_action( 'wpcafe_reservation_request_saved', [ $this, 'mark_form_booking' ] );
        // Before the booking email and integrations, so they see the linked account.
        add_action( 'wpcafe_after_reservation_create', [ $this, 'link_account' ], 1 );
        add_filter( 'wpcafe_reservation_create_response', [ $this, 'add_to_response' ], 10, 2 );
        add_action( 'after_password_reset', [ $this, 'verify' ] );
        // WooCommerce before 10.9 skips after_password_reset on its own reset form.
        add_action( 'woocommerce_customer_reset_password', [ $this, 'verify' ] );
        // Only a password login fires wp_login; sign_in() below does not.
        add_action( 'wp_login', [ $this, 'verify_on_login' ], 10, 2 );
    }

    /**
     * The booking that made this account while its owner has not proved the
     * email is theirs, else 0. Until then "My bookings" shows only that booking.
     *
     * @param int $user_id Account.
     * @return int
     */
    public static function unverified_booking( int $user_id ): int {
        return $user_id > 0 ? (int) get_user_meta( $user_id, self::META_UNVERIFIED, true ) : 0;
    }

    /**
     * Whether a booking made this account and its owner has not set a password yet.
     *
     * @param int $user_id Account.
     * @return bool
     */
    public static function is_unverified( int $user_id ): bool {
        return self::unverified_booking( $user_id ) > 0;
    }

    /**
     * Setting a password proves the email belongs to this person.
     *
     * @param \WP_User $user Account.
     * @return void
     */
    public function verify( $user ) {
        $user_id        = $user instanceof \WP_User ? (int) $user->ID : 0;
        $reservation_id = self::unverified_booking( $user_id );

        if ( ! $reservation_id ) {
            return;
        }

        delete_user_meta( $user_id, self::META_UNVERIFIED );

        /**
         * A customer whose account a booking made has set a password, so the email is theirs.
         *
         * @param int $user_id        Account.
         * @param int $reservation_id Booking that made the account.
         */
        do_action( 'wpcafe_customer_account_verified', $user_id, $reservation_id );
    }

    /**
     * @param string   $user_login Login name.
     * @param \WP_User $user       Account.
     * @return void
     */
    public function verify_on_login( $user_login, $user ) {
        $this->verify( $user );
    }

    /**
     * Whether the admin turned accounts on.
     *
     * @return bool
     */
    public static function enabled() {
        return wpc_is_option_on( self::SETTING_CREATE, false );
    }

    /**
     * Only the create route fires this, so a booking seen here came from a
     * browser. Waitlist and other paths skip it and are never signed in.
     *
     * @param Reservation_Model $reservation Booking.
     * @return void
     */
    public function mark_form_booking( $reservation ) {
        if ( 0 === get_current_user_id() ) {
            self::$from_form[] = (int) $reservation->id;
        }
    }

    /**
     * Find or make the customer's account and make them the booking's author.
     *
     * @param Reservation_Model $reservation Booking.
     * @return void
     */
    public function link_account( $reservation ) {
        if ( ! self::enabled() || ! $reservation instanceof Reservation_Model ) {
            return;
        }

        $current = wp_get_current_user();

        // A signed-in customer booking for themselves is already the author.
        if ( $current->exists() && ! self::is_staff( $current ) ) {
            return;
        }

        $email = sanitize_email( (string) $reservation->email );

        if ( ! is_email( $email ) ) {
            return;
        }

        $existing = get_user_by( 'email', $email );

        /**
         * Whether a booking may create a WordPress account for a new email. Runs
         * even when "Anyone can register" is off in Settings > General.
         *
         * @param bool              $create      Default true.
         * @param string            $email       Customer email.
         * @param Reservation_Model $reservation Booking.
         */
        if ( ! $existing && ! apply_filters( 'wpcafe_create_customer_account', true, $email, $reservation ) ) {
            return;
        }

        $user_id = $existing ? (int) $existing->ID : $this->create_user( $email, (string) $reservation->name, (int) $reservation->id );

        if ( $user_id <= 0 ) {
            return;
        }

        $this->set_author( (int) $reservation->id, $user_id );

        $signed_in = false;

        if ( ! $existing ) {
            /**
             * A booking just made this customer's account.
             *
             * @param int               $user_id     New account.
             * @param Reservation_Model $reservation Booking.
             */
            do_action( 'wpcafe_customer_account_created', $user_id, $reservation );

            // Never into an account that already existed: anyone could type its email.
            $signed_in = $this->should_sign_in( (int) $reservation->id, $email ) && $this->sign_in( $user_id );
        }

        self::$results[ (int) $reservation->id ] = [
            'status'    => $existing ? 'existing' : 'created',
            'signed_in' => $signed_in,
        ];
    }

    /**
     * Tell the booking form what happened, and hand a signed-in browser the
     * REST nonce for its new session: the page's nonce belongs to a guest.
     *
     * @param array             $payload     Create response data.
     * @param Reservation_Model $reservation Booking.
     * @return array
     */
    public function add_to_response( $payload, $reservation ) {
        $result = self::$results[ (int) $reservation->id ] ?? null;

        /*
         * With auto login off, new and known emails get the same answer. With it on,
         * a signed-in answer shows the email had no account; a known one never signs in.
         */
        $linked = $result && ! $result['signed_in'];

        $payload['account'] = [
            'status'    => $result ? ( $linked ? 'linked' : 'created' ) : '',
            'signed_in' => $result ? $result['signed_in'] : false,
            'login_url' => $linked ? wp_login_url() : '',
        ];

        if ( $result && $result['signed_in'] ) {
            $payload['nonce'] = wp_create_nonce( 'wp_rest' );
        }

        return $payload;
    }

    /**
     * A customer account with a random password; the email carries the link to set one.
     *
     * Unverified until a password is set: anyone could have typed the email.
     *
     * @param string $email          Customer email.
     * @param string $name           Name on the booking.
     * @param int    $reservation_id Booking that makes it.
     * @return int Account id or 0.
     */
    private function create_user( string $email, string $name, int $reservation_id ) {
        $name    = sanitize_text_field( $name );
        $user_id = wp_insert_user(
            [
                'user_login'   => $this->username_for( $email ),
                'user_email'   => $email,
                'user_pass'    => wp_generate_password( 24 ),
                'first_name'   => $name,
                'display_name' => '' !== $name ? $name : $email,
                // Never the site's default role: an altered default must not hand out more rights.
                'role'         => get_role( 'customer' ) ? 'customer' : 'subscriber',
            ]
        );

        if ( is_wp_error( $user_id ) ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( '[WPCafe] Booking account not created: ' . $user_id->get_error_message() );
            return 0;
        }

        update_user_meta( (int) $user_id, self::META_UNVERIFIED, $reservation_id );

        return (int) $user_id;
    }

    /**
     * "rahim" from rahim@mail.com, then rahim1, rahim2 while taken.
     *
     * @param string $email Customer email.
     * @return string
     */
    private function username_for( string $email ) {
        $base = sanitize_user( current( explode( '@', $email ) ), true );
        $base = '' !== $base ? $base : 'customer';
        $name = $base;

        for ( $i = 1; username_exists( $name ); $i++ ) {
            $name = $base . $i;
        }

        return $name;
    }

    /**
     * Auto login is on, the booking came from a signed-out browser on the form,
     * and the email has no earlier bookings.
     *
     * @param int    $reservation_id Booking.
     * @param string $email          Customer email.
     * @return bool
     */
    private function should_sign_in( int $reservation_id, string $email ) {
        return wpc_is_option_on( self::SETTING_AUTO_LOGIN, false )
            && in_array( $reservation_id, self::$from_form, true )
            && 0 === get_current_user_id()
            // "My bookings" matches by email, so a session would show that email's
            // earlier guest bookings to whoever typed it. Those owners set a password first.
            && ! self::has_earlier_bookings( $email, $reservation_id );
    }

    /**
     * Whether this email booked before, as a guest, ahead of this booking.
     *
     * @param string $email          Customer email.
     * @param int    $reservation_id This booking.
     * @return bool
     */
    private static function has_earlier_bookings( string $email, int $reservation_id ) {
        $query = new \WP_Query(
            [
                'post_type'      => 'wpc_reservation',
                'post_status'    => 'any',
                'posts_per_page' => 2,
                'fields'         => 'ids',
                'no_found_rows'  => true,
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- one key, only when a new account would be signed in.
                'meta_query'     => [ [ 'key' => 'email', 'value' => $email ] ],
            ]
        );

        // Two matches are enough to find another booking without an SQL exclusion.
        return ! empty( array_diff( array_map( 'intval', $query->posts ), [ $reservation_id ] ) );
    }

    /**
     * Sign the new customer in for this and later requests.
     *
     * @param int $user_id New account.
     * @return bool
     */
    private function sign_in( int $user_id ) {
        /*
         * wp_create_nonce() reads the session token from the logged-in cookie,
         * which the browser only sends on the next request. Put it in $_COOKIE
         * now, or the nonce returned to the form is invalid.
         */
        $capture = static function ( $cookie ) {
            self::$logged_in_cookie = (string) $cookie;
        };
        add_action( 'set_logged_in_cookie', $capture );

        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id, false, is_ssl() );

        remove_action( 'set_logged_in_cookie', $capture );

        if ( '' === self::$logged_in_cookie ) {
            return false;
        }

        $_COOKIE[ LOGGED_IN_COOKIE ] = self::$logged_in_cookie;

        return true;
    }

    /**
     * Only the author column changes. wp_update_post() would re-fire the save
     * hooks for a booking created a moment ago, and warns because the booking
     * post type is not registered.
     *
     * @param int $reservation_id Booking.
     * @param int $user_id        Customer.
     * @return void
     */
    private function set_author( int $reservation_id, int $user_id ) {
        global $wpdb;

        $wpdb->update( $wpdb->posts, [ 'post_author' => $user_id ], [ 'ID' => $reservation_id ], [ '%d' ], [ '%d' ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        clean_post_cache( $reservation_id );
    }

    /**
     * Someone who books for other people, so never the booking's customer.
     *
     * @param \WP_User $user User.
     * @return bool
     */
    public static function is_staff( $user ) {
        foreach ( self::STAFF_CAPS as $cap ) {
            if ( user_can( $user, $cap ) ) {
                return true;
            }
        }

        return false;
    }
}
