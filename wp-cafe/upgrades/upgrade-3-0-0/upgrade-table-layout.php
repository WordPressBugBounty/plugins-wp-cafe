<?php
namespace WpCafe\Upgrades\Upgrade_3_0_0;

use WpCafePro\Models\Seat_Plan_Model;

/**
 * Migrates table layout data from wp_options to wpcafe_seat database table.
 *
 * @package WpCafe\Upgrades\Upgrade_3_0_0
 */
class Upgrade_Table_Layout {

    /**
     * Constructor for the Upgrade_Table_Layout class.
     *
     * Automatically triggers the upgrade process when an instance is created.
     */
    public function __construct() {
        $this->migrate_table_layout();
    }

    /**
     * Migrate table layout data from options to database table.
     *
     * @return void
     */
    public function migrate_table_layout() {
        // Get the old table layout data from options
        $old_layout_data = get_option( 'wpc_table_layout', [] );

        if ( empty( $old_layout_data ) ) {
            return;
        }

        update_option( 'wpcafe_table_layout_migrated', true ); // Store that migration was attempted

        // Process each schedule's layout
        foreach ( $old_layout_data as $schedule_slug => $layout_data ) {
            if ( in_array( $schedule_slug, [ 'common_mapping', 'booked_ids', 'booked_table_ids' ] ) ) {
                continue; // Skip non-layout entries and metadata
            }

            if ( ! is_array( $layout_data ) ) {
                continue;
            }

            $this->migrate_layout_to_seats( $layout_data );
        }

        update_option( 'wpcafe_table_layout_version', '3.0.0' );
    }

    /**
     * Convert individual layout data to seat records and save to database.
     *
     * @param array $layout_data The layout data from old format
     * @return void
     */
    private function migrate_layout_to_seats( $layout_data ) {
        $branch_id = 0;

        $canvas_styles = [
            'table_fill_color'  => isset( $layout_data['table_fill_color'] ) ? $layout_data['table_fill_color'] : '#ffffff',
            'chair_fill_color'  => isset( $layout_data['chair_fill_color'] ) ? $layout_data['chair_fill_color'] : '#e8e8e8',
            'text_fill_color'   => isset( $layout_data['text_fill_color'] )  ? $layout_data['text_fill_color']  : '#000000',
            'canvas_bg_img'     => isset( $layout_data['canvas_bg_img'] )    ? $layout_data['canvas_bg_img']    : '',
        ];

        $chair_intersect_data = isset( $layout_data['chairIntersectData'] ) ? $layout_data['chairIntersectData'] : [];

        $chair_to_table_map = $this->build_chair_to_table_mapping( $chair_intersect_data );

        // Create all tables first
        $old_to_new_id_map = [];
        if ( isset( $layout_data['objects'] ) && is_array( $layout_data['objects'] ) ) {
            foreach ( $layout_data['objects'] as $object_id => $object_data ) {
                // Only process tables in pass 1 (objects that appear as keys in chairIntersectData)
                if ( isset( $chair_intersect_data[ $object_id ] ) ) {
                    $new_seat_id = $this->create_seat_from_object( $object_data, $object_id, $branch_id, 0, $canvas_styles );
                    if ( $new_seat_id ) {
                        $old_to_new_id_map[ $object_id ] = $new_seat_id;
                    }
                }
            }
        }

        // Create all chairs and other objects with correct parent (table) reference
        if ( isset( $layout_data['objects'] ) && is_array( $layout_data['objects'] ) ) {
            foreach ( $layout_data['objects'] as $object_id => $object_data ) {
                if ( isset( $chair_intersect_data[ $object_id ] ) ) {
                    continue; // Skip tables
                }

                // Determine parent ID for this object
                $parent_id = 0;
                if ( isset( $chair_to_table_map[ $object_id ] ) ) {
                    $table_old_id = $chair_to_table_map[ $object_id ];
                    $parent_id = $old_to_new_id_map[ $table_old_id ] ?? 0;
                }

                $this->create_seat_from_object( $object_data, $object_id, $branch_id, $parent_id, $canvas_styles );
            }
        }
    }

    /**
     * Build a reverse mapping from chair IDs to table IDs.
     *
     * @param array $chair_intersect_data The chairIntersectData structure
     * @return array Array with chair_id as key and table_id as value
     */
    private function build_chair_to_table_mapping( $chair_intersect_data ) {
        $chair_to_table = [];

        if ( ! empty( $chair_intersect_data ) && is_array( $chair_intersect_data ) ) {
            foreach ( $chair_intersect_data as $table_id => $chair_ids ) {
                if ( is_array( $chair_ids ) ) {
                    foreach ( $chair_ids as $chair_id ) {
                        $chair_to_table[ $chair_id ] = $table_id;
                    }
                }
            }
        }

        return $chair_to_table;
    }

