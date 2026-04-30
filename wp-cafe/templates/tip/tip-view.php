<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals -- template scope; locally-extracted variables and third-party (Elementor) hook names.

    use WpCafe\Settings;

    $settings   = Settings::get();
    $wc_session = WC()->session;

    $type                 = wpc_get_option( 'tipping_calculation_method', 'fixed_amount' );
    $tip_options          = wpc_get_option( 'tip_options', [] );
    $is_enable_custom_tip = wpc_get_option( 'enable_custom_tipping' );
    $tip_selected_type    = $type;
    $tip_amount           = 0;

    $types = [
        'fixed_amount'      => __( 'Fixed', 'wp-cafe' ),
        'percentage_amount' => __( 'Percentage(%)', 'wp-cafe' ),
        'custom'            => wpc_get_option( 'custom_tipping_label', __( 'Custom Tip', 'wp-cafe' ) ),
    ];

    $tip_added = 0;
    $tip_data = $wc_session->get('wpc_pro_tip');

    if ( ! empty( $tip_data ) ) {
        $tip_added             = $tip_data['tip_added'];
        $tip_selected_type     = $tip_data['tip_selected_type'];
        $tip_amount            = $tip_data['tip_amount'];
    }  
    
    if ( $tip_selected_type == 'custom' && ! $is_enable_custom_tip ) {
        $tip_selected_type = $type;
        $wc_session->__unset('wpc_pro_tip');
    }

    // Get currency symbol
    $currency_symbol = get_woocommerce_currency_symbol();
    
    // Get colors for dynamic styling
    $primary_color = wpc_get_option('primary_color', '#E7272D');
    $secondary_color = wpc_get_option('secondary_color', '#cf4c1f');

    // Only render tip section if there are tip options OR custom tipping is enabled
    if ( empty( $tip_options ) && ! $is_enable_custom_tip ) {
        return;
    }
?>

<style>
    :root {
        --wpc-tip-primary: <?php echo esc_attr($primary_color); ?>;
        --wpc-tip-secondary: <?php echo esc_attr($secondary_color); ?>;
        --wpc-tip-primary-rgb: <?php echo esc_attr(implode(', ', sscanf($primary_color, "#%02x%02x%02x"))); ?>;
    }
</style>

<div class="wpc_pro_order_tip_block" id="wpc_pro_order_tip_block">
    <div class="wpc_pro_order_tip_title">
        <h3><?php echo esc_html__( 'Tip Amount', 'wp-cafe' ); ?></h3>
    </div>

    <div class="wpc_pro_order_tip_wrapper" id="wpc_pro_order_tip_wrapper">
        
        <!-- Hidden input to store selected tip type -->
        <input type="hidden" name="wpc_pro_tip_type" class="wpc_pro_tip_type" value="<?php echo esc_attr($tip_selected_type); ?>" />
        
        <!-- Preset Tip Buttons -->
        <?php if ( ! empty( $tip_options ) ): ?>
        <div class="wpc_tip_preset_buttons">
            <?php foreach( $tip_options as $option ): 
                $is_selected = ($tip_added && $tip_selected_type === $type && $tip_amount == $option);
                $button_class = $is_selected ? 'wpc-tip-btn wpc-tip-btn-active' : 'wpc-tip-btn';
            ?>
                <button 
                    type="button" 
                    class="<?php echo esc_attr($button_class); ?>" 
                    data-tip-type="<?php echo esc_attr($type); ?>"
                    data-tip-amount="<?php echo esc_attr($option); ?>"
                >
                    <?php 
                    if ($type === 'percentage_amount') {
                        echo esc_html($option) . '%';
                    } else {
                        echo esc_html($currency_symbol . $option);
                    }
                    ?>
                </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Custom Tip Button -->
        <?php if( $is_enable_custom_tip ): 
            $is_custom_selected = ($tip_added && $tip_selected_type === 'custom');
            $custom_class = $is_custom_selected ? 'wpc-tip-btn wpc-tip-custom wpc-tip-btn-active' : 'wpc-tip-btn wpc-tip-custom';
        ?>
        <div class="wpc_tip_action_buttons">
            <button 
                type="button" 
                class="<?php echo esc_attr($custom_class); ?>"
                data-tip-type="custom"
            >
                <?php echo esc_html( $types['custom'] ); ?>
            </button>
        </div>
        <?php endif; ?>

        <!-- Custom Amount Input (Hidden by default) -->
        <?php if( $is_enable_custom_tip ): ?>
        <div class="wpc_pro_tip_type_custom_wrap" style="<?php echo ( $tip_selected_type != 'custom' ) ? 'display: none;' : ''; ?>">
            <div class="wpc-custom-tip-input-group">
                <span class="wpc-currency-symbol"><?php echo esc_html($currency_symbol); ?></span>
                <input 
                    type="number" 
                    name="wpc_pro_custom_tip_amount" 
                    class="wpc_pro_custom_tip_amount" 
                    id="wpc_pro_custom_tip_amount" 
                    placeholder="0.00"
                    min="0"
                    step="0.01"
                    value="<?php echo ($tip_selected_type === 'custom') ? esc_attr($tip_amount) : ''; ?>" 
                />
                <button 
                    type="button" 
                    class="wpc-btn wpc_pro_add_tip wpc-custom-apply-btn"
                    <?php echo ( $tip_selected_type !== 'custom' || empty($tip_amount) ) ? 'disabled' : ''; ?>
                >
                    <?php echo esc_html__( 'Apply', 'wp-cafe' ); ?>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Hidden fields for compatibility with existing JS -->
        <input type="hidden" class="wpc_pro_percentage_tip_amount" value="<?php echo ($tip_selected_type !== 'custom') ? esc_attr($tip_amount) : '0'; ?>" />

        <!-- Message Display -->
        <div class="wpc_pro_tip_msg_wrap">
            <span class="wpc_pro_tip_msg"></span>
        </div>

        <!-- Remove Tip Button (shown when tip is added) -->
        <?php if ($tip_added): ?>
        <div class="wpc_tip_remove_wrap">
            <button type="button" class="wpc_pro_remove_tip">
                <svg class="wpc-tip-trash-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    <line x1="10" y1="11" x2="10" y2="17"></line>
                    <line x1="14" y1="11" x2="14" y2="17"></line>
                </svg>
                <?php echo esc_html__( 'Remove Tip', 'wp-cafe' ); ?>
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>
