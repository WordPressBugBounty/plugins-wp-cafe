<?php
namespace WpCafe\Extensions;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Picks the toast shown after Pointics is activated from the Addons tab.
 *
 * Reads Pointics' table directly on purpose: in the activation request Pointics
 * was not loaded at plugins_loaded, so none of its classes are available.
 *
 * @package WpCafe/Extensions
 */
class Pointics_Activation_Message {
    /**
     * Toast text for this site.
     *
     * @return string
     */
    public static function for_site(): string {
        if ( self::active_rule_count() > 0 ) {
            return __( 'Pointics is active. Points now show on your food menus. You can turn them off for each menu.', 'wp-cafe' );
        }

        return __( 'Pointics is active. Click Configure to create your first earning rule.', 'wp-cafe' );
    }

    /**
     * Active, not deleted earning rules.
     *
     * @return int
     */
    private static function active_rule_count(): int {
        global $wpdb;

        $table = $wpdb->prefix . 'pointics_channels';

        // Pointics can change rules between requests; keep this cache request-only.
        wp_cache_add_non_persistent_groups( 'wpcafe_pointics_activation' );
        $count = wp_cache_get( $table, 'wpcafe_pointics_activation', false, $found );

        if ( $found ) {
            return (int) $count;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Pointics' repository is unavailable in its activation request.
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
            wp_cache_set( $table, 0, 'wpcafe_pointics_activation' );
            return 0;
        }

        // Soft-deleted rules keep their row; they must not count as live.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Pointics is not loaded during activation; prefix-derived table name, value prepared.
        $count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . esc_sql( $table ) . ' WHERE is_active = %d AND deleted_at IS NULL', 1 ) );
        wp_cache_set( $table, $count, 'wpcafe_pointics_activation' );

        return $count;
    }
}
