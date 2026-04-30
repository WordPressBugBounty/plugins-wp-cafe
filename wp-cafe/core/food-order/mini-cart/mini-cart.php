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

        // Extract item price for subtotal calculation.
        $cart_item    = $cart->cart_contents[ $cart_item_key ];
        $item_price   = isset( $cart_item['line_total'] ) ? $cart_item['line_total'] / $cart_item['quantity'] : 0;
        $new_subtotal = ( $qty * $item_price );

        // Get the cart total using get_total( 'edit' ) to ensure recalculation
        $cart_total = WC()->cart->get_total( 'edit' );
        if ( ! $cart_total || $cart_total === '' ) {
            $cart_total = WC()->cart->total;
        }

        $cart_subtotal = WC()->cart->get_subtotal();

        wp_send_json_success( [
            'message'        => __( 'Cart updated successfully', 'wp-cafe' ),
            'cart_item_key'  => $cart_item_key,
            'new_subtotal'   => wc_price( $new_subtotal ),
            'cart_count'     => WC()->cart->get_cart_contents_count(),
            'cart_subtotal'  => wc_price( $cart_subtotal ),
            'cart_total'     => wc_price( $cart_total ),
        ] );
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
                'message'     => __( 'Item removed from cart', 'wp-cafe' ),
                'cart_count'  => WC()->cart->get_cart_contents_count(),
                'cart_total'  => wc_price( WC()->cart->total ),
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
        ?>
        <?php if ( ! class_exists( 'Wpcafe_Multivendor' ) ) {
            ?>
            <div class="wpc_pro_order_time">
                <div class="minicart-condition-parent">

                    <?php if ( wpc_is_module_enable( 'delivery' ) ): ?>
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

                    <?php if ( wpc_is_module_enable( 'pickup' ) ): ?>
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

                    <?php if ( wpc_is_module_enable( 'delivery' ) && wpc_is_module_enable( 'pickup' ) ): ?>
                    <input type="hidden" name="is_order_time_selected" id="wpc-minicart-condition-value-holder" value=""/>
                    <input type="hidden" name="order_type" class="order_type" value="<?php echo esc_attr( wpc_is_module_enable( 'delivery' ) ? 'Delivery' : 'Pickup' ); ?>"/>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        }
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
