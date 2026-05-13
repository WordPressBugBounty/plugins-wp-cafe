<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals -- template scope; locally-extracted variables and third-party (Elementor) hook names.

global $woocommerce;

if ( is_object( WC()->cart ) && WC()->cart->cart_contents_count === 0 ) {
    $cart_empty = 1;
} else {
    $cart_empty = 0;
}
?>

<!-- render html -->
<div class="food_location" data-cart_empty="<?php echo esc_attr( $cart_empty ); ?>">
    <?php
    if ( ! empty( $products ) ) {
        $location_total_pages = isset( $total_pages ) ? (int) $total_pages : 0;
        $location_current_page = isset( $current_page ) ? max( 1, (int) $current_page ) : 1;
        $location_show_pagination = isset( $show_pagination ) ? $show_pagination : ( $product_data['show_pagination'] ?? 'yes' );
        ?>
        <div class="wpc-paginated-products"
            data-shortcode="food_location_menu"
            data-current="<?php echo esc_attr( $location_current_page ); ?>"
            data-product_data="<?php echo esc_attr( wp_json_encode( $product_data ) ); ?>">
            <div class="wpc-paginated-products-body">
                <?php include wpcafe()->plugin_directory . "/widgets/wpc-menus-list/style/{$style}.php"; ?>
            </div>
            <?php
            if ( 'yes' === $location_show_pagination ) {
                echo \WpCafe\Utils\Wpc_Utilities::render_menu_pagination( $location_current_page, $location_total_pages ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            ?>
        </div>
        <?php
    } else {
        ?>
        <div><?php esc_html_e( 'No menu found', 'wp-cafe' ); ?></div>
        <?php
    }
    ?>
</div>
