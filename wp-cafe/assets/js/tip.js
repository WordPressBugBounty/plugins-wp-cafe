(function($) {
    "use strict";

    $(document).ready(function() {

        var tip_wrapper = $('.woocommerce');
        var tip_block = $('#wpc_pro_order_tip_block');

        // Preset Tip Button Click (Percentage or Fixed)
        tip_wrapper.on('click', '.wpc-tip-btn:not(.wpc-tip-custom):not(.wpc_pro_add_tip):not(.wpc_pro_remove_tip)', function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var tip_type = $this.data('tip-type');
            var tip_amount = $this.data('tip-amount');

            // Clear any previous messages
            clearMessage();

            // For preset tips, apply immediately
            if (tip_amount && tip_amount > 0) {
                // Update hidden fields
                tip_wrapper.find('.wpc_pro_tip_type').val(tip_type);
                tip_wrapper.find('.wpc_pro_percentage_tip_amount').val(tip_amount);
                
                // Hide custom input if visible
                tip_wrapper.find('.wpc_pro_tip_type_custom_wrap').slideUp(200);
                
                // Add tip via AJAX
                addTipToSession(tip_type, tip_amount, function(success) {
                    if (success) {
                        // Update UI - mark this button as active
                        tip_wrapper.find('.wpc-tip-btn').removeClass('wpc-tip-btn-active');
                        $this.addClass('wpc-tip-btn-active');
                    }
                });
            }
        });

        // Custom Tip Button Click
        tip_wrapper.on('click', '.wpc-tip-custom', function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var custom_wrap = tip_wrapper.find('.wpc_pro_tip_type_custom_wrap');
            
            // Clear messages
            clearMessage();
            
            // Toggle custom input visibility
            if (custom_wrap.is(':visible')) {
                custom_wrap.slideUp(200);
                $this.removeClass('wpc-tip-btn-active');
            } else {
                custom_wrap.slideDown(200);
                // Mark all other buttons as inactive
                tip_wrapper.find('.wpc-tip-btn').removeClass('wpc-tip-btn-active');
                $this.addClass('wpc-tip-btn-active');
                
                // Update hidden type field
                tip_wrapper.find('.wpc_pro_tip_type').val('custom');
                
                // Focus on input
                setTimeout(function() {
                    tip_wrapper.find('.wpc_pro_custom_tip_amount').focus();
                }, 250);
            }
        });

        // Custom Amount Input Change - Enable/Disable Apply Button
        tip_wrapper.on('input change keyup', '.wpc_pro_custom_tip_amount', function() {
            var amount = parseFloat($(this).val()) || 0;
            var apply_btn = tip_wrapper.find('.wpc-custom-apply-btn');
            
            if (amount > 0) {
                apply_btn.removeAttr('disabled');
            } else {
                apply_btn.attr('disabled', 'disabled');
            }
        });

        // Custom Amount Apply Button Click
        tip_wrapper.on('click', '.wpc_pro_add_tip, .wpc-custom-apply-btn', function(e) {
            e.preventDefault();
            
            var tip_type = 'custom';
            var tip_amount = parseFloat(tip_wrapper.find('.wpc_pro_custom_tip_amount').val()) || 0;
            
            if (tip_amount <= 0) {
                return;
            }
            
            // Clear messages
            clearMessage();
            
            // Update hidden fields
            tip_wrapper.find('.wpc_pro_tip_type').val(tip_type);
            
            // Add tip via AJAX
            addTipToSession(tip_type, tip_amount, function(success) {
                if (success) {
                    // Keep custom button active
                    tip_wrapper.find('.wpc-tip-btn').removeClass('wpc-tip-btn-active');
                    tip_wrapper.find('.wpc-tip-custom').addClass('wpc-tip-btn-active');
                }
            });
        });

        // Remove Tip Button Click
        tip_wrapper.on('click', '.wpc_pro_remove_tip', function(e) {
            e.preventDefault();
            
            removeTipFromSession(function() {
                resetTipUI();
            });
        });

        // Add Tip to Session via AJAX
        function addTipToSession(tip_type, tip_amount, callback) {
            // Show loading state
            tip_block.addClass('wpc-loading');
            $('.woocommerce').block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });

            $.ajax({
                type: "POST",
                url: wpc_pro_tip_obj.ajax_url,
                dataType: 'json',
                data: { 
                    action: 'add_tip', 
                    security: wpc_pro_tip_obj.add_tip_nonce,
                    tip_selected_type: tip_type, 
                    tip_amount: tip_amount, 
                },
                complete: function() {
                    $('.woocommerce').unblock();
                    tip_block.removeClass('wpc-loading');
                },
                success: function(res) {
                    var success = res.status_code == 1;
                    
                    if (success) {
                        // Trigger cart/checkout update
                        $('body').trigger('update_checkout');
                        $('[name="update_cart"]').attr('aria-disabled', false).removeAttr('disabled').trigger('click');
                        
                        // Show remove button
                        showRemoveButton();
                    }
                    
                    // Show message
                    if (res.message) {
                        showMessage(res.message);
                    }
                    
                    if (callback) callback(success);
                },
                error: function() {
                    $('.woocommerce').unblock();
                    tip_block.removeClass('wpc-loading');
                    showMessage('An error occurred. Please try again.');
                    if (callback) callback(false);
                }
            });
        }

        // Remove Tip from Session via AJAX
        function removeTipFromSession(callback) {
            // Show loading state
            tip_block.addClass('wpc-loading');
            $('.woocommerce').block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });

            $.ajax({
                type: "POST",
                url: wpc_pro_tip_obj.ajax_url,
                dataType: 'json',
                data: { 
                    action: 'remove_tip', 
                    security: wpc_pro_tip_obj.remove_tip_nonce,
                },
                complete: function() {
                    $('.woocommerce').unblock();
                    tip_block.removeClass('wpc-loading');
                },
                success: function(res) {
                    if (res.status_code == 1) {
                        // Trigger cart/checkout update
                        $('body').trigger('update_checkout');
                        $('[name="update_cart"]').attr('aria-disabled', false).removeAttr('disabled').trigger('click');
                    }
                    
                    // Show message
                    if (res.message) {
                        showMessage(res.message);
                    }
                    
                    if (callback) callback(res.status_code == 1);
                },
                error: function() {
                    $('.woocommerce').unblock();
                    tip_block.removeClass('wpc-loading');
                    showMessage('An error occurred. Please try again.');
                    if (callback) callback(false);
                }
            });
        }

        // Reset Tip UI
        function resetTipUI() {
            // Clear all active states
            tip_wrapper.find('.wpc-tip-btn').removeClass('wpc-tip-btn-active');
            
            // Hide custom input
            tip_wrapper.find('.wpc_pro_tip_type_custom_wrap').slideUp(200);
            
            // Clear custom input value
            tip_wrapper.find('.wpc_pro_custom_tip_amount').val('');
            
            // Clear hidden fields
            tip_wrapper.find('.wpc_pro_percentage_tip_amount').val('0');
            
            // Hide remove button
            hideRemoveButton();
            
            // Clear messages
            clearMessage();
        }

        // Show Message
        function showMessage(message) {
            var msg_block = tip_wrapper.find('.wpc_pro_tip_msg');
            var msg_wrap = tip_wrapper.find('.wpc_pro_tip_msg_wrap');
            
            msg_block.text(message);
            msg_wrap.addClass('wpc-has-message');
            
            setTimeout(function() {
                clearMessage();
            }, 3000);
        }

        // Clear Message
        function clearMessage() {
            var msg_block = tip_wrapper.find('.wpc_pro_tip_msg');
            var msg_wrap = tip_wrapper.find('.wpc_pro_tip_msg_wrap');
            
            msg_block.text('');
            msg_wrap.removeClass('wpc-has-message');
        }

        // Show Remove Tip Button
        function showRemoveButton() {
            var remove_wrap = tip_wrapper.find('.wpc_tip_remove_wrap');
            if (remove_wrap.length === 0) {
                // Create remove button wrapper if it doesn't exist
                var remove_html = '<div class="wpc_tip_remove_wrap" style="display:none;">' +
                    '<button type="button" class="wpc_pro_remove_tip">Remove Tip</button>' +
                    '</div>';
                tip_wrapper.find('.wpc_pro_order_tip_wrapper').append(remove_html);
                remove_wrap = tip_wrapper.find('.wpc_tip_remove_wrap');
            }
            remove_wrap.slideDown(200);
        }

        // Hide Remove Tip Button
        function hideRemoveButton() {
            tip_wrapper.find('.wpc_tip_remove_wrap').slideUp(200);
        }

        // Handle Enter key in custom input
        tip_wrapper.on('keypress', '.wpc_pro_custom_tip_amount', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                var apply_btn = tip_wrapper.find('.wpc-custom-apply-btn');
                if (!apply_btn.is(':disabled')) {
                    apply_btn.trigger('click');
                }
            }
        });

    });

})(jQuery);
