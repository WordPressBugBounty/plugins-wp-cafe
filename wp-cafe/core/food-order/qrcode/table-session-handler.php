<?php
namespace WpCafe\FoodOrder\Qrcode;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Session;

/**
 * Table Session Handler Class
 *
 * Handles capturing and storing table ID from URL parameters
 * and saving it to reservations and orders
 */
class Table_Session_Handler implements Hookable_Service_Contract {

    /**
     * Initialize the class by hooking into WordPress actions.
     */
    public function register() {
        add_action( 'wp_loaded', [ $this, 'capture_table_id_from_url' ] );
        add_action( 'woocommerce_checkout_create_order', [ $this, 'save_table_id_to_order' ], 10, 2 );

        // Display table ID column in admin orders list
        add_filter( 'manage_woocommerce_page_wc-orders_columns', [ $this, 'add_table_id_column' ] );
        add_action( 'manage_woocommerce_page_wc-orders_custom_column', [ $this, 'display_table_id_column' ], 10, 2 );
        add_action( 'manage_shop_order_posts_custom_column', [ $this, 'display_table_id_column' ], 10, 2 );

        // Display table ID on customer-facing thank you page
        add_action( 'woocommerce_order_details_before_order_table', [ $this, 'display_table_id_on_thankyou' ], 10, 1 );

        // Display table ID on admin order details page
        add_action( 'woocommerce_admin_order_data_after_billing_address', [ $this, 'display_table_id_on_admin_order' ], 10, 1 );

        add_action( 'woocommerce_email_order_meta', [ $this, 'add_table_name_in_wooocommerce_order_email' ], 10, 4 );
    }

    /**
     * Add table ID column to admin orders list
     *
     * @param   array  $columns  Existing columns
     *
     * @return  array
     */
    public function add_table_id_column( $columns ) {
        $columns['tableId'] = esc_html__( 'Table Name', 'wp-cafe' );
        return $columns;
    }

    /**
     * Display table ID column data in admin orders list
     *
     * @param   string  $column     Column name
     * @param   int     $order_id   Order ID
     *
     * @return  void
     */
    public function display_table_id_column( $column, $order_id ) {
        if ( 'tableId' !== $column || ! function_exists( 'wc_get_order' ) ) {
            return;
        }

        $order = wc_get_order( $order_id );
        $table_id = $order->get_meta( 'wpc_table_id' );

        if ( $table_id ) {
            echo esc_html( $table_id );
        }
    }

    /**
     * Capture table ID from URL parameter and store in session
     *
     * @return void
     */
    public function capture_table_id_from_url() {
        // Check if wpc-table_id parameter exists in URL
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public QR code scan, no nonce by design
        if ( isset( $_GET['wpc-table_id'] ) && ! empty( $_GET['wpc-table_id'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public QR code scan, no nonce by design
            $table_id = sanitize_text_field( wp_unslash( $_GET['wpc-table_id'] ) );
        }

        if ( isset( $table_id ) ) {
            Session::set( 'wpc_table_id', $table_id );
        }
    }

    /**
     * Save table ID from session to WooCommerce order meta
     *
     * @param   Object  $order  WC Order Object
     * @param   array   $data   Order data
     *
     * @return  void
     */
    public function save_table_id_to_order( $order, $data ) {
        $table_id = wpc_get_table_id_from_session();

        if ( ! empty( $table_id ) ) {
            $order->update_meta_data( 'wpc_table_id', $table_id );
        }
    }

    /**
     * Display table ID on customer-facing thank you page
     *
     * @param   Object  $order  WC Order Object
     *
     * @return  void
     */
    public function display_table_id_on_thankyou( $order ) {
        if ( ! function_exists( 'wc_get_order' ) ) {
            return;
        }

        $table_id = $order->get_meta( 'wpc_table_id' );

        if ( empty( $table_id ) ) {
            return;
        }

        ?>
        <div class="wpc-order-table-id">
            <p>
                <strong><?php echo esc_html__( 'Table Number', 'wp-cafe' ); ?>:</strong>
                <?php echo esc_html( $table_id ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Display table ID on admin order details page
     *
     * @param   Object  $order  WC Order Object
     *
     * @return  void
     */
    public function display_table_id_on_admin_order( $order ) {
        if ( ! function_exists( 'wc_get_order' ) ) {
            return;
        }

        $table_id = $order->get_meta( 'wpc_table_id' );

        if ( empty( $table_id ) ) {
            return;
        }

        ?>
        <p>
            <strong><?php echo esc_html__( 'Table Number', 'wp-cafe' ); ?>:</strong>
            <?php echo esc_html( $table_id ); ?>
        </p>
        <?php
    }

    /**
     * Handle pickup email for wc order information
     *
     * @param   Order Object  $order          [$order description]
     * @param   string  $sent_to_admin  [$sent_to_admin description]
     * @param   string  $plain_text     [$plain_text description]
     * @param   string  $email          [$email description]
     *
     * @return  void
     */
    public function add_table_name_in_wooocommerce_order_email( $order, $sent_to_admin, $plain_text, $email ) {
        $wpc_table_id = $order->get_meta('wpc_table_id');
        
        if ( $wpc_table_id ) {
            echo '<p><strong>Table name:</strong> ' . esc_html($wpc_table_id) . '</p>';
        }
    }
}
