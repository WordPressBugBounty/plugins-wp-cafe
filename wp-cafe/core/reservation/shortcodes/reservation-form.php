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
