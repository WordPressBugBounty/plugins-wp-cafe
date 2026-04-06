<?php
namespace WpCafe\Upgrades\Upgrade_3_0_0;

use WpCafe\Models\Reservation_Model;

/**
 * Class Upgrade_Reservation
 *
 * Handles the upgrade process for version 3.0.0.
 */
class Upgrade_Reservation {
    
    /**
     * Constructor for the Upgrade_Reservation class.
     *
     * Automatically triggers the upgrade process when an instance is created.
     */
    public function __construct() {
        $this->migrate_reservation();
        $this->migrate_reservation_schedule();
    }

    /**
     * Upgrade the reservation.
     *
     * @return void
     */
    public function migrate_reservation() {

        $reservations = get_posts( [
            'post_type'      => 'wpc_reservation',
            'posts_per_page' => -1,
        ] );

        if ( $reservations ) {
            foreach ( $reservations as $reservation ) {
                $reservation_data = get_post_meta( $reservation->ID );
                $id = $reservation->ID;
                $term = get_term_by('name', get_post_meta( $id, 'wpc_branch', true ), 'wpc_branch');

                if ( $term ) {
                    $branch_id = $term->term_id;
                } else {
                    $branch_id = '';
                }

                $date           = get_post_meta( $id, 'wpc_booking_date', true );
                $start_time_str = get_post_meta( $id, 'wpc_from_time', true );
                $end_time_str   = get_post_meta( $id, 'wpc_to_time', true );

                // Convert time strings to timestamps
                $start_time = '';
                $end_time   = '';

                if ( ! empty( $date ) && ! empty( $start_time_str ) ) {
                    $start_time = strtotime( $date . ' ' . $start_time_str );
                }

                if ( ! empty( $date ) && ! empty( $end_time_str ) ) {
                    $end_time = strtotime( $date . ' ' . $end_time_str );
                }

                $data = [
                    'name'          => get_post_meta( $id, 'wpc_name', true ),
                    'email'         => get_post_meta( $id, 'wpc_email', true ),
                    'phone'         => get_post_meta( $id, 'wpc_phone', true ),
                    'date'          => $date,
                    'start_time'    => $start_time,
                    'end_time'      => $end_time,
                    'total_guest'   => get_post_meta( $id, 'wpc_total_guest', true ),
                    'table_name'    => get_post_meta( $id, '_table_name', true ),
                    'status'        => get_post_meta( $id, 'wpc_reservation_state', true ),
                    'branch_id'     => $branch_id,
                    'branch_name'   => get_post_meta( $id, 'wpc_branch', true ),
                    'notes'         => get_post_meta( $id, 'wpc_booking_note', true ),
                    'invoice'       => get_post_meta( $id, 'wpc_reservation_invoice', true ),
                    'total_price'  => '',
                ];

                $reservation_model = new Reservation_Model( $id );
                $reservation_model->update( $data );
            }
        }
    }

    /**
     * Migrate reservation schedule
     *
     * @return  void
     */
    private function migrate_reservation_schedule() {
        $multi_slot = wpc_get_option('reser_multi_schedule', false);

        if ( 'on' === $multi_slot ) {
            $this->migrate_multi_slot_daily_schedule();
            $this->migrate_multi_slot_weekly_schedule();
        } else {
            $this->migrate_weekly_reservation_schedule();
            $this->migrate_daily_schedule();
        }
    }

    /**
     * Migrate weekly reservation schedule
     *
     * @return  void
     */
    public function migrate_weekly_reservation_schedule() {
        $reservation_schedule = wpc_get_option('wpc_weekly_schedule', []);
        $reservation_schedule_start_time = wpc_get_option('wpc_weekly_schedule_start_time', []);
        $reservation_schedule_end_time = wpc_get_option('wpc_weekly_schedule_end_time', []);

        $schedule_data = $this->convert_weekly_schedule_data($reservation_schedule, $reservation_schedule_start_time, $reservation_schedule_end_time);

        if ( $schedule_data ) {
            wpc_update_option('override_reservation_schedule', "1");
            wpc_update_option('reservation_schedule', $schedule_data);
        }
    }

