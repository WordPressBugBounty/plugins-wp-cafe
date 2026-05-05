<?php
/**
 * Plugin Name:        WPCafe - Restaurant Menu, Online Food Ordering & Table Booking System
 * Plugin URI:         https://product.themewinter.com/wpcafe
 * Description:        WordPress Restaurant solution plugin to launch Restaurant Websites.
 * Version:            3.0.10
 * Author:             Themewinter
 * Author URI:         http://themewinter.com/
 * License:            GPL-2.0+
 * License URI:        http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:        wp-cafe
 * Domain Path:       /languages
 * Requires at least: 6.2
 * Requires PHP:      7.4
 */

defined( 'ABSPATH' ) || exit;

use WpCafe\Init;
use WpCafe\Container\Container;
use WpCafe\Providers\Global_Service_Provider;
use WpCafe\Compatibility_Handler;

require_once __DIR__ . '/vendor/autoload.php';


// Define constant for the Plugin file.
define( 'WPCAFE_PLUGIN_NAME', 'WPCafe' );
defined( 'WPCAFE_FILE' ) || define( 'WPCAFE_FILE', __FILE__ );
defined( 'WPCAFE_DIR' ) || define( 'WPCAFE_DIR', __DIR__ );
defined( 'WPCAFE_VERSION' ) || define( 'WPCAFE_VERSION', '3.0.10' );

add_action( 'init', [ Compatibility_Handler::class, 'init' ] );
add_action( 'init', [ Compatibility_Handler::class, 'register_hooks' ] );

global $wpcafe_container;

$wpcafe_container = new Container();

$wpcafe_container->add_service_provider( 'global', Global_Service_Provider::class );

/**
 * wpcafe container
 *
 * @return  Container
 */
function wpcafe_container() {
    global $wpcafe_container;

    return $wpcafe_container;
}

/**
 * Main plugin initialization
 *
 * @return Wpcafe
 */
function wpcafe() {
    return Init::instance();
}

// Kick-off the plugin.
wpcafe();

/**
 * Allow SVG uploads by adding the SVG mime type.
 * Note: SVGs can contain scripts. Consider using a sanitizer if accepting user uploads.
 */
if ( ! function_exists( 'wpcafe_allow_svg_uploads' ) ) {
    function wpcafe_allow_svg_uploads( $mimes ) {
        if ( current_user_can( 'manage_options' ) ) {
            $mimes['svg'] = 'image/svg+xml';
        }
        return $mimes;
    }
}
add_filter( 'upload_mimes', 'wpcafe_allow_svg_uploads' );

/**
 * Ensure WordPress correctly recognizes SVG file type and mime on upload.
 * This does NOT sanitize SVG content; it only fixes detection.
 */
if ( ! function_exists( 'wpcafe_sanitize_svg' ) ) {
    function wpcafe_sanitize_svg( $data, $file, $filename, $mimes ) {
        $ext = pathinfo( $filename, PATHINFO_EXTENSION );
        if ( strtolower( $ext ) === 'svg' ) {
            $data['ext']  = 'svg';
            $data['type'] = 'image/svg+xml';
        }
        return $data;
    }
}
add_filter( 'wp_check_filetype_and_ext', 'wpcafe_sanitize_svg', 10, 4 );

/**
 * Sanitize SVG content after upload to prevent XSS.
 * Strips dangerous tags, event handlers (quoted and unquoted), javascript:/data: URIs,
 * <foreignObject>, <style> blocks, and external href references.
 */
if ( ! function_exists( 'wpcafe_sanitize_svg_on_upload' ) ) {
    function wpcafe_sanitize_svg_on_upload( $upload ) {
        if ( isset( $upload['type'] ) && $upload['type'] === 'image/svg+xml' ) {
            $file = $upload['file'];
            $svg  = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

            // Strip <script> blocks (with or without closing tag).
            $svg = preg_replace( '#<script[\s\S]*?(?:</script>|$)#i', '', $svg );

            // Strip <style> blocks (CSS data exfiltration vector).
            $svg = preg_replace( '#<style[\s\S]*?</style>#i', '', $svg );

            // Strip <foreignObject> blocks (arbitrary HTML embedding).
            $svg = preg_replace( '#<foreignObject[\s\S]*?</foreignObject>#i', '', $svg );

            // Strip on* event attributes — quoted, unquoted, and without value.
            $svg = preg_replace( '/\bon\w+\s*(?:=\s*(?:["\'][^"\']*["\']|[^\s>\/]*))?/i', '', $svg );

            // Strip javascript: and data: URIs in href, xlink:href, src, and action attributes.
            $svg = preg_replace( '/\b(?:href|xlink:href|src|action)\s*=\s*["\']?\s*(?:javascript|data):[^"\'\s>]*/i', '', $svg );

            // Strip all href/xlink:href pointing to external URLs (any scheme) in <use> tags.
            $svg = preg_replace( '#(<use[^>]*\s)(?:xlink:)?href\s*=\s*(["\'])[^"\']*\2#i', '$1', $svg );

            file_put_contents( $file, $svg ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        }
        return $upload;
    }
}
add_filter( 'wp_handle_upload', 'wpcafe_sanitize_svg_on_upload' );

/**
 * Add a "Go Pro" action link on the Plugins page.
 * Hidden when the pro plugin is active.
 */
if ( ! function_exists( 'wpcafe_add_go_pro_action_link' ) ) {
    function wpcafe_add_go_pro_action_link( $links ) {
        if ( function_exists( 'wpcafe_pro' ) ) {
            return $links;
        }

        $go_pro = sprintf(
            '<a href="%1$s" target="_blank" rel="noopener noreferrer" style="color:#e8364c;font-weight:600;">%2$s</a>',
            esc_url( 'https://themewinter.com/wp-cafe/pricing/' ),
            esc_html__( 'Go Pro', 'wp-cafe' )
        );

        $links[] = $go_pro;
        return $links;
    }
}
add_filter( 'plugin_action_links_' . plugin_basename( WPCAFE_FILE ), 'wpcafe_add_go_pro_action_link' );