<?php
/**
 * Date Utility for Dashboard Reports
 *
 * Handles date range calculations for all dashboard reports.
 *
 * @package WpCafe\Dashboard\Reports
 * @since 1.0.0
 */

namespace WpCafe\Dashboard\Reports;

/**
 * Date Utility Class
 *
 * Provides consistent date range handling across all dashboard reports.
 *
 * @package WpCafe\Dashboard\Reports
 * @since 1.0.0
 */
class Date_Utility {

    /**
     * Get date range for the specified period.
     *
     * @since 1.0.0
     * @param string $period Time period (today, yesterday, week, month, custom).
     * @param string $start_date Custom start date (Y-m-d format) - required for custom period.
     * @param string $end_date Custom end date (Y-m-d format) - required for custom period.
     * @return array Array with start and end dates.
     */
    public static function get_date_range( $period, $start_date = '', $end_date = '' ) {
        $today = current_time( 'Y-m-d' );
        $now   = current_time( 'timestamp' );

        switch ( $period ) {
            case 'today':
                return array(
                    'start' => $today,
                    'end'   => $today,
                );
            case 'yesterday':
                $yesterday = wp_date( 'Y-m-d', strtotime( '-1 day', $now ) );
                return array(
                    'start' => $yesterday,
                    'end'   => $yesterday,
                );
            case 'week':
                $week_start = wp_date( 'Y-m-d', strtotime( 'monday this week', $now ) );
                $week_end   = wp_date( 'Y-m-d', strtotime( 'sunday this week', $now ) );
                return array(
                    'start' => $week_start,
                    'end'   => $week_end,
                );
            case 'month':
                $month_start = wp_date( 'Y-m-01', $now );
                $month_end   = wp_date( 'Y-m-t', $now );
                return array(
                    'start' => $month_start,
                    'end'   => $month_end,
                );
            case 'custom':
                // Validate custom dates
                if ( empty( $start_date ) || empty( $end_date ) ) {
                    // Fallback to today if custom dates are not provided
                    return array(
                        'start' => $today,
                        'end'   => $today,
                    );
                }
                
                // Validate date format
                if ( ! self::is_valid_date( $start_date ) || ! self::is_valid_date( $end_date ) ) {
                    // Fallback to today if dates are invalid
                    return array(
                        'start' => $today,
                        'end'   => $today,
                    );
                }
                
                // Ensure start_date is not after end_date
                if ( strtotime( $start_date ) > strtotime( $end_date ) ) {
                    $temp = $start_date;
                    $start_date = $end_date;
                    $end_date = $temp;
                }
                
                return array(
                    'start' => $start_date,
                    'end'   => $end_date,
                );
            default:
                return array(
                    'start' => $today,
                    'end'   => $today,
                );
        }
    }

    /**
     * Get previous period date range for comparison.
     *
     * @since 1.0.0
     * @param string $period Time period.
     * @param string $start_date Custom start date (Y-m-d format) - used for custom period.
     * @param string $end_date Custom end date (Y-m-d format) - used for custom period.
     * @return array Array with start and end dates.
     */
    public static function get_previous_period_range( $period, $start_date = '', $end_date = '' ) {
        $today = current_time( 'Y-m-d' );
        $now   = current_time( 'timestamp' );

        switch ( $period ) {
            case 'today':
                $yesterday = wp_date( 'Y-m-d', strtotime( '-1 day', $now ) );
                return array(
                    'start' => $yesterday,
                    'end'   => $yesterday,
                );
            case 'yesterday':
                $day_before = wp_date( 'Y-m-d', strtotime( '-2 days', $now ) );
                return array(
                    'start' => $day_before,
                    'end'   => $day_before,
                );
            case 'week':
                $prev_week_start = wp_date( 'Y-m-d', strtotime( 'monday last week', $now ) );
                $prev_week_end   = wp_date( 'Y-m-d', strtotime( 'sunday last week', $now ) );
                return array(
                    'start' => $prev_week_start,
                    'end'   => $prev_week_end,
                );
            case 'month':
                $prev_month_start = wp_date( 'Y-m-01', strtotime( '-1 month', $now ) );
                $prev_month_end   = wp_date( 'Y-m-t', strtotime( '-1 month', $now ) );
                return array(
                    'start' => $prev_month_start,
                    'end'   => $prev_month_end,
                );
            case 'custom':
                if ( empty( $start_date ) || empty( $end_date ) ) {
                    $yesterday = wp_date( 'Y-m-d', strtotime( '-1 day', $now ) );
                    return array(
                        'start' => $yesterday,
                        'end'   => $yesterday,
                    );
                }
                
                // Calculate the duration of the custom period
                $start_timestamp = strtotime( $start_date );
                $end_timestamp = strtotime( $end_date );
                $duration_days = ( $end_timestamp - $start_timestamp ) / DAY_IN_SECONDS;
                
                // Calculate previous period with same duration
                $prev_end_date = wp_date( 'Y-m-d', strtotime( $start_date . ' -1 day', $now ) );
                $prev_start_date = wp_date( 'Y-m-d', strtotime( $prev_end_date . ' -' . $duration_days . ' days', $now ) );
                
                return array(
                    'start' => $prev_start_date,
                    'end'   => $prev_end_date,
                );
            default:
                $yesterday = wp_date( 'Y-m-d', strtotime( '-1 day', $now ) );
                return array(
                    'start' => $yesterday,
                    'end'   => $yesterday,
                );
        }
    }

