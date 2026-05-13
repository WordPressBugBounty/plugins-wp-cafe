<?php
namespace WpCafe\Products\Nutrition;

use WpCafe\Providers\Base_Service_Provider;
use WpCafe\Contracts\Switchable_Provider_Contract;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Wires the Nutrition + Allergen module.
 *
 * - Loads helpers + presets (function-based files).
 * - Registers admin metabox class.
 * - Filters single-product tabs to inject "Nutrition" tab.
 *
 * @package WpCafe/Products/Nutrition
 */
class Nutrition_Service_Provider extends Base_Service_Provider implements Switchable_Provider_Contract {

    protected $services = [
        Nutrition_Admin::class,
    ];

    public function get_services() {
        return apply_filters( 'wpcafe_nutrition_services', $this->services );
    }

    public function boot() {
        // Load helper + preset function files (not autoloaded, function-based).
        $base = __DIR__;
        require_once $base . '/allergen-presets.php';
        require_once $base . '/helpers.php';

        parent::boot();

        add_filter( 'woocommerce_product_tabs', [ $this, 'register_frontend_tab' ], 15 );
        add_action( 'wp_enqueue_scripts',       [ $this, 'enqueue_frontend_assets' ], 30 );
        add_action( 'variation/popup_content',  [ $this, 'render_popup_dietary_section' ], 35 );
    }

    /**
     * Module is active whenever WooCommerce is loaded.
     */
    public function is_enable() {
        return function_exists( 'WC' );
    }

    /**
     * Inject "Nutrition" tab on single-product page.
     */
    public function register_frontend_tab( $tabs ) {
        global $product;
        if ( ! $product || ! is_object( $product ) ) return $tabs;

        $product_id = method_exists( $product, 'get_id' ) ? $product->get_id() : 0;
        if ( ! $product_id ) return $tabs;

        if ( ! function_exists( 'wpc_product_has_nutrition_data' ) || ! wpc_product_has_nutrition_data( $product_id ) ) {
            return $tabs;
        }

        $nutrition     = wpc_product_nutrition( $product_id );
        $allergens     = wpc_product_allergens( $product_id );
        $has_nutrition = ! empty( $nutrition['enabled'] ) && (
            array_filter( array_intersect_key( $nutrition, array_flip( wpc_nutrition_field_keys() ) ), function ( $v ) { return $v !== '' && $v !== null; } )
            || ! empty( $nutrition['vitamins'] )
            || ! empty( $nutrition['ingredients'] )
        );
        $has_allergens = ! empty( $allergens['contains'] ) || ! empty( $allergens['may_contain'] ) || ! empty( $allergens['custom'] );

        if ( $has_nutrition && $has_allergens ) {
            $title = __( 'Nutrition & Allergens', 'wp-cafe' );
        } elseif ( $has_allergens ) {
            $title = __( 'Allergens', 'wp-cafe' );
        } else {
            $title = __( 'Nutrition', 'wp-cafe' );
        }

        $tabs['wpcafe_nutrition'] = [
            'title'    => $title,
            'priority' => 15,
            'callback' => [ $this, 'render_frontend_tab_content' ],
        ];
        return $tabs;
    }

