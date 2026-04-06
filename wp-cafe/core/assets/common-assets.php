<?php
namespace WpCafe\Assets;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Manage all common scripts and styles
 */
class Common_Assets extends Base_Assets {
    /**
     * Register single service
     *
     * @return  void
     */
    public function register() {
        if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts',  [$this, 'register_styles_scripts'] );
            add_action( 'admin_enqueue_scripts',  [$this, 'enqueue'] );
        } else {
            add_action( 'wp_enqueue_scripts',  [$this, 'register_styles_scripts'] );
            add_action( 'wp_enqueue_scripts',  [$this, 'enqueue'] );
        }
    }

    /**
     * Enqueue scripts and styles
     *
     * @return  void
     */
    public function enqueue() {

    }

    /**
     * Get all scripts
     *
     * @return  array List register scripts
     */
    public function get_scripts(): array {
        $scripts = [];

        return apply_filters( 'wpcafe_common_assets', $scripts );
    }

    /**
     * List of register styles
     *
     * @return  array
     */
    public function get_styles(): array {
        $styles = [];

        return apply_filters( 'wpcafe_common_styles', $styles );
    }
}


