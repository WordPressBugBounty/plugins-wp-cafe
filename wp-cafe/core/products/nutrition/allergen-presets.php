<?php
/**
 * Preset list of major food allergens.
 *
 * Slug = stable identifier stored in product meta.
 * Label = human-readable, translated at render time.
 * Icon = SVG sprite symbol id (matches assets/icons/allergens.svg <symbol id>).
 *
 * @package WpCafe/Products/Nutrition
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'wpc_allergen_presets' ) ) {
    /**
     * Return the canonical EU-14 allergen list plus a few common extras.
     *
     * @return array<string, array{label:string, icon:string}>
     */
    function wpc_allergen_presets() {
        $presets = [
            'gluten'    => [ 'label' => __( 'Gluten', 'wp-cafe' ),    'icon' => 'wpc-allergen-gluten' ],
            'dairy'     => [ 'label' => __( 'Dairy', 'wp-cafe' ),     'icon' => 'wpc-allergen-dairy' ],
            'eggs'      => [ 'label' => __( 'Eggs', 'wp-cafe' ),      'icon' => 'wpc-allergen-eggs' ],
            'fish'      => [ 'label' => __( 'Fish', 'wp-cafe' ),      'icon' => 'wpc-allergen-fish' ],
            'shellfish' => [ 'label' => __( 'Shellfish', 'wp-cafe' ), 'icon' => 'wpc-allergen-shellfish' ],
            'peanuts'   => [ 'label' => __( 'Peanuts', 'wp-cafe' ),   'icon' => 'wpc-allergen-peanuts' ],
            'tree_nuts' => [ 'label' => __( 'Tree Nuts', 'wp-cafe' ), 'icon' => 'wpc-allergen-tree-nuts' ],
            'soy'       => [ 'label' => __( 'Soy', 'wp-cafe' ),       'icon' => 'wpc-allergen-soy' ],
            'sesame'    => [ 'label' => __( 'Sesame', 'wp-cafe' ),    'icon' => 'wpc-allergen-sesame' ],
            'mustard'   => [ 'label' => __( 'Mustard', 'wp-cafe' ),   'icon' => 'wpc-allergen-mustard' ],
            'celery'    => [ 'label' => __( 'Celery', 'wp-cafe' ),    'icon' => 'wpc-allergen-celery' ],
            'sulphites' => [ 'label' => __( 'Sulphites', 'wp-cafe' ), 'icon' => 'wpc-allergen-sulphites' ],
            'lupin'     => [ 'label' => __( 'Lupin', 'wp-cafe' ),     'icon' => 'wpc-allergen-lupin' ],
            'molluscs'  => [ 'label' => __( 'Molluscs', 'wp-cafe' ),  'icon' => 'wpc-allergen-molluscs' ],
        ];

        return apply_filters( 'wpc_allergen_presets', $presets );
    }
}

if ( ! function_exists( 'wpc_allergen_preset_slugs' ) ) {
    /**
     * Flat list of valid preset slugs — used as save-time allowlist.
     *
     * @return string[]
     */
    function wpc_allergen_preset_slugs() {
        return array_keys( wpc_allergen_presets() );
    }
}
