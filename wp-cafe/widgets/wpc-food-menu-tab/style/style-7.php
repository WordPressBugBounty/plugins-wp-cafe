<?php
/**
 * Food menu tab style-7 — boxed row cards beside the vertical rail nav.
 *
 * The results counter lives inside this file (not in the tab template) so the
 * pagination AJAX response replaces it along with the products.
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

$result_total = isset( $wpc_result_total ) ? (int) $wpc_result_total : 0;
$per_page     = max( 1, (int) ( $wpc_menu_count ?? count( $products ) ) );
$page_number  = max( 1, (int) ( $wpc_current_page ?? 1 ) );
$range_from   = ( ( $page_number - 1 ) * $per_page ) + 1;
$range_to     = $range_from + count( $products ) - 1;
?>
<?php if ( $result_total > 0 ) : ?>
    <p class="wpc-results-count">
        <?php
        printf(
            /* translators: 1: total dish count, 2: first item on the page, 3: last item on the page. */
            esc_html__( '%1$s dishes · showing %2$s-%3$s', 'wp-cafe' ),
            esc_html( number_format_i18n( $result_total ) ),
            esc_html( number_format_i18n( $range_from ) ),
            esc_html( number_format_i18n( $range_to ) )
        );
        ?>
    </p>
<?php endif; ?>

<div class="wpc-card-row-list wpc-card-row-list--boxed">
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
                'variant'          => 'boxed',
            ]
        );
        ?>
    <?php endforeach; ?>
</div>
