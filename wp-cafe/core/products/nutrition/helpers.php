<?php
/**
 * Read + render helpers for product nutrition + allergen meta.
 *
 * @package WpCafe/Products/Nutrition
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'wpc_nutrition_field_keys' ) ) {
    /**
     * Numeric nutrition fields stored in the meta blob.
     *
     * @return string[]
     */
    function wpc_nutrition_field_keys() {
        return [
            'calories', 'energy_kj', 'protein',
            'fat_total', 'fat_saturated', 'fat_trans',
            'carbs_total', 'carbs_sugars', 'carbs_fiber',
            'sodium', 'cholesterol',
        ];
    }
}

if ( ! function_exists( 'wpc_nutrition_formats' ) ) {
    /**
     * Allowed display format slugs.
     */
    function wpc_nutrition_formats() {
        return [ 'fda', 'eu', 'plain' ];
    }
}

if ( ! function_exists( 'wpc_nutrition_serving_units' ) ) {
    function wpc_nutrition_serving_units() {
        return [ 'g', 'ml', 'oz', 'fl_oz', 'pieces' ];
    }
}

if ( ! function_exists( 'wpc_nutrition_vitamin_units' ) ) {
    function wpc_nutrition_vitamin_units() {
        return [ '% DV', 'mg', 'mcg', 'g', 'IU' ];
    }
}

if ( ! function_exists( 'wpc_product_nutrition' ) ) {
    /**
     * Read the nutrition meta blob for a product.
     *
     * @param int $product_id
     * @return array
     */
    function wpc_product_nutrition( $product_id ) {
        $product_id = absint( $product_id );
        if ( ! $product_id ) return [];

        $raw = get_post_meta( $product_id, '_wpcafe_nutrition_info', true );
        if ( is_string( $raw ) && '' !== $raw ) {
            $decoded = json_decode( $raw, true );
            if ( is_array( $decoded ) ) {
                return $decoded;
            }
        }
        if ( is_array( $raw ) ) return $raw;

        return [];
    }
}

if ( ! function_exists( 'wpc_product_allergens' ) ) {
    /**
     * Read the allergen meta blob for a product.
     *
     * @param int $product_id
     * @return array{contains: string[], may_contain: string[], custom: string[]}
     */
    function wpc_product_allergens( $product_id ) {
        $product_id = absint( $product_id );
        $defaults = [ 'contains' => [], 'may_contain' => [], 'custom' => [] ];
        if ( ! $product_id ) return $defaults;

        $raw = get_post_meta( $product_id, '_wpcafe_allergens', true );
        if ( is_string( $raw ) && '' !== $raw ) {
            $decoded = json_decode( $raw, true );
            if ( is_array( $decoded ) ) {
                return wp_parse_args( $decoded, $defaults );
            }
        }
        if ( is_array( $raw ) ) {
            return wp_parse_args( $raw, $defaults );
        }

        return $defaults;
    }
}

if ( ! function_exists( 'wpc_product_has_nutrition_data' ) ) {
    /**
     * True when product has any meaningful nutrition or allergen data.
     */
    function wpc_product_has_nutrition_data( $product_id ) {
        $nutrition = wpc_product_nutrition( $product_id );
        $allergens = wpc_product_allergens( $product_id );

        $has_nutrition = ! empty( $nutrition['enabled'] ) && (
            array_filter( array_intersect_key( $nutrition, array_flip( wpc_nutrition_field_keys() ) ), function ( $v ) { return $v !== '' && $v !== null; } )
            || ! empty( $nutrition['vitamins'] )
            || ! empty( $nutrition['ingredients'] )
        );

        $has_allergens = ! empty( $allergens['contains'] )
            || ! empty( $allergens['may_contain'] )
            || ! empty( $allergens['custom'] );

        return $has_nutrition || $has_allergens;
    }
}

