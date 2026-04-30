<?php

namespace WpCafe\Core\Modules\Reservation;

use WpCafe\Utils\Wpc_Utilities;

defined( 'ABSPATH' ) || exit;

class Hooks{

    use \WpCafe\Traits\Wpc_Singleton;

    /**
     * Make array for chart
     */
    public static function chart_format_data($data){
        $sumArray = array();
        foreach ($data as $k=>$subArray) {
            foreach ($subArray as $id=>$value) {
                if (isset($sumArray[$id])) {
                    $sumArray[$id] +=$value;
                }else {
                    $sumArray[$id] =$value;
                }
            }
        }
        
        if ( count($sumArray)>0 ) {
            $sumArray = array_values($sumArray);
        }

        return $sumArray;
    }


    /**
     * Filter data for chart
     */
    public function filter_report_by_date($type,$date_range){
        global $wpdb;
        $label_arr      = array();
        $cancel_arr     = array();
        $confirm_arr    = array();
        $query_type     = "single";

        if (( $date_range[0] !== null && $date_range[0] !=="" ) && ( $date_range[1] !== null && $date_range[1] !=="" )) {
            $query_type     = "both";
        }
        else if (( $date_range[0] !== null && $date_range[0] !=="" ) && ( $date_range[1] == null || $date_range[1] =="" )) {
            $query_type     = "first_single";
        }
        else if (( $date_range[1] !== null && $date_range[1] !=="" ) && ( $date_range[0] == null || $date_range[0] =="" )) {
            $query_type     = "second_single";
        }

        $results        = array('labels' => $label_arr , 'datasets' => [ [ 'borderColor' => 'rgb(255, 99, 132)' , 'label'  => esc_html__('Confirmed','wp-cafe') , 'data'  => [] ] ,
            [ 'borderColor' => 'rgb(75, 192, 192)' , 'label'  => esc_html__('Cancelled','wp-cafe') , 'data'  => [] ]
         ]);

        if ( "reservations" == $type ) {

			$base_query = "SELECT DISTINCT {$wpdb->posts}.ID AS id,
			(SELECT DISTINCT MONTHNAME(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = 'wpc_booking_date' AND post_id = {$wpdb->posts}.ID) AS wpc_booking_date,
			(SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'wpc_total_guest' AND post_id = {$wpdb->posts}.ID) AS wpc_total_guest,
			(SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'wpc_reservation_state' AND post_id = {$wpdb->posts}.ID) AS wpc_reservation_state
			FROM {$wpdb->posts} INNER JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
			WHERE {$wpdb->posts}.post_type='wpc_reservation' AND {$wpdb->postmeta}.meta_key IN ('wpc_booking_date','wpc_total_guest','wpc_reservation_state')";

			if ( "both" == $query_type ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $base_query contains only table names and static SQL, user input properly escaped via prepare() placeholders.
			$prepared_query = $wpdb->prepare($base_query . " AND {$wpdb->postmeta}.meta_value BETWEEN %s AND %s", $date_range[0], $date_range[1]);
			} else if ( "first_single" == $query_type ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $base_query contains only table names and static SQL, user input properly escaped via prepare() placeholders.
			$prepared_query = $wpdb->prepare($base_query . " AND {$wpdb->postmeta}.meta_value = %s", $date_range[0]);
			} else if ( "second_single" == $query_type ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $base_query contains only table names and static SQL, user input properly escaped via prepare() placeholders.
			$prepared_query = $wpdb->prepare($base_query . " AND {$wpdb->postmeta}.meta_value = %s", $date_range[1]);
			}

            $all_reservations = $wpdb->get_results($prepared_query, ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

            if (count($all_reservations)) {
                foreach ($all_reservations as $key => $value) {

                    if ( !in_array($value['wpc_booking_date'],$label_arr) ) {
                        $labels = $value['wpc_booking_date'] !== null ? $value['wpc_booking_date'] : "";
                        array_push($label_arr,$labels);
                    }

                    if ( "confirmed" == $value['wpc_reservation_state'] || "Processing" == $value['wpc_reservation_state'] ||
                     "completed" == $value['wpc_reservation_state'] ) {
                        array_push($confirm_arr, [ $value['wpc_booking_date'] => (int) 1 ]);
                    }

                    if ( "cancelled" == $value['wpc_reservation_state'] ) {
                        array_push($cancel_arr,[ $value['wpc_booking_date'] => (int) 1 ]);
                    }
                }

                if (count($confirm_arr)>0) {
                    $confirm_arr = self::chart_format_data($confirm_arr);
                }
                if (count($cancel_arr)>0) {
                    $cancel_arr  = self::chart_format_data($cancel_arr);
                }

                $results  = array('labels' => $label_arr , 
                    'datasets' => [ [ 'borderColor' => 'rgb(255, 99, 132)' , 'label'  => esc_html__('Confirmed','wp-cafe') , 'data'  => $confirm_arr ] ,
                    [ 'borderColor' => 'rgb(75, 192, 192)' , 'label'  => esc_html__('Cancelled','wp-cafe') , 'data'  => $cancel_arr ]
                ]);
            }

        } 

        else if ( "food_ordering" == $type ) {
  
            if ( "both" == $query_type ) {
                $query = $wpdb->prepare(
                    "SELECT DISTINCT MONTHNAME(post_date) as order_date,
                    SUM(1) as order_count,
                    CASE post_status
                        WHEN 'wc-processing' THEN 'wc-completed'
                        WHEN 'wc-completed' THEN 'wc-completed'
                        WHEN 'wc-refunded' THEN 'wc-refunded'
                    END AS new_status
                    FROM {$wpdb->posts}
                    WHERE post_type = 'shop_order' AND post_status IN ('wc-processing','wc-completed','wc-refunded')
                    AND post_date BETWEEN %s AND %s
                    GROUP BY order_date, new_status",
                    $date_range[0],
                    $date_range[1]
                );
            }
            else if ( "first_single" == $query_type ) {
                $query = $wpdb->prepare(
                    "SELECT DISTINCT MONTHNAME(post_date) as order_date,
                    SUM(1) as order_count,
                    CASE post_status
                        WHEN 'wc-processing' THEN 'wc-completed'
                        WHEN 'wc-completed' THEN 'wc-completed'
                        WHEN 'wc-refunded' THEN 'wc-refunded'
                    END AS new_status
                    FROM {$wpdb->posts}
                    WHERE post_type = 'shop_order' AND post_status IN ('wc-processing','wc-completed','wc-refunded')
                    AND post_date = %s
                    GROUP BY order_date, new_status",
                    $date_range[0]
                );
            }
            else if ( "second_single" == $query_type ) {
                $query = $wpdb->prepare(
                    "SELECT DISTINCT MONTHNAME(post_date) as order_date,
                    SUM(1) as order_count,
                    CASE post_status
                        WHEN 'wc-processing' THEN 'wc-completed'
                        WHEN 'wc-completed' THEN 'wc-completed'
                        WHEN 'wc-refunded' THEN 'wc-refunded'
                    END AS new_status
                    FROM {$wpdb->posts}
                    WHERE post_type = 'shop_order' AND post_status IN ('wc-processing','wc-completed','wc-refunded')
                    AND post_date = %s
                    GROUP BY order_date, new_status",
                    $date_range[1]
                );
            }

            $cache_key = Wpc_Utilities::get_query_cache( $query );
			$food_ordering = wp_cache_get( $cache_key, 'wpcafe_order_cache');

			if( !$food_ordering ) {

				$food_ordering = $wpdb->get_results($query, ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

				wp_cache_set($cache_key, $food_ordering, 'wpcafe_query_cache', 60);
			}
            
            

            if (count($food_ordering)) {

                foreach ($food_ordering as $key => $value) {
    
                    if (!in_array($value['order_date'],$label_arr)) {
                        array_push($label_arr,$value['order_date']);
                    }
                    if ( "wc-completed" == $value['new_status'] ) {
                        array_push($confirm_arr, $value['order_count'] );
                    }
                    if ( "wc-refunded" == $value['new_status']  ) {
                        array_push($cancel_arr, $value['order_count'] );
                    }
                }
    
                $results        = array('labels' => $label_arr , 
                    'datasets' => [ [ 'borderColor' => 'rgb(255, 99, 132)' , 'label'  => esc_html__('Confirmed','wp-cafe') , 'data'  => $confirm_arr ] ,
                    [ 'borderColor' => 'rgb(75, 192, 192)' , 'label'  => esc_html__('Refunded','wp-cafe') , 'data'  => $cancel_arr ]
                ]);
            }


        }
         
        return $results;

    }


    /**
     * get monthly reservation details
     */
    public function get_monthly_reservation(){
        $current_timestamp = current_time( 'timestamp' );
        $start = wp_date( 'Y-m-01', $current_timestamp );
        $end   = wp_date( 'Y-m-t', $current_timestamp );

        $meta_query = 
        array(
            'relation' => 'AND',
            array(
                'key'           => 'wpc_reservation_state',
                'value'         => array( 'Confirmed', 'Completed', 'Processing' ),
                'compare'       => 'IN'
            ),
            array(
                'key'     => 'wpc_booking_date',
                'value'   => array($start, $end),
                'compare' => 'BETWEEN',
            )
        );
        
        $all_reservations = get_posts(
            array(
                'post_type'         => 'wpc_reservation',
                'numberposts'       => -1,
                'post_status'       => 'publish',
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- required for report/filter functionality
                'meta_query'        => $meta_query
            )
        );

        return count($all_reservations);
    }

    // Convert reservation form email template tags 
    public function filter_template_tags( $reservation_id, $content, $invoice="" ){

        $wpc_date_format    = get_option('date_format');
        $wpc_time_format    = get_option('time_format');
        $wpc_booking_date   = get_post_meta( $reservation_id, 'wpc_booking_date', true );
        $time_start         = get_post_meta( $reservation_id, 'wpc_from_time', true );
        $time_end           = get_post_meta( $reservation_id, 'wpc_to_time', true );
        $reservation_invoice= ( isset($invoice) || $invoice !="" ) ? $invoice : get_post_meta( $reservation_id, 'wpc_reservation_invoice', true);
         $schedule_1         = $time_start !=="" ? esc_html__(' Start time ', 'wp-cafe') . Wpc_Utilities::get_formatted_time( $time_start ) : " ";
        $schedule_2         = $time_end !=="" ? esc_html__(' End time ', 'wp-cafe'). Wpc_Utilities::get_formatted_time( $time_end ) : " ";
        $separator          = ( $time_start !=="" && $time_end !=="" ) ? " : " : "";

        //pro active tag list check

        $wpc_tag_arr = [
            '{site_name}',
            '{site_link}',
            '{user_name}',
            '{user_email}',
            '{phone}',
            '{message}',
            '{party}',
            '{date}',
            '{current_time}',
            '{invoice_no}',
            '{branch_name}',
            '{extra_field}'
        ];
		
        $wpc_value_arr = [
            get_bloginfo( 'name' ),
            get_option( 'home' ),
            get_post_meta( $reservation_id, 'wpc_name', true ),
            get_post_meta( $reservation_id, 'wpc_email', true ),
            get_post_meta( $reservation_id, 'wpc_phone', true ),
            get_post_meta( $reservation_id, 'wpc_message', true ),
            get_post_meta( $reservation_id, 'wpc_total_guest', true ),
            date_i18n($wpc_date_format, strtotime( $wpc_booking_date ) ).' ' . $schedule_1 . $separator. $schedule_2,
            date_i18n( $wpc_date_format . ' ' . $wpc_time_format ),
            $reservation_invoice,
            get_post_meta( $reservation_id, 'wpc_branch', true ),
            $this->get_extra_fields($reservation_id)
        ];

        return str_replace( $wpc_tag_arr, $wpc_value_arr , $content );
    }

    public function get_extra_fields($reservation_id){
        $reserv_extra   = get_post_meta($reservation_id, 'reserv_extra', true);
        $output = "";
        if(is_array($reserv_extra) && !empty($reserv_extra)){
            for ($i=0; $i < count( $reserv_extra ) ; $i++) {
                $value = get_post_meta($reservation_id, 'reserv_extra_'.$i, true);
                if(!empty($value)){
                    $output .= "{$reserv_extra[$i]['label']}: {$value}\n";
                }
            }    
        }
        
        return $output;
    }

}