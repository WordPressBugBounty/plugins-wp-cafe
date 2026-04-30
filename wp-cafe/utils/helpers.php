<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- wpc_ / wpcafe_ are the plugin's registered prefixes.

use WpCafe\Session;
use WpCafe\Validation\Validator;
use WpCafe\Validation\Rule_Factory;
use WpCafe\Settings;
use WpCafe\Models\Location_Model;


/**
 * Global helper to validate data.
 *
 * @param array $data
 * @param array $rules
 * @return true|WP_Error
 */

if ( ! function_exists('wpcafe_validate') ) {
    /**
     * Validate data against rules.
     *
     * @param array $data
     * @param array $rules
     * @return true|WP_Error
     */
    function wpcafe_validate($data, $rules) {
        $parsed_rules = [];

        foreach ($rules as $field => $rule_set) {
            $parsed_rules[$field] = Rule_Factory::make($rule_set);
        }

        $validator = new Validator($data, $parsed_rules);

        if ( ! $validator->passes()) {
            $errors = $validator->errors();
            $wp_error = new WP_Error();

            foreach ($errors as $field => $messages) {
                foreach ((array) $messages as $message) {
                    $wp_error->add($field, $message);
                }
            }

            return $wp_error;
        }

        return true;
    }
}

if ( ! function_exists('wpcafe_extension') ) {
    /**
     * Get a specific extension by slug.
     *
     * @param string $slug
     * @return Object \Arraytics\Tools\Extention
     */
    function wpcafe_extension() {
        $extensions = wpcafe_get_extension_list();

        return new Arraytics\ToolsSdk\Extension( 'wpcafe_tools_settings', $extensions );
    }
}

if ( ! function_exists( 'wpc_is_module_enable' ) ) {
    /**
     * Check whether a module or submodule is enabled.
     *
     * Conditions:
     * - If the module has no parent and status === 'on' → true
     * - If the module has a parent, and both the parent and the module have status === 'on'  true
     * - Else → false
     *
     * @param string $module The extension module (module or submodule).
     *
     * @return bool
     */
    function wpc_is_module_enable( $module ) {
        $test_module = wpcafe_extension()->find( $module );
        $is_pro = ! empty( $test_module['is_pro'] ) ? $test_module['is_pro'] : false;

        if ( $is_pro && ! function_exists('wpcafe_pro') ) {
            return false;
        }


        return wpcafe_extension()->is_enabled( $module );
    }
}

if ( ! function_exists( 'wpc_is_integration_enable' ) ) {
    /**
     * Check whether a integration or subintegration is enabled.
     *
     * Conditions:
     * - If the integration has no parent and status === 'on' → true
     * - If the integration has a parent, and both the parent and the integration have status === 'on'  true
     * - Else → false
     *
     * @param string $integration The integration module (module or submodule).
     *
     * @return bool
     */
    function wpc_is_integration_enable( $integration ) {
        return wpc_integration()->is_enabled( $integration );
    }
}


if ( ! function_exists( 'wpc_get_pickup_delivery_properties' ) ) {
    /**
     * Get pickup delivery properties
     *
     * @return  array
     */
    function wpc_get_pickup_delivery_properties() {
        $pickup_properties = [
            'wpc_location_name'     => __( 'Food Order Location', 'wp-cafe' ),
            'wpc_pro_order_time'    => __( 'Delivery Type', 'wp-cafe' ),
            'wpc_pro_delivery_date' => __( 'Delivery Date', 'wp-cafe' ),
            'wpc_pro_delivery_time' => __( 'Delivery Time', 'wp-cafe' ),
            'wpc_pro_pickup_date'   => __( 'Pickup Date', 'wp-cafe' ),
            'wpc_pro_pickup_time'   => __( 'Pickup Time', 'wp-cafe' ),
        ];

        return $pickup_properties;
    }
}

if ( ! function_exists( 'wpc_get_option' ) ) {
    /**
     * Get wp cafe settings
     *
     * @param   string  $key  Option name
     *
     * @return  mixed   option data name
     */
    function  wpc_get_option($key = null, $default = false) {
        $data = Settings::get($key);

        if ( '' == $data ) {
            return $default;
        }

        return $data;
    }
}

if ( ! function_exists( 'wpc_update_option' ) ) {
    /**
     * Get wp cafe settings
     *
     * @param   string  $key  Option name
     *
     * @return  mixed   option data name
     */
    function  wpc_update_option($key, $value) {
        return Settings::update([$key => $value]);
    }
}

if ( ! function_exists( 'wpc_selected_location_id' ) ) {
    /**
     * Get selected location ID from session
     *
     * @return int|null Selected location ID or null if invalid/disabled
     */
    function  wpc_selected_location_id() {
        if ( ! wpc_is_module_enable( 'location' ) ) {
            return null;
        }

        $session_location_id = Session::get('selected_location');

        if ( ! $session_location_id && ! empty( $_COOKIE['wpc_selected_location'] ) ) {
            $session_location_id = absint( $_COOKIE['wpc_selected_location'] );
            if ( $session_location_id ) {
                Session::set( 'selected_location', $session_location_id );
            }
        }

        if ( ! $session_location_id ) {
            return null;
        }

        $location = Location_Model::find( $session_location_id );

        if ( ! $location ) {
            Session::delete('selected_location');
            return null;
        }

        return (int) $session_location_id;
    }
}

if ( ! function_exists( 'wpc_get_table_id_from_session' ) ) {
    /**
     * Get table ID from session
     *
     * @return  string|null  Table ID from session
     */
    function wpc_get_table_id_from_session() {
        return Session::get('wpc_table_id');
    }
}

if ( ! function_exists( 'wpc_get_plugin_status' ) ) {
    /**
     * Get plugin status
     *
     * @param   string  $slug  The plugin slug
     *
     * @return  string Plugin current status
     */
    function wpc_get_plugin_status($slug) {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        include_once ABSPATH . 'wp-admin/includes/file.php';

        $plugins = get_plugins();

        foreach ( $plugins as $path => $plugin ) {
            // Check if the plugin path starts with the slug followed by a slash
            if ( strpos( $path, $slug . '/' ) === 0 ) {
                if ( is_plugin_active( $path ) ) {
                    return 'active';
                } else {
                    return 'inactive';
                }
            }
            // Check if the plugin file exactly matches the slug.php
            if ( $path === $slug . '.php' ) {
                if ( is_plugin_active( $path ) ) {
                    return 'active';
                } else {
                    return 'inactive';
                }
            }
        }

        return 'not-installed';
    }
}