    /**
     * Migrate daily reservation schedule
     *
     * @return  void
     */
    private function migrate_daily_schedule() {
        $start_time = wpc_get_option('wpc_all_day_start_time', '');
        $end_time   = wpc_get_option('wpc_all_day_end_time', '');

        $days = [
            "Mon" => ["status" => "on", "slots" => [
                [
                    'start' => $start_time,
                    'end'   => $end_time,
                ]
            ]],
            "Tue" => ["status" => "on", "slots" => [
                [
                    'start' => $start_time,
                    'end'   => $end_time,
                ]
            ]],
            "Wed" => ["status" => "on", "slots" => [
                [
                    'start' => $start_time,
                    'end'   => $end_time,
                ]
            ]],
            "Thu" => ["status" => "on", "slots" => [
                [
                    'start' => $start_time,
                    'end'   => $end_time,
                ]
            ]],
            "Fri" => ["status" => "on", "slots" => [
                [
                    'start' => $start_time,
                    'end'   => $end_time,
                ]
            ]],
            "Sat" => ["status" => "on", "slots" => [
                [
                    'start' => $start_time,
                    'end'   => $end_time,
                ]
            ]],
            "Sun" => ["status" => "on", "slots" => [
                [
                    'start' => $start_time,
                    'end'   => $end_time,
                ]
            ]],
        ];

        if ( $start_time && $end_time ) {
            wpc_update_option('override_reservation_schedule', "1");
            wpc_update_option('reservation_schedule', $days);
        }
    }

    /**
     * Convert schedule data
     *
     * @return  array
     */
    private function convert_weekly_schedule_data($schedule_days, $schedule_start_time, $schedule_end_time) {
        
        if ( empty( $schedule_days ) || empty( $schedule_start_time ) || empty( $schedule_end_time ) ) {
            return [];
        }

        // Initialize all days
        $days = [
            "Mon" => ["status" => "off", "slots" => []],
            "Tue" => ["status" => "off", "slots" => []],
            "Wed" => ["status" => "off", "slots" => []],
            "Thu" => ["status" => "off", "slots" => []],
            "Fri" => ["status" => "off", "slots" => []],
            "Sat" => ["status" => "off", "slots" => []],
            "Sun" => ["status" => "off", "slots" => []],
        ];
        
        
        // Merge logic
        foreach ($schedule_days as $index => $dayRow) {
            foreach ($dayRow as $day => $status) {
        
                // Set status to ON
                $days[$day]['status'] = $status;
        
                // Add slot for that day
                $days[$day]['slots'][] = [
                    'start' => $schedule_start_time[$index],
                    'end'   => $schedule_end_time[$index],
                ];
            }
        }

        return $days;
    }

    /**
     * Migrate multi slot daily schedule
     *
     * @return  array
     */
    private function migrate_multi_slot_daily_schedule() {
        $start_time_entries = wpc_get_option('multi_start_time', []);
        $end_time_entries   = wpc_get_option('multi_end_time', []);

        if ( empty( $start_time_entries ) || empty( $end_time_entries ) ) {
            return [];
        }

        $time_entries = array_combine($start_time_entries, $end_time_entries);

        $slots = [];

        foreach ( $time_entries as $start_time => $end_time ) {
            $slots[] = [
                'start' => $start_time,
                'end'   => $end_time,
            ];
        }

        if ( $slots ) {
            $schedule_data = [
                "Mon" => ["status" => "on", "slots" => $slots],
                "Tue" => ["status" => "on", "slots" => $slots],
                "Wed" => ["status" => "on", "slots" => $slots],
                "Thu" => ["status" => "on", "slots" => $slots],
                "Fri" => ["status" => "on", "slots" => $slots],
                "Sat" => ["status" => "on", "slots" => $slots],
                "Sun" => ["status" => "on", "slots" => $slots],
            ];

            wpc_update_option('override_reservation_schedule', "1");
            wpc_update_option('reservation_schedule', $schedule_data);
        }
    }

    /**
     * Migrate multi slot weekly schedule
     *
     * @return  void
     */
    private function migrate_multi_slot_weekly_schedule() {
        $result = [];

        $weekly_multi_diff_times = wpc_get_option('weekly_multi_diff_times', []);

        if ( empty( $weekly_multi_diff_times ) ) {
            return [];
        }

        foreach ($weekly_multi_diff_times as $day => $items) {
            $result[$day] = [
                'status' => 'on',
                'slots'  => array_map(fn($t) => [
                    'start' => $t['start_time'],
                    'end'   => $t['end_time'],
                ], $items)
            ];
        }

        if ( $result ) {
            wpc_update_option('override_reservation_schedule', "1");
            wpc_update_option('reservation_schedule', $result);
        }
    }
} 
