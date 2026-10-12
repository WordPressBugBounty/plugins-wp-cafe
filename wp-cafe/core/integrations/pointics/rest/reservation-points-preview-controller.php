<?php
namespace WpCafe\Integrations\Pointics;

if ( ! defined( 'ABSPATH' ) ) exit;

use Pointics\Earn_Preview\Earn_Preview_Service;
use Pointics\Integrations\Wpcafe\Wpcafe_Trigger_Provider;
use Pointics\Points\Balance_Service;
use Pointics\Points\Program;
use Pointics\Rewards\Reward_Service;
use Pointics\Storefront\Account_Url;
use WP_REST_Server;
use WpCafe\Abstract\Base_Rest_Controller;
use WpCafe\CustomerAccounts\Booking_Account;
use WpCafe\Models\Reservation_Model;

/**
 * "You'll earn 190 points" in the booking form.
 *
 * Public route (the form is public): nonce + per-IP rate limit, prices from the
 * server cart, never reads an email, so it cannot be used to look up accounts.
 *
 * @package WpCafe/Integrations/Pointics
 */
class Reservation_Points_Preview_Controller extends Base_Rest_Controller {
    const RATE_BUCKET = 'pointics_preview';
    const RATE_GROUP  = 'wpcafe_rate';

    /** @var string */
    protected $namespace = 'wpcafe/v2';

    /** @var string */
    protected $rest_base = 'pointics/reservation-preview';

    /**
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'preview' ],
                'permission_callback' => [ $this, 'preview_permissions_check' ],
                'args'                => [
                    'total_guest' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'minimum'           => 1,
                        'maximum'           => 500,
                        // Named explicitly: WordPress skips its schema check once a sanitize callback is set.
                        'validate_callback' => 'rest_validate_request_arg',
                        'sanitize_callback' => 'absint',
                    ],
                    // Optional: a multi-location site can price the booking per
                    // branch, so the quote has to know which one is picked.
                    'branch_id'   => [
                        'required'          => false,
                        'type'              => 'integer',
                        'default'           => 0,
                        'minimum'           => 0,
                        'validate_callback' => 'rest_validate_request_arg',
                        'sanitize_callback' => 'absint',
                    ],
                    // Points money off already applied in the form. It can only
                    // lower the quote, so a public caller gains nothing by it.
                    'discount'    => [
                        'required'          => false,
                        'type'              => 'number',
                        'default'           => 0,
                        'minimum'           => 0,
                        'validate_callback' => 'rest_validate_request_arg',
                    ],
                ],
            ]
        );
    }

    /**
     * Rate limit first, then nonce. Returns true or WP_Error only.
     *
     * @param \WP_REST_Request $request Request.
     * @return true|\WP_Error
     */
    public function preview_permissions_check( $request ) {
        $limit = (int) apply_filters( 'wpcafe_pointics_preview_rate_limit', 30 );

        if ( $this->hits() >= $limit ) {
            return new \WP_Error( 'rate_limited', __( 'Too many requests. Please try again later.', 'wp-cafe' ), [ 'status' => 429 ] );
        }

        if ( ! $this->verify_rest_nonce( $request ) ) {
            return new \WP_Error( 'wpcafe_invalid_nonce', __( 'Invalid security token.', 'wp-cafe' ), [ 'status' => 403 ] );
        }

        return true;
    }

    /**
     * @param \WP_REST_Request $request Request.
     * @return \WP_HTTP_Response
     */
    public function preview( $request ) {
        $this->record_hit();

        $user_id  = get_current_user_id();
        $referral = $this->referral( $user_id );
        $input    = Reservation_Points_Calculator::input_for_cart( (int) $request['total_guest'], (int) $request['branch_id'], (float) $request['discount'] );

        return $this->response(
            [
                'points'     => $this->points( $user_id, $input ),
                // Paying online charges the booking and its food as one order,
                // which the order rule prices on top. The form adds this in
                // once the customer picks WooCommerce.
                'order_points' => $this->order_points( $user_id, $input ),
                // A yes/no, not the status lists: which statuses pay is private.
                'earns_on_create' => 'earn' === Reservation_Points_Reconciler::status_effect( Reservation_Model::default_status() ),
                'signed_in'  => $user_id > 0,
                'balance'    => $user_id > 0 ? (int) $this->make( Balance_Service::class )->snapshot( $user_id )['available'] : null,
                'can_spend'  => $user_id > 0 && $this->can_spend( $user_id ),
                // A guest's booking makes them an account, so it earns like a member's.
                'account_coming' => 0 === $user_id && Booking_Account::enabled(),
                // Signed in by a booking but no password yet: a referral waits for one.
                'password_needed' => $user_id > 0 && Booking_Account::is_unverified( $user_id ),
                // The inviter this booking would pay, and the friend's own bonus from that rule.
                'referrer_name'  => $referral['name'],
                'referral_bonus' => $referral['bonus'],
                // Same account page the menu badges link to; guests only.
                'sign_in_url' => $user_id > 0 ? '' : esc_url_raw( (string) $this->make( Account_Url::class )->resolve() ),
            ]
        );
    }

