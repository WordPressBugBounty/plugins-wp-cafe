<?php
/**
 * Init Main Class
 *
 * @package WpCafe/Init
 */

namespace WpCafe;

if ( ! defined( 'ABSPATH' ) ) exit;

use Arraytics\PluginNotice\PluginNotice;
/**
 * Class Init
 */
class Init {

    /**
     * Plugin version.
     *
     * @var string
     */
    public $version;

    /**
     * Plugin file.
     *
     * @var string
     */
    public $plugin_file;

    /**
     * Plugin directory.
     *
     * @var string
     */
    public $plugin_directory;

    /**
     * @var string
     */
    public $build_url;

    /**
     * Plugin base name.
     *
     * @var string
     */
    public $basename;

    /**
     * Plugin text directory path.
     *
     * @var string
     */
    public $text_domain_directory;

    /**
     * Plugin text directory path.
     *
     * @var string
     */
    public $template_directory;

    /**
     * Plugin assets directory path.
     *
     * @var string
     */
    public $assets_url;

    /**
     * Plugin url.
     *
     * @var string
     */
    public $plugin_url;

    /**
     * Core directory path.
     *
     * @var string
     */
    public $core_dir;

    /**
     * Widgets directory path.
     *
     * @var string
     */
    public $widgets_dir;

    /**
     * Container that holds all the services.
     *
     * @var Container
     */
    public $container;

    /**
     * Boiler Constructor.
     */
    public function __construct() {
        $this->init();

        add_action( 'init', [ $this, 'init_classes' ] );

        register_activation_hook( $this->plugin_file, [ $this, 'activate' ]);

        register_deactivation_hook($this->plugin_file, [ $this, 'deactivate' ] );
    }

    /**
     * Initialize the plugin.
     *
     * @return void
     */
    protected function init(): void {
        $this->version               = WPCAFE_VERSION;
        $this->plugin_file           = WPCAFE_FILE;
        $this->plugin_directory      = WPCAFE_DIR;
        $this->basename              = plugin_basename( $this->plugin_file );
        $this->text_domain_directory = $this->plugin_directory . '/languages';
        $this->template_directory    = $this->plugin_directory . '/templates';
        $this->core_dir              = $this->plugin_directory . '/core/';
        $this->widgets_dir           = $this->plugin_directory . '/widgets/';
        $this->plugin_url            = plugins_url( '', $this->plugin_file );
        $this->assets_url            = $this->plugin_url . '/assets';
        $this->build_url             = $this->plugin_url . '/build';
    }

    /**
     * Init classes
     *
     * @return  void
     */
    public function init_classes() {
        Plugin_Compatibility_Manager::check(); // Check pro plugin compatibility

        $this->get_container()->get('global');

        $notifyer = new PluginNotice([
            'plugin_slug'      => 'wp-cafe',
            'plugin_name'      => 'WpCafe',
            'plugin_url'       => 'https://themewinter.com/wp-cafe/',
            'support_url'      => 'https://themewinter.com/support/',
            'review_url'       => 'https://wordpress.org/support/plugin/wp-event-solution/reviews/#new-post',
            'allowed_screens'  => 'toplevel_page_wpcafe',
            'stories_api_url'  => 'https://banner.themefunction.com/public/stories/',
            'banners_api_url'  => 'https://banner.themefunction.com/public/jhanda',
            'api_url'          => 'https://banner.themefunction.com',
            // Dynamic filter string components
            'base_plugins'     => ['wp-cafe', 'wpcafe-free-only'],
            'pro_plugin_slug'  => 'wpcafe-pro',
            'pro_class'        => 'WpCafe_Pro',
        ]);

        $notifyer->boot();
    }

    /**
     * Get container
     *
     * @return  Container
     */
    public function get_container() {
        return wpcafe_container();
    }

    /**
     * Initializes the WpCafe class.
     *
     * Checks for an existing WeMeal instance
     * and if it doesn't find one, creates it.
     *
     * @return Init
     */
    public static function instance(): Init {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Trigger plugin activation class
     *
     * @return void
     */
    public function activate() {
        Activate::run();
    }

    /**
     * Trigger plugin deactivation class
     *
     * @return void
     */
    public function deactivate() {
        Deactivate::run();
    }
}
