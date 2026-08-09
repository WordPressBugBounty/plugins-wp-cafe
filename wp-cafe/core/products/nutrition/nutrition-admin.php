<?php
namespace WpCafe\Products\Nutrition;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;

/**
 * WC product data tab + panel for Nutrition + Allergen entry.
 */
class Nutrition_Admin implements Hookable_Service_Contract {

    const NONCE_ACTION = 'wpcafe_nutrition_save';
    const NONCE_NAME   = 'wpcafe_nutrition_nonce';

    public function register() {
        add_filter( 'woocommerce_product_data_tabs',   [ $this, 'add_product_data_tab' ] );
        add_action( 'woocommerce_product_data_panels', [ $this, 'render_panel' ] );
        add_action( 'woocommerce_process_product_meta', [ $this, 'save' ] );
        add_action( 'admin_enqueue_scripts',           [ $this, 'enqueue_assets' ] );
    }

    /**
     * Add tab to product data metabox.
     */
    public function add_product_data_tab( $tabs ) {
        $tabs['wpcafe_nutrition'] = [
            'label'    => __( 'Nutrition & Allergens', 'wp-cafe' ),
            'target'   => 'wpcafe_nutrition_panel',
            'class'    => [],
            'priority' => 65,
        ];
        return $tabs;
    }

    /**
     * Render the nutrition + allergen panel.
     */
    public function render_panel() {
        global $post;
        if ( ! $post ) return;
        self::render_panel_body( $post->ID );
    }

