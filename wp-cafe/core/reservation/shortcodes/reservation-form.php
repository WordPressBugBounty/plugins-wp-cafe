<?php
namespace WpCafe\Reservation\Shortcodes;

use WpCafe\Abstract\Base_Shortcode;

/**
 * Reservation Form Shortcode
 */
class Reservation_Form extends Base_Shortcode {
    /**
     * Shortcode tag name.
     *
     * @return string
     */
    public function tag() {
        return 'wpc_reservation_form';
    }

    /**
     * Render shortcode content.
     *
     * @param array  $atts    Shortcode attributes.
     * @param string $content Shortcode content.
     *
     * @return string
     */
    public function render( $atts = [], $content = null ) {
        // Define default attributes
        $default_atts = [
            'date_selector'    => 'date_picker',
            'reservation_style' => 'style-1',
            'form_display_type' => 'wizard',
            'image_link'       => '',
        ];

        // Parse and merge attributes with defaults
        $atts = shortcode_atts( $default_atts, $atts, $this->tag() );

        wp_enqueue_style( 'wpcafe-frontend-style' );
        wp_enqueue_script( 'wpcafe-frontend-scripts' );

        // The food list loads later over AJAX (get_food_list), so any assets the
        // food shortcode enqueues server-side never reach this page. When the form
        // has a food_menu field, enqueue them up-front:
        //   - wpc-card-core: the card layout CSS (else the cards render unstyled)
        //   - Optiontics frontend: the addon form's styles + live price calculator
        //     (else the addon popup shows but its price never updates)
        if ( ! empty( wpc_get_reservation_food_menu_fields() ) ) {
            wp_enqueue_style( 'wpc-card-core' );
            wp_enqueue_style( 'wpc-popup' );

            if ( function_exists( 'optiontics_enqueue_frontend' ) ) {
                optiontics_enqueue_frontend();
            }
        }

        ob_start();

        ?>
<div class="wpc-reservation-form-root" data-component="wpc-reservation-form"
    data-date-selector="<?php echo esc_attr( $atts['date_selector'] ); ?>"
    data-reservation-style="<?php echo esc_attr( $atts['reservation_style'] ); ?>"
    data-form-display-type="<?php echo esc_attr( $atts['form_display_type'] ); ?>"
    data-image-link="<?php echo esc_url( $atts['image_link'] ); ?>">
    
</div>
<?php
        return ob_get_clean();
    }
}
