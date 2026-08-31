<?php
/**
 * Food menu list style-4 — full-width row cards.
 *
 * Included by the list shortcode, the Elementor widget view, the food-list
 * block and the pagination AJAX handler. Every one of those sets the same
 * variables in scope, so anything optional is defaulted here.
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

$class      = ( isset( $title_link_show ) && 'yes' === $title_link_show ) ? '' : 'wpc-no-link';
$cart_icon  = wpc_get_option( 'cart_icon' );
?>
<div class="wpc-card-row-list">
    <?php foreach ( $products as $product ) : ?>
        <?php
        $permalink = ( isset( $title_link_show ) && 'yes' === $title_link_show ) ? get_permalink( $product->get_id() ) : '#';

        Wpc_Widget_Template::wpc_food_menu_card_row(
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
