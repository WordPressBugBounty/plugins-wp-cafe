<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals -- template scope; locally-extracted variables and third-party (Elementor) hook names.

use WpCafe\Utils\Wpc_Utilities;
use WpCafe\Core\Shortcodes\Template_Functions;

// Exit if WooCommerce is not active.
if ( ! class_exists( 'WooCommerce' ) ) {
    return;
}

// Exit if no tabs are available.
if ( empty( $food_menu_tabs ) || ! is_array( $food_menu_tabs ) ) {
    return;
}

// Elementor filter hook.
apply_filters( 'elementor/control/search_data', $settings, $unique_id, 'wpc-food-menu-tab' );

// Sanitize and set defaults.
$wpc_menu_count   = ! empty( $settings['wpc_menu_count'] ) ? intval( $settings['wpc_menu_count'] ) : 5;
$wpc_show_desc    = ! empty( $settings['wpc_show_desc'] ) ? sanitize_text_field( $settings['wpc_show_desc'] ) : 'yes';
$wpc_show_vendor  = ! empty( $settings['wpc_show_vendor'] ) ? sanitize_text_field( $settings['wpc_show_vendor'] ) : 'no';
$show_thumbnail   = ! empty( $settings['show_thumbnail'] ) ? sanitize_text_field( $settings['show_thumbnail'] ) : 'yes';
$title_link_show  = ! empty( $settings['title_link_show'] ) ? sanitize_text_field( $settings['title_link_show'] ) : 'yes';
$class            = ( 'yes' === $title_link_show ) ? '' : 'wpc-no-link';
?>

<div class="wpc-food-tab-wrapper wpc-nav-shortcode main_wrapper_<?php echo esc_attr( $unique_id ); ?>" data-id="<?php echo esc_attr( $unique_id ); ?>">

    <?php Template_Functions::render_food_menu_tab_nav( $food_menu_tabs ); ?>

    <div class="wpc-tab-content wpc-widget-wrapper">
        <?php foreach ( $food_menu_tabs as $content_key => $value ) : ?>
            <?php if ( ! empty( $value['post_cats'][0] ) ) : ?>
                <?php
                $active_class = ( 0 === $content_key ) ? 'tab-active' : '';
                $cat_id       = intval( $value['post_cats'][0] );

                $food_tab_args = [
                    'post_type'     => 'product',
                    'no_of_product' => $wpc_menu_count,
                    'wpc_cat'       => $value['post_cats'],
                    'order'         => $wpc_menu_order,
                ];

                $selected_location = wpc_selected_location_id();
                if ( ! empty( $selected_location ) ) {
                    $food_tab_args['wpc_location'] = $selected_location;
                }

                $products = Wpc_Utilities::product_query( $food_tab_args );

                $menu_tab_args = [
                    'active_class'     => $active_class,
                    'content_key'      => $content_key,
                    'cat_id'           => $cat_id,
                    'unique_id'        => $unique_id,
                    'products'         => $products,
                    'style'            => $style,
                    'wpc_cart_button'  => $wpc_cart_button,
                    'wpc_price_show'   => $wpc_price_show,
                    'wpc_show_desc'    => $wpc_show_desc,
                    'show_thumbnail'   => $show_thumbnail,
                    'title_link_show'  => $title_link_show,
                    'show_item_status' => $show_item_status,
                    'wpc_desc_limit'   => $wpc_desc_limit,
                    'wpc_show_vendor'  => $wpc_show_vendor,
                ];

                extract( $menu_tab_args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
                ?>
                <div class="wpc-tab <?php echo esc_attr( $active_class ); ?>" 
                    data-id="tab_<?php echo intval( $content_key ); ?>" 
                    data-cat_id="<?php echo esc_attr( $cat_id ); ?>">
                    
                    <div class="tab_template_<?php echo esc_attr( "{$cat_id}_{$unique_id}" ); ?>"></div>
                    
                    <div class="template_data_<?php echo esc_attr( "{$cat_id}_{$unique_id}" ); ?>">
                        <?php
                        $template = trailingslashit( wpcafe()->plugin_directory ) . "/widgets/wpc-food-menu-tab/style/{$style}.php";
                        if ( ! file_exists( $template ) && ( function_exists( 'wpcafe_pro' ) || defined( 'WPCAFE_PRO_FILE' ) ) ) {
                            if ( function_exists( 'wpcafe_pro' ) ) {
                                $pro_template = trailingslashit( wpcafe_pro()->plugin_directory ) . "/widgets/food-menu-tab/style/{$style}.php";
                                if ( file_exists( $pro_template ) ) {
                                    $template = $pro_template;
                                }
                            }
                        }

                        if ( file_exists( $template ) ) {
                            // Add variables for pro styles compatibility
                            if ( ! isset( $wpc_menu_col ) ) {
                                $wpc_menu_col = 6;
                            }
                            if ( ! isset( $wpc_delivery_time_show ) ) {
                                $wpc_delivery_time_show = 'no';
                            }
                            if ( ! isset( $wpc_btn_text ) ) {
                                $wpc_btn_text = '';
                            }
                            if ( ! isset( $customize_btn ) ) {
                                $customize_btn = 'no';
                            }
                            include $template;
                        }
                        ?>
                    </div>
                </div><!-- .wpc-tab -->
            <?php endif; ?>
        <?php endforeach; ?>
    </div><!-- .wpc-tab-content -->

</div><!-- .wpc-food-tab-wrapper -->

<?php return; ?>
