<?php
namespace WpCafe\Integrations\Pointics;

if ( ! defined( 'ABSPATH' ) ) exit;

use Pointics\Channels\Values\Trigger_Behavior;
use Pointics\Integrations\Wpcafe\Wpcafe_Trigger_Provider;
use WpCafe\Contracts\Hookable_Service_Contract;

/**
 * Supplies the referral card's award to the card Pointics declares.
 *
 * Pointics declares every WP Cafe card, so the "WP Cafe" tab shows even when
 * WP Cafe is not installed. The booking card is paid by our own reconciler; the
 * referral card's award reads WP Cafe's reservation state, so its resolver and
 * reversal resolver are attached here through the trigger-behavior mechanism.
 *
 * @package WpCafe/Integrations/Pointics
 */
class Pointics_Cards implements Hookable_Service_Contract {
    /**
     * Register hooks. The bridge runs on plugins_loaded, before Pointics fires
     * this action on init priority 1, so the listener is always in time.
     *
     * @return void
     */
    public function register() {
        add_action( 'pointics_register_trigger_providers', [ $this, 'attach_referral_behavior' ] );
    }

    /**
     * Attach the referral card's runtime. Harmless when the referrals feature
     * is off: nothing fires the referral hook, so the bound resolver is idle.
     *
     * @param \Pointics\Integrations\Trigger_Registry $registry Registry.
     * @return void
     */
    public function attach_referral_behavior( $registry ) {
        $registry->attach_behavior(
            Wpcafe_Trigger_Provider::REFERRAL,
            new Trigger_Behavior( $this->resolver(), $this->reversal_resolver() )
        );
    }

    /**
     * Pay the inviter when the referred friend's booking completes.
     *
     * @return \Closure fn(array $args): ?array
     */
    private function resolver() {
        return static function ( array $args ) {
            $referrer_id    = (int) ( $args[0] ?? 0 );
            $reservation_id = Reservation_Referral::current_reservation_id();

            if ( $referrer_id <= 0 || $reservation_id <= 0 ) {
                return null;
            }

            return [
                'user_id' => $referrer_id,
                'amount'  => (float) ( $args[3] ?? 0 ),
                'context' => [
                    'object_type' => Wpcafe_Trigger_Provider::RESERVATION_OBJECT,
                    'object_id'   => $reservation_id,
                    'referral_id' => (int) ( $args[2] ?? 0 ),
                    'referee_id'  => (int) ( $args[1] ?? 0 ),
                ],
            ];
        };
    }

    /**
     * Take the inviter's points back when the booking is cancelled.
     *
     * @return \Closure fn(array $args): ?array
     */
    private function reversal_resolver() {
        return static function ( array $args ) {
            $reservation_id = (int) ( $args[0] ?? 0 );
            $referrer_id    = (int) get_post_meta( $reservation_id, Reservation_Referral::META_REFERRER, true );

            if ( $reservation_id <= 0 || $referrer_id <= 0 ) {
                return null;
            }

            return [
                'user_id'     => $referrer_id,
                'object_id'   => $reservation_id,
                'object_type' => Wpcafe_Trigger_Provider::RESERVATION_OBJECT,
                'ratio'       => 1.0,
                'reversal_id' => 'cancelled',
            ];
        };
    }
}
