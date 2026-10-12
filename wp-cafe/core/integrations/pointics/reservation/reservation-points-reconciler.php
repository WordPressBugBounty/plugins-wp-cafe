<?php
namespace WpCafe\Integrations\Pointics;

if ( ! defined( 'ABSPATH' ) ) exit;

use Pointics\Channels\Channel_Service;
use Pointics\Channels\Frequency\Frequency_Checker;
use Pointics\Database\Repositories\Ledger_Repository;
use Pointics\Integrations\Wpcafe\Wpcafe_Trigger_Provider;
use Pointics\Points\Expiration\Expiry_Calculator;
use Pointics\Points\Ledger_Service;
use Pointics\Points\Program;
use Pointics\Support\Config;
use WpCafe\Models\Reservation_Model;

/**
 * Makes the Pointics ledger match what a booking is worth right now.
 *
 * Pointics' dispatcher allows one award per object forever, which cannot follow
 * "cancel, then confirm again" or "guests 8 to 4". This runs on every booking
 * change and writes only the difference.
 *
 * @package WpCafe/Integrations/Pointics
 */
class Reservation_Points_Reconciler {
    const META_USERS = '_wpc_pointics_user_ids';
    const META_SEQ   = '_wpc_pointics_seq';
    /** Rule ids whose frequency limit refused this booking; see first_award(). */
    const META_CAPPED = '_wpc_pointics_capped';

    /**
     * Bring one booking's points up to date.
     *
     * @param int    $reservation_id Reservation id.
     * @param string $reason         Why it ran; stored on corrections.
     * @return void
     */
    public function reconcile( int $reservation_id, string $reason ) {
        if ( ! $this->make( Program::class )->enabled() || 'wpc_reservation' !== get_post_type( $reservation_id ) ) {
            return;
        }

        $reservation = Reservation_Model::find( $reservation_id );

        if ( ! $reservation ) {
            return;
        }

        $status = (string) get_post_status( $reservation_id );
        $effect = self::status_effect( $status );

        $user_id = ( new Reservation_Account_Resolver() )->resolve( $reservation );
        $earns   = $this->earns_by_channel( $reservation_id, $user_id );
        $active  = [];

        // A rule outside its run window is treated like one switched off: it keeps what it paid.
        foreach ( Reservation_Points_Calculator::open_rules() as $channel ) {
            $active[ $channel->id ] = $channel;
        }

        $input = Reservation_Points_Calculator::input_for_reservation( $reservation );

        foreach ( array_unique( array_merge( array_keys( $active ), array_keys( $earns ) ) ) as $channel_id ) {
            $held = $earns[ $channel_id ] ?? [];

            foreach ( $held as $holder_id => $holder_earns ) {
                if ( $holder_id !== $user_id ) {
                    $this->take_back( $reservation_id, $channel_id, $holder_id, $holder_earns, $this->net( $holder_earns ), $reason );
                }
            }

            // A status in neither list leaves what the booking holds alone.
            if ( $user_id <= 0 || 'hold' === $effect ) {
                continue;
            }

            $mine    = $held[ $user_id ] ?? [];
            $current = $this->net( $mine );

            if ( 'revoke' === $effect ) {
                $target = 0;
            } else {
                $target = isset( $active[ $channel_id ] )
                    ? ( new Reservation_Points_Calculator() )->points_for( $active[ $channel_id ], $user_id, $input, [] === $mine )
                    // Rule switched off or deleted: never claw back an earning booking.
                    : $current;
            }

            $delta = $target - $current;

            if ( $delta > 0 && [] === $mine ) {
                $this->first_award( $active[ $channel_id ], $reservation_id, $user_id, $input );
            } elseif ( $delta > 0 ) {
                $this->credit( $active[ $channel_id ], $reservation_id, $user_id, $delta, $input, $reason );
            } elseif ( $delta < 0 ) {
                $this->take_back( $reservation_id, $channel_id, $user_id, $mine, -$delta, $reason );
            }
        }
    }

    /**
     * Take back everything the booking card still holds, for a booking being deleted.
     * Referral earns on the booking belong to the inviter and are left to the referral code.
     *
     * @param int    $reservation_id Reservation id.
     * @param string $reason         Why.
     * @return void
     */
    public function take_back_all( int $reservation_id, string $reason ) {
        foreach ( $this->earns_by_channel( $reservation_id, 0 ) as $channel_id => $by_user ) {
            foreach ( $by_user as $holder_id => $earns ) {
                $earns = array_values( array_filter( $earns, static fn( $earn ) => Wpcafe_Trigger_Provider::RESERVATION === $earn->trigger ) );
                $this->take_back( $reservation_id, $channel_id, $holder_id, $earns, $this->net( $earns ), $reason );
            }
        }
    }

