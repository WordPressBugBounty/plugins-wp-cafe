<?php
/**
 * Mini Cart
 *
 * Handles mini cart rendering and fragment refresh.
 *
 * @package WpCafe\FoodOrder\Mini_Cart
 * @since   1.0.0
 */

namespace WpCafe\FoodOrder\Mini_Cart;

if ( ! defined( 'ABSPATH' ) ) exit;

use Astra_Woocommerce;

/**
 * Mini Cart Class
 *
 * Provides frontend mini cart rendering, cart fragment updates,
 * and extra content like pickup/delivery toggles.
 *
 * @since 1.0.0
 */
class Mini_Cart {

    /**
     * Initialize the mini cart functionality.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'wp_head', array( $this, 'add_inline_script' ) );
        add_action( 'wp_footer', array( $this, 'add_mini_cart' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_mini_cart_scripts' ) );

        // add_action( 'woocommerce_widget_shopping_cart_buttons', array( $this, 'mini_cart_add_class' ), 20 );
        add_action( 'woocommerce_widget_shopping_cart_before_buttons', array( $this, 'handle_mini_cart_buttons_before' ) );
        add_action( 'woocommerce_widget_shopping_cart_before_buttons', array( $this, 'before_minicart_buttons_add_extra_content' ), 9, 1 );

        add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'wpc_add_to_cart_count_fragment_refresh' ), 30, 1 );
        add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'wpc_add_to_cart_content_fragment_refresh' ) );

        // AJAX handlers for mini cart quantity updates.
        add_action( 'wp_ajax_wpc_update_cart_quantity', array( $this, 'wpc_update_cart_quantity' ) );
        add_action( 'wp_ajax_nopriv_wpc_update_cart_quantity', array( $this, 'wpc_update_cart_quantity' ) );

        // AJAX handlers for mini cart item removal.
        add_action( 'wp_ajax_wpc_remove_cart_item', array( $this, 'wpc_remove_cart_item' ) );
        add_action( 'wp_ajax_nopriv_wpc_remove_cart_item', array( $this, 'wpc_remove_cart_item' ) );

        // Remove Astra cart fragment handling to avoid conflicts.
        if ( class_exists( 'Astra_Woocommerce' ) ) {
            $obj = Astra_Woocommerce::get_instance();
            remove_filter( 'woocommerce_add_to_cart_fragments', array( $obj, 'cart_link_fragment' ), 11 );
            remove_filter( 'add_to_cart_fragments', array( $obj, 'cart_link_fragment' ), 11 );
        }
    }

    /**
     * Enqueue scripts and localize nonce data.
     *
     * @since 1.0.0
     * @return void
     */
    public function enqueue_mini_cart_scripts() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        // Mini-cart styles (split out of wpc-public.css). The footer mini-cart
        // renders on the wp_footer hook sitewide, so load its CSS here.
        wp_enqueue_style( 'wpc-minicart' );
        // Cart-icon glyph font (<i class="wpcafe-cart_icon">) lives in wpc-icon.css.
        wp_enqueue_style( 'wpc-icon' );

