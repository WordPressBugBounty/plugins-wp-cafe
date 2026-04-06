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
        ob_start();

        Wpc_Utilities::select_food_locations_filter($atts);

        return ob_get_clean();
    }
}
