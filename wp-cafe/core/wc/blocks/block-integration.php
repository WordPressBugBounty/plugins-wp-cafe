<?php
namespace WpCafe\Wc\Blocks;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Block Integration
 *
 * Hooks the WPCafe checkout-block JS bundle into WooCommerce's Blocks
 * IntegrationRegistry. Exposes runtime data through the
 * `wpc-checkout-blocks_data` settings key (read in JS via
 * `getSetting( 'wpc-checkout-blocks_data' )`).
 */
class Block_Integration implements IntegrationInterface {

    const HANDLE = 'wpc-checkout-blocks';

    /**
     * Integration name.
     *
     * @return string
     */
    public function get_name() {
        return 'wpc-checkout-blocks';
    }

    /**
     * Register the JS bundle.
     *
     * @return void
     */
    public function initialize() {
        $script_url = wpcafe()->assets_url . '/build/js/wc-checkout-blocks.js';
        $asset_path = wpcafe()->plugin_directory . '/assets/build/js/wc-checkout-blocks.asset.php';

        $fallback_deps = [ 'wc-blocks-checkout', 'wc-settings', 'wp-element', 'wp-i18n', 'wp-data', 'wp-components' ];
        $deps = $fallback_deps;
        $ver  = defined( 'WPCAFE_VERSION' ) ? WPCAFE_VERSION : false;

        if ( file_exists( $asset_path ) ) {
            $asset = include $asset_path;
            if ( ! empty( $asset['dependencies'] ) ) {
                $deps = array_values( array_unique( array_merge( $fallback_deps, (array) $asset['dependencies'] ) ) );
            }
            if ( ! empty( $asset['version'] ) ) {
                $ver = $asset['version'];
            }
        }

        wp_register_script( self::HANDLE, $script_url, $deps, $ver, true );
    }

    /**
     * Frontend script handles.
     *
     * @return string[]
     */
    public function get_script_handles() {
        return [ self::HANDLE ];
    }

    /**
     * Editor script handles. Reuses the frontend bundle.
     *
     * @return string[]
     */
    public function get_editor_script_handles() {
        return [ self::HANDLE ];
    }

    /**
     * Data exposed to JS via wcSettings.
     *
     * @return array
     */
    public function get_script_data() {
        return [
            'rest_url'                  => esc_url_raw( rest_url( 'wpcafe/v2/locations' ) ),
            'nonce'                     => wp_create_nonce( 'wp_rest' ),
            'reservation_data'          => $this->get_reservation_data_from_session(),
            'show_reservation_end_time' => $this->show_reservation_end_time(),
            'time_format'               => get_option( 'time_format', 'H:i' ),
            'discard_nonce'             => wp_create_nonce( 'wpc_discard_reservation' ),
            'ajax_url'                  => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
            'selected_location_id'      => function_exists( 'wpc_selected_location_id' ) ? wpc_selected_location_id() : null,
            'location_module'           => function_exists( 'wpc_is_module_enable' ) ? (bool) wpc_is_module_enable( 'location' ) : false,
            'tipping_module'            => function_exists( 'wpc_is_module_enable' ) ? (bool) wpc_is_module_enable( 'tipping' ) : false,
            'tipping_settings'          => $this->get_tipping_settings(),
            'tipping_current'           => $this->get_tipping_current_from_session(),
            'currency_symbol'           => function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol() ) : '',
        ];
    }

    /**
     * Read tipping settings via the shared wpc_get_option helper.
     *
     * @return array
     */
    private function get_tipping_settings() {
        if ( ! function_exists( 'wpc_get_option' ) ) {
            return [
                'calculation_method'    => '',
                'tip_options'           => [],
                'enable_custom_tipping' => false,
                'custom_tipping_label'  => __( 'Custom Tip', 'wp-cafe' ),
            ];
        }

        $raw_options = wpc_get_option( 'tip_options', [] );
        $tip_options = [];
        if ( is_array( $raw_options ) ) {
            foreach ( $raw_options as $value ) {
                if ( is_numeric( $value ) ) {
                    $tip_options[] = floatval( $value );
                }
            }
        }

        return [
            'calculation_method'    => (string) wpc_get_option( 'tipping_calculation_method', '' ),
            'tip_options'           => $tip_options,
            'enable_custom_tipping' => (bool) wpc_get_option( 'enable_custom_tipping', false ),
            'custom_tipping_label'  => (string) wpc_get_option( 'custom_tipping_label', __( 'Custom Tip', 'wp-cafe' ) ),
        ];
    }

    /**
     * Snapshot the WC session tip so the block can re-hydrate the active
     * button on reload.
     *
     * @return array|null
     */
    private function get_tipping_current_from_session() {
        if ( ! function_exists( 'WC' ) || ! WC()->session ) {
            return null;
        }

        $tip = WC()->session->get( 'wpc_pro_tip' );
        if ( empty( $tip ) || ! is_array( $tip ) || empty( $tip['tip_added'] ) ) {
            return null;
        }

        return [
            'tip_selected_type' => isset( $tip['tip_selected_type'] ) ? (string) $tip['tip_selected_type'] : '',
            'tip_amount'        => isset( $tip['tip_amount'] ) ? floatval( $tip['tip_amount'] ) : 0,
        ];
    }

    /**
     * Pull the reservation payload out of the WC session.
     *
     * @return array|null
     */
    private function get_reservation_data_from_session() {
        if ( ! function_exists( 'WC' ) || ! WC()->session ) {
            return null;
        }

        $data = WC()->session->get( 'wpc_reservation_data' );
        if ( empty( $data ) || ! is_array( $data ) ) {
            return null;
        }

        $time_keys = [ 'start_time', 'end_time' ];
        $format    = get_option( 'time_format', 'H:i' );
        foreach ( $time_keys as $key ) {
            if ( ! empty( $data[ $key ] ) && is_numeric( $data[ $key ] ) ) {
                $data[ $key . '_formatted' ] = gmdate( $format, (int) $data[ $key ] );
            }
        }

        return $data;
    }

    /**
     * Reads the reservation form customisation to decide whether the end-time
     * row should render. Mirrors templates/reservation/reservation-view.php.
     *
     * @return bool
     */
    private function show_reservation_end_time() {
        if ( ! function_exists( 'wpc_get_option' ) ) {
            return false;
        }

        $form_customization = wpc_get_option( 'reservation_form_customization', [] );
        if ( empty( $form_customization ) || ! is_array( $form_customization ) ) {
            return false;
        }

        foreach ( $form_customization as $step ) {
            if ( empty( $step['fields'] ) || ! is_array( $step['fields'] ) ) {
                continue;
            }

            foreach ( $step['fields'] as $field ) {
                if ( empty( $field['id'] ) ) {
                    continue;
                }

                if ( ( 'to_time' === $field['id'] || 'end_time' === $field['id'] ) && ! empty( $field['visible'] ) ) {
                    return true;
                }
            }
        }

        return false;
    }
}
