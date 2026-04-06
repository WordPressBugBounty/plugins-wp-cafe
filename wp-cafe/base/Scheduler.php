<?php
/**
 * Init Main Class
 *
 * @package WpCafe/Init
 */

namespace WpCafe;

use DateTime;
use DatePeriod;
use DateInterval;
use WpCafe\Models\Reservation_Model;

/**
 * Restaurant Scheduler Class
 *
 * Generates time slots for restaurant schedules.
 */

 class Scheduler {

    /**
     * Restaurant schedule.
     *
     * @var array
     */
    protected $schedule;

    /**
     * Start date.
     *
     * @var string
     */
    protected $start_date;

    /**
     * End date.
     *
     * @var string
     */
    protected $end_date;

    /**
     * Interval in minutes.
     *
     * @var int
     */
    protected $interval;

    /**
     * Max seat.
     *
     * @var int
     */
    protected $max_seat;

    /**
     * Custom schedules.
     *
     * @var array
     */
    protected $custom_schedules;

    /**
     * Custom schedule enabled.
     *
     * @var bool
     */
    protected $enable_custom_schedule;

    /**
     * Custom holidays.
     *
     * @var array
     */
    protected $custom_holidays;

    /**
     * Custom holiday enabled.
     *
     * @var bool
     */
    protected $enable_custom_holiday;

    /**
     * Constructor.
     *
     * @param array  $schedule   Restaurant schedule.
     * @param string $start_date Start date (Y-m-d).
     * @param string $end_date   End date (Y-m-d).
     * @param int    $total_capacity Total capacity.
     * @param int    $location_id Optional location ID for location-specific settings.
     */
    public function __construct( $schedule, $start_date, $end_date, $total_capacity, $branch_id = null ) {
        $this->schedule   = $schedule;
        $this->start_date = $start_date;
        $this->end_date   = $end_date;
        $this->interval   = wpc_get_reservation_slot_interval( $branch_id );
        $this->max_seat   = $total_capacity;
        
        $this->enable_custom_schedule = wpc_get_option('enable_custom_schedule', false);
        $this->custom_schedules = wpc_get_option('custom_schedules', []);

        $this->enable_custom_holiday = wpc_get_option('enable_custom_holiday', false);
        $this->custom_holidays = wpc_get_option('custom_holidays', []);
    }

    /**
     * Generate all slots within the date range.
     *
     * @return array
     */
    public function generate() {

        if ( ! $this->schedule ) {
            return [];
        }

        if ( ! $this->start_date || ! $this->end_date ) {
            return [];
        }

        $result = array();
        $period = new DatePeriod(
            new DateTime( $this->start_date ),
            new DateInterval( 'P1D' ),
            ( new DateTime( $this->end_date ) )->modify( '+1 day' )
        );

        foreach ( $period as $date ) {
            $formatted_date = $date->format( 'Y-m-d' );
            $day = $date->format( 'D' );

            // Check if date is a custom holiday - holidays take highest priority
            if ( $this->enable_custom_holiday && $this->is_custom_holiday( $formatted_date ) ) {
                $result[ $formatted_date ] = array(
                    'status' => 'off',
                    'slots'  => array(),
                );
                continue;
            }

            // Check custom schedule if enabled
            if ( $this->enable_custom_schedule && $this->has_custom_schedule( $formatted_date ) ) {
                $custom_schedule = $this->get_custom_schedule( $formatted_date );
                $result[ $formatted_date ] = array(
                    'status' => 'on',
                    'slots'  => $this->generate_slots( $date, array( $custom_schedule ) ),
                );
                continue;
            }

            if ( ! isset( $this->schedule[ $day ] ) ) {
                continue;
            }

            $day_schedule = $this->schedule[ $day ];
            $status       = $day_schedule['status'];

            $result[ $formatted_date ] = array(
                'status' => $status,
                'slots'  => ( 'on' === $status )
                    ? $this->generate_slots( $date, $day_schedule['slots'] )
                    : array(),
            );
        }

        return $result;
    }

    /**
     * Generate slots for a single day.
     *
     * @param array $time_ranges Array of time ranges with start and end times.
     *
     * @return array
     */
    protected function generate_slots( $date, $time_ranges ) {
        $slots = array();
        $date = $date->format( 'Y-m-d' );

        foreach ( $time_ranges as $range ) {
            $start = DateTime::createFromFormat( 'h:i A', $range['start'] );
            $end   = DateTime::createFromFormat( 'h:i A', $range['end'] );

            while ( $start < $end ) {
                $slot_start = clone $start;
                $slot_end   = ( clone $start )->modify( '+' . $this->interval . ' minutes' );

                if ( $slot_end > $end ) {
                    break;
                }

                $slots[] = array(
                    'start'          => $slot_start->format( get_option('time_format') ),
                    'end'            => $slot_end->format( get_option('time_format') ),
                    'status'         => $this->get_slot_status( $date,$slot_start, $slot_end ),
                    'available_seat' => $this->get_available_seat( $date, $slot_start, $slot_end ),
                );

                $start = $slot_end;
            }
        }

        return $slots;
    }

    /**
     * Determine slot status.
     *
     * @param DateTime $start Start time.
     * @param DateTime $end   End time.
     *
     * @return string
     */
    protected function get_slot_status( $date, $start, $end ) {
        $total_booked_seat = $this->get_booked_seat($date, $start, $end);

        return $total_booked_seat < $this->max_seat ? 'available' : 'unavailable';
    }

    /**
     * Get available seat count.
     *
     * @param DateTime $start Start time.
     * @param DateTime $end   End time.
     *
     * @return int
     */
    protected function get_available_seat( $date, $start, $end ) {
        $total_booked_seat = Reservation_Model::get_total_guest_by_date_time( $date, $start, $end );

        return $this->max_seat - $total_booked_seat;
    }

    /**
     * Get booked seat
     *
     * @return  integer Total booked seat
     */
    protected function get_booked_seat($date, $start, $end) {
        $total_booked_seat = Reservation_Model::get_total_guest_by_date_time( $date, $start, $end );

        return $total_booked_seat;
    }

    /**
     * Check if a custom schedule exists for the given date.
     *
     * @param string $date Date in Y-m-d format.
     * @return bool
     */
    protected function has_custom_schedule( $date ) {
        if ( empty( $this->custom_schedules ) ) {
            return false;
        }

        foreach ( $this->custom_schedules as $schedule ) {
            if ( isset( $schedule['date'] ) && $schedule['date'] === $date ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get custom schedule for the given date.
     *
     * @param string $date Date in Y-m-d format.
     * @return array Schedule with 'start' and 'end' keys.
     */
    protected function get_custom_schedule( $date ) {
        if ( empty( $this->custom_schedules ) ) {
            return array();
        }

        foreach ( $this->custom_schedules as $schedule ) {
            if ( isset( $schedule['date'] ) && $schedule['date'] === $date ) {
                if ( isset( $schedule['time'] ) && is_array( $schedule['time'] ) ) {
                    return $schedule['time'];
                }
            }
        }

        return array();
    }

    /**
     * Check if a date is a custom holiday.
     *
     * @param string $date Date in Y-m-d format.
     * @return bool True if the date is a custom holiday, false otherwise.
     */
    protected function is_custom_holiday( $date ) {
        if ( empty( $this->custom_holidays ) ) {
            return false;
        }

        // Convert input date to timestamp for comparison
        $date_timestamp = strtotime( $date );
        if ( ! $date_timestamp ) {
            return false;
        }

        foreach ( $this->custom_holidays as $holiday ) {
            $holiday_timestamp = strtotime( $holiday );
            if ( ! $holiday_timestamp ) {
                continue;
            }

            if ( wp_date( 'Y-m-d', $date_timestamp ) === wp_date( 'Y-m-d', $holiday_timestamp ) ) {
                return true;
            }
        }

        return false;
    }
}
