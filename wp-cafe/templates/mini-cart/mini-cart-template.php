<?php
/**
 * Mini Cart Template
 *
 * Handles WooCommerce mini-cart display with coupon support, subtotal,
 * minimum order amount check, and empty cart message.
 *
 * @package WpCafe
 */

use WpCafe\Utils\Wpc_Utilities;

defined( 'ABSPATH' ) || exit;
// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template scope; variables are locally extracted/passed, not globals.

if ( wpc_is_module_enable( 'mini_cart') ) {
    wp_enqueue_script( 'wpc-mini-cart' );
}

do_action( 'woocommerce_before_mini_cart' );

$settings         = wpc_get_option();
$min_order_amount = ! empty( $settings['min_order_amount'] ) ? floatval( $settings['min_order_amount'] ) : 0;
$cart_link        = wpc_get_option('mini_cart_empty_button_link', get_permalink( wc_get_page_id( 'shop' ) )) ;
?>

<?php if ( ! WC()->cart->is_empty() ) : ?>
    <div class="cart-wrapper" data-component="wpc-mini-cart">
        <ul class="wpc-woocommerce-mini-cart cart_list product_list_widget">
            <?php
            do_action( 'woocommerce_before_mini_cart_contents' );

            foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                $_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
                $product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

                if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 &&
                    apply_filters( 'woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key )
                ) {
                    $product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
                    $thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
                    $product_price     = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
                    $product_permalink = apply_filters(
                        'woocommerce_cart_item_permalink',
                        $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '',
                        $cart_item,
                        $cart_item_key
                    );
                    ?>
                    <li class="wpc-woocommerce-mini-cart-item <?php echo esc_attr( apply_filters( 'woocommerce_mini_cart_item_class', 'mini_cart_item', $cart_item, $cart_item_key ) ); ?>">
                        <?php
                        // Remove link.
                        echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            'woocommerce_cart_item_remove_link',
                            sprintf(
                                '<a href="%s" class="remove remove_from_cart_button" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#e7272d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                </a>',
                                esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
                                esc_attr__( 'Remove this item', 'wp-cafe' ),
                                esc_attr( $product_id ),
                                esc_attr( $cart_item_key ),
                                esc_attr( $_product->get_sku() )
                            ),
                            $cart_item_key
                        );

                        // Product link or plain text.
                        if ( empty( $product_permalink ) ) {
                            echo Wpc_Utilities::wpc_render( $thumbnail ) . Wpc_Utilities::wpc_kses( $product_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        } else {
                            echo '<a href="' . esc_url( $product_permalink ) . '">' . Wpc_Utilities::wpc_render( $thumbnail ) . Wpc_Utilities::wpc_kses( $product_name ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        }

                        // Meta data.
                        echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        ?>

                        <div class="mini-cart-quantity-wrapper">
                            <?php
                            // Get the product price from the cart item (which includes variations)
                            $item_price = isset( $cart_item['line_total'] ) ? $cart_item['line_total'] / $cart_item['quantity'] : $_product->get_price();
                            ?>
                            <?php
                            // Quantity input and price using WooCommerce filter.
                            echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                'woocommerce_widget_cart_item_quantity',
                                '<div class="quantity">
                                    <button type="button" class="minus">-</button>
                                    <input type="number" class="qty" name="cart[' . esc_attr( $cart_item_key ) . '][qty]"
                                        value="' . esc_attr( $cart_item['quantity'] ) . '" min="1" step="1"
                                        data-cart-item-key="' . esc_attr( $cart_item_key ) . '"
                                        data-product-id="' . esc_attr( $product_id ) . '"
                                    />
                                    <button type="button" class="plus">+</button>
                                </div>
                                <span class="quantity-text">' . sprintf( '%s &times; %s', $product_price, $cart_item['quantity'] ) . '</span>',
                                $cart_item,
                                $cart_item_key
                            );
                            ?>
                            <strong class="single-subtotal-item">   
                                <?php  $line_total = isset( $cart_item['line_total'] ) ? floatval( $cart_item['line_total'] ) : ( $item_price * $cart_item['quantity'] ); ?>
                                <span class="wpc-minicart-subtotal" data-item-price="<?php echo esc_attr( $item_price ); ?>">
                                    <?php echo wp_kses_post( wc_price( $line_total ) ); ?>
                                </span>
                            </strong>
                            
                        </div>
                    </li>
                    <?php
                }
            }

            do_action( 'woocommerce_mini_cart_contents' );
            ?>
        </ul>
        <?php 
        
            if ( function_exists( 'wpcafe_pro' ) ) {
                include_once wpcafe_pro()->template_directory . '/mini-cart/cross-sell.php';
            }
        ?>
        <div class="wpc-subtotal-wrap">
            <?php if ( function_exists( 'wpcafe_pro' ) ) : ?>
                <div class="wpc-coupon-wrapper">
                    <?php
                    wc_print_notices();

                    if ( empty( WC()->cart->get_coupons() ) ) :
                        if ( wc_coupons_enabled() ) :
                            ?>
                            <label class="showcoupon wpc-minicart-copoun-label" for="minicart-coupon">
                                <?php echo esc_html__( 'Have a coupon code?', 'wp-cafe' ); ?>
                            </label>
                            <div class="coupon_from_wrap">
                                <form class="coupon_from widget_shopping_cart_content" method="post">
                            <?php else : ?>
                            <div class="coupon_from_wrap">
                                <form id="apply-promo-code" class="coupon_from wpc_coupon_form widget_shopping_cart__coupon">
                            <?php endif; ?>
                                    <input id="minicart-coupon" class="input-text wpc-minicart-coupon-field" type="text" name="coupon_code"/>
                                    <button type="submit" id="minicart-apply-button" class="wpc-cupon-btn button" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'wp-cafe' ); ?>">
                                        <?php echo esc_html__( 'Apply', 'wp-cafe' ); ?>
                                    </button>
                                    <?php do_action( 'woocommerce_cart_coupon' ); ?>
                                    <?php do_action( 'woocommerce_cart_actions' ); ?>
                                </form>
                            </div>
                        <?php endif; ?>
                </div>
            <?php endif; ?>

            <p class="wpc-woocommerce-mini-cart__total total">
                <?php
                /**
                 * Hook: woocommerce_widget_shopping_cart_total.
                 *
                 * @hooked woocommerce_widget_shopping_cart_subtotal - 10
                 */
                do_action( 'woocommerce_widget_shopping_cart_total' );

                foreach ( WC()->cart->get_coupons() as $code => $coupon ) :
                    ?>
                    <div id="widget-shopping-cart-remove-coupon" class="mini_cart_coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
                        <?php esc_html_e( 'Coupon: ', 'wp-cafe' ); ?>
                        <?php echo esc_html( sanitize_title( $code ) ); ?>
                        <?php wc_cart_totals_coupon_html( $coupon ); ?>
                    </div>
                <?php endforeach; ?>
            </p>

            <?php
            do_action( 'woocommerce_widget_shopping_cart_before_buttons' );

            if ( floatval( WC()->cart->subtotal ) > $min_order_amount || 0 === $min_order_amount ) :
                ?>
                <p class="wpc-woocommerce-mini-cart__buttons buttons">
                    <?php do_action( 'woocommerce_widget_shopping_cart_buttons' ); ?>
                </p>
            <?php else : ?>
                <?php
                $message = sprintf(
                    /* translators: 1: current subtotal, 2: minimum order amount */
                    __( 'Your current amount is %1$s, You need to add at least %2$s to place order', 'wp-cafe' ),
                    \WpCafe\Core\Modules\Food_Menu\Hooks::get_price_with_currency_symbol( WC()->cart->subtotal ),
                    \WpCafe\Core\Modules\Food_Menu\Hooks::get_price_with_currency_symbol( $min_order_amount )
                );
                wc_print_notice( $message, 'error' );
            endif;

            do_action( 'woocommerce_widget_shopping_cart_after_buttons' );
            ?>
        </div>
    </div>
<?php else : ?>
    <div class="wpc-empty-cart">
        <div class="cart-wrapper">
            <p class="wpc-woocommerce-mini-cart__empty-message">
                <?php esc_html_e( 'No products in the cart.', 'wp-cafe' ); ?>
            </p>
            <a href="<?php echo esc_url( $cart_link ); ?>" class="wpc-btn wpc-empty-btn">
                <?php esc_html_e( 'Explore Food Items', 'wp-cafe' ); ?>
            </a>
        </div>
    </div>
<?php endif; ?>

<?php do_action( 'woocommerce_after_mini_cart' ); ?>
