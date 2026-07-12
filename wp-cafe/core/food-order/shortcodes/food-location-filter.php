<?php
namespace WpCafe\FoodOrder\Shortcodes;

use WpCafe\Abstract\Base_Shortcode;
use WpCafe\Utils\Wpc_Utilities;


/**
 * Food Menu Shortcode
 */
class Food_Location_Filter extends Base_Shortcode {
    /**
     * Shortcode tag name
     *
     * @return  string
     */
    public function tag() {
        return 'food_location_filter';
    }

    /**
     * Food menu short render content
     *
     * @param   array  $atts     Shortcode attributes
     * @param   string  $content  content
     *
     * @return  []                [return description]
     */
    public function render($atts = [], $content = null) {
        // FE2: results render WP Cafe product cards (initial + AJAX), so the
        // shared card stylesheet must be present on the page.
        wp_enqueue_style( 'wpc-card-core' );
        wp_enqueue_style( 'wpc-popup' );
        wp_enqueue_style( 'wpc-pagination' );

        ob_start();

        Wpc_Utilities::select_food_locations_filter($atts);

        return ob_get_clean();
    }
}