    /**
     * Create a seat record from a layout object (table or chair).
     *
     * @param array $object_data The object data from the layout
     * @param string $object_id The object ID from the layout
     * @param int $branch_id The branch ID
     * @param int $parent_id The parent seat ID (0 for tables, table_id for chairs)
     * @param array $canvas_styles Canvas styling information
     * @return int|false The ID of the created seat, or false on failure
     */
    private function create_seat_from_object( $object_data, $object_id, $branch_id, $parent_id, $canvas_styles ) {

        if ( ! class_exists(Seat_Plan_Model::class) ) {
            return false;
        }

        // Determine object type and prepare default values
        $object_type = isset( $object_data['type'] ) ? $object_data['type'] : 'unknown';
        $label = isset( $object_data['name'] ) ? $object_data['name'] : "Item $object_id";
        $text = isset( $object_data['text'] ) ? $object_data['text'] : '';

        // Determine appropriate fill color based on parent (tables vs chairs)
        $is_table = ( 0 === $parent_id );
        $default_color = $is_table ? $canvas_styles['table_fill_color'] : $canvas_styles['chair_fill_color'];

        // Prepare seat data
        $seat_data = [
            'label' => sanitize_text_field( $label ),
            'type' => sanitize_text_field( $object_type ),
            'number' => $object_id, // Original ID from layout
            'status' => 'active',
            'angle' => isset( $object_data['angle'] ) ? (string) $object_data['angle'] : '0',
            'scaleX' => isset( $object_data['scaleX'] ) ? (string) $object_data['scaleX'] : '1',
            'scaleY' => isset( $object_data['scaleY'] ) ? (string) $object_data['scaleY'] : '1',
            'positionX' => isset( $object_data['left'] ) ? (string) $object_data['left'] : '0',
            'positionY' => isset( $object_data['top'] ) ? (string) $object_data['top'] : '0',
            'zoomX' => isset( $object_data['scaleX'] ) ? (string) $object_data['scaleX'] : '1',
            'zoomY' => isset( $object_data['scaleY'] ) ? (string) $object_data['scaleY'] : '1',
            'color' => isset( $object_data['fill'] ) ? $object_data['fill'] : $default_color,
            'shapeType' => $this->get_shape_type( $object_type ),
            'fill' => isset( $object_data['fill'] ) ? $object_data['fill'] : '1',
            'stroke' => isset( $object_data['stroke'] ) ? $object_data['stroke'] : '#000000',
            'strokeWidth' => isset( $object_data['strokeWidth'] ) ? (string) $object_data['strokeWidth'] : '1',
            'radius' => isset( $object_data['rx'] ) ? (string) $object_data['rx'] : '0',
            'width' => isset( $object_data['width'] ) ? (string) $object_data['width'] : '50',
            'height' => isset( $object_data['height'] ) ? (string) $object_data['height'] : '50',
            'ticketType' => isset( $object_data['ticketType'] ) ? $object_data['ticketType'] : '',
            'price' => isset( $object_data['price'] ) ? (string) $object_data['price'] : '0',
            'branch' => (int) $branch_id,
            'parent' => (int) $parent_id,
            'text' => sanitize_text_field( $text ),
            'fontSize' => isset( $object_data['fontSize'] ) ? (string) $object_data['fontSize'] : '14',
            'cursor' => isset( $object_data['cursor'] ) ? $object_data['cursor'] : 'default',
        ];

        // Create the seat
        $seat = Seat_Plan_Model::create( $seat_data );

        return $seat ? $seat->id : false;
    }

    /**
     * Determine the shape type based on the object type.
     *
     * @param string $object_type The object type from the layout
     * @return string The shape type
     */
    private function get_shape_type( $object_type ) {
        $type_map = [
            'rect' => 'rectangle',
            'circle' => 'circle',
            'ellipse' => 'ellipse',
            'polygon' => 'polygon',
            'text' => 'text',
            'image' => 'image',
            'table' => 'rectangle',
            'chair' => 'circle',
            'round_table' => 'circle',
        ];

        return isset( $type_map[ $object_type ] ) ? $type_map[ $object_type ] : 'rectangle';
    }
}