if ( ! function_exists( 'wpc_get_currencies' ) ) {
    /**
     * Get currency list
     *
     * @return  array
     */
    function wpc_get_currencies() {
        $currencies = require_once __DIR__ . '/currency.php';

        return $currencies;
    }
}

if ( ! function_exists('wpcafe_get_extension_list') ) {
    /**
     * Get a specific extension by slug.
     *
     * @param string $slug
     * @return array|null
     */
    function wpcafe_get_extension_list() {
        $existing_tools_settings      = get_option( 'wpcafe_tools_settings' );
        $table_layout_status          = isset( $tools_settings['enable_table_layout'] ) ? $existing_tools_settings['enable_table_layout'] : 'on';
        $delivery_module_status       = isset( $existing_tools_settings['enable_delivery_module'] ) ? $existing_tools_settings['enable_delivery_module'] : 'on';

        $pickup_module_status       = isset( $existing_tools_settings['enable_pickup_module'] ) ? $existing_tools_settings['enable_pickup_module'] : 'on';

        $extension_list = require wpcafe()->plugin_directory . '/utils/extension-list.php';

        return $extension_list;
    }
}

if ( ! function_exists('wpc_get_integration_list') ) {
    /**
     * Get a specific extension by slug.
     *
     * @param string $slug
     * @return array|null
     */
    function wpc_get_integration_list() {
        $integration_list = require wpcafe()->plugin_directory . '/utils/integration-list.php';

        return $integration_list;
    }
}

if ( ! function_exists('wpc_integration') ) {
    /**
     * Get integration class object
     *
     * @return \Arraytics\ToolsSdk\Extension
     */
    function wpc_integration() {
        $integration_list = wpc_get_integration_list();

        return new Arraytics\ToolsSdk\Extension( 'wpcafe_integration_settings', $integration_list );
    }
}


if ( ! function_exists( 'wpc_get_addons_icon_url' ) ) {
    /**
     * Get addons icon url
     *
     * @return string
     */
    function wpc_get_addons_icon_url($name) {
        
        return wpcafe()->assets_url . '/images/addons/' . $name . '.svg';
    }
}

if ( ! function_exists('wpc_get_pages') ) {
    /**
     * Get all WordPress pages with only ID and title
     *
     * @return array
     */
    function wpc_get_pages() {
        $pages = get_pages( array(
            'sort_order'  => 'asc',
            'sort_column' => 'post_title',
            'post_status' => 'publish',
        ) );

        $result = array();

        foreach ( $pages as $page ) {
            $result[] = array(
                'id'    => $page->ID,
                'title' => $page->post_title,
            );
        }

        return $result;
    }
}

if ( ! function_exists( 'wpc_get_reservation_schedule' ) ) {
    /**
     * Get reservation schedule
     * @param   int $selected_location_id the branch/location id for getting schedule null means using global settings.
     * @return  array
     */
    function wpc_get_reservation_schedule( $selected_location_id = null ) {
        if ( ! $selected_location_id ) {
            $selected_location_id = wpc_selected_location_id();
        }

        $location   = Location_Model::find( $selected_location_id );

        if ( $location && $location->override_reservation_schedule && ! empty( $location->reservation_schedule ) ) {
            return $location->reservation_schedule;
        }

        if ( $location && $location->override_restaurant_schedule && ! empty( $location->restaurant_schedule ) ) {
            return $location->restaurant_schedule;
        }

        $override_reservation_schedule = wpc_get_option('override_reservation_schedule', false);
        $reservation_schedule = wpc_get_option('reservation_schedule', []);
        
        if ( $override_reservation_schedule && ! empty( $reservation_schedule ) ) {
            return $reservation_schedule;
        }

        return wpc_get_option('restaurant_schedule', []);
    }
}

if ( ! function_exists( 'wpc_get_reservation_slot_interval' ) ) {
    /**
     * Get reservation slot interval
     *
     * @return  int
     */
    function wpc_get_reservation_slot_interval( $selected_location_id = null ) {
        if ( ! isset( $selected_location_id ) ) {
            $selected_location_id = wpc_selected_location_id();
        }

        $location   = Location_Model::find( $selected_location_id );

        if ( $location && $location->override_reservation_schedule && ! empty( $location->reservation_slot_interval ) ) {
            return $location->reservation_slot_interval;
        }

        if ( $location && $location->override_restaurant_schedule && ! empty( $location->slot_interval ) ) {
            return $location->slot_interval;
        }

        $override_reservation_schedule = wpc_get_option('override_reservation_schedule', false);
        $reservation_slot_interval = wpc_get_option('reservation_slot_interval', 30);
        
        if ( $override_reservation_schedule && ! empty( $reservation_slot_interval ) ) {
            return $reservation_slot_interval;
        }

        return wpc_get_option('slot_interval', 30);
    }
}

if ( ! function_exists( 'wpc_get_schedule' ) ) {
    /**
     * Get schedule
     *
     * @return  array
     */
    function wpc_get_schedule() {
        return wpc_get_option('restaurant_schedule', wpc_get_default_schedule());
    }
}

if ( ! function_exists( 'wpc_get_default_schedule' ) ) {
    /**
     * Get default schedule
     *
     * @return  array
     */
    function wpc_get_default_schedule() {
        return [
            'Mon' => [
                'status' => 'on',
                'slots' => [
                    [
                        'start' => '8:00 AM',
                        'end'   => '10:00 PM',
                    ],
                ],
            ],
            'Tue' => [
                'status' => 'on',
                'slots' => [
                    [
                        'start' => '8:00 AM',
                        'end'   => '10:00 PM',
                    ],
                ],
            ],
            'Wed' => [
                'status' => 'on',
                'slots' => [
                    [
                        'start' => '8:00 AM',
                        'end'   => '10:00 PM',
                    ],
                ],
            ],
            'Thu' => [
                'status' => 'on',
                'slots' => [
                    [
                        'start' => '8:00 AM',
                        'end'   => '10:00 PM',
                    ],
                ],
            ],
            'Fri' => [
                'status' => 'on',
                'slots' => [
                    [
                        'start' => '8:00 AM',
                        'end'   => '10:00 PM',
                    ],
                ],
            ],
            'Sat' => [
                'status' => 'off',
                'slots' => [
                    [
                        'start' => '8:00 AM',
                        'end'   => '10:00 PM',
                    ],
                ],
            ],
            'Sun' => [
                'status' => 'off',
                'slots' => [
                    [
                        'start' => '8:00 AM',
                        'end'   => '10:00 PM',
                    ],
                ],
            ],
        ];
    }
}