    /**
     * Earned and net points from the booking card only, for every account that
     * held them. Referral earns on the same booking belong to the inviter and
     * are left out.
     *
     * @param int $reservation_id Reservation id.
     * @return array{earned: int, net: int}
     */
    public function booking_totals( int $reservation_id ) {
        $earned = 0;
        $net    = 0;

        foreach ( $this->earns_by_channel( $reservation_id, 0 ) as $by_user ) {
            foreach ( $by_user as $earns ) {
                foreach ( $earns as $earn ) {
                    if ( Wpcafe_Trigger_Provider::RESERVATION !== $earn->trigger ) {
                        continue;
                    }

                    $earned += (int) $earn->points;
                    $net    += $this->make( Ledger_Repository::class )->net_points_for_earn( $earn->id );
                }
            }
        }

        return [
            'earned' => $earned,
            'net'    => $net,
        ];
    }

    /**
     * What a booking status does to its points, from Pointics' WP Cafe settings.
     *
     * A status in both lists revokes. A Pointics without these settings keeps
     * the old rule: only Confirmed earns.
     *
     * @param string $status Booking status.
     * @return string earn, revoke, or hold (leave what it holds).
     */
    public static function status_effect( string $status ): string {
        $config = pointics_container()->make( Config::class );
        $earn   = $config->get( 'wpcafe_earn_reservation_statuses' );
        $revoke = $config->get( 'wpcafe_revoke_reservation_statuses' );

        if ( ! is_array( $earn ) || ! is_array( $revoke ) ) {
            return 'confirmed' === $status ? 'earn' : 'revoke';
        }

        if ( in_array( $status, $revoke, true ) ) {
            return 'revoke';
        }

        return in_array( $status, $earn, true ) ? 'earn' : 'hold';
    }

    /**
     * First points for this user and rule: through Pointics, so its hooks and email run.
     *
     * @param \Pointics\Channels\Values\Channel $channel        Rule.
     * @param int                               $reservation_id Reservation id.
     * @param int                               $user_id        Account.
     * @param array                             $input          Booking input.
     * @return void
     */
    private function first_award( $channel, $reservation_id, $user_id, array $input ) {
        $capped = array_map( 'intval', (array) get_post_meta( $reservation_id, self::META_CAPPED, true ) );

        // Refused once means refused for good: a later run falls in a new period and would pay it.
        if ( in_array( (int) $channel->id, $capped, true ) ) {
            return;
        }

        $key = $this->make( Frequency_Checker::class )->can_award( $channel, $user_id, Wpcafe_Trigger_Provider::RESERVATION . ':obj:' . $reservation_id );

        if ( null === $key ) {
            $capped[] = (int) $channel->id;
            update_post_meta( $reservation_id, self::META_CAPPED, array_values( array_filter( $capped ) ) );
            return;
        }

        $this->remember_holder( $reservation_id, $user_id );

        $event = ( new Reservation_Points_Calculator() )->event( $channel, $user_id, $input );
        $entry = $this->make( Channel_Service::class )->award_channel( $channel, $user_id, $key, Reservation_Points_Calculator::amount( $input ), $event->context );

        if ( $entry ) {
            $this->bump_seq( $reservation_id );
        }
    }

    /**
     * Add points to a booking that already earned under this rule.
     *
     * @param \Pointics\Channels\Values\Channel $channel        Rule.
     * @param int                               $reservation_id Reservation id.
     * @param int                               $user_id        Account.
     * @param int                               $delta          Points to add.
     * @param array                             $input          Booking input.
     * @param string                            $reason         Why.
     * @return void
     */
    private function credit( $channel, $reservation_id, $user_id, $delta, array $input, $reason ) {
        $entry = $this->make( Ledger_Service::class )->credit(
            $user_id,
            $delta,
            'earn',
            Wpcafe_Trigger_Provider::ID,
            Wpcafe_Trigger_Provider::RESERVATION,
            $this->expiry( $channel ),
            $channel->name,
            [
                'channel_id' => $channel->id,
                'reason'     => $reason,
                'input'      => $input,
            ],
            $this->key( $reservation_id, $channel->id, $user_id, '+' . $delta ),
            true,
            Wpcafe_Trigger_Provider::RESERVATION_OBJECT,
            $reservation_id
        );

        if ( $entry ) {
            $this->bump_seq( $reservation_id );
        }
    }

