<?php
namespace WpCafe\RestaurantManagement;

if ( ! defined( 'ABSPATH' ) ) exit;

use WP_Role;

/**
 * Role lifecycle manager for restaurant panel access.
 */
class Roles {
    /**
     * Primitive capability that opens the WPCafe admin menu and bypasses the
     * WooCommerce `/my-account/` admin redirect. Grant via any role editor to
     * elevate a user without changing their role.
     */
    public const ADMIN_ACCESS_CAP = 'wpcafe_access_admin_menu';

    /**
     * Roles that receive the admin-access capability automatically.
     *
     * @return string[]
     */
    private static function get_admin_access_roles(): array {
        return [ 'administrator', 'shop_manager' ];
    }

    /**
     * Register restaurant roles and synchronize their managed capabilities.
     *
     * @return void
     */
    public static function register(): void {
        foreach ( self::get_role_definitions() as $role_slug => $definition ) {
            $expected_caps = self::get_expected_caps( $definition['caps'] );
            $role = get_role( $role_slug );

            if ( ! $role ) {
                add_role( $role_slug, $definition['label'], $expected_caps );
                $role = get_role( $role_slug );
            }

            if ( $role instanceof WP_Role ) {
                self::sync_role_caps( $role, $expected_caps );
            }
        }

        self::grant_baseline_view_caps();
        self::grant_admin_access_cap();
    }

    /**
     * Grant the admin-access cap to roles that should land in wp-admin and see
     * the WPCafe menu by default.
     *
     * @return void
     */
    private static function grant_admin_access_cap(): void {
        foreach ( self::get_admin_access_roles() as $role_slug ) {
            $role = get_role( $role_slug );
            if ( ! $role instanceof WP_Role ) {
                continue;
            }

            if ( ! $role->has_cap( self::ADMIN_ACCESS_CAP ) ) {
                $role->add_cap( self::ADMIN_ACCESS_CAP, true );
            }
        }
    }

    /**
     * Relax the WooCommerce admin-access redirect for users holding the WPCafe
     * admin cap. Only ever returns `false` to relax — never tightens.
     *
     * @param  bool $prevent Current prevent flag from WooCommerce.
     * @return bool
     */
    public static function filter_wc_prevent_admin_access( $prevent ): bool {
        if ( current_user_can( self::ADMIN_ACCESS_CAP ) ) {
            return false;
        }

        return (bool) $prevent;
    }

    /**
     * Grant own-view capabilities to default WP/WC roles so any logged-in
     * customer/subscriber can access their own orders + reservations.
     *
     * @return void
     */
    private static function grant_baseline_view_caps(): void {
        $baseline_roles = [ 'customer', 'subscriber' ];
        $baseline_caps  = [ 'wpcafe_view_own_orders', 'wpcafe_view_own_reservations' ];

        foreach ( $baseline_roles as $role_slug ) {
            $role = get_role( $role_slug );
            if ( ! $role instanceof WP_Role ) {
                continue;
            }

            foreach ( $baseline_caps as $cap ) {
                if ( ! $role->has_cap( $cap ) ) {
                    $role->add_cap( $cap, true );
                }
            }
        }
    }

    /**
     * Remove restaurant roles on deactivation.
     *
     * @return void
     */
    public static function deregister(): void {
        foreach ( array_keys( self::get_role_definitions() ) as $role_slug ) {
            remove_role( $role_slug );
        }

        $baseline_roles = [ 'customer', 'subscriber' ];
        foreach ( $baseline_roles as $role_slug ) {
            $role = get_role( $role_slug );
            if ( ! $role instanceof WP_Role ) {
                continue;
            }
            foreach ( self::get_managed_caps() as $cap ) {
                if ( $role->has_cap( $cap ) ) {
                    $role->remove_cap( $cap );
                }
            }
        }

        foreach ( self::get_admin_access_roles() as $role_slug ) {
            $role = get_role( $role_slug );
            if ( ! $role instanceof WP_Role ) {
                continue;
            }
            if ( $role->has_cap( self::ADMIN_ACCESS_CAP ) ) {
                $role->remove_cap( self::ADMIN_ACCESS_CAP );
            }
        }
    }

    /**
     * Get role capability matrix.
     *
     * @return array<string, array{label:string,caps:string[]}>
     */
    private static function get_role_definitions(): array {
        return [
            'wpcafe_customer' => [
                'label' => __( 'WPCafe Customer', 'wp-cafe' ),
                'caps'  => [
                    'wpcafe_view_own_orders',
                    'wpcafe_view_own_reservations',
                ],
            ],
            'wpcafe_staff' => [
                'label' => __( 'WPCafe Staff', 'wp-cafe' ),
                'caps'  => [
                    'wpcafe_view_all_orders',
                    'wpcafe_view_all_reservations',
                    'wpcafe_create_dine_in_order',
                    'wpcafe_edit_own_open_orders',
                ],
            ],
            'wpcafe_manager' => [
                'label' => __( 'WPCafe Manager', 'wp-cafe' ),
                'caps'  => [
                    'wpcafe_view_all_orders',
                    'wpcafe_view_all_reservations',
                    'wpcafe_manage_orders',
                    'wpcafe_manage_reservations',
                    'wpcafe_create_dine_in_order',
                    'wpcafe_edit_own_open_orders',
                    'wpcafe_edit_any_open_order',
                ],
            ],
        ];
    }

    /**
     * Capabilities managed by this role system.
     *
     * @return string[]
     */
    private static function get_managed_caps(): array {
        return [
            'wpcafe_view_own_orders',
            'wpcafe_view_own_reservations',
            'wpcafe_view_all_orders',
            'wpcafe_view_all_reservations',
            'wpcafe_manage_orders',
            'wpcafe_manage_reservations',
            'wpcafe_create_dine_in_order',
            'wpcafe_edit_own_open_orders',
            'wpcafe_edit_any_open_order',
        ];
    }

    /**
     * Build expected role capabilities including baseline read.
     *
     * @param  string[] $caps Role-specific capabilities.
     * @return array<string, bool>
     */
    private static function get_expected_caps( array $caps ): array {
        $caps[] = 'read';

        return array_fill_keys( $caps, true );
    }

    /**
     * Sync managed capabilities for an existing role.
     *
     * @param  WP_Role            $role          Role object.
     * @param  array<string,bool> $expected_caps Expected capability map.
     * @return void
     */
    private static function sync_role_caps( WP_Role $role, array $expected_caps ): void {
        foreach ( array_keys( $expected_caps ) as $cap ) {
            if ( ! $role->has_cap( $cap ) ) {
                $role->add_cap( $cap, true );
            }
        }

        foreach ( self::get_managed_caps() as $managed_cap ) {
            if ( isset( $expected_caps[ $managed_cap ] ) ) {
                continue;
            }

            if ( $role->has_cap( $managed_cap ) ) {
                $role->remove_cap( $managed_cap );
            }
        }
    }
}