    /**
     * The inviter a booking by this visitor would pay, and what the friend gets.
     * Empty when no referral rule is live or the booking would not count.
     *
     * @param int $user_id Account or 0.
     * @return array{name: string, bonus: int}
     */
    private function referral( $user_id ) {
        $none = [ 'name' => '', 'bonus' => 0 ];

        // Same gate as the bridge, which only listens for booking referrals when both hold.
        if ( ! method_exists( '\Pointics\Referrals\Referral_Service', 'complete_for_hook' )
            || ! $this->make( \Pointics\Support\Features::class )->enabled( 'referrals' ) ) {
            return $none;
        }

        $rules = $this->make( \Pointics\Channels\Active_Channel_Index::class )->open_for_trigger( Wpcafe_Trigger_Provider::REFERRAL );
        $user  = [] !== $rules ? get_userdata( ( new Reservation_Referral() )->pending_referrer( (int) $user_id ) ) : false;

        if ( ! $user ) {
            return $none;
        }

        $first = (string) get_user_meta( $user->ID, 'first_name', true );

        return [
            'name'  => '' !== $first ? $first : (string) $user->display_name,
            'bonus' => max( 0, (int) ( $rules[0]->meta['referred_points'] ?? 0 ) ),
        ];
    }

    /**
     * Sum over active booking rules this visitor could earn from.
     *
     * @param int   $user_id Account or 0.
     * @param array $input   Booking input.
     * @return int
     */
    private function points( $user_id, array $input ) {
        if ( ! $this->make( Program::class )->enabled() ) {
            return 0;
        }

        return ( new Reservation_Points_Calculator() )->total_for( (int) $user_id, $input );
    }

    /**
     * What the order rule would pay for the same booking, paid online.
     *
     * @param int   $user_id Account or 0.
     * @param array $input   Booking input.
     * @return int
     */
    private function order_points( $user_id, array $input ) {
        if ( ! $this->make( Program::class )->enabled() || ! class_exists( Earn_Preview_Service::class ) ) {
            return 0;
        }

        $quote = $this->make( Earn_Preview_Service::class )
            ->context( $user_id, 'woocommerce.order_paid' )
            ->quote(
                Reservation_Points_Calculator::amount( $input ),
                [ 'object_type' => 'order', 'object_id' => 0 ]
            );

        return (int) $quote->points;
    }

    /**
     * Whether the member can already take at least one reward.
     *
     * @param int $user_id Account.
     * @return bool
     */
    private function can_spend( $user_id ) {
        foreach ( $this->make( Reward_Service::class )->active_for( $user_id, [ 'per_page' => 100 ] )['items'] as $item ) {
            if ( ! empty( $item['affordable'] ) && ! empty( $item['eligible'] ) && ! empty( $item['redeemable'] ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Copied from Reservation_Controller::rate_limit_key() so this folder stays
     * self-contained; same IP-based key, own bucket.
     *
     * @return string
     */
    private function rate_key() {
        $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );

        return 'wpc_rate_' . self::RATE_BUCKET . '_' . md5( $ip );
    }

    /**
     * @return int
     */
    private function hits() {
        if ( wp_using_ext_object_cache() ) {
            $count = wp_cache_get( $this->rate_key(), self::RATE_GROUP );
            return false === $count ? 0 : (int) $count;
        }

        return (int) get_transient( $this->rate_key() );
    }

    /**
     * @return void
     */
    private function record_hit() {
        $key = $this->rate_key();

        if ( wp_using_ext_object_cache() ) {
            wp_cache_add( $key, 0, self::RATE_GROUP, MINUTE_IN_SECONDS );
            if ( false !== wp_cache_incr( $key, 1, self::RATE_GROUP ) ) {
                return;
            }
        }

        set_transient( $key, (int) get_transient( $key ) + 1, MINUTE_IN_SECONDS );
    }

    /**
     * @param string $class Pointics class.
     * @return object
     */
    private function make( $class ) {
        return pointics_container()->make( $class );
    }
}