    /**
     * Take points back, newest earn first, each capped at what that earn still holds.
     *
     * @param int    $reservation_id Reservation id.
     * @param int    $channel_id     Rule id.
     * @param int    $user_id        Account.
     * @param array  $earns          That user's earns for this booking and rule, oldest first.
     * @param int    $amount         Points to take back.
     * @param string $reason         Why.
     * @return void
     */
    private function take_back( $reservation_id, $channel_id, $user_id, array $earns, $amount, $reason ) {
        if ( $amount <= 0 ) {
            return;
        }

        $base_key = $this->key( $reservation_id, $channel_id, $user_id, '-' . $amount );
        $ledger   = $this->make( Ledger_Service::class );
        $wrote    = false;

        foreach ( array_reverse( $earns ) as $earn ) {
            $take = min( $amount, $this->make( Ledger_Repository::class )->net_points_for_earn( $earn->id ) );

            if ( $take <= 0 ) {
                continue;
            }

            $entry = $ledger->reverse(
                $user_id,
                $take,
                Wpcafe_Trigger_Provider::ID,
                Wpcafe_Trigger_Provider::RESERVATION,
                $earn->id,
                (string) $earn->description,
                [
                    'channel_id'     => $channel_id,
                    'reservation_id' => $reservation_id,
                    'reason'         => $reason,
                ],
                $base_key . ':earn:' . $earn->id
            );

            if ( ! $entry ) {
                break; // Nothing left to take: the customer already spent it.
            }

            $wrote   = true;
            $amount -= abs( (int) $entry->points );

            if ( $amount <= 0 ) {
                break;
            }
        }

        if ( $wrote ) {
            $this->bump_seq( $reservation_id );
        }
    }

    /**
     * Earns for this booking, by rule id then user id.
     *
     * @param int $reservation_id Reservation id.
     * @param int $user_id        Current account.
     * @return array<int, array<int, \Pointics\Points\Values\Ledger_Entry[]>>
     */
    private function earns_by_channel( $reservation_id, $user_id ) {
        $holders = array_map( 'intval', (array) get_post_meta( $reservation_id, self::META_USERS, true ) );

        if ( $user_id > 0 ) {
            $holders[] = $user_id;
        }

        $grouped = [];

        foreach ( array_unique( array_filter( $holders ) ) as $holder_id ) {
            $earns = $this->make( Ledger_Repository::class )->find_completed_earns_for_object(
                $holder_id,
                Wpcafe_Trigger_Provider::ID,
                $reservation_id,
                Wpcafe_Trigger_Provider::RESERVATION_OBJECT
            );

            foreach ( $earns as $earn ) {
                $grouped[ (int) ( $earn->meta['channel_id'] ?? 0 ) ][ $holder_id ][] = $earn;
            }
        }

        return $grouped;
    }

    /**
     * Net points a list of earns still holds.
     *
     * @param array $earns Earns.
     * @return int
     */
    private function net( array $earns ) {
        $net = 0;

        foreach ( $earns as $earn ) {
            $net += $this->make( Ledger_Repository::class )->net_points_for_earn( $earn->id );
        }

        return $net;
    }

    /**
     * Same expiry rule as Channel_Service::award_channel().
     *
     * @param \Pointics\Channels\Values\Channel $channel Rule.
     * @return string|null
     */
    private function expiry( $channel ) {
        if ( null === $channel->expiry_days ) {
            return $this->make( Expiry_Calculator::class )->for_now();
        }

        return $channel->expiry_days <= 0 ? null : gmdate( 'Y-m-d H:i:s', time() + $channel->expiry_days * DAY_IN_SECONDS );
    }

    /**
     * @param int    $reservation_id Reservation id.
     * @param int    $channel_id     Rule id.
     * @param int    $user_id        Account.
     * @param string $signed_delta   "+50" or "-50".
     * @return string
     */
    private function key( $reservation_id, $channel_id, $user_id, $signed_delta ) {
        return sprintf(
            'wpcafe:reservation:%d:channel:%d:user:%d:seq:%d:%s',
            $reservation_id,
            $channel_id,
            $user_id,
            (int) get_post_meta( $reservation_id, self::META_SEQ, true ),
            $signed_delta
        );
    }

    /**
     * @param int $reservation_id Reservation id.
     * @return void
     */
    private function bump_seq( $reservation_id ) {
        update_post_meta( $reservation_id, self::META_SEQ, (int) get_post_meta( $reservation_id, self::META_SEQ, true ) + 1 );
    }

    /**
     * Remember every account that ever held points for this booking.
     *
     * @param int $reservation_id Reservation id.
     * @param int $user_id        Account.
     * @return void
     */
    private function remember_holder( $reservation_id, $user_id ) {
        $holders = array_map( 'intval', (array) get_post_meta( $reservation_id, self::META_USERS, true ) );

        if ( ! in_array( $user_id, $holders, true ) ) {
            $holders[] = $user_id;
            update_post_meta( $reservation_id, self::META_USERS, array_values( array_filter( $holders ) ) );
        }
    }

    /**
     * @param string $class Pointics class.
     * @return object
     */
    private function make( $class ) {
        return pointics_container()->make( $class );
    }
}
