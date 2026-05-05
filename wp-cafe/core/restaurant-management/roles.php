<?php
namespace WpCafe\RestaurantManagement;

if ( ! defined( 'ABSPATH' ) ) exit;

use WP_Role;

/**
 * Role lifecycle manager for restaurant panel access.
 */
class Roles {
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
                ],
            ],
            'wpcafe_manager' => [
                'label' => __( 'WPCafe Manager', 'wp-cafe' ),
                'caps'  => [
                    'wpcafe_view_all_orders',
                    'wpcafe_view_all_reservations',
                    'wpcafe_manage_orders',
                    'wpcafe_manage_reservations',
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
