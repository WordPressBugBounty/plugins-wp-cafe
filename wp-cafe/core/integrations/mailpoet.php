<?php
namespace WpCafe\Integrations;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Contracts\Switchable_Service_Contract;

/**
 * MailPoet integration.
 *
 * Unlike the webhook integrations (Fluent CRM, Pabbly), MailPoet is a local
 * plugin with an in-process PHP API, so we call it directly instead of posting
 * to a URL. On a new reservation or WooCommerce order the customer is added to
 * the list(s) chosen in the integration config.
 *
 * @since 1.0.0
 */
class Mail_Poet implements Hookable_Service_Contract, Switchable_Service_Contract {

    /**
     * Register hooks.
     *
     * @return void
     */
    public function register() {
        add_action( 'wpcafe_after_reservation_create', [ $this, 'send_reservation_data' ] );
        add_action( 'woocommerce_checkout_order_processed', [ $this, 'send_woocommerce_order_data' ] );
    }

    /**
     * Subscribe the reservation customer to the selected MailPoet list(s).
     *
     * @param object $reservation Reservation model with name/email/phone.
     * @return void
     */
    public function send_reservation_data( $reservation ) {
        if ( ! $this->can_send() ) {
            return;
        }

        $email = ! empty( $reservation->email ) ? $reservation->email : '';
        if ( ! $email ) {
            return;
        }

        // Reservation stores a single name field, so split on the first space
        // to fill MailPoet's first/last name.
        $parts = preg_split( '/\s+/', trim( (string) ( $reservation->name ?? '' ) ), 2 );

        $this->subscribe( [
            'email'      => $email,
            'first_name' => $parts[0] ?? '',
            'last_name'  => $parts[1] ?? '',
        ] );
    }

    /**
     * Subscribe the order's billing customer to the selected MailPoet list(s).
     *
     * @param int $order_id WooCommerce order ID.
     * @return void
     */
    public function send_woocommerce_order_data( $order_id ) {
        if ( ! $this->can_send() ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $email = $order->get_billing_email();
        if ( ! $email ) {
            return;
        }

        $this->subscribe( [
            'email'      => $email,
            'first_name' => $order->get_billing_first_name(),
            'last_name'  => $order->get_billing_last_name(),
        ] );
    }

    /**
     * Whether we should attempt a send: integration on and MailPoet loaded.
     *
     * @return bool
     */
    private function can_send() {
        return $this->is_enable() && class_exists( '\MailPoet\API\API' );
    }

    /**
     * Configured list IDs, normalized to a non-empty array of ints.
     *
     * @return int[]
     */
    private function get_list_ids() {
        $list_ids = wpc_get_option( 'mailpoet_list_ids' );
        return array_values( array_filter( array_map( 'intval', (array) $list_ids ) ) );
    }

    /**
     * Add or re-subscribe a subscriber to the configured lists.
     *
     * addSubscriber() throws if the email already exists, so we fall back to
     * looking it up and subscribing the existing record (duplicate-safe). We
     * never set a status — MailPoet decides it from its own opt-in setting.
     *
     * @param array{email:string,first_name:string,last_name:string} $subscriber
     * @return void
     */
    private function subscribe( array $subscriber ) {
        $list_ids = $this->get_list_ids();
        if ( empty( $list_ids ) ) {
            return;
        }

        try {
            $mp = \MailPoet\API\API::MP( 'v1' );

            try {
                $mp->addSubscriber( $subscriber, $list_ids, [ 'skip_subscriber_notification' => true ] );
            } catch ( \Exception $e ) {
                // Email already exists — subscribe the existing record instead.
                $existing = $mp->getSubscriber( $subscriber['email'] );
                if ( ! empty( $existing['id'] ) ) {
                    $mp->subscribeToLists( $existing['id'], $list_ids );
                }
            }
        } catch ( \Throwable $e ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'WP Cafe MailPoet: ' . $e->getMessage() );
            }
        }
    }

    /**
     * Whether the MailPoet integration is enabled.
     *
     * @return bool
     */
    public function is_enable() {
        return wpc_is_integration_enable( 'mailpoet' );
    }
}
