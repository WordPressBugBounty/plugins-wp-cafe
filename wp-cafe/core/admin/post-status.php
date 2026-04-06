<?php
namespace WpCafe\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;

/**
 * Add default settings
 *
 * @package WpCafe/Default settings
 */
class Post_Status implements Hookable_Service_Contract {
    /**
     * Register hooks
     *
     * @return  void
     */
    public function register() {
        add_action( 'init', [ $this, 'register_active_post_status' ], 999 );
    }

    /**
     * Registers custom post status 'active' for posts.
     *
     * This function is hooked into 'init' and registers a new post status called 'active'.
     * The status is public, included in admin lists, and displays a count.
     *
     * @return void
     */
    public function register_active_post_status() {
        register_post_status( 'active', [
            'label'                     => _x( 'Active', 'post status', 'wp-cafe' ),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            /* translators: %s: number of posts */
            'label_count'               => _n_noop( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', 'wp-cafe' ),
        ] );

        register_post_status( 'inactive', [
            'label'                     => _x( 'Inactive', 'post status', 'wp-cafe' ),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => false,
            'show_in_admin_status_list' => false,
            /* translators: %s: number of posts */
            'label_count'               => _n_noop( 'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', 'wp-cafe' ),
        ] );

        register_post_status( 'confirmed', [
            'label'                     => _x( 'Confirmed', 'post status', 'wp-cafe' ),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => false,
            'show_in_admin_status_list' => false,
            /* translators: %s: number of posts */
            'label_count'               => _n_noop( 'Confirmed <span class="count">(%s)</span>', 'Confirmed <span class="count">(%s)</span>', 'wp-cafe' ),
        ] );

        register_post_status( 'pending_payment', [
            'label'                     => _x( 'Pending Payment', 'post status', 'wp-cafe' ),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => false,
            'show_in_admin_status_list' => false,
            /* translators: %s: number of posts */
            'label_count'               => _n_noop( 'Pending Payment <span class="count">(%s)</span>', 'Pending Payment <span class="count">(%s)</span>', 'wp-cafe' ),
        ] );

        register_post_status( 'refunded', [
            'label'                     => _x( 'Refunded', 'post status', 'wp-cafe' ),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => false,
            'show_in_admin_status_list' => false,
            /* translators: %s: number of posts */
            'label_count'               => _n_noop( 'Refunded <span class="count">(%s)</span>', 'Refunded <span class="count">(%s)</span>', 'wp-cafe' ),
        ] );

        register_post_status( 'cancelled', [
            'label'                     => _x( 'Cancelled', 'post status', 'wp-cafe' ),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => false,
            'show_in_admin_status_list' => false,
            /* translators: %s: number of posts */
            'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'wp-cafe' ),
        ] );
    }
}