        // Mini-cart behaviour (open/close panel + qty handlers). Enqueued here so it loads sitewide with the footer mini-cart, not just on gated pages.
        wp_enqueue_script( 'wpc-mini-cart' );
        wp_enqueue_script( 'wc-cart-fragments' );
        // Localize nonce data for AJAX requests.
        wp_localize_script( 'jquery', 'wpc_cart_nonce_data', [ 'nonce'    => wp_create_nonce( 'wpc_cart_nonce' ), 'ajax_url' => admin_url( 'admin-ajax.php' ) ] );
    }

    /**
     * Handle AJAX cart quantity update.
     *
     * Updates cart item quantity and returns only updated subtotal to avoid full re-render.
     *
     * @since 1.0.0
     * @return void
     */
    public function wpc_update_cart_quantity() {
        check_ajax_referer( 'wpc_cart_nonce', 'nonce' );

        if ( ! isset( $_POST['cart_item_key'] ) || ! isset( $_POST['qty'] ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid parameters', 'wp-cafe' ) ] );
        }

        $cart_item_key = sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) );
        $qty           = intval( $_POST['qty'] );

        if ( $qty < 1 ) {
            $qty = 1;
        }

        // Check if the cart item exists.
        $cart = WC()->cart;
        if ( ! isset( $cart->cart_contents[ $cart_item_key ] ) ) {
            wp_send_json_error( [ 'message' => __( 'Cart item not found', 'wp-cafe' ) ] );
        }

        $cart->set_quantity( $cart_item_key, $qty );

        WC()->cart->calculate_totals();

        // Pull the just-recalculated cart item.
        $cart_item = $cart->cart_contents[ $cart_item_key ];
        $_product  = isset( $cart_item['data'] ) ? $cart_item['data'] : null;

        // Line subtotal HTML respects woocommerce_tax_display_cart (incl/excl).
        $new_subtotal_html = $_product
            ? WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] )
            : wc_price( 0 );

        // Display-aware unit price for the data-item-price attribute used by optimistic JS recalc.
        $display_unit_price = $_product ? wc_get_price_to_display( $_product ) : 0;

        wp_send_json_success( [
            'message'         => __( 'Cart updated successfully', 'wp-cafe' ),
            'cart_item_key'   => $cart_item_key,
            'new_subtotal'    => $new_subtotal_html,
            'item_unit_price' => $display_unit_price,
            'cart_count'      => WC()->cart->get_cart_contents_count(),
            'cart_subtotal'   => WC()->cart->get_cart_subtotal(),
            'cart_total'      => WC()->cart->get_total(),
            'item_tax_html'   => $this->build_item_tax_html( $cart_item_key ),
        ] );
    }

    /**
     * Build the per-item tax line HTML for an AJAX response.
     *
     * Returns empty string when the toggle is off, taxes are disabled, the
     * cart item is gone, or the line tax is zero — JS treats empty as "remove".
     *
     * @since 1.0.0
     * @param  string $cart_item_key Cart item key.
     * @return string Pre-escaped HTML.
     */
    private function build_item_tax_html( string $cart_item_key ): string {
        if ( ! wc_tax_enabled() || ! wpc_get_option( 'mini_cart_show_per_item_tax', false ) ) {
            return '';
        }

        $cart_item = WC()->cart->cart_contents[ $cart_item_key ] ?? null;
        $line_tax  = $cart_item ? (float) ( $cart_item['line_tax'] ?? 0 ) : 0;
        if ( $line_tax <= 0 ) {
            return '';
        }

        return sprintf(
            '<small class="wpc-minicart-item-tax" data-cart-item-key="%s">%s %s</small>',
            esc_attr( $cart_item_key ),
            esc_html__( 'incl. tax', 'wp-cafe' ),
            wp_kses_post( wc_price( $line_tax ) )
        );
    }

    /**
     * Handle AJAX cart item removal.
     *
     * Removes item from cart and triggers fragment refresh.
     *
     * @since 1.0.0
     * @return void
     */
    public function wpc_remove_cart_item() {
        check_ajax_referer( 'wpc_cart_nonce', 'nonce' );

        if ( ! isset( $_POST['cart_item_key'] ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid parameters', 'wp-cafe' ) ] );
        }

        $cart_item_key = sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) );

        // Check if the cart item exists.
        $cart = WC()->cart;
        if ( ! isset( $cart->cart_contents[ $cart_item_key ] ) ) {
            wp_send_json_error( [ 'message' => __( 'Cart item not found', 'wp-cafe' ) ] );
        }

        // Remove the item from cart.
        $removed = $cart->remove_cart_item( $cart_item_key );

        if ( $removed ) {
            WC()->cart->calculate_totals();

            wp_send_json_success( [
                'message'       => __( 'Item removed from cart', 'wp-cafe' ),
                'cart_count'    => WC()->cart->get_cart_contents_count(),
                'cart_subtotal' => WC()->cart->get_cart_subtotal(),
                'cart_total'    => WC()->cart->get_total(),
            ] );
        } else {
            wp_send_json_error( [ 'message' => __( 'Failed to remove item', 'wp-cafe' ) ] );
        }
    }

    /**
     * Add mini cart markup to the footer.
     *
     * Shows location modal if enabled in settings and location not selected.
     *
     * @since 1.0.0
     * @return void
     */
    public function add_mini_cart() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        if ( is_checkout() || is_cart() ) {
            return;
        }

        $settings = wpc_get_option();
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin list-table filter, capability-gated
        $location = isset( $_GET['location'] ) ? absint( $_GET['location'] ) : 0;

        // Load custom mini cart template.
        $custom_mini_cart = wpcafe()->template_directory . '/mini-cart/custom-mini-cart.php';
        if ( file_exists( $custom_mini_cart ) ) {
            include_once $custom_mini_cart;
        }
    }

    /**
     * Add inline script for mini cart template.
     *
     * @since 1.0.0
     * @return void
     */
    public function add_inline_script() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        $template = wpcafe()->template_directory . '/mini-cart/mini-cart.php';
        if ( file_exists( $template ) ) {
            include_once $template;
        }
    }

    /**
     * Add checkout button in mini cart.
     *
     * @since 1.0.0
     * @return void
     */
    public function mini_cart_add_class() {
        echo '<a href="' . esc_url( wc_get_checkout_url() ) . '" class="button checkout wc-forward">' . esc_html__( 'Checkout', 'wp-cafe' ) . '</a>';
    }

    /**
     * Handle mini cart button wrapper logic.
     *
     * Adds pickup/delivery toggle, cross-sells, coupon toggle,
     * and quantity update handling in mini cart.
     *
     * @since 1.0.0
     * @return void
     */
    public function handle_mini_cart_buttons_before() {
        if ( class_exists( 'Wpcafe_Multivendor' ) ) {
            return;
        }

        $show_delivery = (bool) apply_filters( 'wpcafe_minicart_show_delivery', wpc_is_module_enable( 'delivery' ) );
        $show_pickup   = (bool) apply_filters( 'wpcafe_minicart_show_pickup', wpc_is_module_enable( 'pickup' ) );
        // Dine-in visibility is gated on both the dine-in module AND the
        // customer-checkout toggle — operators can run dine-in staff-only.
        $show_dine_in = false;
        if ( function_exists( 'wpc_is_dine_in_customer_checkout_enabled' ) ) {
            $show_dine_in = (bool) apply_filters( 'wpcafe_minicart_show_dine_in', wpc_is_dine_in_customer_checkout_enabled() );
        }

        if ( ! $show_delivery && ! $show_pickup && ! $show_dine_in ) {
            return;
        }
        $default_mode = $show_delivery ? 'Delivery' : ( $show_pickup ? 'Pickup' : 'DineIn' );
        $option_count = (int) $show_delivery + (int) $show_pickup + (int) $show_dine_in;
        ?>
            <div class="wpc_pro_order_time">
                <div class="minicart-condition-parent">
                    <?php if ( $show_delivery ): ?>
                    <div class="wpc-field-wrap">
                        <label for="wpc_pro_order_time_delivary">
                            <input
                                type="radio"
                                name="wpc_pro_order_time"
                                class="wpc-minicart-condition-input" id="wpc_pro_order_time_delivary"
                                value="Delivery"
                            >
                            <?php echo esc_html__( 'Delivery', 'wp-cafe' ); ?>
                            <span class="dot-shadow"></span>
                        </label>
                    </div>
                    <?php endif; ?>

                    <?php if ( $show_pickup ): ?>
                    <div class="wpc-field-wrap">
                        <label for="wpc_pro_order_time_pickup">
                            <input
                                type="radio"
                                name="wpc_pro_order_time"
                                class="wpc-minicart-condition-input" id="wpc_pro_order_time_pickup"
                                value="Pickup"
                            >
                            <?php echo esc_html__( 'Pickup', 'wp-cafe' ); ?>
                            <span class="dot-shadow"></span>
                        </label>
                    </div>
                    <?php endif; ?>

                    <?php if ( $show_dine_in ): ?>
                    <div class="wpc-field-wrap">
                        <label for="wpc_pro_order_time_dine_in">
                            <input
                                type="radio"
                                name="wpc_pro_order_time"
                                class="wpc-minicart-condition-input" id="wpc_pro_order_time_dine_in"
                                value="DineIn"
                            >
                            <?php echo esc_html__( 'Dine in', 'wp-cafe' ); ?>
                            <span class="dot-shadow"></span>
                        </label>
                    </div>
                    <?php endif; ?>

                    <?php if ( $option_count > 1 ): ?>
                    <input type="hidden" name="is_order_time_selected" id="wpc-minicart-condition-value-holder" value=""/>
                    <input type="hidden" name="order_type" class="order_type" value="<?php echo esc_attr( $default_mode ); ?>"/>
                    <?php endif; ?>
                </div>
            </div>
            <?php
    }

    /**
     * Refresh cart count fragment.
     *
     * @since 1.0.0
     * @param array $fragments Cart fragments.
     * @return array
     */
    public function wpc_add_to_cart_count_fragment_refresh( $fragments ) {
        ob_start();
        ?>
        <span class="wpc-mini-cart-count">
            <?php echo esc_html( WC()->cart->get_cart_contents_count() ); ?>
        </span>
        <?php
        $fragments['.wpc-mini-cart-count'] = ob_get_clean();
        return $fragments;
    }

    /**
     * Refresh mini cart content fragment.
     *
     * @since 1.0.0
     * @param array $fragments Cart fragments.
     * @return array
     */
    public function wpc_add_to_cart_content_fragment_refresh( $fragments ) {
        ob_start();
        ?>
        <div class="widget_shopping_cart_content">
            <?php
            if ( file_exists( wpcafe()->template_directory . '/mini-cart/mini-cart-template.php' ) ) {
                include_once wpcafe()->template_directory . '/mini-cart/mini-cart-template.php';
            }
            ?>
        </div>
        <?php
        $fragments['div.widget_shopping_cart_content'] = ob_get_clean();
        return $fragments;
    }

    /**
     * Add extra content like total inside mini cart.
     *
     * @since 1.0.0
     * @return void
     */
    public function before_minicart_buttons_add_extra_content() {
        $cart_obj = WC()->cart;

        if ( ! empty( $cart_obj ) ) {
            ?>
            <div class="wpc-minicart-extra">
                <div class="wpc-minicart-extra-total">
                    <span>
                        <?php echo esc_html__( 'Total', 'wp-cafe' ); ?>
                        <span class="wpc-extra-text"><?php echo esc_html__( '(including all charges)', 'wp-cafe' ); ?></span>
                    </span>
                    <p class="wpc-minicart-total">
                        <?php
                        echo wp_kses(
                            wc_price( $cart_obj->total ),
                            array(
                                'span'  => array(),
                                'small' => array(),
                                'a'     => array(),
                                'bdi'   => array(),
                                'del'   => array(),
                            )
                        );
                        ?>
                    </p>
                </div>
            </div>
            <?php
        }
    }
}
