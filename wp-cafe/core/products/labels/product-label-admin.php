<?php
namespace WpCafe\Products\Labels;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;

/**
 * Admin UI for the wpcafe_product_label taxonomy:
 *  - Add/Edit term form fields (display, fg, bg, icon picker, preview)
 *  - Save handler with sanitization + allowlists
 *  - Custom "Badge" column in the term list table
 *  - Asset enqueue scoped to the taxonomy edit-tags screen
 */
class Product_Label_Admin implements Hookable_Service_Contract {

    const TAXONOMY = 'wpcafe_product_label';

    const NONCE_ACTION = 'wpcafe_product_label_save';
    const NONCE_NAME   = 'wpcafe_product_label_nonce';

    /**
     * Allowed display modes.
     *
     * @var array
     */
    private $allowed_display = [ 'name', 'icon', 'icon_name', 'name_icon' ];

    /**
     * Allowed icon source types.
     *
     * @var array
     */
    private $allowed_icon_types = [ 'dashicons', 'svg' ];

    public function register() {
        add_action( self::TAXONOMY . '_add_form_fields', [ $this, 'render_add_form_fields' ] );
        add_action( self::TAXONOMY . '_edit_form_fields', [ $this, 'render_edit_form_fields' ] );

        add_action( 'created_' . self::TAXONOMY, [ $this, 'save_term_meta' ] );
        add_action( 'edited_' . self::TAXONOMY, [ $this, 'save_term_meta' ] );

        add_filter( 'manage_edit-' . self::TAXONOMY . '_columns', [ $this, 'add_badge_column' ] );
        add_filter( 'manage_' . self::TAXONOMY . '_custom_column', [ $this, 'render_badge_column' ], 10, 3 );

        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Default values for new terms.
     *
     * @return array
     */
    private function defaults() {
        return [
            'display'    => 'name',
            'bg'         => '#1F2937',
            'fg'         => '#FFFFFF',
            'icon_type'  => 'dashicons',
            'icon_value' => '',
        ];
    }

    /**
     * Read meta for an existing term, falling back to defaults.
     *
     * @param int $term_id
     * @return array
     */
    private function get_meta( $term_id ) {
        if ( function_exists( 'wpc_product_label_meta' ) ) {
            return wpc_product_label_meta( $term_id );
        }
        return $this->defaults();
    }

    /**
     * Add-form fields (rendered on edit-tags.php for new terms).
     */
    public function render_add_form_fields() {
        $values = $this->defaults();
        wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
        $this->render_fields( $values, 'add' );
    }

    /**
     * Edit-form fields (rendered on the per-term edit screen).
     *
     * @param \WP_Term $term
     */
    public function render_edit_form_fields( $term ) {
        $values = $this->get_meta( $term->term_id );
        wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
        $this->render_fields( $values, 'edit' );
    }

    /**
     * Shared field renderer for both add and edit screens.
     *
     * @param array  $values
     * @param string $context 'add' | 'edit'
     */
    private function render_fields( $values, $context ) {
        $is_edit  = ( 'edit' === $context );
        $row_open = $is_edit ? '<tr class="form-field wpcafe-label-field"><th scope="row">' : '<div class="form-field wpcafe-label-field">';
        $row_mid  = $is_edit ? '</th><td>' : '';
        $row_end  = $is_edit ? '</td></tr>' : '</div>';

        $svg_url = '';
        if ( 'svg' === $values['icon_type'] && ! empty( $values['icon_value'] ) ) {
            $svg_url = wp_get_attachment_url( absint( $values['icon_value'] ) );
        }

        // Display option
        echo wp_kses_post( $row_open );
        echo '<label for="wpcafe-label-display">' . esc_html__( 'Display option', 'wp-cafe' ) . '</label>';
        echo wp_kses_post( $row_mid );
        echo '<select id="wpcafe-label-display" name="wpcafe_label_display">';
        $display_labels = [
            'name'      => __( 'Name', 'wp-cafe' ),
            'icon'      => __( 'Icon', 'wp-cafe' ),
            'icon_name' => __( 'Icon and Name', 'wp-cafe' ),
            'name_icon' => __( 'Name and Icon', 'wp-cafe' ),
        ];
        foreach ( $display_labels as $value => $label ) {
            echo '<option value="' . esc_attr( $value ) . '"' . selected( $values['display'], $value, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
        echo wp_kses_post( $row_end );

        // Foreground color
        echo wp_kses_post( $row_open );
        echo '<label for="wpcafe-label-fg">' . esc_html__( 'Foreground color', 'wp-cafe' ) . '</label>';
        echo wp_kses_post( $row_mid );
        echo '<input type="text" id="wpcafe-label-fg" class="wpcafe-label-color" name="wpcafe_label_fg" value="' . esc_attr( $values['fg'] ) . '" data-default-color="#FFFFFF" />';
        echo wp_kses_post( $row_end );

        // Background color
        echo wp_kses_post( $row_open );
        echo '<label for="wpcafe-label-bg">' . esc_html__( 'Background color', 'wp-cafe' ) . '</label>';
        echo wp_kses_post( $row_mid );
        echo '<input type="text" id="wpcafe-label-bg" class="wpcafe-label-color" name="wpcafe_label_bg" value="' . esc_attr( $values['bg'] ) . '" data-default-color="#1F2937" />';
        echo wp_kses_post( $row_end );

        // Icon type tabs
        echo wp_kses_post( $row_open );
        echo '<label>' . esc_html__( 'Icon source', 'wp-cafe' ) . '</label>';
        echo wp_kses_post( $row_mid );
        echo '<div class="wpcafe-label-icon-source">';
        foreach ( [ 'dashicons' => __( 'Dashicons', 'wp-cafe' ), 'svg' => __( 'Custom SVG', 'wp-cafe' ) ] as $val => $lbl ) {
            echo '<label style="margin-right:1em;"><input type="radio" name="wpcafe_label_icon_type" value="' . esc_attr( $val ) . '"' . checked( $values['icon_type'], $val, false ) . ' /> ' . esc_html( $lbl ) . '</label>';
        }
        echo '</div>';
        echo wp_kses_post( $row_end );

        // Icon picker (Dashicons grid + SVG upload)
        echo wp_kses_post( $row_open );
        echo '<label>' . esc_html__( 'Icon', 'wp-cafe' ) . '</label>';
        echo wp_kses_post( $row_mid );

        // Hidden field: stores the icon value (dashicon class OR attachment ID)
        echo '<input type="hidden" id="wpcafe-label-icon-value" name="wpcafe_label_icon_value" value="' . esc_attr( $values['icon_value'] ) . '" />';

        // Dashicons sub-panel
        echo '<div class="wpcafe-label-icon-panel wpcafe-label-icon-panel--dashicons"' . ( 'dashicons' === $values['icon_type'] ? '' : ' style="display:none;"' ) . '>';
        echo '<input type="search" class="wpcafe-label-icon-search" placeholder="' . esc_attr__( 'Search Dashicons...', 'wp-cafe' ) . '" />';
        echo '<div class="wpcafe-label-icon-grid"></div>';
        echo '<p class="description">' . esc_html__( 'Click an icon to select it.', 'wp-cafe' ) . '</p>';
        echo '</div>';

        // SVG upload sub-panel
        echo '<div class="wpcafe-label-icon-panel wpcafe-label-icon-panel--svg"' . ( 'svg' === $values['icon_type'] ? '' : ' style="display:none;"' ) . '>';
        echo '<button type="button" class="button wpcafe-label-svg-upload">' . esc_html__( 'Select SVG...', 'wp-cafe' ) . '</button> ';
        echo '<button type="button" class="button-link wpcafe-label-svg-clear"' . ( $svg_url ? '' : ' style="display:none;"' ) . '>' . esc_html__( 'Clear', 'wp-cafe' ) . '</button>';
        echo '<div class="wpcafe-label-svg-preview" style="margin-top:8px;">';
        if ( $svg_url ) {
            echo '<img src="' . esc_url( $svg_url ) . '" alt="" style="max-width:48px;height:auto;" />';
        }
        echo '</div>';
        echo '</div>';

        echo wp_kses_post( $row_end );

        // Preview
        echo wp_kses_post( $row_open );
        echo '<label>' . esc_html__( 'Preview', 'wp-cafe' ) . '</label>';
        echo wp_kses_post( $row_mid );
        echo '<div class="wpcafe-label-preview-wrap"><span class="wpcafe-label-preview">' . esc_html__( 'Preview', 'wp-cafe' ) . '</span></div>';
        echo '<p class="description">' . esc_html__( 'Live preview of how the label will appear on product cards.', 'wp-cafe' ) . '</p>';
        echo wp_kses_post( $row_end );
    }

    /**
     * Save term meta with strict sanitization + allowlist checks.
     *
     * @param int $term_id
     */
    public function save_term_meta( $term_id ) {
        // Capability check
        if ( ! current_user_can( 'manage_categories' ) ) {
            return;
        }

        // Nonce check
        $nonce = isset( $_POST[ self::NONCE_NAME ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ) : '';
        if ( ! $nonce || ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
            return;
        }

        // Display
        $display = isset( $_POST['wpcafe_label_display'] ) ? sanitize_key( wp_unslash( $_POST['wpcafe_label_display'] ) ) : 'name';
        if ( ! in_array( $display, $this->allowed_display, true ) ) {
            $display = 'name';
        }
        update_term_meta( $term_id, '_wpcafe_label_display', $display );

        // Background
        $background = isset( $_POST['wpcafe_label_bg'] ) ? sanitize_text_field( wp_unslash( $_POST['wpcafe_label_bg'] ) ) : '';
        $background = sanitize_hex_color( $background );
        if ( ! $background ) {
            $background = '#1F2937';
        }
        update_term_meta( $term_id, '_wpcafe_label_bg', $background );

        // Foreground
        $foreground = isset( $_POST['wpcafe_label_fg'] ) ? sanitize_text_field( wp_unslash( $_POST['wpcafe_label_fg'] ) ) : '';
        $foreground = sanitize_hex_color( $foreground );
        if ( ! $foreground ) {
            $foreground = '#FFFFFF';
        }
        update_term_meta( $term_id, '_wpcafe_label_fg', $foreground );

        // Icon type
        $icon_type = isset( $_POST['wpcafe_label_icon_type'] ) ? sanitize_key( wp_unslash( $_POST['wpcafe_label_icon_type'] ) ) : 'dashicons';
        if ( ! in_array( $icon_type, $this->allowed_icon_types, true ) ) {
            $icon_type = 'dashicons';
        }
        update_term_meta( $term_id, '_wpcafe_label_icon_type', $icon_type );

        // Icon value
        $raw_icon_value = isset( $_POST['wpcafe_label_icon_value'] ) ? sanitize_text_field( wp_unslash( $_POST['wpcafe_label_icon_value'] ) ) : '';
        if ( 'svg' === $icon_type ) {
            $icon_value = (string) absint( $raw_icon_value );
        } else {
            $icon_value = sanitize_html_class( $raw_icon_value );
        }
        update_term_meta( $term_id, '_wpcafe_label_icon_value', $icon_value );
    }

    /**
     * Add a "Badge" column to the term list table.
     *
     * @param array $columns
     * @return array
     */
    public function add_badge_column( $columns ) {
        $new = [];
        foreach ( $columns as $key => $label ) {
            if ( 'name' === $key ) {
                $new['wpcafe_badge'] = __( 'Badge', 'wp-cafe' );
            }
            $new[ $key ] = $label;
        }
        return $new;
    }

    /**
     * Render the "Badge" column body.
     *
     * @param string $content
     * @param string $column_name
     * @param int    $term_id
     * @return string
     */
    public function render_badge_column( $content, $column_name, $term_id ) {
        if ( 'wpcafe_badge' !== $column_name ) {
            return $content;
        }

        $term = get_term( $term_id, self::TAXONOMY );
        if ( ! $term || is_wp_error( $term ) ) {
            return $content;
        }

        ob_start();
        if ( function_exists( 'wpc_product_labels' ) ) {
            // wpc_product_labels expects a product id, but we can render a
            // single term standalone by calling the meta + icon helpers.
            $meta = wpc_product_label_meta( $term_id );
            $icon = wpc_product_label_icon_html( $meta );
            $name = $term->name;

            $display = $meta['display'];
            if ( in_array( $display, [ 'icon', 'icon_name', 'name_icon' ], true ) && '' === $icon ) {
                $display = 'name';
            }

            $style = sprintf(
                'background-color:%s;color:%s;',
                esc_attr( $meta['bg'] ),
                esc_attr( $meta['fg'] )
            );

            echo '<span class="wpc-product-label wpc-product-label--' . esc_attr( $display ) . '" style="' . esc_attr( $style ) . '">';
            switch ( $display ) {
                case 'icon':
                    echo wp_kses_post( $icon );
                    break;
                case 'icon_name':
                    echo wp_kses_post( $icon );
                    echo '<span class="wpc-product-label__name">' . esc_html( $name ) . '</span>';
                    break;
                case 'name_icon':
                    echo '<span class="wpc-product-label__name">' . esc_html( $name ) . '</span>';
                    echo wp_kses_post( $icon );
                    break;
                default:
                    echo '<span class="wpc-product-label__name">' . esc_html( $name ) . '</span>';
            }
            echo '</span>';
        }
        return ob_get_clean();
    }

    /**
     * Enqueue color picker, media uploader, and our custom JS/CSS — only
     * on the wpcafe_product_label edit-tags screen.
     *
     * @param string $hook
     */
    public function enqueue_assets( $hook ) {
        if ( 'edit-tags.php' !== $hook && 'term.php' !== $hook ) {
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only asset-enqueue gate, not form processing.
        $taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : '';
        if ( self::TAXONOMY !== $taxonomy ) {
            return;
        }

        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        wp_enqueue_media();
        wp_enqueue_style( 'dashicons' );

        $base_url = plugins_url( 'assets/admin/', WPCAFE_FILE );

        wp_enqueue_style(
            'wpcafe-product-label-admin',
            $base_url . 'product-label.css',
            [ 'wp-color-picker', 'dashicons' ],
            defined( 'WPCAFE_VERSION' ) ? WPCAFE_VERSION : false
        );

        wp_enqueue_script(
            'wpcafe-product-label-admin',
            $base_url . 'product-label.js',
            [ 'jquery', 'wp-color-picker' ],
            defined( 'WPCAFE_VERSION' ) ? WPCAFE_VERSION : false,
            true
        );

        wp_localize_script(
            'wpcafe-product-label-admin',
            'wpcafeProductLabel',
            [
                'dashicons' => $this->get_dashicons_list(),
                'i18n'      => [
                    'preview'    => __( 'Preview', 'wp-cafe' ),
                    'noResults'  => __( 'No icons found.', 'wp-cafe' ),
                    'svgTitle'   => __( 'Select SVG icon', 'wp-cafe' ),
                    'useSvg'     => __( 'Use this SVG', 'wp-cafe' ),
                ],
            ]
        );
    }

    /**
     * Curated list of common Dashicons class names suitable for product
     * labeling. Kept short (~80) to keep the picker fast.
     *
     * @return array
     */
    private function get_dashicons_list() {
        return [
            'dashicons-star-filled', 'dashicons-star-half', 'dashicons-heart', 'dashicons-thumbs-up', 'dashicons-thumbs-down',
            'dashicons-flag', 'dashicons-awards', 'dashicons-megaphone', 'dashicons-tag', 'dashicons-tickets-alt',
            'dashicons-cart', 'dashicons-tickets', 'dashicons-bell', 'dashicons-yes', 'dashicons-yes-alt',
            'dashicons-warning', 'dashicons-shield', 'dashicons-shield-alt', 'dashicons-lock', 'dashicons-unlock',
            'dashicons-clock', 'dashicons-calendar-alt', 'dashicons-marker', 'dashicons-location', 'dashicons-location-alt',
            'dashicons-store', 'dashicons-products', 'dashicons-money-alt', 'dashicons-money', 'dashicons-buddicons-buddypress-logo',
            'dashicons-food', 'dashicons-coffee', 'dashicons-carrot', 'dashicons-drumstick', 'dashicons-pets',
            'dashicons-leaf', 'dashicons-palmtree', 'dashicons-egg', 'dashicons-superhero', 'dashicons-superhero-alt',
            'dashicons-fire', 'dashicons-water', 'dashicons-cloud', 'dashicons-sun', 'dashicons-airplane',
            'dashicons-cover-image', 'dashicons-hammer', 'dashicons-art', 'dashicons-camera', 'dashicons-format-image',
            'dashicons-format-gallery', 'dashicons-format-video', 'dashicons-format-status', 'dashicons-format-quote', 'dashicons-format-aside',
            'dashicons-screenoptions', 'dashicons-info', 'dashicons-info-outline', 'dashicons-lightbulb', 'dashicons-smiley',
            'dashicons-thumbs-up', 'dashicons-no', 'dashicons-no-alt', 'dashicons-plus', 'dashicons-minus',
            'dashicons-arrow-up-alt', 'dashicons-arrow-down-alt', 'dashicons-controls-play', 'dashicons-controls-pause', 'dashicons-controls-skipforward',
            'dashicons-buddicons-tropes', 'dashicons-businessperson', 'dashicons-groups', 'dashicons-admin-users', 'dashicons-id',
            'dashicons-email', 'dashicons-phone', 'dashicons-share', 'dashicons-twitter', 'dashicons-facebook',
            'dashicons-instagram', 'dashicons-youtube', 'dashicons-googleplus', 'dashicons-rss', 'dashicons-update',
        ];
    }
}
