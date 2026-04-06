"use strict";

/**
 * Intercepts WooCommerce Blocks Store API calls and triggers fragment refresh events for mini-cart updates
 */
(function() {
    if (typeof window !== 'undefined' && typeof window.fetch !== 'undefined') {
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            const fetchPromise = originalFetch.apply(this, args);

            return fetchPromise.then(response => {
                if (args[0] && typeof args[0] === 'string' && args[0].includes('wc/store/v1/cart')) {
                    const clonedResponse = response.clone();

                    clonedResponse.json().then(data => {
                        if (data && (data.items || data.totals)) {
                            if (typeof jQuery !== 'undefined') {
                                jQuery('body').trigger('wc_fragment_refresh');
                                jQuery(document).trigger('wc_fragment_refresh');
                            }
                        }
                    }).catch(() => {});
                }
                return response;
            }).catch(error => {
                return Promise.reject(error);
            });
        };
    }
})();

(function($) {

    $(document).ready(function() {

        // Mini-Cart Quantity Handlers

        // Increase quantity
        $(document).on('click', '.wpc-woocommerce-mini-cart .quantity .plus', function(e) {
            e.preventDefault();
            const $input = $(this).siblings('.qty');
            const currentVal = parseInt($input.val());
            $input.val(currentVal + 1).trigger('change');
        });

        // Decrease quantity
        $(document).on('click', '.wpc-woocommerce-mini-cart .quantity .minus', function(e) {
            e.preventDefault();
            const $input = $(this).siblings('.qty');
            const currentVal = parseInt($input.val());

            if (currentVal > 1) {
                $input.val(currentVal - 1).trigger('change');
            }
        });

        // Handle quantity input change
        $(document).on('change', '.wpc-woocommerce-mini-cart .qty', function() {
            const $qtyInput = $(this);
            let newQty = parseFloat($qtyInput.val());

            // Ensure quantity is at least 1
            if (newQty < 1 || isNaN(newQty)) {
                $qtyInput.val(1);
                newQty = 1;
            }

            // Get cart_item_key and product_id from the input's data attributes
            let cartItemKey = $qtyInput.data('cart-item-key');
            let productId = $qtyInput.data('product-id');

            // Fallback to getting from remove link if not found in input
            if (!cartItemKey || !productId) {
                const $cartItem = $qtyInput.closest('.wpc-woocommerce-mini-cart-item');
                const $removeLink = $cartItem.find('a.remove_from_cart_button');
                cartItemKey = $removeLink.data('cart_item_key');
                productId = $removeLink.data('product_id');
            }

            const $cartItem = $qtyInput.closest('.wpc-woocommerce-mini-cart-item');
            const $priceElement = $cartItem.find('.wpc-minicart-subtotal');
            const itemPrice = parseFloat($priceElement.data('item-price') || 0);

            if (!cartItemKey || !productId) {
                return;
            }

            const newSubtotal = (newQty * itemPrice).toFixed(2);
            $priceElement.text(newSubtotal);

            // Send AJAX request to update cart
            $.ajax({
                type: 'POST',
                url: wpc_cart_nonce_data.ajax_url,
                data: {
                    action: 'wpc_update_cart_quantity',
                    nonce: wpc_cart_nonce_data.nonce,
                    cart_item_key: cartItemKey,
                    qty: newQty
                },
                success: function(response) {
                    if (response.success) {
                        $('.wpc-mini-cart-count').text(response.data.cart_count);

                        // Update item subtotal
                        $priceElement.text(newSubtotal);

                        // Update the main cart subtotal
                        const $subtotalElement = $('p.wpc-woocommerce-mini-cart__total.total');
                        if ($subtotalElement.length > 0 && response.data.cart_subtotal) {
                            $subtotalElement.html('<strong>Subtotal:</strong> ' + response.data.cart_subtotal);
                        }

                        // Update the cart total
                        if (response.data.cart_total) {
                            $('.wpc-minicart-total').html(response.data.cart_total);
                        }

                        // Trigger custom event for external handlers
                        $(document.body).trigger('wpc_cart_quantity_updated', {
                            cart_item_key: response.data.cart_item_key,
                            new_qty: newQty,
                            new_subtotal: response.data.new_subtotal,
                            cart_total: response.data.cart_total
                        });
                    }
                },
                error: function() {
                    location.reload();
                }
            });
        });

        // Add plus/minus buttons if not present
        if ($('.minus').length === 0) {
            $('.mini-cart-quantity-wrapper .quantity').prepend('<button type="button" class="minus">-</button>');
        }
        if ($('.plus').length === 0) {
            $('.mini-cart-quantity-wrapper .quantity').append('<button type="button" class="plus">+</button>');
        }

        // Mini-Cart Coupon Handler
        $(document).on('submit', '.wpc_coupon_form, .coupon_from', function() {
            // Let WooCommerce handle the form submission
            // This ensures proper nonce checking and cart updates
            return true;
        });

        // Mini-Cart Item Removal
        $(document).on('removed_from_cart', function() {
            $('body').trigger('wc_fragment_refresh');
            $('body').trigger('wpc-mini-cart-count');
        });

        // Handle remove button clicks in mini-cart
        $(document).on('click', '.wpc-woocommerce-mini-cart .remove_from_cart_button, .product_list_widget .remove_from_cart_button', function(e) {
            e.preventDefault();

            const $removeButton = $(this);
            const cartItemKey = $removeButton.data('cart_item_key');

            if (!cartItemKey) {
                return false;
            }

            // Get the cart item element
            const $cartItem = $removeButton.closest('.wpc-woocommerce-mini-cart-item');

            // Add loading state
            $cartItem.addClass('loading');

            // Send AJAX request to remove item using the existing wpc_cart_nonce_data
            $.ajax({
                type: 'POST',
                url: wpc_cart_nonce_data.ajax_url,
                data: {
                    action: 'wpc_remove_cart_item',
                    nonce: wpc_cart_nonce_data.nonce,
                    cart_item_key: cartItemKey
                },
                success: function(response) {
                    if (response.success) {
                        $('body').trigger('wc_fragment_refresh');
                        $(document.body).trigger('removed_from_cart', [cartItemKey, $removeButton]);
                    } else {
                        // Remove loading state on error
                        $cartItem.removeClass('loading');
                    }
                },
                error: function() {
                    location.reload();
                }
            });

            return false;
        });

        function updateCartCountBadge(countValue) {
            var $countSpans = $('.wpc-mini-cart-count');

            if ($countSpans.length === 0) {
                var $cartIcon = $('.wpc_cart_icon');
                if ($cartIcon.length > 0) {     // Create count badge for empty cart
                    var $sup = $cartIcon.find('.basket-item-count');
                    if ($sup.length === 0) {
                        $cartIcon.append('<sup class="basket-item-count" style="display: inline-block;">' +
                                       '<span class="cart-items-count count wpc-mini-cart-count">' + countValue + '</span>' +
                                       '</sup>');
                    } else {
                        $sup.html('<span class="cart-items-count count wpc-mini-cart-count">' + countValue + '</span>');
                    }
                }
            } else {
                $countSpans.text(countValue);
            }
        }

        // Handle WooCommerce cart updates (both traditional and Blocks)
        function refreshMiniCart() {
            if (typeof wc_cart_fragments_params === 'undefined') {
                return;
            }

            $.ajax({
                url: wc_cart_fragments_params.wc_ajax_url.toString().replace('%%endpoint%%', 'get_refreshed_fragments'),
                type: 'POST',
                dataType: 'json',
                success: function(data) {
                    if (data && data.fragments) {
                        $.each(data.fragments, function(selector, html) {
                            if (selector === '.wpc-mini-cart-count') {
                                updateCartCountBadge($(html).text());
                            } else {
                                $(selector).replaceWith(html);
                            }
                        });
                        $('body').trigger('wc_fragments_refreshed');
                    }
                }
            });
        }

        // Listen for both added_to_cart and fragment refresh events
        $('body').on('added_to_cart wc_fragment_refresh', function() {
            refreshMiniCart();
        });

        // Mini-Cart Utilities

        // Remove order time if reservation exists
        const get_reserv_detials = localStorage.getItem('wpc_reservation_details');
        if (typeof get_reserv_detials !== 'undefined' && get_reserv_detials !== null) {
            $('.wpc_pro_order_time').remove();
        }

        // Cross sell products swiper
        if (document.querySelector('.wpc-cross-sells') && typeof Swiper !== 'undefined') {
            new Swiper('.wpc-cross-sells', {
                navigation: {
                    nextEl: '.swiper-btn-next',
                    prevEl: '.swiper-btn-prev',
                },
                autoplay: false,
                spaceBetween: 0,
                pagination: true
            });
        }

        // Coupon form toggle
        $(document).on('click', '.showcoupon', function() {
            $('.coupon_from_wrap').slideToggle(400);
        });

        // After add to cart message and reservation details
        $('body').on('added_to_cart', function(_event, _fragments, _cartHash, button) {
            $('.wpc-cart-message').fadeIn().delay(3000).fadeOut();

            // Pass product data to reservation details if enabled
            if (typeof food_details_reservation !== 'undefined' &&
                typeof get_reserv_detials !== 'undefined' &&
                get_reserv_detials !== null &&
                typeof button !== 'undefined'
            ) {
                var product_id = button.data('product_id'),
                    product_name = button.data('product_name'),
                    product_price = button.data('product_price');

                food_details_reservation(
                    {
                        product_id: product_id,
                        product_name: product_name,
                        product_price: product_price,
                    },
                    $
                );
            }
        });
    });

})(jQuery);