    /**
     * Render the nutrition + allergen panel body for one product.
     *
     * Shared entry point so contexts outside the WooCommerce admin metabox
     * (e.g. the WCFM vendor product manager in the multivendor addon) can
     * render the identical UI. Includes its own nonce field, so a caller that
     * embeds this in another form gets the nonce for free.
     *
     * @param int $product_id Product to load values from (0 when adding new).
     * @return void
     */
    public static function render_panel_body( $product_id ) {
        $nutrition = wpc_product_nutrition( $product_id );
        $allergens = wpc_product_allergens( $product_id );
        $presets   = wpc_allergen_presets();

        $enabled            = ! empty( $nutrition['enabled'] );
        $format_candidate   = $nutrition['format'] ?? 'fda';
        $format             = in_array( $format_candidate, wpc_nutrition_formats(), true ) ? $format_candidate : 'fda';
        $serving_label      = $nutrition['serving_label'] ?? '';
        $serving_size       = $nutrition['serving_size'] ?? '';
        $serving_unit_value = $nutrition['serving_unit'] ?? 'g';
        $serving_unit       = in_array( $serving_unit_value, wpc_nutrition_serving_units(), true ) ? $serving_unit_value : 'g';

        $val = function ( $key ) use ( $nutrition ) {
            return isset( $nutrition[ $key ] ) ? $nutrition[ $key ] : '';
        };

        $vitamins = isset( $nutrition['vitamins'] ) && is_array( $nutrition['vitamins'] ) ? $nutrition['vitamins'] : [];
        $ingredients_raw = $nutrition['ingredients'] ?? '';
        if ( is_array( $ingredients_raw ) ) {
            $ingredients_list = array_values( array_filter( array_map( 'trim', $ingredients_raw ) ) );
        } else {
            $ingredients_list = array_values( array_filter( array_map( 'trim', explode( ',', (string) $ingredients_raw ) ) ) );
        }
        $custom_allergens = isset( $allergens['custom'] ) && is_array( $allergens['custom'] ) ? $allergens['custom'] : [];
        $contains_set    = is_array( $allergens['contains'] ) ? $allergens['contains'] : [];
        $may_contain_set = is_array( $allergens['may_contain'] ) ? $allergens['may_contain'] : [];
        ?>
        <div id="wpcafe_nutrition_panel" class="panel woocommerce_options_panel wpcafe-nutrition-panel">
            <?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

            <div class="options_group">
                <p class="form-field wpcafe_nutrition_enable_field">
                    <label for="wpcafe_nutrition_enabled"><?php esc_html_e( 'Enable Nutrition Information', 'wp-cafe' ); ?></label>
                    <input type="checkbox" name="wpcafe_nutrition[enabled]" id="wpcafe_nutrition_enabled" value="1" <?php checked( $enabled ); ?> />
                </p>
            </div>

            <div class="wpcafe-nutrition-section" data-section="nutrition">
                <h4 class="wpcafe-nutrition-section__title"><?php esc_html_e( 'Nutrition Information', 'wp-cafe' ); ?></h4>

                <div class="options_group">
                    <p class="form-field">
                        <label><?php esc_html_e( 'Display Format', 'wp-cafe' ); ?></label>
                        <span class="wpcafe-nutrition-formats">
                            <label><input type="radio" name="wpcafe_nutrition[format]" value="fda"   <?php checked( $format, 'fda' ); ?>> <?php esc_html_e( 'FDA (US, per serving + %DV)', 'wp-cafe' ); ?></label>
                            <label><input type="radio" name="wpcafe_nutrition[format]" value="eu"    <?php checked( $format, 'eu' ); ?>> <?php esc_html_e( 'EU (per 100g + per serving)', 'wp-cafe' ); ?></label>
                            <label><input type="radio" name="wpcafe_nutrition[format]" value="plain" <?php checked( $format, 'plain' ); ?>> <?php esc_html_e( 'Plain (no regional format)', 'wp-cafe' ); ?></label>
                        </span>
                    </p>

                    <p class="form-field">
                        <label for="wpcafe_nutrition_serving_label"><?php esc_html_e( 'Serving Label', 'wp-cafe' ); ?></label>
                        <input type="text" class="short" name="wpcafe_nutrition[serving_label]" id="wpcafe_nutrition_serving_label" value="<?php echo esc_attr( $serving_label ); ?>" placeholder="<?php esc_attr_e( '1 bowl, 1 cup, 2 pieces, ...', 'wp-cafe' ); ?>" />
                        <span class="description"><?php esc_html_e( 'Optional. Free text describing one serving.', 'wp-cafe' ); ?></span>
                    </p>

                    <p class="form-field">
                        <label for="wpcafe_nutrition_serving_size"><?php esc_html_e( 'Serving Size', 'wp-cafe' ); ?></label>
                        <input type="number" min="0" step="any" class="short" name="wpcafe_nutrition[serving_size]" id="wpcafe_nutrition_serving_size" value="<?php echo esc_attr( $serving_size ); ?>" />
                        <select name="wpcafe_nutrition[serving_unit]" class="short">
                            <?php foreach ( wpc_nutrition_serving_units() as $unit ) :
                                $unit_label = ( 'fl_oz' === $unit ) ? 'fl oz' : ( 'pieces' === $unit ? __( 'pieces', 'wp-cafe' ) : $unit );
                                ?>
                                <option value="<?php echo esc_attr( $unit ); ?>" <?php selected( $serving_unit, $unit ); ?>><?php echo esc_html( $unit_label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                </div>

                <div class="options_group">
                    <p class="form-field"><label for="wpcafe_nutrition_calories"><?php esc_html_e( 'Calories', 'wp-cafe' ); ?></label>
                        <input type="number" min="0" step="any" class="short" name="wpcafe_nutrition[calories]" id="wpcafe_nutrition_calories" value="<?php echo esc_attr( $val( 'calories' ) ); ?>" />
                        <span class="description">kcal</span>
                    </p>
                    <p class="form-field"><label for="wpcafe_nutrition_energy_kj"><?php esc_html_e( 'Energy', 'wp-cafe' ); ?></label>
                        <input type="number" min="0" step="any" class="short" name="wpcafe_nutrition[energy_kj]" id="wpcafe_nutrition_energy_kj" value="<?php echo esc_attr( $val( 'energy_kj' ) ); ?>" />
                        <span class="description">kJ</span>
                    </p>
                </div>

                <div class="options_group">
                    <p class="form-field"><label for="wpcafe_nutrition_protein"><?php esc_html_e( 'Protein', 'wp-cafe' ); ?></label>
                        <input type="number" min="0" step="any" class="short" name="wpcafe_nutrition[protein]" id="wpcafe_nutrition_protein" value="<?php echo esc_attr( $val( 'protein' ) ); ?>" /> <span class="description">g</span>
                    </p>
                    <p class="form-field"><label for="wpcafe_nutrition_fat_total"><?php esc_html_e( 'Total Fat', 'wp-cafe' ); ?></label>
                        <input type="number" min="0" step="any" class="short" name="wpcafe_nutrition[fat_total]" id="wpcafe_nutrition_fat_total" value="<?php echo esc_attr( $val( 'fat_total' ) ); ?>" /> <span class="description">g</span>
                    </p>
                    <p class="form-field wpcafe-nutrition-sub"><label for="wpcafe_nutrition_fat_saturated"><?php esc_html_e( '— Saturated Fat', 'wp-cafe' ); ?></label>
                        <input type="number" min="0" step="any" class="short" name="wpcafe_nutrition[fat_saturated]" id="wpcafe_nutrition_fat_saturated" value="<?php echo esc_attr( $val( 'fat_saturated' ) ); ?>" /> <span class="description">g</span>
                    </p>
                    <p class="form-field wpcafe-nutrition-sub"><label for="wpcafe_nutrition_fat_trans"><?php esc_html_e( '— Trans Fat', 'wp-cafe' ); ?></label>
                        <input type="number" min="0" step="any" class="short" name="wpcafe_nutrition[fat_trans]" id="wpcafe_nutrition_fat_trans" value="<?php echo esc_attr( $val( 'fat_trans' ) ); ?>" /> <span class="description">g</span>
                    </p>
                    <p class="form-field"><label for="wpcafe_nutrition_carbs_total"><?php esc_html_e( 'Total Carbohydrates', 'wp-cafe' ); ?></label>
                        <input type="number" min="0" step="any" class="short" name="wpcafe_nutrition[carbs_total]" id="wpcafe_nutrition_carbs_total" value="<?php echo esc_attr( $val( 'carbs_total' ) ); ?>" /> <span class="description">g</span>
                    </p>
                    <p class="form-field wpcafe-nutrition-sub"><label for="wpcafe_nutrition_carbs_sugars"><?php esc_html_e( '— Sugars', 'wp-cafe' ); ?></label>
                        <input type="number" min="0" step="any" class="short" name="wpcafe_nutrition[carbs_sugars]" id="wpcafe_nutrition_carbs_sugars" value="<?php echo esc_attr( $val( 'carbs_sugars' ) ); ?>" /> <span class="description">g</span>
                    </p>
                    <p class="form-field wpcafe-nutrition-sub"><label for="wpcafe_nutrition_carbs_fiber"><?php esc_html_e( '— Fiber', 'wp-cafe' ); ?></label>
                        <input type="number" min="0" step="any" class="short" name="wpcafe_nutrition[carbs_fiber]" id="wpcafe_nutrition_carbs_fiber" value="<?php echo esc_attr( $val( 'carbs_fiber' ) ); ?>" /> <span class="description">g</span>
                    </p>
                    <p class="form-field"><label for="wpcafe_nutrition_sodium"><?php esc_html_e( 'Sodium', 'wp-cafe' ); ?></label>
                        <input type="number" min="0" step="any" class="short" name="wpcafe_nutrition[sodium]" id="wpcafe_nutrition_sodium" value="<?php echo esc_attr( $val( 'sodium' ) ); ?>" /> <span class="description">mg</span>
                    </p>
                    <p class="form-field"><label for="wpcafe_nutrition_cholesterol"><?php esc_html_e( 'Cholesterol', 'wp-cafe' ); ?></label>
                        <input type="number" min="0" step="any" class="short" name="wpcafe_nutrition[cholesterol]" id="wpcafe_nutrition_cholesterol" value="<?php echo esc_attr( $val( 'cholesterol' ) ); ?>" /> <span class="description">mg</span>
                    </p>
                </div>

                <div class="options_group">
                    <p class="form-field"><label><?php esc_html_e( 'Vitamins & Minerals', 'wp-cafe' ); ?></label></p>
                    <div class="wpcafe-repeater" data-repeater="vitamins">
                        <div class="wpcafe-repeater__rows">
                            <?php if ( ! empty( $vitamins ) ) :
                                foreach ( $vitamins as $i => $v ) :
                                    $name  = $v['name']  ?? '';
                                    $value = $v['value'] ?? '';
                                    $unit  = $v['unit']  ?? '% DV';
                                    ?>
                                    <div class="wpcafe-repeater__row">
                                        <input type="text"   name="wpcafe_nutrition[vitamins][<?php echo (int) $i; ?>][name]"  value="<?php echo esc_attr( $name ); ?>"  placeholder="<?php esc_attr_e( 'Name (e.g., Vitamin A)', 'wp-cafe' ); ?>" />
                                        <input type="number" step="any" min="0" name="wpcafe_nutrition[vitamins][<?php echo (int) $i; ?>][value]" value="<?php echo esc_attr( $value ); ?>" placeholder="0" />
                                        <select name="wpcafe_nutrition[vitamins][<?php echo (int) $i; ?>][unit]">
                                            <?php foreach ( wpc_nutrition_vitamin_units() as $u ) : ?>
                                                <option value="<?php echo esc_attr( $u ); ?>" <?php selected( $unit, $u ); ?>><?php echo esc_html( $u ); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="button wpcafe-repeater__remove">&times;</button>
                                    </div>
                                <?php endforeach;
                            endif; ?>
                        </div>
                        <button type="button" class="button wpcafe-repeater__add"><?php esc_html_e( '+ Add Vitamin/Mineral', 'wp-cafe' ); ?></button>
                        <template class="wpcafe-repeater__template">
                            <div class="wpcafe-repeater__row">
                                <input type="text"   name="wpcafe_nutrition[vitamins][__INDEX__][name]"  value="" placeholder="<?php esc_attr_e( 'Name (e.g., Vitamin A)', 'wp-cafe' ); ?>" />
                                <input type="number" step="any" min="0" name="wpcafe_nutrition[vitamins][__INDEX__][value]" value="" placeholder="0" />
                                <select name="wpcafe_nutrition[vitamins][__INDEX__][unit]">
                                    <?php foreach ( wpc_nutrition_vitamin_units() as $u ) : ?>
                                        <option value="<?php echo esc_attr( $u ); ?>"><?php echo esc_html( $u ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="button wpcafe-repeater__remove">&times;</button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="options_group">
                    <p class="form-field">
                        <label><?php esc_html_e( 'Ingredients', 'wp-cafe' ); ?></label>
                        <span class="description"><?php esc_html_e( 'Add each ingredient as its own row. They render on the frontend as a clean comma-separated list.', 'wp-cafe' ); ?></span>
                    </p>
                    <div class="wpcafe-repeater wpcafe-repeater--single" data-repeater="ingredients">
                        <div class="wpcafe-repeater__rows">
                            <?php if ( ! empty( $ingredients_list ) ) :
                                foreach ( $ingredients_list as $i => $ing ) : ?>
                                    <div class="wpcafe-repeater__row">
                                        <input type="text" name="wpcafe_nutrition[ingredients][<?php echo (int) $i; ?>]" value="<?php echo esc_attr( $ing ); ?>" placeholder="<?php esc_attr_e( 'e.g. Wheat flour', 'wp-cafe' ); ?>" />
                                        <button type="button" class="button wpcafe-repeater__remove">&times;</button>
                                    </div>
                                <?php endforeach;
                            endif; ?>
                        </div>
                        <button type="button" class="button wpcafe-repeater__add"><?php esc_html_e( '+ Add Ingredient', 'wp-cafe' ); ?></button>
                        <template class="wpcafe-repeater__template">
                            <div class="wpcafe-repeater__row">
                                <input type="text" name="wpcafe_nutrition[ingredients][__INDEX__]" value="" placeholder="<?php esc_attr_e( 'e.g. Wheat flour', 'wp-cafe' ); ?>" />
                                <button type="button" class="button wpcafe-repeater__remove">&times;</button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="wpcafe-nutrition-section" data-section="allergens">
                <h4 class="wpcafe-nutrition-section__title"><?php esc_html_e( 'Allergen Information', 'wp-cafe' ); ?></h4>

                <div class="options_group wpcafe-allergens-grid">
                    <p class="form-field">
                        <label><?php esc_html_e( 'Allergens', 'wp-cafe' ); ?></label>
                        <span class="description"><?php esc_html_e( 'Click a chip to cycle: off → Contains → May Contain → off.', 'wp-cafe' ); ?></span>
                    </p>
                    <div class="wpcafe-allergens">
                        <?php foreach ( $presets as $slug => $info ) :
                            $state = '';
                            if ( in_array( $slug, $contains_set, true ) ) {
                                $state = 'contains';
                            } elseif ( in_array( $slug, $may_contain_set, true ) ) {
                                $state = 'may_contain';
                            }
                            ?>
                            <div class="wpcafe-allergen" data-slug="<?php echo esc_attr( $slug ); ?>" data-state="<?php echo esc_attr( $state ); ?>">
                                <input type="hidden" name="wpcafe_allergens[states][<?php echo esc_attr( $slug ); ?>]" value="<?php echo esc_attr( $state ); ?>" />
                                <button type="button" class="wpcafe-allergen__btn">
                                    <span class="wpcafe-allergen__icon" aria-hidden="true"><?php echo wpc_allergen_icon_svg( $info['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                                    <span class="wpcafe-allergen__label"><?php echo esc_html( $info['label'] ); ?></span>
                                    <span class="wpcafe-allergen__state"></span>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="options_group">
                    <p class="form-field"><label><?php esc_html_e( 'Custom Allergens', 'wp-cafe' ); ?></label></p>
                    <div class="wpcafe-repeater" data-repeater="custom_allergens">
                        <div class="wpcafe-repeater__rows">
                            <?php if ( ! empty( $custom_allergens ) ) :
                                foreach ( $custom_allergens as $i => $name ) : ?>
                                    <div class="wpcafe-repeater__row">
                                        <input type="text" name="wpcafe_allergens[custom][<?php echo (int) $i; ?>]" value="<?php echo esc_attr( $name ); ?>" placeholder="<?php esc_attr_e( 'Allergen name', 'wp-cafe' ); ?>" />
                                        <button type="button" class="button wpcafe-repeater__remove">&times;</button>
                                    </div>
                                <?php endforeach;
                            endif; ?>
                        </div>
                        <button type="button" class="button wpcafe-repeater__add"><?php esc_html_e( '+ Add Custom Allergen', 'wp-cafe' ); ?></button>
                        <template class="wpcafe-repeater__template">
                            <div class="wpcafe-repeater__row">
                                <input type="text" name="wpcafe_allergens[custom][__INDEX__]" value="" placeholder="<?php esc_attr_e( 'Allergen name', 'wp-cafe' ); ?>" />
                                <button type="button" class="button wpcafe-repeater__remove">&times;</button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Persist nutrition + allergen meta.
     */
    public function save( $post_id ) {
        if ( ! current_user_can( 'edit_product', $post_id ) ) {
            return;
        }

        $nonce = isset( $_POST[ self::NONCE_NAME ] ) ? wp_unslash( $_POST[ self::NONCE_NAME ] ) : '';
        if ( ! $nonce || ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
            return;
        }

        self::save_payload( $post_id, $_POST );
    }

    /**
     * Sanitize a raw nutrition/allergen POST payload and persist the two meta
     * blobs. Split out of save() so other contexts (WCFM vendor product
     * manager) can reuse the identical sanitization + storage after doing
     * their own capability/ownership/nonce checks.
     *
     * @param int   $product_id Product to write meta to.
     * @param array $raw_post   Raw request array (slashed), typically $_POST.
     * @return void
     */
    public static function save_payload( $product_id, array $raw_post ) {
        $raw_nutrition = isset( $raw_post['wpcafe_nutrition'] ) && is_array( $raw_post['wpcafe_nutrition'] )
            ? wp_unslash( $raw_post['wpcafe_nutrition'] )
            : [];

        $raw_format       = $raw_nutrition['format'] ?? 'fda';
        $raw_serving_unit = $raw_nutrition['serving_unit'] ?? 'g';

        $sanitized = [
            'enabled'       => ! empty( $raw_nutrition['enabled'] ),
            'format'        => in_array( $raw_format, wpc_nutrition_formats(), true ) ? $raw_format : 'fda',
            'serving_label' => isset( $raw_nutrition['serving_label'] ) ? sanitize_text_field( $raw_nutrition['serving_label'] ) : '',
            'serving_size'  => isset( $raw_nutrition['serving_size'] ) && '' !== $raw_nutrition['serving_size'] ? self::sanitize_decimal( $raw_nutrition['serving_size'] ) : '',
            'serving_unit'  => in_array( $raw_serving_unit, wpc_nutrition_serving_units(), true ) ? $raw_serving_unit : 'g',
            'ingredients'   => self::sanitize_ingredients( $raw_nutrition['ingredients'] ?? [] ),
        ];

        foreach ( wpc_nutrition_field_keys() as $k ) {
            $sanitized[ $k ] = isset( $raw_nutrition[ $k ] ) && '' !== $raw_nutrition[ $k ]
                ? self::sanitize_decimal( $raw_nutrition[ $k ] )
                : '';
        }

        $sanitized['vitamins'] = [];
        if ( ! empty( $raw_nutrition['vitamins'] ) && is_array( $raw_nutrition['vitamins'] ) ) {
            $allowed_units = wpc_nutrition_vitamin_units();
            foreach ( $raw_nutrition['vitamins'] as $row ) {
                if ( ! is_array( $row ) ) continue;
                $name = isset( $row['name'] ) ? sanitize_text_field( $row['name'] ) : '';
                if ( '' === $name ) continue;
                $sanitized['vitamins'][] = [
                    'name'  => $name,
                    'value' => isset( $row['value'] ) && '' !== $row['value'] ? self::sanitize_decimal( $row['value'] ) : '',
                    'unit'  => in_array( ( $row['unit'] ?? '% DV' ), $allowed_units, true ) ? $row['unit'] : '% DV',
                ];
            }
        }

        update_post_meta( $product_id, '_wpcafe_nutrition_info', wp_json_encode( $sanitized ) );

        // Allergens.
        $raw_allergens = isset( $raw_post['wpcafe_allergens'] ) && is_array( $raw_post['wpcafe_allergens'] )
            ? wp_unslash( $raw_post['wpcafe_allergens'] )
            : [];

        $allowed_slugs   = wpc_allergen_preset_slugs();
        $contains        = [];
        $may_contain     = [];
        if ( ! empty( $raw_allergens['states'] ) && is_array( $raw_allergens['states'] ) ) {
            foreach ( $raw_allergens['states'] as $slug => $state ) {
                $slug  = sanitize_key( $slug );
                $state = sanitize_key( $state );
                if ( ! in_array( $slug, $allowed_slugs, true ) ) continue;
                if ( 'contains' === $state ) {
                    $contains[] = $slug;
                } elseif ( 'may_contain' === $state ) {
                    $may_contain[] = $slug;
                }
            }
        }

        $custom = [];
        if ( ! empty( $raw_allergens['custom'] ) && is_array( $raw_allergens['custom'] ) ) {
            foreach ( $raw_allergens['custom'] as $name ) {
                $name = sanitize_text_field( $name );
                if ( '' !== $name ) $custom[] = $name;
            }
        }

        $allergen_blob = [
            'contains'    => array_values( array_unique( $contains ) ),
            'may_contain' => array_values( array_unique( $may_contain ) ),
            'custom'      => array_values( $custom ),
        ];

        update_post_meta( $product_id, '_wpcafe_allergens', wp_json_encode( $allergen_blob ) );
    }

    /**
     * Normalize ingredients to a clean array of trimmed strings.
     * Accepts array (repeater), comma-separated string (legacy), or anything else.
     */
    private static function sanitize_ingredients( $value ) {
        if ( is_string( $value ) ) {
            $value = explode( ',', $value );
        }
        if ( ! is_array( $value ) ) {
            return [];
        }
        $clean = [];
        foreach ( $value as $item ) {
            $item = sanitize_text_field( trim( (string) $item ) );
            if ( '' !== $item ) {
                $clean[] = $item;
            }
        }
        return array_values( $clean );
    }

    /**
     * Sanitize a decimal-style number input — keep as string to preserve precision.
     */
    private static function sanitize_decimal( $value ) {
        if ( is_numeric( $value ) ) {
            return (string) (float) $value;
        }
        return '';
    }

    /**
     * Nutrition admin asset descriptors (handle, url, version) for the CSS + JS
     * that drive the panel (repeaters, allergen chip cycling). Shared so other
     * contexts (WCFM vendor product manager) enqueue the exact same files
     * instead of hardcoding wp-cafe paths.
     *
     * @return array{css:array,js:array,localize:array}
     */
    public static function asset_urls() {
        $base_url = plugins_url( 'assets/admin/', WPCAFE_FILE );
        $version  = defined( 'WPCAFE_VERSION' ) ? WPCAFE_VERSION : false;

        return [
            'css'      => [
                'handle'  => 'wpcafe-nutrition-admin',
                'src'     => $base_url . 'nutrition.css',
                'version' => $version,
            ],
            'js'       => [
                'handle'  => 'wpcafe-nutrition-admin',
                'src'     => $base_url . 'nutrition.js',
                'version' => $version,
            ],
            'localize' => [
                'object' => 'wpcafeNutrition',
                'data'   => [
                    'i18n' => [
                        'off'        => __( 'off', 'wp-cafe' ),
                        'contains'   => __( 'Contains', 'wp-cafe' ),
                        'mayContain' => __( 'May Contain', 'wp-cafe' ),
                    ],
                ],
            ],
        ];
    }

    /**
     * Enqueue admin assets only on the product edit screen.
     */
    public function enqueue_assets( $hook ) {
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
            return;
        }
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || 'product' !== $screen->id ) {
            return;
        }

        $assets = self::asset_urls();

        wp_enqueue_style( $assets['css']['handle'], $assets['css']['src'], [], $assets['css']['version'] );

        // Converted to vanilla JS — no jQuery dependency.
        wp_enqueue_script( $assets['js']['handle'], $assets['js']['src'], [], $assets['js']['version'], true );

        wp_localize_script( $assets['js']['handle'], $assets['localize']['object'], $assets['localize']['data'] );
    }
}
