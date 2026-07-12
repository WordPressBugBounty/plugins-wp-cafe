<?php
/**
 * Location Selector Popup
 *
 * @package WP Cafe
 *
 * @var \WpCafe\Models\Location_Model[]      $locations
 * @var int|null                              $selected_location_id
 * @var \WpCafe\Models\Location_Model|null   $selected_location
 * @var string                                $primary_color
 **/
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals -- template scope.

$require_location = (bool) wpc_get_option( 'require_location' );
?>
<div id="loader" style="display: none;">
    <div class="spinner"></div>
</div>

<div
    id="wpc-location-selector-modal"
    class="wpc-modal-overlay"
    role="dialog"
    aria-modal="true"
    aria-labelledby="wpc-modal-title"
    style="--wpc-primary: <?php echo esc_attr( $primary_color ); ?>;"
>
    <div class="wpc-modal-box" role="document">
        <header class="wpc-modal-header">
            <div class="wpc-modal-heading">
                <h3 id="wpc-modal-title" class="wpc-modal-title"><?php esc_html_e( 'Choose a location', 'wp-cafe' ); ?></h3>
                <p class="wpc-modal-subtitle"><?php esc_html_e( 'Pick the branch you want to order from.', 'wp-cafe' ); ?></p>
            </div>
            <?php if ( ! $require_location ) : ?>
                <button type="button" class="wpc-modal-close" aria-label="<?php esc_attr_e( 'Close', 'wp-cafe' ); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            <?php endif; ?>
        </header>

        <div class="wpc-modal-search">
            <span class="wpc-modal-search__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </span>
            <input
                type="search"
                class="wpc-modal-search__input wpc-location-search"
                placeholder="<?php esc_attr_e( 'Search by name or area…', 'wp-cafe' ); ?>"
                aria-label="<?php esc_attr_e( 'Search locations', 'wp-cafe' ); ?>"
            />
        </div>

        <select id="wpc-locationSelect" name="wpc-location" hidden>
            <option value=""></option>
            <?php if ( ! empty( $locations ) ) : ?>
                <?php foreach ( $locations as $location ) : ?>
                    <option
                        value="<?php echo esc_attr( $location->term_id ); ?>"
                        <?php selected( $location->term_id, $selected_location_id ); ?>
                    ><?php echo esc_html( $location->restaurant_name ); ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>

        <ul class="wpc-location-list" role="radiogroup" aria-label="<?php esc_attr_e( 'Available locations', 'wp-cafe' ); ?>">
            <?php if ( ! empty( $locations ) ) : ?>
                <?php foreach ( $locations as $location ) :
                    $is_active = ( (int) $location->term_id === (int) $selected_location_id );
                    $address   = is_array( $location->location ) ? ( $location->location['address'] ?? '' ) : (string) ( $location->location ?? '' );
                    $haystack  = strtolower( trim( ( $location->restaurant_name ?? '' ) . ' ' . $address ) );
                ?>
                    <li
                        class="wpc-location-card<?php echo $is_active ? ' is-selected' : ''; ?>"
                        data-location-id="<?php echo esc_attr( $location->term_id ); ?>"
                        data-search="<?php echo esc_attr( $haystack ); ?>"
                        role="radio"
                        tabindex="0"
                        aria-checked="<?php echo $is_active ? 'true' : 'false'; ?>"
                    >
                        <div class="wpc-location-card__thumb">
                            <?php if ( ! empty( $location->location_image ) ) : ?>
                                <img src="<?php echo esc_url( $location->location_image ); ?>" alt="" loading="lazy" />
                            <?php else : ?>
                                <span class="wpc-location-card__thumb-fallback" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="wpc-location-card__body">
                            <span class="wpc-location-card__name"><?php echo esc_html( $location->restaurant_name ); ?></span>
                            <?php if ( ! empty( $address ) ) : ?>
                                <span class="wpc-location-card__address"><?php echo esc_html( $address ); ?></span>
                            <?php endif; ?>
                            <div class="wpc-location-card__chips">
                                <?php if ( ! empty( $location->enable_pickup ) ) : ?>
                                    <span class="wpc-chip"><?php esc_html_e( 'Pickup', 'wp-cafe' ); ?></span>
                                <?php endif; ?>
                                <?php if ( ! empty( $location->enable_delivery ) ) : ?>
                                    <span class="wpc-chip"><?php esc_html_e( 'Delivery', 'wp-cafe' ); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="wpc-location-card__check" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                    </li>
                <?php endforeach; ?>
            <?php else : ?>
                <li class="wpc-location-empty"><?php esc_html_e( 'No locations available yet.', 'wp-cafe' ); ?></li>
            <?php endif; ?>
        </ul>

        <div class="wpc-location-no-results" hidden><?php esc_html_e( 'No matches. Try another search.', 'wp-cafe' ); ?></div>

        <footer class="wpc-modal-footer">
            <?php // Clearing only makes sense when a location exists and one is not mandatory. ?>
            <?php if ( ! $require_location && ! empty( $selected_location_id ) ) : ?>
                <button id="wpc-clearLocation" type="button" class="wpc-modal-clear-btn"><?php esc_html_e( 'Clear location', 'wp-cafe' ); ?></button>
            <?php endif; ?>
            <?php if ( ! $require_location ) : ?>
                <button type="button" class="wpc-modal-cancel-btn wpc-modal-close"><?php esc_html_e( 'Cancel', 'wp-cafe' ); ?></button>
            <?php endif; ?>
            <button id="wpc-saveLocation" type="button" class="wpc-modal-save-btn">
                <span class="wpc-modal-save-btn__label"><?php esc_html_e( 'Confirm location', 'wp-cafe' ); ?></span>
                <span class="wpc-btn-loader" style="display: none;">
                    <span class="wpc-spinner"></span>
                </span>
            </button>
        </footer>
    </div>
</div>
