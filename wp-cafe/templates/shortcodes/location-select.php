<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// food location list
use WpCafe\Utils\Wpc_Utilities;

Wpc_Utilities::select_food_locations_filter($location_alignment);

include_once wpcafe()->template_directory . "/shortcodes/location-menu.php";
?>