    /**
     * Validate if a date string is in Y-m-d format.
     *
     * @since 1.0.0
     * @param string $date Date string to validate.
     * @return bool True if valid, false otherwise.
     */
    private static function is_valid_date( $date ) {
        if ( empty( $date ) ) {
            return false;
        }
        
        $d = \DateTime::createFromFormat( 'Y-m-d', $date );
        return $d && $d->format( 'Y-m-d' ) === $date;
    }

    /**
     * Normalize an incoming date string to Y-m-d using multi-format parsing.
     *
     * Order of attempts: admin-configured WP date_format, canonical Y-m-d,
     * common formats (slash/dot/dash variants, named months), strtotime fallback.
     *
     * @since 1.0.0
     * @param string $value    Raw date input.
     * @param string $fallback Y-m-d fallback when parsing fails (default: today in site tz).
     * @return string Y-m-d formatted date.
     */
    public static function normalize_date( $value, $fallback = '' ) : string {
        if ( '' === $fallback ) {
            $fallback = current_time( 'Y-m-d' );
        }
        if ( empty( $value ) ) {
            return $fallback;
        }
        $value = trim( (string) $value );

        $formats = array_unique( array_filter( array(
            get_option( 'date_format' ),
            'Y-m-d',
        ) ) );

        foreach ( $formats as $fmt ) {
            $dt        = \DateTime::createFromFormat( '!' . $fmt, $value, wp_timezone() );
            $errors    = \DateTime::getLastErrors();
            $err_count = is_array( $errors ) ? ( $errors['warning_count'] + $errors['error_count'] ) : 0;
            if ( $dt && 0 === $err_count && $dt->format( $fmt ) === $value ) {
                return $dt->format( 'Y-m-d' );
            }
        }

        // Last-resort: strtotime handles natural language ("next Monday") and ISO inputs.
        $ts = strtotime( $value );
        if ( false !== $ts ) {
            return wp_date( 'Y-m-d', $ts );
        }

        return $fallback;
    }

    /**
     * Get available period options for the frontend.
     *
     * @since 1.0.0
     * @return array Array of period options.
     */
    public static function get_period_options() {
        return array(
            'today'     => __( 'Today', 'wp-cafe' ),
            'yesterday' => __( 'Yesterday', 'wp-cafe' ),
            'week'      => __( 'This Week', 'wp-cafe' ),
            'month'     => __( 'This Month', 'wp-cafe' ),
            'custom'    => __( 'Custom Range', 'wp-cafe' ),
        );
    }

    /**
     * Format date range for display.
     *
     * @since 1.0.0
     * @param string $period Time period.
     * @param string $start_date Custom start date.
     * @param string $end_date Custom end date.
     * @return string Formatted date range string.
     */
    public static function format_date_range( $period, $start_date = '', $end_date = '' ) {
        $now = current_time( 'timestamp' );

        switch ( $period ) {
            case 'today':
                return __( 'Today', 'wp-cafe' );
            case 'yesterday':
                return __( 'Yesterday', 'wp-cafe' );
            case 'week':
                $week_start = wp_date( 'M j', strtotime( 'monday this week', $now ) );
                $week_end   = wp_date( 'M j', strtotime( 'sunday this week', $now ) );
                /* translators: %1$s: start date, %2$s: end date */
                return sprintf( __( '%1$s - %2$s', 'wp-cafe' ), $week_start, $week_end );
            case 'month':
                $month_name = wp_date( 'F Y', $now );
                return $month_name;
            case 'custom':
                if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
                    $start_formatted = wp_date( 'M j, Y', strtotime( $start_date, $now ) );
                    $end_formatted = wp_date( 'M j, Y', strtotime( $end_date, $now ) );
                    /* translators: %1$s: start date, %2$s: end date */
                    return sprintf( __( '%1$s - %2$s', 'wp-cafe' ), $start_formatted, $end_formatted );
                }
                return __( 'Custom Range', 'wp-cafe' );
            default:
                return __( 'Today', 'wp-cafe' );
        }
    }
} 