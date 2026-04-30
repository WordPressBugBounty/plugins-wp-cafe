<?php
namespace WpCafe\Location;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Models\Location_Model;

/**
 * Product Filter Class
 */
class Product_Filter implements Hookable_Service_Contract {
    /**
     * Initialize the class by hooking into WordPress actions.
     */
    public function register() {

        /**
         * Add custom filter dropdown on WooCommerce Products admin list
         */
        add_action( 'restrict_manage_posts', [ $this, 'display_product_filter' ] );

        /**
         * Modify the query to filter products by location (custom taxonomy)
         */
        add_filter( 'parse_query', [ $this, 'get_product_filter' ] );

        /**
         * Filter shop page products by selected location
         */
        add_action( 'woocommerce_product_query', [ $this, 'filter_shop_by_location' ] );

    }

    /**
     * Display a dropdown filter for locations on the WooCommerce Products admin list.
     *
     * This function outputs a <select> element populated with all available locations,
     * allowing administrators to filter products by location in the admin area.
     *
     * @return void
     */
    public function display_product_filter() {  
        global $typenow;

        if ( $typenow !== 'product' ) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin list-table filter, capability-gated
        $selected = isset( $_GET['location'] ) ? absint( $_GET['location'] ) : 0;
        $locations = Location_Model::all();
        ?>
        <select name="location">
            <option value=""><?php esc_html_e( 'All Locations', 'wp-cafe' ); ?></option>
            <?php foreach ( $locations as $location ) { ?>
                <option value="<?php echo esc_attr( $location->term_id ); ?>" <?php selected( $selected, $location->term_id ); ?>><?php echo esc_html( $location->restaurant_name ); ?></option>
            <?php } ?>
        </select>
        <?php
    }

    /**
     * Filter WooCommerce products by location in the admin product list.
     *
     * This function modifies the main query on the products admin page to filter products
     * based on the selected location from the custom dropdown filter. It checks if the current
     * page is the product list in the admin area and if a location has been selected, then
     * applies a tax_query to filter products by the 'wpcafe_location' taxonomy.
     *
     * @param WP_Query $query The current WP_Query instance (passed by reference).
     * @return void
     */
    public function get_product_filter( $query ) {
        global $pagenow;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin list-table filter, capability-gated
        if ( is_admin() && $pagenow === 'edit.php' && ! empty( $_GET['location'] ) ) {
            if ( isset( $query->query_vars['post_type'] ) && $query->query_vars['post_type'] === 'product' ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin list-table filter, capability-gated
                $location = absint( $_GET['location'] );

                $query->set( 'tax_query', array(
                    array(
                        'taxonomy' => 'wpcafe_location',
                        'field'    => 'id',
                        'terms'    => $location,
                    ),
                ));
            }
        }
    }

    /**
     * Filter WooCommerce shop page products by selected location.
     *
     * @param WP_Query $q The current WP_Query instance (passed by reference).
     * @return void
     */
    public function filter_shop_by_location( $query ) {
        if ( ! function_exists( 'is_shop' ) ) {
            return;
        }

        if ( ! is_shop() ) {
            return;
        }

        $selected_location = wpc_selected_location_id();

        if ( empty( $selected_location ) ) {
            return;
        }

        $tax_query = (array) $query->get( 'tax_query' );

        $tax_query[] = array(
            'taxonomy' => 'wpcafe_location',
            'field'    => 'term_id',
            'terms'    => $selected_location,
            'operator' => 'IN',
        );

        $query->set( 'tax_query', $tax_query );
    }
}
