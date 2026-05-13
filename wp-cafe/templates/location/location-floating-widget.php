<?php
/**
 * Floating Location Widget
 *
 * @package WP Cafe
 *
 * @var \WpCafe\Models\Location_Model|null $selected_location
 * @var string                              $primary_color
 **/
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals -- template scope.

$has_location = ! empty( $selected_location );
$label        = $has_location
    ? $selected_location->restaurant_name
    : __( 'Select location', 'wp-cafe' );
$address_raw  = $has_location ? $selected_location->location : '';
$sublabel     = is_array( $address_raw ) ? ( $address_raw['address'] ?? '' ) : (string) $address_raw;
if ( '' === $sublabel ) {
    $sublabel = __( 'Tap to choose a branch', 'wp-cafe' );
}
?>
<div
    class="wpc-floating-location"
    data-state="<?php echo $has_location ? 'selected' : 'empty'; ?>"
    style="--wpc-primary: <?php echo esc_attr( $primary_color ); ?>;"
>
    <button
        type="button"
        class="wpc-floating-location__btn wpc-location__address-button"
        wpc-store-popup-open="1"
        aria-label="<?php echo esc_attr( $has_location ? __( 'Change location', 'wp-cafe' ) : __( 'Select location', 'wp-cafe' ) ); ?>"
    >
        <span class="wpc-floating-location__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                <circle cx="12" cy="10" r="3"/>
            </svg>
        </span>
        <span class="wpc-floating-location__text">
            <span class="wpc-floating-location__hint"><?php echo esc_html( $has_location ? __( 'Restaurant', 'wp-cafe' ) : __( 'Your branch', 'wp-cafe' ) ); ?></span>
            <span class="wpc-floating-location__label"><?php echo esc_html( $label ); ?></span>
        </span>
        <span class="wpc-floating-location__chevron" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </span>
    </button>
</div>