    /**
     * Render the frontend tab body.
     */
    public function render_frontend_tab_content() {
        global $product;
        if ( ! $product ) return;
        $product_id = $product->get_id();

        $has_nutrition = ! empty( wpc_product_nutrition( $product_id )['enabled'] );
        $allergens     = wpc_product_allergens( $product_id );
        $has_allergens = ! empty( $allergens['contains'] ) || ! empty( $allergens['may_contain'] ) || ! empty( $allergens['custom'] );
        $has_ingredients = ! empty( wpc_product_nutrition( $product_id )['ingredients'] );
        ?>
        <div class="wpc-nutrition-panel<?php echo ( $has_nutrition && ( $has_allergens || $has_ingredients ) ) ? '' : ' is-single-column'; ?>">
            <?php if ( $has_nutrition ) : ?>
                <div class="wpc-nutrition-panel__col wpc-nutrition-panel__col--label">
                    <?php wpc_render_nutrition_label( $product_id ); ?>
                </div>
            <?php endif; ?>

            <?php if ( $has_allergens || $has_ingredients ) : ?>
                <div class="wpc-nutrition-panel__col wpc-nutrition-panel__col--side">
                    <?php
                    wpc_render_allergens( $product_id );
                    wpc_render_ingredients( $product_id );
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Enqueue frontend CSS only on single-product pages with nutrition data.
     */
    public function enqueue_frontend_assets() {
        $should_load = false;

        if ( function_exists( 'is_product' ) && is_product() ) {
            $product_id = get_queried_object_id();
            if ( function_exists( 'wpc_product_has_nutrition_data' ) && wpc_product_has_nutrition_data( $product_id ) ) {
                $should_load = true;
            }
        }

        if ( ! $should_load && wp_script_is( 'wpc-public', 'enqueued' ) ) {
            $should_load = true;
        }

        if ( ! $should_load ) return;

        wp_enqueue_style(
            'wpcafe-nutrition',
            wpcafe()->assets_url . '/css/nutrition.css',
            [],
            defined( 'WPCAFE_VERSION' ) ? WPCAFE_VERSION : false
        );
    }

    /**
     * Render the nutrition + allergen accordion inside the variation popup.
     * Bails when the product has no dietary data.
     */
    public function render_popup_dietary_section() {
        global $product;
        if ( ! ( $product instanceof \WC_Product ) ) return;

        $product_id = $product->get_id();
        if ( ! function_exists( 'wpc_product_has_nutrition_data' ) || ! wpc_product_has_nutrition_data( $product_id ) ) {
            return;
        }

        $nutrition       = wpc_product_nutrition( $product_id );
        $allergens       = wpc_product_allergens( $product_id );
        $has_nutrition   = ! empty( $nutrition['enabled'] ) && (
            array_filter( array_intersect_key( $nutrition, array_flip( wpc_nutrition_field_keys() ) ), function ( $v ) { return $v !== '' && $v !== null; } )
            || ! empty( $nutrition['vitamins'] )
        );
        $has_ingredients = ! empty( $nutrition['ingredients'] );
        $has_allergens   = ! empty( $allergens['contains'] ) || ! empty( $allergens['may_contain'] ) || ! empty( $allergens['custom'] );

        if ( ! $has_nutrition && ! $has_allergens && ! $has_ingredients ) return;
        ?>
        <section class="wpc-popup-dietary">
            <?php if ( $has_nutrition ) : ?>
                <details class="wpc-popup-dietary__item">
                    <summary class="wpc-popup-dietary__summary">
                        <span class="wpc-popup-dietary__title"><?php echo esc_html__( 'Nutrition Facts', 'wp-cafe' ); ?></span>
                        <span class="wpc-popup-dietary__chevron" aria-hidden="true"></span>
                    </summary>
                    <div class="wpc-popup-dietary__body">
                        <?php wpc_render_nutrition_label( $product_id ); ?>
                    </div>
                </details>
            <?php endif; ?>

            <?php if ( $has_allergens || $has_ingredients ) : ?>
                <details class="wpc-popup-dietary__item">
                    <summary class="wpc-popup-dietary__summary">
                        <span class="wpc-popup-dietary__title"><?php echo esc_html__( 'Allergens & Ingredients', 'wp-cafe' ); ?></span>
                        <span class="wpc-popup-dietary__chevron" aria-hidden="true"></span>
                    </summary>
                    <div class="wpc-popup-dietary__body">
                        <?php
                        if ( $has_allergens ) {
                            wpc_render_allergens( $product_id );
                        }
                        if ( $has_ingredients ) {
                            wpc_render_ingredients( $product_id );
                        }
                        ?>
                    </div>
                </details>
            <?php endif; ?>
        </section>
        <?php
    }
}