if ( ! function_exists( 'wpc_allergen_icon_svg' ) ) {
    /**
     * Render an inline <svg><use> reference to the allergen sprite.
     */
    function wpc_allergen_icon_svg( $symbol_id ) {
        $symbol_id = sanitize_html_class( $symbol_id );
        if ( ! $symbol_id ) return '';
        $sprite_url = plugins_url( 'assets/icons/allergens.svg', WPCAFE_FILE );
        return sprintf(
            '<svg class="wpc-allergen-chip__icon" aria-hidden="true" focusable="false"><use href="%s#%s"></use></svg>',
            esc_url( $sprite_url ),
            esc_attr( $symbol_id )
        );
    }
}

if ( ! function_exists( 'wpc_render_nutrition_label' ) ) {
    /**
     * Echo the nutrition facts label markup for a product.
     */
    function wpc_render_nutrition_label( $product_id ) {
        $data = wpc_product_nutrition( $product_id );
        if ( empty( $data ) || empty( $data['enabled'] ) ) return;

        $format        = in_array( ( $data['format'] ?? 'fda' ), wpc_nutrition_formats(), true ) ? $data['format'] : 'fda';
        $serving_label = isset( $data['serving_label'] ) ? (string) $data['serving_label'] : '';
        $serving_size  = isset( $data['serving_size'] ) ? (string) $data['serving_size'] : '';
        $serving_unit  = isset( $data['serving_unit'] ) ? (string) $data['serving_unit'] : 'g';
        $has_serving   = ( '' !== $serving_label ) || ( '' !== $serving_size );

        $rows = [
            'calories'      => [ 'label' => __( 'Calories', 'wp-cafe' ),         'unit' => 'kcal', 'bold' => true ],
            'fat_total'     => [ 'label' => __( 'Total Fat', 'wp-cafe' ),        'unit' => 'g',    'bold' => true ],
            'fat_saturated' => [ 'label' => __( 'Saturated Fat', 'wp-cafe' ),    'unit' => 'g',    'indent' => true ],
            'fat_trans'     => [ 'label' => __( 'Trans Fat', 'wp-cafe' ),        'unit' => 'g',    'indent' => true ],
            'cholesterol'   => [ 'label' => __( 'Cholesterol', 'wp-cafe' ),      'unit' => 'mg',   'bold' => true ],
            'sodium'        => [ 'label' => __( 'Sodium', 'wp-cafe' ),           'unit' => 'mg',   'bold' => true ],
            'carbs_total'   => [ 'label' => __( 'Total Carbohydrate', 'wp-cafe' ), 'unit' => 'g',  'bold' => true ],
            'carbs_fiber'   => [ 'label' => __( 'Dietary Fiber', 'wp-cafe' ),    'unit' => 'g',    'indent' => true ],
            'carbs_sugars'  => [ 'label' => __( 'Total Sugars', 'wp-cafe' ),     'unit' => 'g',    'indent' => true ],
            'protein'       => [ 'label' => __( 'Protein', 'wp-cafe' ),          'unit' => 'g',    'bold' => true ],
        ];

        // FDA Daily Value reference amounts (standard 2000 kcal diet).
        $dv = [
            'fat_total'    => 78,    // g
            'fat_saturated' => 20,   // g
            'cholesterol'  => 300,   // mg
            'sodium'       => 2300,  // mg
            'carbs_total'  => 275,   // g
            'carbs_fiber'  => 28,    // g
        ];
        $show_dv = ( 'fda' === $format ) && $has_serving;

        ?>
        <div class="wpc-nutrition-label wpc-nutrition-label--<?php echo esc_attr( $format ); ?>">
            <h3 class="wpc-nutrition-label__title"><?php esc_html_e( 'Nutrition Facts', 'wp-cafe' ); ?></h3>
            <?php if ( $has_serving ) : ?>
                <p class="wpc-nutrition-label__serving">
                    <?php
                    $bits = [];
                    if ( '' !== $serving_label ) $bits[] = $serving_label;
                    if ( '' !== $serving_size )  $bits[] = $serving_size . ( 'pieces' === $serving_unit ? ' ' . __( 'pieces', 'wp-cafe' ) : ( 'fl_oz' === $serving_unit ? ' fl oz' : ' ' . $serving_unit ) );
                    $prefix = 'fda' === $format ? __( 'Serving size: ', 'wp-cafe' ) : __( 'Per serving: ', 'wp-cafe' );
                    echo esc_html( $prefix . implode( ' (', $bits ) . ( count( $bits ) > 1 ? ')' : '' ) );
                    ?>
                </p>
            <?php endif; ?>

            <table class="wpc-nutrition-label__table">
                <?php if ( $show_dv ) : ?>
                    <thead>
                        <tr>
                            <th colspan="2"><?php esc_html_e( 'Amount per serving', 'wp-cafe' ); ?></th>
                            <th class="wpc-nutrition-label__dv-head"><?php esc_html_e( '% DV*', 'wp-cafe' ); ?></th>
                        </tr>
                    </thead>
                <?php endif; ?>
                <tbody>
                    <?php foreach ( $rows as $key => $meta ) :
                        $value = isset( $data[ $key ] ) ? $data[ $key ] : '';
                        if ( '' === $value || null === $value ) continue;

                        $row_classes = [ 'wpc-nutrition-label__row' ];
                        if ( ! empty( $meta['indent'] ) ) $row_classes[] = 'is-indent';
                        if ( ! empty( $meta['bold'] ) )   $row_classes[] = 'is-bold';

                        $dv_pct = '';
                        if ( $show_dv && isset( $dv[ $key ] ) && $dv[ $key ] > 0 && is_numeric( $value ) ) {
                            $dv_pct = round( ( (float) $value / $dv[ $key ] ) * 100 ) . '%';
                        }
                        ?>
                        <tr class="<?php echo esc_attr( implode( ' ', $row_classes ) ); ?>">
                            <td class="wpc-nutrition-label__name"><?php echo esc_html( $meta['label'] ); ?></td>
                            <td class="wpc-nutrition-label__value">
                                <?php echo esc_html( $value . ( 'calories' === $key ? '' : ' ' . $meta['unit'] ) ); ?>
                            </td>
                            <?php if ( $show_dv ) : ?>
                                <td class="wpc-nutrition-label__dv"><?php echo esc_html( $dv_pct ); ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>

                    <?php if ( ! empty( $data['energy_kj'] ) && '' !== $data['energy_kj'] ) : ?>
                        <tr class="wpc-nutrition-label__row wpc-nutrition-label__row--energy-kj">
                            <td class="wpc-nutrition-label__name"><?php esc_html_e( 'Energy', 'wp-cafe' ); ?></td>
                            <td class="wpc-nutrition-label__value" colspan="<?php echo $show_dv ? 2 : 1; ?>">
                                <?php echo esc_html( $data['energy_kj'] . ' kJ' ); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ( ! empty( $data['vitamins'] ) && is_array( $data['vitamins'] ) ) : ?>
                <ul class="wpc-nutrition-label__vitamins">
                    <?php foreach ( $data['vitamins'] as $v ) :
                        if ( empty( $v['name'] ) ) continue;
                        $name  = (string) $v['name'];
                        $value = isset( $v['value'] ) ? (string) $v['value'] : '';
                        $unit  = isset( $v['unit'] ) ? (string) $v['unit'] : '';
                        ?>
                        <li>
                            <span class="wpc-nutrition-label__vitamin-name"><?php echo esc_html( $name ); ?></span>
                            <?php if ( '' !== $value ) : ?>
                                <span class="wpc-nutrition-label__vitamin-value">
                                    <?php echo esc_html( trim( $value . ' ' . $unit ) ); ?>
                                </span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ( $show_dv ) : ?>
                <p class="wpc-nutrition-label__footnote">
                    <?php esc_html_e( '* Percent Daily Values are based on a 2,000 calorie diet.', 'wp-cafe' ); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }
}

