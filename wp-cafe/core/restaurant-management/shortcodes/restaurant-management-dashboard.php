<?php
namespace WpCafe\RestaurantManagement\Shortcodes;

use WpCafe\Abstract\Base_Shortcode;

/**
 * Restaurant Management Dashboard Shortcode
 *
 * Renders staff-only frontend management panel inside a Shadow DOM.
 */
class Restaurant_Management_Dashboard extends Base_Shortcode {
    /**
     * Panel capabilities allowed to access the dashboard.
     *
     * @return string[]
     */
    private function get_panel_access_caps(): array {
        return [
            'manage_woocommerce',
            'wpcafe_view_own_orders',
            'wpcafe_view_all_orders',
            'wpcafe_manage_orders',
            'wpcafe_view_own_reservations',
            'wpcafe_view_all_reservations',
            'wpcafe_manage_reservations',
        ];
    }

    /**
     * Check whether current user can access panel.
     *
     * @return bool
     */
    private function user_has_panel_access(): bool {
        foreach ( $this->get_panel_access_caps() as $cap ) {
            if ( current_user_can( $cap ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get current user capability payload for frontend app.
     *
     * @return string[]
     */
    private function get_current_user_caps(): array {
        $caps = [];

        foreach ( $this->get_panel_access_caps() as $cap ) {
            if ( current_user_can( $cap ) ) {
                $caps[] = $cap;
            }
        }

        return array_values( array_unique( $caps ) );
    }

    /**
     * Shortcode tag name.
     *
     * @return string
     */
    public function tag() {
        return 'wpc_restaurant_management_dashboard';
    }

    /**
     * Render shortcode content.
     *
     * @param  array  $atts    Shortcode attributes.
     * @param  string $content Shortcode content.
     *
     * @return string
     */
    public function render( $atts = [], $content = null ) {
        $default_atts = [];
        $atts = shortcode_atts( $default_atts, $atts, $this->tag() );

        if ( ! is_user_logged_in() ) {
            $login_url = wp_login_url( get_permalink() );
            return sprintf(
                '<div class="wpc-restaurant-management-auth-notice" style="padding:24px;border:1px solid #e5e7eb;background:#f9fafb;border-radius:8px;text-align:center;color:#374151;font-size:14px;">%s <a href="%s">%s</a></div>',
                esc_html__( 'You must be logged in to access this panel.', 'wp-cafe' ),
                esc_url( $login_url ),
                esc_html__( 'Log in', 'wp-cafe' )
            );
        }

        if ( ! $this->user_has_panel_access() ) {
            return sprintf(
                '<div class="wpc-restaurant-management-auth-notice" style="padding:24px;border:1px solid #fee2e2;background:#fef2f2;border-radius:8px;text-align:center;color:#991b1b;font-size:14px;">%s</div>',
                esc_html__( 'You do not have permission to access the restaurant management panel.', 'wp-cafe' )
            );
        }

        wp_enqueue_style( 'wpcafe-restaurant-management-style' );

        // Ensure pro filters (e.g. wp_cafe_visual_table) are registered.
        if ( wp_script_is( 'frontend-pro-script', 'registered' ) ) {
            wp_enqueue_script( 'frontend-pro-script' );
        }

        wp_enqueue_script( 'wpcafe-restaurant-management-scripts' );

        $current_user = wp_get_current_user();
        wp_localize_script(
            'wpcafe-restaurant-management-scripts',
            'wpcafeRestaurantPanel',
            [
                'currentUser' => [
                    'id'           => (int) $current_user->ID,
                    'display_name' => sanitize_text_field( $current_user->display_name ),
                    'email'        => sanitize_email( $current_user->user_email ),
                    'avatar_url'   => esc_url_raw( get_avatar_url( $current_user->ID, [ 'size' => 96 ] ) ),
                    'capabilities' => $this->get_current_user_caps(),
                    'logout_url'   => esc_url_raw( html_entity_decode( wp_logout_url( get_permalink() ) ) ),
                ],
            ]
        );

        $css_url = wpcafe()->assets_url . '/build/css/restaurant-management.css';

        ob_start();
        ?>
<div class="wpc-restaurant-management-root"
    data-component="wpc-restaurant-management-dashboard"
    data-css-url="<?php echo esc_url( $css_url ); ?>">
</div>
        <?php
        return ob_get_clean();
    }
}
