<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals -- template scope; locally-extracted variables and third-party/public hook names.

use WpCafe\Utils\Wpc_Utilities;
use \WpCafe\Core\Shortcodes\Template_Functions;

//check if woocommerce exists
if (!class_exists('Woocommerce')) { return; }

if( is_array( $food_menu_tabs ) && count( $food_menu_tabs )>0 ){
    
apply_filters( 'cafetics/elementor/control/search_data' , $settings , $unique_id , 'wpc-food-menu-tab' );

$wpc_menu_count = is_array($settings) && isset($settings['wpc_menu_count']) ? $settings['wpc_menu_count'] : 5;
$wpc_show_desc  = is_array($settings) && isset($settings['wpc_show_desc']) ? $settings['wpc_show_desc'] : 'yes';
$wpc_show_vendor  = is_array($settings) && isset($settings['wpc_show_vendor']) ? $settings['wpc_show_vendor'] : 'no';
$show_thumbnail = is_array($settings) && isset($settings['show_thumbnail']) ? $settings['show_thumbnail'] : 'yes';
$title_link_show= is_array($settings) && isset($settings['title_link_show']) ? $settings['title_link_show'] : 'yes';
$show_pagination = is_array($settings) && isset($settings['show_pagination']) ? $settings['show_pagination'] : 'yes';
$class = ($title_link_show=='yes')? '' : 'wpc-no-link';
?>
<div class="wpc-food-tab-wrapper wpc-nav-shortcode main_wrapper_<?php echo esc_attr($unique_id)?>" data-id="<?php echo esc_attr($unique_id);?>">
    
    <?php Template_Functions::render_food_menu_tab_nav( $food_menu_tabs ); ?>
    
    <div class="wpc-tab-content wpc-widget-wrapper">
        <?php
            foreach ($food_menu_tabs as $content_key => $value) {
                if(isset( $value['post_cats'][0] )){
                    $active_class = (($content_key == array_keys($food_menu_tabs)[0]) ? 'tab-active' : ' ');
                    $cat_id = isset($value['post_cats'][0] ) ? intval( $value['post_cats'][0] ) : 0 ;

                    $current_page = 1;
                    if ( isset( $settings['_page_per_cat'][ $cat_id ] ) ) {
                        $current_page = max( 1, (int) $settings['_page_per_cat'][ $cat_id ] );
                    }

                    $food_tab_args = array(
                        'post_type'     => 'product',
                        'no_of_product' => $wpc_menu_count,
                        'wpc_cat'       => $value['post_cats'],
                        'order'         => $wpc_menu_order,
                        'page'          => $current_page,
                    );

                    $selected_location = wpc_selected_location_id();
                    if ( ! empty( $selected_location ) ) {
                        $food_tab_args['wpc_location'] = $selected_location;
                    }

                    $page_result = Wpc_Utilities::product_query_with_pagination( $food_tab_args );
                    $products    = $page_result['products'];
                    $total_pages = $page_result['total_pages'];

                    $tab_product_data = array(
                        'style'             => $style,
                        'no_of_product'     => $wpc_menu_count,
                        'wpc_menu_order'    => $wpc_menu_order,
                        'wpc_cart_button'   => $wpc_cart_button,
                        'wpc_price_show'    => $wpc_price_show,
                        'wpc_show_desc'     => $wpc_show_desc,
                        'product_thumbnail' => $show_thumbnail,
                        'title_link_show'   => $title_link_show,
                        'show_item_status'  => $show_item_status,
                        'wpc_desc_limit'    => $wpc_desc_limit,
                        'wpc_show_vendor'   => $wpc_show_vendor,
                        'show_pagination'   => $show_pagination,
                        'cat_id'            => $cat_id,
                        'post_cats'         => $value['post_cats'],
                    );

                    $menu_tab_args = array(
                        'active_class'      => $active_class,
                        'content_key'       => $content_key,
                        'cat_id'            => $cat_id,
                        'unique_id'         => $unique_id,
                        'products'          => $products,
                        'style'             => $style,
                        'wpc_cart_button'   => $wpc_cart_button,
                        'wpc_price_show'    => $wpc_price_show,
                        'wpc_show_desc'     => $wpc_show_desc,
                        'show_thumbnail'    => $show_thumbnail,
                        'title_link_show'   => $title_link_show,
                        'show_item_status'  => $show_item_status,
                        'show_item_label'   => isset( $show_item_label ) ? $show_item_label : 'no',
                        'wpc_desc_limit'    => $wpc_desc_limit,
                        'wpc_show_vendor'   => $wpc_show_vendor,
                        'wpc_menu_col'      => 6, // Default column setting for pro styles
                        'current_page'      => $current_page,
                        'total_pages'       => $total_pages,
                        'show_pagination'   => $show_pagination,
                        'product_data'      => $tab_product_data,
                    );
                    Template_Functions::render_food_menu_tab_product_block( $menu_tab_args );
                }
            } 
        ?>
    </div><!-- Tab content-->
</div>
<?php
}
return;

