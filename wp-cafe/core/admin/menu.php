<?php
namespace WpCafe\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;

/**
 * Menu class. Register menus
 *
 * @package WpCafe/AdminMenu
 */
class Menu implements Hookable_Service_Contract {
    /**
     * Register hooks
     *
     * @return  void
     */
    public function register() {
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] ); 
    }

    /**
     * Register admin menu
     *
     * @return  void
     */
    public function register_admin_menu() {
        global $submenu;
        $capability = 'manage_options';
        $slug       = 'wpcafe';
        $url        = 'admin.php?page=' . $slug . '#';  
        add_menu_page(
            esc_html__('WPCafe', 'wp-cafe'),
            esc_html__('WPCafe', 'wp-cafe'),
            $capability,
            $slug,
            array($this, 'wpcafe_dashboard_view'),
            wpcafe()->assets_url . '/images/brand_icon.svg',
            30
        );
 
    }

    
    
    /**
     * Render main menu page
     *
     * @return  void
     */
    public function wpcafe_dashboard_view() {
        ?>
        <div class="wrap" id="wpcafe_dashboard"></div>
        <?php
    }
 
  
}