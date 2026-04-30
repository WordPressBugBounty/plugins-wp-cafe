<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals -- template scope; locally-extracted variables and third-party (Elementor) hook names.

/**
 * Location Selection
 *
 * @package WP Cafe
 **/

use WpCafe\Models\Location_Model;
$selected_loction_id = wpc_selected_location_id();
$location = Location_Model::find( $selected_loction_id );
$primary_color = wpc_get_option('primary_color') ?: '#c82333';
?>
<tr>
    <th>
        <?php esc_html_e( 'Location', 'wp-cafe' ); ?>
    </th>
    <td>
        <div class="wpc-checkout-store-selector">
            <div class="wpc-location" data-type="pickup">
                <div class="wpc-location__icon-wrap" wpc-store-popup-open="1">
                    <div class="wpc-location__icon">
                    </div>
                </div>
                <div class="wpc-location__address ">
                    <?php if ( $location ) : ?>
                        <p class="wpc-location__address-postcode"><?php echo esc_html( $location->restaurant_name ); ?></p>
                        <a class="wpc-location__address-button" wpc-store-popup-open="1" href=""><?php esc_html_e( 'Edit Location', 'wp-cafe' ); ?></a>
                    <?php wp_nonce_field( 'wpc_selected_location', 'wpc_selected_location' ) ?>
                    <?php else : ?>
                        <a class="wpc-location__address-button" style=" color: <?php echo esc_attr( $primary_color ); ?>;"wpc-store-popup-open="1" href=""><?php esc_html_e( 'Find Location', 'wp-cafe' ); ?></a>
                    <?php wp_nonce_field( 'wpc_selected_location', 'wpc_selected_location' ) ?>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </td>
</tr>
