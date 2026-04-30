<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template scope; variables are locally extracted/passed, not globals.

/**
 * Mini Cart Template
 *
 * Handles the display of mini-cart in different styles.
 *
 * @package WpCafe
 */

if ( wpc_is_module_enable( 'mini_cart') ) {
    wp_enqueue_script( 'wpc-mini-cart' );
}

$settings       = wpc_get_option();
$cart_icon      = ! empty( $wpc_cart_icon ) ? $wpc_cart_icon : 'wpcafe-cart_icon';
$minicart_style = wpc_get_option('mini_cart_style', 'style-1');

$cart_icon_link = wpc_get_option('mini_cart_icon') ;

$icon_type =  ! empty( $cart_icon_link['type'] ) ? $cart_icon_link['type'] : '';
$icon_value = ! empty( $cart_icon_link['value'] ) ? $cart_icon_link['value'] : '';


do_action( 'wpc_before_minicart' );

if ( 'style-1' === $minicart_style ) :
    /**
     * Mini Cart Style 1
     */
    ?>
    <div class="wpc_cart_block wpc-minicart-wrapper style1 wpc-cart_main_block">

        <a href="#" class="wpc_cart_icon">
            <div class="wpc-cart-message">
                <?php echo esc_html__( 'Product has been added', 'wp-cafe' ); ?>
            </div>

            <?php if ( $icon_value ) : ?>
                <?php if ( $icon_type === 'custom' ) : ?>
                    <img src="<?php echo esc_url( $icon_value ); ?>" alt="<?php echo esc_attr( $icon_value ); ?>">
                <?php else : 
                    $icon_url = wpcafe()->assets_url . '/images/mini-cart/' . $icon_value . '.svg' ;    
                ?>

                    <img src="<?php echo esc_url( $icon_url ); ?>" alt="<?php echo esc_attr( $icon_value ); ?>">
                <?php endif; ?>
            <?php else : ?>
                <i class="<?php echo esc_attr( $cart_icon ); ?>"></i>
            <?php endif; ?>

            <?php $cart_count = WC()->cart->get_cart_contents_count(); ?>
            <?php if ( $cart_count > 0 ) : ?>
            <sup class="basket-item-count" style="display: inline-block;">
                <span class="cart-items-count count wpc-mini-cart-count"><?php echo esc_html( $cart_count ); ?></span>
            </sup>
            <?php endif; ?>
        </a>

        <div class="wpc-menu-mini-cart wpc_background_color">
            <div class="widget_shopping_cart_content">
                <?php
                $template = wpcafe()->template_directory . '/mini-cart/mini-cart-template.php';

                if ( file_exists( $template ) ) {
                    include $template;
                }
                ?>
            </div>
        </div>
    </div>

<?php else : ?>
    <?php
    /**
     * Mini Cart Style 2
     */
    ?>
    <div class="wpc-minicart-wrapper style2 wpc-cart_main_block">

        <a href="#" class="wpc_cart_icon">
            <div class="wpc-cart-message">
                <?php echo esc_html__( 'Product has been added', 'wp-cafe' ); ?>
            </div>

            <?php if ( $icon_value ) : ?>
                <?php if ( $icon_type === 'custom' ) : ?>
                    <img src="<?php echo esc_url( $icon_value ); ?>" alt="<?php echo esc_attr( $icon_value ); ?>">
                <?php else : 
                    $icon_url = wpcafe()->assets_url . '/images/mini-cart/' . $icon_value . '.svg' ;    
                ?>

                    <img src="<?php echo esc_url( $icon_url ); ?>" alt="<?php echo esc_attr( $icon_value ); ?>">
                <?php endif; ?>
            <?php else : ?>
                <i class="<?php echo esc_attr( $cart_icon ); ?>"></i>
            <?php endif; ?>

            <?php $cart_count = WC()->cart->get_cart_contents_count(); ?>
            <?php if ( $cart_count > 0 ) : ?>
            <sup class="basket-item-count" style="display: inline-block;">
                <span class="cart-items-count count wpc-mini-cart-count"><?php echo esc_html( $cart_count ); ?></span>
            </sup>
            <?php endif; ?>
        </a>

        <div class="wpc_cart_block">
            <div class="wpc-minicart-header">
                <div class="cart-counts">
                    <span class="cart-count">
                        <span class="cart-items-count count wpc-mini-cart-count"><?php echo esc_html( WC()->cart->get_cart_contents_count() ); ?></span>
                        <?php
                        $cart_count = count( WC()->cart->get_cart() );
                        echo esc_html(
                            $cart_count > 1
                                ? __( ' items', 'wp-cafe' )
                                : __( ' item', 'wp-cafe' )
                        );
                        ?>
                    </span>
                    <?php echo esc_html__( 'in cart', 'wp-cafe' ); ?>
                </div>

                <button type="button" class="minicart-close wpc-btn-border wpc-btn">
                    <svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9.93359 16.0653L16.0653 9.93359"
                            stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M16.0653 16.0653L9.93359 9.93359"
                            stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M9.7513 23.8346H16.2513C21.668 23.8346 23.8346 21.668 23.8346 16.2513V9.7513C23.8346 4.33464 21.668 2.16797 16.2513 2.16797H9.7513C4.33464 2.16797 2.16797 4.33464 2.16797 9.7513V16.2513C2.16797 21.668 4.33464 23.8346 9.7513 23.8346Z"
                            stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>

            <div class="wpc-menu-mini-cart wpc_background_color">
                <div class="widget_shopping_cart_content">
                    <?php
                    $template = wpcafe()->template_directory . '/mini-cart/mini-cart-template.php';

                    if ( file_exists( $template ) ) {
                        include $template;
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php do_action( 'wpc_after_minicart' ); ?>
