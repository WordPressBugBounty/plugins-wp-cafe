<?php
namespace WpCafe\Wc\Blocks;

defined( 'ABSPATH' ) || exit;

use WpCafe\Contracts\Hookable_Service_Contract;

/**
 * Block Service
 *
 * Registers the IntegrationInterface implementation with WooCommerce's
 * checkout block registry, and wires the Store API extension that persists
 * block data into the order on submission.
 */
class Block_Service implements Hookable_Service_Contract {

    /**
     * Register hooks.
     *
     * @return void
     */
    public function register() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        add_action(
            'woocommerce_blocks_checkout_block_registration',
            function ( $integration_registry ) {
                $integration_registry->register( new Block_Integration() );
            },
            10,
            1
        );

        // We are already on init priority 20 (Base_Service_Provider::boot
        // hooks register() there). Adding another init/20 hook from inside
        // that callback won't fire in the same do_action loop, so call the
        // registrar directly.
        $this->register_blocks();
        add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_bundle' ] );

        ( new Store_Api_Extension() )->register();
    }

    /**
     * Force-enqueue the checkout-blocks bundle on every block editor screen so
     * `registerBlockType` runs in time for the inserter to surface our blocks.
     * Without this, the script only loads when an instance already exists in
     * the post — chicken-and-egg for first-time insertion.
     *
     * @return void
     */
    public function enqueue_editor_bundle() {
        $handle = Block_Integration::HANDLE;

        if ( ! wp_script_is( $handle, 'registered' ) ) {
            $asset_path = wpcafe()->plugin_directory . '/assets/build/js/wc-checkout-blocks.asset.php';
            $fallback   = [ 'wc-blocks-checkout', 'wc-settings', 'wp-element', 'wp-i18n', 'wp-data', 'wp-blocks', 'wp-block-editor', 'wp-components' ];
            $args       = self::resolve_script_args( $asset_path, $fallback );
            wp_register_script( $handle, wpcafe()->assets_url . '/build/js/wc-checkout-blocks.js', $args['deps'], $args['ver'], true );
        }

        wp_enqueue_script( $handle );
    }

    /**
     * Register the editor blocks server-side so the parser keeps them in the
     * checkout post content. Without this, custom checkout-block names are
     * stripped at render time and WC's force-render appends them at the end.
     *
     * @return void
     */
    public function register_blocks() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        $handle    = Block_Integration::HANDLE;
        $script_ok = wp_script_is( $handle, 'registered' );

        if ( ! $script_ok ) {
            $asset_path = wpcafe()->plugin_directory . '/assets/build/js/wc-checkout-blocks.asset.php';
            $fallback   = [ 'wc-blocks-checkout', 'wc-settings', 'wp-element', 'wp-i18n', 'wp-data', 'wp-blocks', 'wp-block-editor', 'wp-components' ];
            $args       = self::resolve_script_args( $asset_path, $fallback );
            wp_register_script( $handle, wpcafe()->assets_url . '/build/js/wc-checkout-blocks.js', $args['deps'], $args['ver'], true );
        }

        $base = wpcafe()->plugin_directory . '/assets/src/wc-checkout-blocks';

        // Location selection: WC checkout-block (parent-constrained, hydration anchor only).
        $location_path = $base . '/location-selection';
        if ( file_exists( $location_path . '/block.json' ) ) {
            register_block_type(
                $location_path,
                [
                    'editor_script_handles' => [ $handle ],
                    'render_callback'       => function ( $attributes, $content, $block ) {
                        return '<div data-block-name="' . esc_attr( $block->name ) . '"></div>';
                    },
                ]
            );
        }

        // Reservation details: standalone block — placeable anywhere. Renders
        // the same template used by the classic-checkout
        // `woocommerce_before_order_notes` handler.
        $reservation_path = $base . '/reservation-details';
        if ( file_exists( $reservation_path . '/block.json' ) ) {
            register_block_type(
                $reservation_path,
                [
                    'editor_script_handles' => [ $handle ],
                    'render_callback'       => [ $this, 'render_reservation_details_block' ],
                ]
            );
        }
    }

    /**
     * Output the same reservation summary classic checkout shows, driven by
     * `WC()->session->get( 'wpc_reservation_data' )`. Returns an empty string
     * when no reservation is in session so the block self-hides on regular
     * pages without churn.
     *
     * @return string
     */
    public function render_reservation_details_block() {
        if ( ! function_exists( 'WC' ) || ! WC()->session ) {
            return '';
        }

        $reservation_data = WC()->session->get( 'wpc_reservation_data' );
        if ( empty( $reservation_data ) || ! is_array( $reservation_data ) ) {
            return '';
        }

        $override      = locate_template( 'wpcafe/reservation/reservation-view.php' );
        $template_path = $override ?: wpcafe()->template_directory . '/reservation/reservation-view.php';
        if ( ! file_exists( $template_path ) ) {
            return '';
        }

        ob_start();
        include $template_path;
        return (string) ob_get_clean();
    }

    private static function resolve_script_args( string $asset_path, array $fallback_deps ): array {
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

        return [ 'deps' => $deps, 'ver' => $ver ];
    }
}
