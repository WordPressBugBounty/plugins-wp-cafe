<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals -- template scope; locally-extracted variables and third-party (Elementor) hook names.

/**
 * Reservation Details Template
 *
 * Displays reservation information on checkout page
 *
 * @package WpCafe
 * @since 1.0.0
 */

use WpCafe\Settings;

if ( empty( $reservation_data ) || ! is_array( $reservation_data ) ) {
    return;
}

// Get settings for colors
$settings = Settings::get();
$primary_color = isset( $settings['primary_color'] ) && $settings['primary_color'] ? $settings['primary_color'] : '#f00f0fff';
$secondary_color = isset( $settings['secondary_color'] ) && $settings['secondary_color'] ? $settings['secondary_color'] : '#f5f5f5';

// Register and enqueue the discard reservation stylesheet
wp_register_style( 'wpc-discard-reservation', wpcafe()->assets_url . '/css/discard-reservation.css', [], wpcafe()->version );
wp_enqueue_style( 'wpc-discard-reservation' );

// Register and enqueue the discard reservation script
wp_register_script( 'wpc-discard-reservation', wpcafe()->assets_url . '/js/discard-reservation.js', [ 'wp-i18n' ], wpcafe()->version, true );
wp_enqueue_script( 'wpc-discard-reservation' );

// Localize script with color settings
wp_localize_script( 'wpc-discard-reservation', 'wpcDiscardReservationColors', [
    'primary' => esc_attr( $primary_color ),
    'secondary' => esc_attr( $secondary_color ),
] );

// Get show_reservation_end_time from reservation form customization (React form: end_time; legacy: to_time).
$show_reservation_end_time = 'off';
$form_customization = wpc_get_option('reservation_form_customization', []);
if ( ! empty($form_customization) && is_array($form_customization) ) {
    foreach ($form_customization as $step) {
        if ( empty($step['fields']) || ! is_array($step['fields']) ) {
            continue;
        }
        foreach ($step['fields'] as $field ) {
            if ( empty($field['id']) ) {
                continue;
            }
            if ( 'to_time' === $field['id'] || 'end_time' === $field['id'] ) {
                if ( ! empty($field['visible']) ) {
                    $show_reservation_end_time = 'on';
                    break 2;
                }
            }
        }
    }
}
?>

<div class="wpc-reservation-info" style="background:#f9f9f9;padding:15px;margin-bottom:20px;border-radius:6px;">
    <h3><?php echo esc_html__( 'Reservation Details', 'wp-cafe' ); ?></h3>

    <?php if ( ! empty( $reservation_data['name'] ) ) : ?>
        <p class="wpc-reservation-field wpc-reservation-name">
            <strong class="wpc-reservation-label"><?php echo esc_html__( 'Name', 'wp-cafe' ); ?> : </strong>
            <span class="wpc-reservation-value"><?php echo esc_html( $reservation_data['name'] ) ; ?></span>
        </p>
    <?php endif; ?>

    <?php if ( ! empty( $reservation_data['email'] ) ) : ?>
        <p class="wpc-reservation-field wpc-reservation-email">
            <strong class="wpc-reservation-label"><?php echo esc_html__( 'Email', 'wp-cafe' ); ?> : </strong>
            <span class="wpc-reservation-value"><?php echo esc_html( $reservation_data['email'] ); ?></span>
        </p>
    <?php endif; ?>

    <?php if ( ! empty( $reservation_data['phone'] ) ) : ?>
        <p class="wpc-reservation-field wpc-reservation-phone">
            <strong class="wpc-reservation-label"><?php echo esc_html__( 'Phone', 'wp-cafe' ); ?> : </strong>
            <span class="wpc-reservation-value"><?php echo esc_html( $reservation_data['phone'] ); ?></span>
        </p>
    <?php endif; ?>

    <?php if ( ! empty( $reservation_data['reservation_date'] ) ) : ?>
        <p class="wpc-reservation-field wpc-reservation-date">
            <strong class="wpc-reservation-label"><?php echo esc_html__( 'Booking date', 'wp-cafe' ); ?> : </strong>
            <span class="wpc-reservation-value"><?php echo esc_html( $reservation_data['reservation_date'] ); ?></span>
        </p>
    <?php endif; ?>

    <?php if ( ! empty( $reservation_data['total_guest'] ) ) : ?>
        <p class="wpc-reservation-field wpc-reservation-guest">
            <strong class="wpc-reservation-label"><?php echo esc_html__( 'Guest', 'wp-cafe' ); ?> : </strong>
            <span class="wpc-reservation-value"><?php echo esc_html( $reservation_data['total_guest'] ); ?></span>
        </p>
    <?php endif; ?>

    <?php if ( ! empty( $reservation_data['start_time'] ) ) : ?>
        <p class="wpc-reservation-field wpc-reservation-start-time">
            <strong class="wpc-reservation-label"><?php echo esc_html__( 'Start time', 'wp-cafe' ); ?> : </strong>
            <span class="wpc-reservation-value"><?php echo esc_html( gmdate( get_option( 'time_format' ), $reservation_data['start_time'] ) ); ?></span>
        </p>
    <?php endif; ?>

    <?php if ( ! empty( $reservation_data['end_time'] ) && $show_reservation_end_time == 'on' ) : ?>
        <p class="wpc-reservation-field wpc-reservation-end-time">
            <strong class="wpc-reservation-label"><?php echo esc_html__( 'End time', 'wp-cafe' ); ?> : </strong>
            <span class="wpc-reservation-value"><?php echo esc_html( gmdate( get_option( 'time_format' ), $reservation_data['end_time'] ) ); ?></span>
        </p>
    <?php endif; ?>

    <?php if ( ! empty( $reservation_data['notes'] ) ) : ?>
        <p class="wpc-reservation-field wpc-reservation-message">
            <strong class="wpc-reservation-label"><?php echo esc_html__( 'Message', 'wp-cafe' ); ?> : </strong>
            <span class="wpc-reservation-value"><?php echo esc_html( $reservation_data['notes'] ); ?></span>
        </p>
    <?php endif; ?>

    <?php if ( ! empty( $reservation_data['branch_name'] ) ) : ?>
        <p class="wpc-reservation-field wpc-reservation-branch">
            <strong class="wpc-reservation-label"><?php echo esc_html__( 'Branch', 'wp-cafe' ); ?> : </strong>
            <span class="wpc-reservation-value"><?php echo esc_html( $reservation_data['branch_name'] ); ?></span>
        </p>
    <?php endif; ?>

    <?php if ( ! empty( $reservation_data['custom_fields'] ) && is_array( $reservation_data['custom_fields'] ) ) : ?>
        <?php foreach ( $reservation_data['custom_fields'] as $field_id => $field_value ) : ?>
            <?php if ( ! empty( $field_value ) ) : ?>
                <p class="wpc-reservation-field wpc-reservation-custom-field">
                    <strong class="wpc-reservation-label"><?php echo esc_html( $field_id ); ?> : </strong>
                    <span class="wpc-reservation-value"><?php echo esc_html( is_array( $field_value ) ? implode( ', ', $field_value ) : $field_value ); ?></span>
                </p>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="wpc-reservation-actions" style="margin-top: 20px; padding-top: 0;">    
        <button id="wpc-discard-reservation" style="color: <?php echo esc_attr( $primary_color ); ?>;" class="button" data-nonce="<?php echo esc_attr( sanitize_text_field( wp_create_nonce('wpc_discard_reservation') ) ); ?>">
            <?php echo esc_html__( 'Discard Reservation', 'wp-cafe' ); ?>
        </button>
    </div>
</div>