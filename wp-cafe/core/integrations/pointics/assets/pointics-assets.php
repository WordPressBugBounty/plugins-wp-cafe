<?php
namespace WpCafe\Integrations\Pointics;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;

/**
 * Registers the three Pointics bundles through WP Cafe's own asset lists, so
 * their .asset.php dependencies are merged like every other WP Cafe script.
 *
 * @package WpCafe/Integrations/Pointics
 */
class Pointics_Assets implements Hookable_Service_Contract {
    const RULE_EDITOR  = 'wpcafe-pointics-rule-editor';
    const BOOKING_FORM = 'wpcafe-pointics-reservation-form';
    const ADMIN_BLOCK  = 'wpcafe-pointics-admin-booking';
    /** Pointics' points card for the booking form, registered by Pointics itself. */
    const POINTS_CARD  = 'pointics-wpcafe-booking';

    /**
     * @return void
     */
    public function register() {
        add_filter( 'wpcafe_frontend_scripts', [ $this, 'frontend_scripts' ] );
        add_filter( 'wpcafe_frontend_styles', [ $this, 'frontend_styles' ] );
        add_filter( 'wpcafe_admin_scripts', [ $this, 'admin_scripts' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'rule_editor' ] );
    }

    /**
     * @param array $scripts WP Cafe frontend scripts.
     * @return array
     */
    public function frontend_scripts( $scripts ) {
        $scripts[ self::BOOKING_FORM ] = $this->script( 'pointics-reservation-form' );
        $scripts[ self::ADMIN_BLOCK ]  = $this->script( 'pointics-admin-booking' );

        // Pointics registers the card for signed-in customers only, and an
        // unregistered dependency would stop the booking bundle loading at all.
        if ( wp_script_is( self::POINTS_CARD, 'registered' ) ) {
            $scripts[ self::BOOKING_FORM ]['deps'][] = self::POINTS_CARD;
        }

        $scripts = $this->add_dependency( $scripts, 'wpcafe-frontend-scripts', self::BOOKING_FORM );

        return $this->add_dependency( $scripts, 'wpcafe-restaurant-management-scripts', self::ADMIN_BLOCK );
    }

    /**
     * The card's stylesheet travels with the booking form's own.
     *
     * @param array $styles WP Cafe frontend styles.
     * @return array
     */
    public function frontend_styles( $styles ) {
        if ( isset( $styles['wpcafe-frontend-style'] ) && wp_style_is( self::POINTS_CARD, 'registered' ) ) {
            $styles['wpcafe-frontend-style']['deps']   = (array) ( $styles['wpcafe-frontend-style']['deps'] ?? [] );
            $styles['wpcafe-frontend-style']['deps'][] = self::POINTS_CARD;
        }

        return $styles;
    }

    /**
     * @param array $scripts WP Cafe admin scripts.
     * @return array
     */
    public function admin_scripts( $scripts ) {
        $scripts[ self::ADMIN_BLOCK ] = $this->script( 'pointics-admin-booking' );

        return $this->add_dependency( $scripts, 'wpcafe-dashboard-scripts', self::ADMIN_BLOCK );
    }

    /**
     * Rule editor section, on Pointics' own admin screen only.
     *
     * @param string $hook Admin screen hook.
     * @return void
     */
    public function rule_editor( $hook ) {
        if ( 'toplevel_page_pointics' !== $hook ) {
            return;
        }

        $asset_file = WPCAFE_DIR . '/assets/build/js/pointics-rule-editor.asset.php';
        $asset      = file_exists( $asset_file ) ? include $asset_file : [ 'dependencies' => [ 'wp-hooks', 'wp-element', 'wp-i18n', 'wp-data' ], 'version' => WPCAFE_VERSION ];

        // No stylesheet: the one line this bundle adds borrows Pointics' own
        // utility classes, which are already on its rule editor screen.
        wp_enqueue_script( self::RULE_EDITOR, wpcafe()->assets_url . '/build/js/pointics-rule-editor.js', $asset['dependencies'], $asset['version'], true );
        wp_set_script_translations( self::RULE_EDITOR, 'wp-cafe', wpcafe()->text_domain_directory );
    }

    /**
     * @param string $name Build file name without extension.
     * @return array
     */
    private function script( $name ) {
        return [
            'src'       => wpcafe()->assets_url . '/build/js/' . $name . '.js',
            'deps'      => [],
            'in_footer' => true,
        ];
    }

    /**
     * @param array  $scripts Script list.
     * @param string $host    Host handle.
     * @param string $handle  Handle the host must load after.
     * @return array
     */
    private function add_dependency( array $scripts, $host, $handle ) {
        if ( isset( $scripts[ $host ] ) ) {
            $scripts[ $host ]['deps']   = (array) ( $scripts[ $host ]['deps'] ?? [] );
            $scripts[ $host ]['deps'][] = $handle;
        }

        return $scripts;
    }
}
