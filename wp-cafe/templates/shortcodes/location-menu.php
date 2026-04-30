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
        include wpcafe()->plugin_directory . "/widgets/wpc-menus-list/style/{$style}.php";
    } else {
        ?>
        <div><?php esc_html_e( 'No menu found', 'wp-cafe' ); ?></div>
        <?php
    }
    ?>
</div>
