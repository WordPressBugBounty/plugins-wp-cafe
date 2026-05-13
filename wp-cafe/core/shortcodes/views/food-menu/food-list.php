<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals -- template scope; locally-extracted variables and third-party/public hook names.

    use WpCafe\Utils\Wpc_Utilities;
    $style               = $settings["food_menu_style"];
    $show_item_status   = $settings["show_item_status"];
    $show_item_label    = isset($settings["show_item_label"]) ? $settings["show_item_label"] : 'no';
    $show_thumbnail     = $settings["show_thumbnail"];
    $title_link_show    = $settings["title_link_show"];
    $wpc_cart_button    = $settings["wpc_cart_button_show"];
    $wpc_show_desc      = $settings["wpc_show_desc"];
    $wpc_desc_limit     = $settings["wpc_desc_limit"];
    $wpc_menu_cat       = $settings["wpc_menu_cat"];
    $wpc_menu_count     = $settings["wpc_menu_count"];
    $wpc_menu_order     = $settings["wpc_menu_order"];
    $show_thumbnail     = $settings["show_thumbnail"];
    $wpc_price_show     = $settings["wpc_price_show"];
    $wpc_show_vendor    = !empty($settings["wpc_show_vendor"]) ? $settings["wpc_show_vendor"] : '';
    $show_pagination    = isset($settings["show_pagination"]) ? $settings["show_pagination"] : 'yes';
    $no_desc_class      = ($wpc_show_desc != 'yes') ? 'wpc-no-desc' : '';
    $column_desktop     = $settings['wpc_menu_col'];
    $column_tablet      = isset($settings['wpc_menu_col_tablet']) ? $settings['wpc_menu_col_tablet'] : 2;
    $column_mobile      = isset($settings['wpc_menu_col_mobile']) ? $settings['wpc_menu_col_mobile'] : 1;

    apply_filters( 'cafetics/elementor/control/search_data' , $settings , $unique_id , 'wpc-menus-list' );

    $current_page = isset( $settings['_page'] ) ? max( 1, (int) $settings['_page'] ) : 1;

    $food_list_args = array(
        'post_type'     => 'product',
        'no_of_product' => $wpc_menu_count,
        'wpc_cat'       => $wpc_menu_cat,
        'order'         => $wpc_menu_order,
        'page'          => $current_page,
    );

    $selected_location = wpc_selected_location_id();
    if ( ! empty( $selected_location ) ) {
        $food_list_args['wpc_location'] = $selected_location;
    }

    $page_result = Wpc_Utilities::product_query_with_pagination( $food_list_args );
    $products    = $page_result['products'];
    $total_pages = $page_result['total_pages'];

    $wpc_menu_settings = $settings;
    unset( $wpc_menu_settings['_page'] );
    ?>
    <div class="wpc-nav-shortcode main_wrapper_<?php echo esc_attr($unique_id .' '. $no_desc_class)?>" data-id="<?php echo esc_attr($unique_id)?>">
        <div class="wpc-paginated-products" data-shortcode="food_menu_list" data-current="<?php echo esc_attr( $current_page ); ?>" data-product_data="<?php echo esc_attr( wp_json_encode( $wpc_menu_settings ) ); ?>">
            <div class="wpc-paginated-products-body list_template_<?php echo esc_attr($unique_id) ?> wpc-nav-shortcode wpc-widget-wrapper">
                <?php include wpcafe()->plugin_directory . "/widgets/wpc-menus-list/style/{$style}.php"; ?>
            </div>
            <?php
            if ( 'yes' === $show_pagination ) {
                echo Wpc_Utilities::render_menu_pagination( $current_page, $total_pages ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            ?>
        </div>
    </div>
    <?php
    return;