if ( ! function_exists( 'wpc_render_allergens' ) ) {
    /**
     * Echo the allergen panel markup for a product.
     */
    function wpc_render_allergens( $product_id ) {
        $allergens = wpc_product_allergens( $product_id );
        $presets   = wpc_allergen_presets();

        $contains    = is_array( $allergens['contains'] ) ? array_values( array_intersect( $allergens['contains'], array_keys( $presets ) ) ) : [];
        $may_contain = is_array( $allergens['may_contain'] ) ? array_values( array_intersect( $allergens['may_contain'], array_keys( $presets ) ) ) : [];
        $custom      = is_array( $allergens['custom'] ) ? array_values( array_filter( array_map( 'strval', $allergens['custom'] ) ) ) : [];

        if ( empty( $contains ) && empty( $may_contain ) && empty( $custom ) ) {
            return;
        }
        ?>
        <div class="wpc-allergens">
            <h3 class="wpc-allergens__title"><?php esc_html_e( 'Allergen Information', 'wp-cafe' ); ?></h3>

            <?php if ( ! empty( $contains ) ) : ?>
                <div class="wpc-allergens__group">
                    <h4 class="wpc-allergens__group-title"><?php esc_html_e( 'Contains', 'wp-cafe' ); ?></h4>
                    <ul class="wpc-allergens__list">
                        <?php foreach ( $contains as $slug ) : $p = $presets[ $slug ]; ?>
                            <li class="wpc-allergen-chip wpc-allergen-chip--contains" title="<?php echo esc_attr( $p['label'] ); ?>">
                                <?php echo wpc_allergen_icon_svg( $p['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <span class="wpc-allergen-chip__label"><?php echo esc_html( $p['label'] ); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $may_contain ) ) : ?>
                <div class="wpc-allergens__group">
                    <h4 class="wpc-allergens__group-title"><?php esc_html_e( 'May Contain', 'wp-cafe' ); ?></h4>
                    <ul class="wpc-allergens__list">
                        <?php foreach ( $may_contain as $slug ) : $p = $presets[ $slug ]; ?>
                            <li class="wpc-allergen-chip wpc-allergen-chip--may-contain" title="<?php echo esc_attr( $p['label'] ); ?>">
                                <?php echo wpc_allergen_icon_svg( $p['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <span class="wpc-allergen-chip__label"><?php echo esc_html( $p['label'] ); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $custom ) ) : ?>
                <div class="wpc-allergens__group">
                    <h4 class="wpc-allergens__group-title"><?php esc_html_e( 'Other', 'wp-cafe' ); ?></h4>
                    <ul class="wpc-allergens__list">
                        <?php foreach ( $custom as $name ) : ?>
                            <li class="wpc-allergen-chip wpc-allergen-chip--custom">
                                <span class="wpc-allergen-chip__label"><?php echo esc_html( $name ); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

if ( ! function_exists( 'wpc_render_ingredients' ) ) {
    /**
     * Echo the ingredients block.
     */
    function wpc_render_ingredients( $product_id ) {
        $data = wpc_product_nutrition( $product_id );
        if ( empty( $data['ingredients'] ) ) return;

        $raw = $data['ingredients'];
        if ( is_array( $raw ) ) {
            $items = array_values( array_filter( array_map( 'trim', $raw ) ) );
        } else {
            $items = array_values( array_filter( array_map( 'trim', explode( ',', (string) $raw ) ) ) );
        }
        if ( empty( $items ) ) return;
        ?>
        <div class="wpc-nutrition-ingredients">
            <h3 class="wpc-nutrition-ingredients__title"><?php esc_html_e( 'Ingredients', 'wp-cafe' ); ?></h3>
            <div class="wpc-nutrition-ingredients__body">
                <?php echo esc_html( implode( ', ', $items ) ); ?>
            </div>
        </div>
        <?php
    }
}
