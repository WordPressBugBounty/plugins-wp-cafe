<?php
namespace WpCafe\Location;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Models\Location_Model;
use WpCafe\Session;

/**
 * Product Filter Class
 */
class Order_Filter implements Hookable_Service_Contract {
    /**
     * Initialize the class by hooking into WordPress actions.
     */
    public function register() {
        /**
         * Add Location filter dropdown to WooCommerce Orders list table
         */
        add_action( 'woocommerce_order_list_table_restrict_manage_orders', [ $this, 'display_order_filter' ] );

        /**
         * Modify the query to filter orders by location
         */
        add_filter( 'woocommerce_order_query_args', [ $this, 'update_query_to_filter_orders_by_location' ], 50 );


        // Add a new column header
        add_filter( 'manage_woocommerce_page_wc-orders_columns', [$this, 'show_order_type_column'] );

        add_action('manage_shop_order_posts_custom_column', [$this, 'show_order_type_data'], 10, 2);

        add_action( 'manage_woocommerce_page_wc-orders_custom_column', [$this, 'show_order_type_data'],20, 2 );
    }

    /**
     * Registers the necessary WordPress actions for the order filter.
     *
     * This method hooks the location filter dropdown into the WooCommerce Orders list table,
     * allowing users to filter orders by location in the admin interface.
     *
     * @return void
     */
    public function display_order_filter() { 
        $locations         = Location_Model::all();
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin list-table filter, capability-gated
        $location_selected = empty( $_GET['wpc_location'] ) ? false : sanitize_text_field( wp_unslash( $_GET['wpc_location'] ) );
        ?>
        <select name="wpc_location">
            <option value="">
                <?php echo esc_html__( 'All locations', 'wp-cafe' ); ?>
            </option>
            <?php foreach ( $locations as $location ) : ?>
                <?php
                    $value = $location->term_id;
                ?>
                <option
                    value="<?php echo esc_attr( $value ); ?>"
                    <?php selected( $location_selected, $value ); ?>
                >
                    <?php echo esc_html( $location->restaurant_name ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Update WP_Query object to filter orders by location.
     *
     * @param WP_Query $query The WP_Query instance (passed by reference).
     * @return void
     */
    public function update_query_to_filter_orders_by_location( $query_args ) {

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin list-table filter, capability-gated
        if ( isset( $_GET['wpc_location'] ) ) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- required for report/filter functionality
            $query_args['meta_key']   = 'wpc_location_id';
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- admin list-table filter, capability-gated; required for report/filter functionality
            $query_args['meta_value'] = absint( $_GET['wpc_location'] );
        }

        return $query_args;
    }

    /**
     * Adds the "Food Location" column to the orders list table in the admin.
     *
     * @param array $columns Existing columns in the orders list table.
     * @return array Modified columns including the "Food Location" column.
     */
    public function show_order_type_column( $columns ) {
        $columns['wpc_location'] = __( 'Food Location', 'wp-cafe' );

        return $columns;
    }

    /**
     * Adds the "Food Location" column to the orders list table in the admin.
     *
     * @param array $columns Existing columns in the orders list table.
     * @return array Modified columns including the "Food Location" column.
     */
    public function show_order_type_data( $column, $order_id ) {
        if ( 'wpc_location' !== $column ) {
            return;
        }
            
        $order = wc_get_order( $order_id );
        $location_id = $order->get_meta( 'wpc_location_id' );

        $location = Location_Model::find($location_id);
        ?>
            <?php if ( $location ): ?>
                <mark class="order-status order-status--delivery" style="background-color: #c5e2df; color: #356964;"><span><?php echo esc_html( $location->restaurant_name ); ?></span></mark>
            <?php else: ?>
                --
            <?php endif; ?>
        <?php
    }
}