if ( ! function_exists( 'wpc_get_reservation_capacity' ) ) {
    /**
     * Get reservation capacity
     *
     * @return  int
     */
    function wpc_get_reservation_capacity( $location_id = null ) {
        if ( $location_id ) {
            $location = Location_Model::find( $location_id );

            if ( $location && $location->override_reservation && ! empty( $location->reservation_total_seat_capacity ) ) {
                return $location->reservation_total_seat_capacity;
            }
        }
        return wpc_get_option( 'reservation_total_seat_capacity', 100 );
    }
}

if ( ! function_exists( 'wpc_get_reservation_advanced' ) ) {
    /**
     * Get reservation advanced booking setting (minimum lead time before reservation)
     *
     * This determines how early customers must book before their reservation.
     * Example: If set to 2 days, customers can't book for today or tomorrow.
     *
     * @param int|null $location_id Optional location ID for location-specific settings
     * @return array { value: int, unit: string } Default: ['value' => 30, 'unit' => 'minutes']
     */
    function wpc_get_reservation_advanced($location_id = null) {
        if ( ! isset( $location_id ) ) {
            $location_id = wpc_selected_location_id();
        }

        if ( $location_id ) {
            $location = Location_Model::find( $location_id );

            if ( $location && $location->override_reservation && ! empty( $location->reservation_advanced ) ) {
                return $location->reservation_advanced;
            }
        }
        
        return wpc_get_option( 'reservation_advanced', ['value' => 30, 'unit' => 'minutes'] );
    }
}

if ( ! function_exists( 'wpc_get_reservation_early_booking_time' ) ) {
    /**
     * Get reservation early booking time limit (maximum booking horizon)
     *
     * This determines how far in advance customers can make reservations.
     * Example: If set to 4 days, customers can only book up to 4 days ahead.
     *
     * @param int|null $location_id Optional location ID for location-specific settings
     * @return string|array "any_time" or { value: int, unit: string } Default: "any_time"
     */
    function wpc_get_reservation_early_booking_time($location_id = null) {
        if ( ! isset( $location_id ) ) {
            $location_id = wpc_selected_location_id();
        }

        if ( $location_id ) {
            $location = Location_Model::find( $location_id );

            if ( $location && $location->override_reservation && ! empty( $location->reservation_early_booking_time ) ) {
                return $location->reservation_early_booking_time;
            }
        }
        
        return wpc_get_option( 'reservation_early_booking_time', 'any_time' );
    }
}

if ( ! function_exists( 'wpc_get_last_order_id' ) ) {
    /**
     * Get the last WooCommerce order ID stored in the transient.
     *
     * Retrieves the last order data from the 'wpc_last_order' transient and returns the order ID.
     * Returns null if no order is found.
     *
     * @since 1.0.0
     *
     * @return int|null The last order ID, or null if not found.
     */
    function wpc_get_last_order_id() {
        if ( ! function_exists( 'wc_get_orders' )) {
            return null;
        }

        $orders = wc_get_orders([
            'limit' => 1,
            'status' => ['wc-processing', 'wc-completed']
        ]);

        if ( ! $orders ) {
            return null;
        }

        return $orders[0]->get_id();
    }
}

if ( ! function_exists( 'wpc_user_is_dokan_vendor' ) ) {
    /**
     * Check if current user is a Dokan vendor
     *
     * @since 3.0.2
     *
     * @return bool True if user is a Dokan vendor, false otherwise
     */
    function wpc_user_is_dokan_vendor() {
        return function_exists('dokan_is_user_seller') && dokan_is_user_seller(get_current_user_id());
    }
}

