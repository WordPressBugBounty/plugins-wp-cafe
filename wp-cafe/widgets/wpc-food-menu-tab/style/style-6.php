<?php
/**
 * Food menu tab style-6 — grid cards under a right-aligned pill nav.
 *
 * Numbering starts at 6 on purpose: the free tab shortcode resolves style-3,
 * style-4 and style-5 to wpcafe-pro template files when Pro is active, so those
 * slots must stay free.
 *
 * @package WpCafe
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals -- widget/template scope; variables come from the including render path.

use WpCafe\Core\Shortcodes\Template_Functions as Wpc_Widget_Template;

if ( ! is_array( $products ) || empty( $products ) ) {
    return;
}

$class     = ( isset( $title_link_show ) && 'yes' === $title_link_show ) ? '' : 'wpc-no-link';
$cart_icon = wpc_get_option( 'cart_icon' );
?>
<div class="wpc-card-grid-list" style="--wpc-grid-columns:<?php echo esc_attr( max( 1, (int) ( $grid_columns ?? 3 ) ) ); ?>">
    <?php foreach ( $products as $product ) : ?>
        <?php
        $permalink = ( isset( $title_link_show ) && 'yes' === $title_link_show ) ? get_permalink( $product->get_id() ) : '#';

        Wpc_Widget_Template::wpc_food_menu_card_grid(
            [
                'product'          => $product,
                'permalink'        => $permalink,
                'class'            => $class,
                'unique_id'        => $unique_id ?? '',
                'show_thumbnail'   => $show_thumbnail ?? 'yes',
                'wpc_price_show'   => $wpc_price_show ?? 'yes',
                'wpc_cart_button'  => $wpc_cart_button ?? 'yes',
                'show_item_status' => $show_item_status ?? 'yes',
                'show_item_label'  => $show_item_label ?? 'no',
                'wpc_show_desc'    => $wpc_show_desc ?? 'yes',
                'wpc_desc_limit'   => $wpc_desc_limit ?? 20,
                'wpc_show_vendor'  => $wpc_show_vendor ?? 'no',
                'cart_icon'        => $cart_icon,
            ]
        );
        ?>
    <?php endforeach; ?>
</div>
