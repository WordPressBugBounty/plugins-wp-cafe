<?php
/**
 * Location Selector Popup
 *
 * @package WP Cafe
 **/
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals -- template scope; locally-extracted variables and third-party (Elementor) hook names.

?>
<div id="loader" style="display: none;">
  <div class="spinner"></div> <!-- You can style this or use an SVG/CSS spinner -->
</div>

<div id="wpc-location-selector-modal" class="wpc-modal-overlay">
    <div class="wpc-modal-box">
        <?php if ( ! wpc_get_option('require_location') ): ?>
        <span class="wpc-modal-close">&times;</span>
        <?php endif; ?>
        <h3><?php esc_html_e( 'Select Location', 'wp-cafe' ); ?></h3>
        <select id="wpc-locationSelect" class="wpc-modal-select" name="wpc-location">
            <option value="">-- Select Location --</option>
            <?php if ( $locations ): ?>
            <?php foreach ( $locations as $location ): ?>
                <option value="<?php echo esc_attr( $location->term_id ); ?>" <?php echo $location->term_id == $selected_location_id ? 'selected' : '' ?>><?php echo esc_html( $location->restaurant_name ); ?></option>
            <?php endforeach; ?>
            <?php endif; ?>
        </select>

        <button id="wpc-saveLocation" class="wpc-modal-save-btn"><?php esc_attr_e( 'Ok', 'wp-cafe' ); ?>
                <span class="wpc-btn-loader" style="display: none;">
                    <span class="wpc-spinner"></span>
                </span>
        </button>
    </div>
</div>