if ( ! function_exists( 'wpcafe_our_plugins_list' ) ) {
    /**
     * Returns the list of Arraytics sibling products shown on the About Us "Our Plugins" section.
     * Status is computed at runtime by Our_Plugins_Controller using PluginManager.
     *
     * @return array
     */
    function wpcafe_our_plugins_list(): array {
        return [
            'eventin' => [
                'name'        => 'eventin',
                'slug'        => 'wp-event-solution',
                'title'       => __( 'Eventin', 'wp-cafe' ),
                'description' => __( 'Complete event management — create events, sell tickets, and engage attendees right from WordPress.', 'wp-cafe' ),
                'is_pro'      => false,
                'doc_link'    => 'https://support.themewinter.com/docs/plugins/docs/eventin/',
                'demo_link'   => '',
                'icon'        => '<svg width="150" height="40px" viewBox="0 0 4698 1080" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1471.62 683.712H1011.35C1019.26 806.694 1065.5 850.702 1186.2 850.702C1279.84 850.702 1331.73 822.497 1341.89 766.086H1462.57C1452.43 892.468 1354.28 958.999 1183.94 958.999C977.502 958.999 885 860.831 885 644.165C885 430.899 976.369 333.863 1179.43 333.863C1383.62 333.863 1471.62 423.085 1471.62 648.674V683.712ZM1013.57 576.5H1340.71C1335.07 481.731 1288.81 442.232 1180.51 442.232C1073.4 442.232 1027.14 478.331 1013.62 576.5H1013.57Z" fill="currentColor"/>
                    <path d="M1927.63 350.314H2058.23L1899.43 942.548H1676.55L1517.75 350.314H1648.36L1778.96 835.164H1798.21L1927.63 350.314Z" fill="currentColor"/>
                    <path d="M2690.98 683.712H2230.69C2238.6 806.694 2284.84 850.702 2405.55 850.702C2499.18 850.702 2551.08 822.497 2561.24 766.086H2681.94C2671.79 892.468 2573.63 958.999 2403.31 958.999C2196.85 958.999 2104.37 860.831 2104.37 644.165C2104.37 430.899 2195.74 333.863 2398.8 333.863C2602.99 333.863 2690.98 423.085 2690.98 648.674V683.712ZM2232.94 576.5H2560.08C2554.44 481.731 2508.17 442.232 2399.88 442.232C2292.75 442.232 2246.48 478.331 2232.94 576.5Z" fill="currentColor"/>
                    <path d="M3320.44 569.9V942.548H3191.95V572.144C3191.95 480.683 3151.39 438.886 3069.11 438.886C2975.56 438.886 2928.23 506.649 2928.23 643.285V942.524H2799.74V350.804H2928.23V443.399C2967.66 369.989 3030.79 333.863 3117.57 333.863C3260.71 333.887 3320.44 402.76 3320.44 569.9Z" fill="currentColor"/>
                    <path d="M3801.41 459.056H3623.9V745.013C3623.9 782.166 3630.62 809.188 3645.25 826.08C3659.87 842.971 3682.32 850.84 3712.64 850.84C3743.79 850.75 3774.39 842.612 3801.48 827.21V933.085C3773.39 949.929 3739.68 959 3698.24 959C3560.03 959 3496 890.302 3496 745.062V459.056H3406.13V352.099H3496V198.966H3624.09V352.099H3801.6L3801.41 459.056Z" fill="currentColor"/>
                    <path d="M3893.88 197.844C3893.88 138.038 3911.94 120 3971.85 120C4033.98 120 4052.06 138.038 4052.06 197.844C4052.06 255.408 4033.98 272.336 3971.85 272.336C3911.94 272.36 3893.88 255.48 3893.88 197.844ZM3908.56 942.548V351.241H4037.38V942.548H3908.56Z" fill="currentColor"/>
                    <path d="M4698 569.9V942.548H4569.5V572.144C4569.5 480.683 4528.94 438.886 4446.67 438.886C4353.12 438.886 4305.79 506.649 4305.79 643.285V942.524H4177.3V350.804H4305.79V443.399C4345.24 369.989 4408.35 333.863 4495.13 333.863C4638.27 333.887 4698 402.76 4698 569.9Z" fill="currentColor"/>
                    <path d="M676.868 519.739L473.555 723.166L342.501 854.334L278.29 790.08L212.558 724.346C196.593 708.41 183.724 689.647 174.602 669.01C144.64 601.521 157.306 519.506 212.558 464.253C238.842 437.962 272.463 420.251 308.997 413.449C345.532 406.647 383.268 411.073 417.241 426.145L341.775 501.675C279.749 563.749 279.742 664.397 341.775 726.477L520.292 547.824L641.434 426.59C626.098 399.157 607.043 373.979 584.812 351.773C569.167 336.109 552.052 321.989 533.704 309.606C510.39 293.855 485.205 281.072 458.731 271.553C426.087 298.743 384.958 313.63 342.488 313.63C300.017 313.63 258.888 298.743 226.244 271.553C178.832 288.682 135.792 316.089 100.204 351.814C-17.4573 469.518 -31.5065 652.277 58.0978 785.528C70.4454 803.912 84.5446 821.055 100.198 836.716L165.909 902.478L342.501 1079.23L584.832 836.716C670.614 750.855 701.323 630.375 676.868 519.739Z" fill="url(#paint0_linear_11013_17)"/>
                    <path d="M342.695 262.505C415.066 262.505 473.735 203.791 473.735 131.365C473.735 58.9385 415.066 0.225159 342.695 0.225159C270.324 0.225159 211.655 58.9385 211.655 131.365C211.655 203.791 270.324 262.505 342.695 262.505Z" fill="url(#paint1_linear_11013_17)"/>
                    <defs>
                        <linearGradient id="paint0_linear_11013_17" x1="94.4163" y1="1012.39" x2="568.462" y2="214.617" gradientUnits="userSpaceOnUse">
                        <stop offset="0.18" stop-color="#702CE7"/>
                        <stop offset="0.82" stop-color="#FF4A97"/>
                        </linearGradient>
                        <linearGradient id="paint1_linear_11013_17" x1="202.285" y1="310.306" x2="484.783" y2="-49.1695" gradientUnits="userSpaceOnUse">
                        <stop offset="0.33" stop-color="#702CE7"/>
                        <stop offset="0.87" stop-color="#FF4A97"/>
                        </linearGradient>
                    </defs>
                    </svg>
',
            ],
                'timetics' => [
                'name'        => 'timetics',
                'slug'        => 'timetics',
                'title'       => __( 'Timetics', 'wp-cafe' ),
                'description' => __( 'Smart appointment scheduling — let clients book 24/7 with calendar sync, team management, and automated reminders.', 'wp-cafe' ),
                'is_pro'      => false,
                'doc_link'    => 'https://docs.arraytics.com/docs/timetics/getting-started/',
                'demo_link'   => '',
                'icon'        => '<svg width="150px" height="40px" viewBox="0 0 154 31" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                        <rect width="154" height="31" fill="url(#pattern0_4929_5696)"/>
                        <defs>
                            <pattern id="pattern0_4929_5696" patternContentUnits="objectBoundingBox" width="1" height="1">
                            <use xlink:href="#image0_4929_5696" transform="matrix(0.000904708 0 0 0.00460829 0.000328985 0)"/>
                            </pattern>
                            <image id="image0_4929_5696" width="1104" height="217" preserveAspectRatio="none" xlink:href="data:image/png;base64,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"/>
                        </defs>
                </svg>
',
            ],
                'booktics' => [
                'name'          => 'booktics',
                'slug'          => 'booktics',
                'type'          => 'plugin',
                'upgrade'       => false,
                'upgrade_link'  => 'https://arraytics.com/booktics',
                'status'        => 'on',
                'is_pro'        => false,
                'title'         => __( 'Booktics', 'wp-cafe' ),
                'description'   => __( 'The ultimate online booking plugin for WordPress — services, staff, schedules, and payments in one place.', 'wp-cafe' ),
                'icon'          => '<svg width="150px" height="40px" viewBox="0 0 154 33" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M45.2714 26V6.47646H52.7452C54.1561 6.47646 55.3287 6.69889 56.2629 7.14376C57.2035 7.58228 57.9057 8.18286 58.3697 8.9455C58.84 9.70814 59.0751 10.5725 59.0751 11.5385C59.0751 12.3329 58.9226 13.0129 58.6175 13.5785C58.3125 14.1378 57.9026 14.5922 57.3878 14.9417C56.873 15.2913 56.2978 15.5423 55.6623 15.6948V15.8855C56.355 15.9236 57.0192 16.1365 57.6547 16.5242C58.2966 16.9055 58.8209 17.4457 59.2276 18.1448C59.6344 18.8439 59.8378 19.6892 59.8378 20.6806C59.8378 21.6911 59.5931 22.5999 59.1037 23.407C58.6144 24.2078 57.8771 24.8402 56.8921 25.3041C55.907 25.768 54.6677 26 53.1742 26H45.2714ZM48.8081 23.0448H52.6118C53.8955 23.0448 54.8202 22.8001 55.3859 22.3107C55.9578 21.815 56.2438 21.1795 56.2438 20.4041C56.2438 19.8258 56.1008 19.3047 55.8148 18.8407C55.5289 18.3704 55.1221 18.0018 54.5946 17.7349C54.0671 17.4616 53.438 17.325 52.7071 17.325H48.8081V23.0448ZM48.8081 14.7797H52.3067C52.9168 14.7797 53.4666 14.6685 53.9559 14.446C54.4453 14.2172 54.8298 13.8963 55.1094 13.4832C55.3954 13.0637 55.5384 12.568 55.5384 11.9961C55.5384 11.2398 55.2715 10.6169 54.7376 10.1276C54.2101 9.63823 53.4252 9.39355 52.383 9.39355H48.8081V14.7797ZM68.6122 26.286C67.1823 26.286 65.943 25.9714 64.8943 25.3422C63.8457 24.713 63.0322 23.8328 62.4539 22.7016C61.8819 21.5703 61.5959 20.2484 61.5959 18.7359C61.5959 17.2233 61.8819 15.8982 62.4539 14.7606C63.0322 13.623 63.8457 12.7396 64.8943 12.1104C65.943 11.4813 67.1823 11.1667 68.6122 11.1667C70.0421 11.1667 71.2814 11.4813 72.3301 12.1104C73.3787 12.7396 74.189 13.623 74.761 14.7606C75.3393 15.8982 75.6285 17.2233 75.6285 18.7359C75.6285 20.2484 75.3393 21.5703 74.761 22.7016C74.189 23.8328 73.3787 24.713 72.3301 25.3422C71.2814 25.9714 70.0421 26.286 68.6122 26.286ZM68.6313 23.5214C69.4066 23.5214 70.0549 23.3085 70.576 22.8827C71.0971 22.4506 71.4848 21.8722 71.739 21.1477C71.9996 20.4232 72.1299 19.6161 72.1299 18.7263C72.1299 17.8302 71.9996 17.0199 71.739 16.2954C71.4848 15.5646 71.0971 14.9831 70.576 14.5509C70.0549 14.1187 69.4066 13.9026 68.6313 13.9026C67.8368 13.9026 67.1759 14.1187 66.6484 14.5509C66.1273 14.9831 65.7364 15.5646 65.4758 16.2954C65.2216 17.0199 65.0945 17.8302 65.0945 18.7263C65.0945 19.6161 65.2216 20.4232 65.4758 21.1477C65.7364 21.8722 66.1273 22.4506 66.6484 22.8827C67.1759 23.3085 67.8368 23.5214 68.6313 23.5214ZM84.4077 26.286C82.9777 26.286 81.7384 25.9714 80.6898 25.3422C79.6412 24.713 78.8277 23.8328 78.2494 22.7016C77.6774 21.5703 77.3914 20.2484 77.3914 18.7359C77.3914 17.2233 77.6774 15.8982 78.2494 14.7606C78.8277 13.623 79.6412 12.7396 80.6898 12.1104C81.7384 11.4813 82.9777 11.1667 84.4077 11.1667C85.8376 11.1667 87.0769 11.4813 88.1255 12.1104C89.1742 12.7396 89.9845 13.623 90.5565 14.7606C91.1348 15.8982 91.424 17.2233 91.424 18.7359C91.424 20.2484 91.1348 21.5703 90.5565 22.7016C89.9845 23.8328 89.1742 24.713 88.1255 25.3422C87.0769 25.9714 85.8376 26.286 84.4077 26.286ZM84.4267 23.5214C85.2021 23.5214 85.8503 23.3085 86.3715 22.8827C86.8926 22.4506 87.2803 21.8722 87.5345 21.1477C87.7951 20.4232 87.9253 19.6161 87.9253 18.7263C87.9253 17.8302 87.7951 17.0199 87.5345 16.2954C87.2803 15.5646 86.8926 14.9831 86.3715 14.5509C85.8503 14.1187 85.2021 13.9026 84.4267 13.9026C83.6323 13.9026 82.9714 14.1187 82.4439 14.5509C81.9227 14.9831 81.5319 15.5646 81.2713 16.2954C81.0171 17.0199 80.89 17.8302 80.89 18.7263C80.89 19.6161 81.0171 20.4232 81.2713 21.1477C81.5319 21.8722 81.9227 22.4506 82.4439 22.8827C82.9714 23.3085 83.6323 23.5214 84.4267 23.5214ZM96.9619 21.4051L96.9524 17.2392H97.5053L102.768 11.3573H106.8L100.327 18.5643H99.6121L96.9619 21.4051ZM93.8161 26V6.47646H97.267V26H93.8161ZM103.006 26L98.2394 19.3364L100.565 16.9055L107.134 26H103.006ZM115.875 11.3573V14.0266H107.457V11.3573H115.875ZM109.535 7.84921H112.986V21.5958C112.986 22.0597 113.056 22.4156 113.196 22.6635C113.342 22.905 113.533 23.0702 113.768 23.1592C114.003 23.2481 114.264 23.2926 114.55 23.2926C114.766 23.2926 114.963 23.2767 115.141 23.245C115.325 23.2132 115.465 23.1846 115.56 23.1592L116.142 25.857C115.957 25.9206 115.694 25.9905 115.35 26.0667C115.014 26.143 114.6 26.1875 114.111 26.2002C113.247 26.2256 112.468 26.0953 111.776 25.8093C111.083 25.517 110.533 25.0658 110.126 24.4557C109.726 23.8455 109.529 23.0829 109.535 22.1677V7.84921ZM118.224 26V11.3573H121.675V26H118.224ZM119.959 9.27915C119.412 9.27915 118.942 9.09803 118.548 8.73577C118.154 8.36716 117.957 7.92547 117.957 7.41069C117.957 6.88955 118.154 6.44786 118.548 6.0856C118.942 5.717 119.412 5.53269 119.959 5.53269C120.512 5.53269 120.982 5.717 121.37 6.0856C121.764 6.44786 121.961 6.88955 121.961 7.41069C121.961 7.92547 121.764 8.36716 121.37 8.73577C120.982 9.09803 120.512 9.27915 119.959 9.27915ZM131.074 26.286C129.612 26.286 128.357 25.965 127.308 25.3232C126.266 24.6813 125.462 23.7947 124.896 22.6635C124.337 21.5259 124.057 20.2167 124.057 18.7359C124.057 17.2487 124.343 15.9364 124.915 14.7987C125.487 13.6548 126.295 12.765 127.337 12.1295C128.385 11.4876 129.625 11.1667 131.055 11.1667C132.243 11.1667 133.295 11.3859 134.21 11.8245C135.132 12.2566 135.866 12.8699 136.412 13.6643C136.959 14.4524 137.27 15.3739 137.346 16.4289H134.048C133.915 15.7234 133.597 15.1356 133.095 14.6653C132.599 14.1886 131.935 13.9503 131.102 13.9503C130.397 13.9503 129.777 14.141 129.243 14.5223C128.71 14.8973 128.293 15.4375 127.995 16.1429C127.702 16.8483 127.556 17.6936 127.556 18.6787C127.556 19.6765 127.702 20.5344 127.995 21.2526C128.287 21.9644 128.697 22.5141 129.224 22.9018C129.758 23.2831 130.384 23.4738 131.102 23.4738C131.611 23.4738 132.065 23.3784 132.466 23.1878C132.872 22.9908 133.212 22.7079 133.486 22.3393C133.759 21.9707 133.946 21.5227 134.048 20.9952H137.346C137.264 22.0311 136.959 22.9494 136.431 23.7502C135.904 24.5446 135.186 25.1675 134.277 25.6187C133.368 26.0636 132.3 26.286 131.074 26.286ZM151.216 15.2277L148.07 15.5709C147.981 15.2532 147.826 14.9545 147.603 14.6748C147.387 14.3952 147.095 14.1696 146.726 13.998C146.358 13.8264 145.906 13.7406 145.373 13.7406C144.654 13.7406 144.051 13.8963 143.561 14.2077C143.078 14.5191 142.84 14.9227 142.846 15.4184C142.84 15.8442 142.996 16.1906 143.313 16.4575C143.638 16.7244 144.171 16.9437 144.915 17.1153L147.413 17.6491C148.798 17.9478 149.828 18.4213 150.501 19.0695C151.181 19.7178 151.524 20.5662 151.531 21.6148C151.524 22.5364 151.254 23.3498 150.721 24.0553C150.193 24.7544 149.459 25.3009 148.518 25.6949C147.578 26.089 146.497 26.286 145.277 26.286C143.485 26.286 142.042 25.911 140.949 25.1611C139.856 24.4048 139.205 23.353 138.995 22.0057L142.36 21.6816C142.513 22.3425 142.837 22.8414 143.332 23.1782C143.828 23.5151 144.473 23.6835 145.268 23.6835C146.087 23.6835 146.745 23.5151 147.241 23.1782C147.743 22.8414 147.994 22.4251 147.994 21.9294C147.994 21.51 147.832 21.1636 147.508 20.8903C147.19 20.617 146.694 20.4073 146.021 20.2611L143.523 19.7368C142.119 19.4445 141.08 18.952 140.406 18.2592C139.732 17.5601 139.399 16.6767 139.405 15.6091C139.399 14.7066 139.643 13.9249 140.139 13.2639C140.641 12.5966 141.337 12.0818 142.227 11.7196C143.123 11.351 144.155 11.1667 145.325 11.1667C147.041 11.1667 148.391 11.5321 149.376 12.263C150.368 12.9938 150.981 13.9821 151.216 15.2277Z" fill="currentColor"/>
                <path d="M12.7452 20.7918L12.5417 23.0943C9.21504 21.2602 7.65892 17.1965 9.0521 13.5507C10.5762 9.56235 15.0626 7.55684 19.051 9.08098C22.6967 10.4742 24.6851 14.343 23.8359 18.0439L21.9825 16.6593C22.1286 14.2388 20.707 11.8956 18.3235 10.9847C15.384 9.86146 12.0792 11.3388 10.9559 14.2783C10.045 16.6618 10.8449 19.2845 12.7452 20.7918Z" fill="#22B855"/>
                <path d="M16.5804 21.9907L17.9382 23.8613C14.2246 24.6613 10.3832 22.6172 9.0411 18.9523C7.57283 14.943 9.64084 10.4852 13.6501 9.01691C17.315 7.67479 21.3546 9.28824 23.1433 12.6376L20.8362 12.8096C19.3576 10.8877 16.747 10.0532 14.351 10.9307C11.3962 12.0128 9.87274 15.2967 10.9548 18.2515C11.8323 20.6475 14.1573 22.101 16.5804 21.9907Z" fill="#22B855"/>
                <path d="M16.3031 0C25.2929 0 32.6074 7.31452 32.6074 16.3043C32.6074 16.8709 32.5789 17.4293 32.5218 17.9816C32.1733 17.6718 31.7535 17.4375 31.2807 17.303L30.547 17.0951C30.5612 16.8322 30.5694 16.5693 30.5694 16.3043C30.5694 8.43748 24.1699 2.03804 16.3031 2.03804C8.43628 2.03804 2.03683 8.43748 2.03683 16.3043C2.03683 24.1711 8.43628 30.5706 16.3031 30.5706C16.5681 30.5706 16.831 30.5624 17.0939 30.5482L17.3017 31.2819C17.4363 31.7547 17.6706 32.1745 17.9763 32.523C17.4261 32.5801 16.8676 32.6086 16.3031 32.6086C7.31332 32.6086 -0.0012064 25.2941 -0.0012064 16.3043C-0.0012064 7.31452 7.31332 0 16.3031 0Z" fill="#22B855"/>
                <path d="M15.7634 15.7664C16.0176 15.512 16.335 15.3301 16.683 15.2395C17.031 15.1489 17.3969 15.1528 17.7428 15.251H17.7434L30.2345 18.8016C31.0491 19.0328 31.622 19.7273 31.6942 20.5711C31.7663 21.4146 31.3196 22.1964 30.5567 22.5622L27.8594 23.858L32.0427 28.0415C32.4069 28.4049 32.6074 28.8885 32.6074 29.4029C32.6074 29.9173 32.4071 30.4009 32.0433 30.7641L30.762 32.0454C30.3868 32.42 29.8942 32.6073 29.4014 32.6073C28.9086 32.6073 28.4158 32.4198 28.0406 32.0448L23.8567 27.8605L22.5609 30.5581C22.1945 31.3213 21.4102 31.7681 20.5693 31.6953C19.726 31.6231 19.0318 31.0501 18.8007 30.2363L15.2507 17.7449C15.1524 17.3993 15.1481 17.0337 15.2382 16.6858C15.3284 16.338 15.5098 16.0205 15.7636 15.7662L15.7634 15.7664ZM20.7469 29.6279L22.6422 25.6823C22.7127 25.5355 22.8174 25.4077 22.9474 25.3097C23.0774 25.2117 23.229 25.1463 23.3895 25.1189C23.5501 25.0916 23.7148 25.1031 23.8699 25.1526C24.0251 25.202 24.1661 25.2879 24.2812 25.4031L29.4018 30.5236L30.5221 29.4031L25.4017 24.2825C25.2867 24.1674 25.2008 24.0263 25.1515 23.8712C25.1021 23.7161 25.0906 23.5514 25.1179 23.3909C25.1453 23.2304 25.2106 23.0788 25.3086 22.9488C25.4066 22.8188 25.5342 22.7141 25.6809 22.6435L29.6264 20.7478L17.2202 17.2211L20.7469 29.6279ZM17.186 17.2112L17.4646 16.2313L17.1837 17.211L17.186 17.2112Z" fill="#22B855"/>
                </svg>',
                'notice'        => '',
                'demo_link'     => '',
                'settings_link' => '',
                'doc_link'      => 'https://docs.arraytics.com/docs/booktics/',
            ],
            'poptics' => [
                'name'        => 'poptics',
                'slug'        => 'poptics',
                'title'       => __( 'Poptics', 'wp-cafe' ),
                'description' => __( 'Popup builder for WordPress — grow your audience with beautiful, targeted popups without writing code.', 'wp-cafe' ),
                'is_pro'      => false,
                'doc_link'    => 'https://docs.aethonic.com/docs/getting-started/intro/',
                'demo_link'   => '',
                'icon'        => '<svg width="150px" height="40px" viewBox="0 0 1257 285" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M98.7624 194.722C96.5896 194.722 94.4168 193.904 92.7802 192.239C89.4787 188.937 89.4787 183.575 92.7802 180.273L174.273 98.7722H126.98C122.296 98.7722 118.515 94.9906 118.515 90.306C118.515 85.6214 122.296 81.8398 126.98 81.8398H194.703C195.86 81.8398 196.932 82.0656 197.948 82.4889C198.964 82.9122 199.895 83.5048 200.685 84.3232C201.503 85.1416 202.096 86.0729 202.519 87.0606C202.943 88.0483 203.168 89.1489 203.168 90.306V158.035C203.168 162.72 199.387 166.502 194.703 166.502C190.019 166.502 186.238 162.72 186.238 158.035V110.738L104.745 192.239C103.08 193.904 100.935 194.722 98.7624 194.722Z" fill="#3842FF"/>
                <path d="M281.162 19.2465C277.748 12.5582 272.415 7.22448 265.755 3.838C258.221 0 250.01 0 233.644 0H131.777C115.439 0 107.228 0 99.6653 3.838C92.9777 7.2527 87.6446 12.5582 84.2584 19.2465C80.4208 26.7814 80.4208 34.9936 80.4208 51.3615V80.4288H51.3564C35.0183 80.4288 26.8069 80.4288 19.2446 84.2668C12.5569 87.6815 7.22376 93.0152 3.83762 99.6752C0 107.21 0 115.422 0 131.79V233.667C0 250.006 0 258.219 3.83762 265.754C7.25198 272.442 12.5569 277.776 19.2446 281.162C26.7787 285 34.9901 285 51.3564 285H153.223C169.561 285 177.772 285 185.335 281.162C192.022 277.747 197.355 272.414 200.742 265.754C204.579 258.219 204.579 250.006 204.579 233.638V204.571H233.644C249.982 204.571 258.193 204.571 265.755 200.733C272.443 197.319 277.776 191.985 281.162 185.325C285 177.79 285 169.578 285 153.238V51.3615C285 35.0218 285 26.8096 281.162 19.2465ZM268.069 153.238C268.069 166.445 268.069 173.726 266.066 177.649C264.288 181.12 261.523 183.886 258.052 185.663C254.13 187.667 246.85 187.667 233.644 187.667H131.777C118.571 187.667 111.291 187.667 107.369 185.663C103.898 183.886 101.133 181.12 99.3549 177.649C97.3515 173.726 97.3515 166.445 97.3515 153.238V51.3615C97.3515 38.1543 97.3515 30.8734 99.3549 26.9507C101.133 23.4796 103.898 20.7139 107.369 18.936C111.291 16.9324 118.571 16.9324 131.777 16.9324H233.644C246.85 16.9324 254.13 16.9324 258.052 18.936C261.523 20.7139 264.288 23.4796 266.066 26.9507C268.069 30.8734 268.069 38.1543 268.069 51.3615V153.238Z" fill="#3842FF"/>
                <path d="M356.25 261.25V61.0714H390.744V78.8839C400.169 65.501 414.494 58.8095 433.72 58.8095C454.077 58.8095 470.193 65.5952 482.068 79.1667C493.943 92.5496 499.881 110.268 499.881 132.321C499.881 154.187 493.566 171.905 480.938 185.476C468.497 199.048 451.91 205.833 431.176 205.833C422.505 205.833 414.588 204.231 407.426 201.027C400.263 197.822 394.702 193.487 390.744 188.021V261.25H356.25ZM427.217 175.863C438.715 175.863 447.951 171.905 454.926 163.988C461.9 156.071 465.387 145.516 465.387 132.321C465.387 119.127 461.9 108.571 454.926 100.655C447.951 92.7381 438.715 88.7798 427.217 88.7798C415.531 88.7798 406.106 92.7381 398.943 100.655C391.969 108.571 388.482 119.127 388.482 132.321C388.482 145.516 391.969 156.071 398.943 163.988C406.106 171.905 415.531 175.863 427.217 175.863Z" fill="currentColor"/>
                <path d="M641.153 185.193C627.204 198.953 609.392 205.833 587.715 205.833C566.039 205.833 548.132 198.953 533.995 185.193C520.047 171.245 513.072 153.621 513.072 132.321C513.072 111.022 520.047 93.4921 533.995 79.7321C548.132 65.7837 566.039 58.8095 587.715 58.8095C609.392 58.8095 627.204 65.7837 641.153 79.7321C655.101 93.4921 662.075 111.022 662.075 132.321C662.075 153.621 655.101 171.245 641.153 185.193ZM587.715 175.863C599.402 175.863 608.826 171.811 615.989 163.705C623.34 155.6 627.016 145.139 627.016 132.321C627.016 119.504 623.34 109.043 615.989 100.938C608.826 92.8323 599.402 88.7798 587.715 88.7798C575.84 88.7798 566.227 92.8323 558.876 100.938C551.713 109.043 548.132 119.504 548.132 132.321C548.132 145.139 551.713 155.6 558.876 163.705C566.227 171.811 575.84 175.863 587.715 175.863Z" fill="currentColor"/>
                <path d="M679.042 261.25V61.0714H713.536V78.8839C722.961 65.501 737.286 58.8095 756.512 58.8095C776.869 58.8095 792.985 65.5952 804.86 79.1667C816.735 92.5496 822.673 110.268 822.673 132.321C822.673 154.187 816.358 171.905 803.729 185.476C791.289 199.048 774.702 205.833 753.967 205.833C745.297 205.833 737.38 204.231 730.217 201.027C723.055 197.822 717.494 193.487 713.536 188.021V261.25H679.042ZM750.009 175.863C761.507 175.863 770.743 171.905 777.717 163.988C784.692 156.071 788.179 145.516 788.179 132.321C788.179 119.127 784.692 108.571 777.717 100.655C770.743 92.7381 761.507 88.7798 750.009 88.7798C738.323 88.7798 728.898 92.7381 721.735 100.655C714.761 108.571 711.274 119.127 711.274 132.321C711.274 145.516 714.761 156.071 721.735 163.988C728.898 171.905 738.323 175.863 750.009 175.863Z" fill="currentColor"/>
                <path d="M852.605 18.0952H887.382V61.0714H926.966V90.1935H887.382V152.396C887.382 166.91 894.262 174.167 908.022 174.167H926.966V203.571H904.064C888.23 203.571 875.696 199.236 866.46 190.565C857.224 181.706 852.605 169.454 852.605 153.81V90.1935H824.614V61.0714H852.605V18.0952Z" fill="currentColor"/>
                <path d="M939.214 0H976.535V38.7351H939.214V0ZM940.628 203.571V61.0714H975.404V203.571H940.628Z" fill="currentColor"/>
                <path d="M1062.88 205.833C1041.21 205.833 1023.68 198.859 1010.29 184.911C997.099 170.774 990.502 153.244 990.502 132.321C990.502 111.399 997.099 93.9633 1010.29 80.0149C1023.68 65.878 1041.21 58.8095 1062.88 58.8095C1081.17 58.8095 1096.72 63.8046 1109.53 73.7946C1122.35 83.7847 1129.52 96.8849 1131.02 113.095H1096.53C1095.21 106.121 1091.44 100.372 1085.22 95.8482C1079.19 91.1359 1072.02 88.7798 1063.73 88.7798C1052.04 88.7798 1042.9 92.8323 1036.31 100.938C1029.9 109.043 1026.69 119.504 1026.69 132.321C1026.69 145.139 1029.99 155.6 1036.59 163.705C1043.19 171.811 1052.42 175.863 1064.3 175.863C1072.4 175.863 1079.38 173.79 1085.22 169.643C1091.25 165.308 1095.12 159.559 1096.81 152.396H1131.87C1129.61 168.041 1122.07 180.858 1109.25 190.848C1096.62 200.838 1081.17 205.833 1062.88 205.833Z" fill="currentColor"/>
                <path d="M1201.11 205.833C1183.77 205.833 1169.72 201.404 1158.98 192.545C1148.42 183.497 1142.77 171.339 1142.01 156.071H1173.68C1174.43 162.857 1177.26 168.229 1182.16 172.188C1187.06 176.146 1193.38 178.125 1201.11 178.125C1207.7 178.125 1213.08 176.711 1217.22 173.884C1221.56 170.868 1223.73 167.192 1223.73 162.857C1223.73 157.956 1221.75 154.187 1217.79 151.548C1214.02 148.72 1209.21 146.93 1203.37 146.176C1197.71 145.422 1191.49 144.291 1184.71 142.783C1177.92 141.086 1171.61 139.107 1165.76 136.845C1160.11 134.583 1155.3 130.437 1151.34 124.405C1147.57 118.373 1145.69 110.645 1145.69 101.22C1145.69 88.7798 1150.5 78.6012 1160.11 70.6845C1169.91 62.7679 1182.16 58.8095 1196.87 58.8095C1213.45 58.8095 1227.02 63.0506 1237.58 71.5327C1248.14 79.8264 1253.79 90.9474 1254.54 104.896H1222.88C1221.93 98.6756 1219.01 93.7748 1214.11 90.1935C1209.21 86.6121 1203.75 84.8214 1197.71 84.8214C1191.49 84.8214 1186.5 86.2351 1182.73 89.0625C1178.96 91.7014 1177.07 95.377 1177.07 100.089C1177.07 104.236 1178.49 107.44 1181.32 109.702C1184.33 111.964 1188.1 113.566 1192.62 114.509C1197.34 115.451 1202.52 116.205 1208.18 116.771C1213.83 117.336 1219.48 118.373 1225.14 119.881C1230.79 121.389 1235.88 123.557 1240.41 126.384C1245.12 129.211 1248.89 133.641 1251.72 139.673C1254.73 145.516 1256.24 152.773 1256.24 161.443C1256.24 174.826 1251.15 185.57 1240.97 193.676C1230.98 201.781 1217.69 205.833 1201.11 205.833Z" fill="currentColor"/>
                </svg>
',
            ]
        ];
    }
}