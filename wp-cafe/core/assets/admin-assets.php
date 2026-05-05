<?php
namespace WpCafe\Assets;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Manage all admin scripts and styles
 */
class Admin_Assets extends Base_Assets {
    /**
     * Register single service
     *
     * @return  void
     */
    public function register() {
        add_action( 'admin_enqueue_scripts',  [$this, 'register_styles_scripts'] );
        add_action( 'admin_enqueue_scripts',  [$this, 'enqueue'] );
    }

    /**
     * Enqueue scripts and styles
     *
     * @return  void
     */
    public function enqueue( $top ) {
        if ( 'toplevel_page_wpcafe' !== $top ) {
            return;
        }

        wp_enqueue_style( 'wpc-admin' );

        wp_enqueue_media();

        wp_enqueue_style( 'wpcafe-admin-style' );

        wp_enqueue_script( 'wpcafe-dashboard-scripts' );

        // Enqueue beacon livechat script
        if ( ! $this->is_aisentic_integrated() ) {
            wp_enqueue_script( 'wpcafe-beacon-livechat' );
        }

        wp_localize_script( 'wpcafe-dashboard-scripts', 'wpCafe', Localize::get_admin() );

        wp_set_script_translations(
            'wpcafe-dashboard-scripts',
            'wp-cafe',
            wpcafe()->text_domain_directory
        );

        wp_localize_script(
            'wpcafe-dashboard-scripts',
            'wpcafeData',
            [
                'publicPath' => plugins_url( '../../build/', __FILE__ ),
            ]
        );

        $this->enqueue_i18n_loader();
  
    }

    /**
     * Skip beacon when Aisentic plugin is active and has a WP Cafe integration entry.
     */
    private function is_aisentic_integrated(): bool {
        if ( ! class_exists( 'Aisentic\Init' ) ) {
            return false;
        }

        $settings = get_option( 'aisentic_integration_settings', [] );

        return isset( $settings['wpcafe']['status'] ) && 'connected' === $settings['wpcafe']['status'];
    }

    /**
     * Get all scripts
     *
     * @return  array List register scripts
     */
    public function get_scripts() {
        $scripts = [
            'wpcafe-i18n' => [
                'src'       => wpcafe()->assets_url . '/build/js/i18n-loader.js',
                'deps'      => [],
                'in_footer' => true,
            ],
            'wpcafe-dashboard-scripts'     => [
                'src'       => wpcafe()->assets_url . '/build/js/dashboard.js',
                'deps'      => [
                    'wp-api-fetch',
                    'wp-data',
                    'wp-element',
                    'wp-i18n'
                ],
                'in_footer' => true,
            ],
            'wpcafe-migration-notice' => [
                'src'       => wpcafe()->assets_url . '/js/migration-notice.js',
                'deps'      => ['jquery'],
                'in_footer' => true,
            ],
            'wpcafe-beacon-livechat' => [
                'src'       => wpcafe()->assets_url . '/js/beacon-livechat.js',
                'deps'      => [],
                'in_footer' => true,
            ],
        ];

        $scripts =  apply_filters( 'wpcafe_admin_scripts', $scripts );

        return $scripts;
    }

    /**
     * List of register styles
     *
     * @return  array
     */
    public function get_styles() {
        $styles = [
            'wpcafe-admin-style'    => [
                'src' => wpcafe()->assets_url . '/build/css/admin.css',
            ],
            'wpc-admin' => [
                'src' => wpcafe()->assets_url . '/css/wpc-admin.css',
            ],
        ];

        return apply_filters( 'wpcafe_admin_styles', $styles );
    }
}