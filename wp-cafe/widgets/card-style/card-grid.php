<?php
/**
 * Grid product card: image on top with badges, content below, cart at the foot.
 *
 * Used by food menu tab style-6 and style-8. Style-8 passes variant "compact",
 * which drops the price and the button onto one row.
 *
 * Expects $wpc_card from Template_Functions::card_args(). Colors come from the
 * --wpc-primary* custom properties printed on the shortcode wrapper.
 *
 * @package WpCafe
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WpCafe\Utils\Wpc_Utilities;
use WpCafe\Core\Shortcodes\Template_Functions;

/** @var array $wpc_card */
$wpc_product   = $wpc_card['product'];
$wpc_permalink = $wpc_card['permalink'];
$wpc_variant   = $wpc_card['variant'];

$wpc_has_thumb = ( 'yes' === $wpc_card['show_thumbnail'] || 'on' === $wpc_card['show_thumbnail'] ) && $wpc_product->get_image();
$wpc_price     = Template_Functions::card_price_html( $wpc_product, $wpc_card['wpc_price_show'] );

$wpc_card_classes = [ 'wpc-card', 'wpc-card-grid' ];
if ( '' !== $wpc_variant ) {
    $wpc_card_classes[] = 'wpc-card-grid--' . sanitize_html_class( $wpc_variant );
}
if ( ! $wpc_has_thumb ) {
    $wpc_card_classes[] = 'wpc-card--no-media';
}
if ( 'yes' !== $wpc_card['wpc_show_desc'] ) {
    $wpc_card_classes[] = 'wpc-card--no-desc';
}
?>
<div class="<?php echo esc_attr( implode( ' ', $wpc_card_classes ) ); ?>">

    <?php if ( $wpc_has_thumb ) : ?>
        <div class="wpc-card-grid__media wpc-food-menu-thumb">
            <a href="<?php echo esc_url( $wpc_permalink ); ?>" class="<?php echo esc_attr( $wpc_card['class'] ); ?>">
                <?php echo wp_kses( $wpc_product->get_image( 'woocommerce_thumbnail' ), Wpc_Utilities::wpc_kses_allowed_tags() ); ?>
            </a>

            <div class="wpc-card-grid__badges wpc-menu-tag-wrap">
                <?php
                if ( 'yes' === $wpc_card['show_item_status'] ) {
                    Wpc_Utilities::wpc_tag( $wpc_product->get_id(), $wpc_product->is_in_stock() );
                }
                if ( 'yes' === $wpc_card['show_item_label'] ) {
                    Wpc_Utilities::wpc_product_labels( $wpc_product->get_id() );
                }
                ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="wpc-card-grid__body">
        <h3 class="wpc-card__title wpc-post-title">
            <a href="<?php echo esc_url( $wpc_permalink ); ?>" class="<?php echo esc_attr( $wpc_card['class'] ); ?>">
                <?php echo esc_html( $wpc_product->get_name() ); ?>
            </a>
        </h3>

        <?php
        // Without a thumbnail the badges have no overlay to sit in, so they follow the title.
        if ( ! $wpc_has_thumb ) {
            ?>
            <div class="wpc-menu-tag-wrap">
                <?php
                if ( 'yes' === $wpc_card['show_item_status'] ) {
                    Wpc_Utilities::wpc_tag( $wpc_product->get_id(), $wpc_product->is_in_stock() );
                }
                if ( 'yes' === $wpc_card['show_item_label'] ) {
                    Wpc_Utilities::wpc_product_labels( $wpc_product->get_id() );
                }
                ?>
            </div>
            <?php
        }

        if ( 'yes' === $wpc_card['show_item_status'] && '' !== $wpc_product->get_price_suffix() && wc_get_price_including_tax( $wpc_product ) ) {
            ?>
            <ul class="wpc-menu-tag wpc-card__tax-suffix">
                <li><?php echo wp_kses( $wpc_product->get_price_suffix(), Wpc_Utilities::wpc_kses_allowed_tags() ); ?></li>
            </ul>
            <?php
        }

        if ( 'yes' === $wpc_card['wpc_show_desc'] ) {
            ?>
            <p class="wpc-card__desc">
                <?php echo esc_html( Wpc_Utilities::wpcafe_trim_words( get_the_excerpt( $wpc_product->get_id() ), $wpc_card['wpc_desc_limit'] ) ); ?>
            </p>
            <?php
        }

        if ( wpcafe_is_multivendor() && 'yes' === $wpc_card['wpc_show_vendor'] ) {
            do_action( 'wpcafe_multivendor_seller', $wpc_product->get_id() );
        }
        ?>

        <div class="wpc-card-grid__footer">
            <div class="wpc-card-grid__pricing">
                <?php if ( '' !== $wpc_price ) : ?>
                    <div class="wpc-card__price wpc-menu-price">
                        <?php
                        // WooCommerce-generated markup: wp_kses_post keeps <del>/<ins>/<bdi> intact.
                        echo wp_kses_post( $wpc_price );
                        ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php
            echo wp_kses(
                Template_Functions::card_cart_button( $wpc_card ),
                Wpc_Utilities::wpc_kses_allowed_tags()
            );
            ?>
        </div>
    </div>

    <div class="wpc_loader_wrapper">
        <div class="loder-dot dot-a"></div>
        <div class="loder-dot dot-b"></div>
        <div class="loder-dot dot-c"></div>
        <div class="loder-dot dot-d"></div>
        <div class="loder-dot dot-e"></div>
        <div class="loder-dot dot-f"></div>
        <div class="loder-dot dot-g"></div>
        <div class="loder-dot dot-h"></div>
    </div>
</div>
