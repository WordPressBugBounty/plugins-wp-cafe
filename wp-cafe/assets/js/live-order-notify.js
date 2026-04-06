jQuery(function($) {
    // Configuration
    const ajaxurl       = wpcLiveOrder.ajax_url;
    const text_notify   = wpcLiveOrder.text_notify;
    const sound_notify  = wpcLiveOrder.sound_notify;
    const sound_url     = wpcLiveOrder.sound_url;
    const last_order_id = wpcLiveOrder.last_order_id;
    const nonce         = wpcLiveOrder.nonce;
    const local         = 'wpc_last_order_id';
    const order_page_url = wpcLiveOrder.order_page_url;
    const order_table_selector = '.wc-orders-list-table tbody';
    const orders_page_value = 'wc-orders';
    let soundInterval = false;
    let pollInterval = null;
    const pollInterval_ms = 10000;

    /**
     * Order labeling functionality
     */
    const orderLabeler = {
        
        /**
         * Check if order is recent based on order date
         * @param {string} orderDateText - Order date string
         * @returns {object} Recent status object
         */
        isRecentOrder: function(orderDateText) {
            if (!orderDateText) return false;
            
            const orderDate = new Date(orderDateText);
            const currentDate = new Date();
            const timeDiff = currentDate.getTime() - orderDate.getTime();
            const hoursDiff = timeDiff / (1000 * 3600);
            
            return {
                isVeryRecent: hoursDiff <= 1,    // Within 1 hour
                isRecent: hoursDiff <= 24,       // Within 24 hours
                isToday: hoursDiff <= 48         // Within 48 hours
            };
        },

        /**
         * Create styled label HTML
         * @param {string} text - Label text
         * @param {string} type - Label type (very-recent, recent, today)
         * @returns {string} HTML string for the label
         */
        createStyledLabel: function(text, type) {
            let style = '';
            
            switch(type) {
                case 'very-recent':
                    style = 'background-color: #ff4757; color: white; animation: pulse 1.5s infinite;';
                    break;
                case 'recent':
                    style = 'background-color: #ff6b6b; color: white; animation: pulse 2s infinite;';
                    break;
                case 'today':
                    style = 'background-color: #ffa502; color: white;';
                    break;
            }
            
            return `<span class="recent-order-label ${type}" style="display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 0.75em; font-weight: bold; margin-left: 5px; text-transform: uppercase; letter-spacing: 0.5px; ${style}">${text}</span>`;
        },

        /**
         * Add recent labels to order numbers
         */
        addRecentLabels: function() {
            $('.order-view strong').each(function() {
                const $orderElement = $(this);
                
                // Skip if already processed
                if ($orderElement.find('.recent-order-label').length > 0) {
                    return;
                }
                
                // Find the order date from the order row
                const $orderRow = $orderElement.closest('tr');
                let orderDateText = $orderRow.find('.column-order_date time').attr('datetime');
                
                if (!orderDateText) {
                    // Try alternative date selectors
                    orderDateText = $orderRow.find('.column-order_date').text().trim();
                }
                
                if (orderDateText) {
                    const recentStatus = orderLabeler.isRecentOrder(orderDateText);
                    
                    if (recentStatus.isVeryRecent) {
                        $orderElement.append(orderLabeler.createStyledLabel('Just Now', 'very-recent'));
                    } else if (recentStatus.isRecent) {
                        $orderElement.append(orderLabeler.createStyledLabel('Recent', 'recent'));
                    } else if (recentStatus.isToday) {
                        $orderElement.append(orderLabeler.createStyledLabel('Today', 'today'));
                    }
                }
            });
        },

        /**
         * Add label to specific order by ID
         * @param {number} orderId - Order ID to label
         */
        addLabelToOrder: function(orderId) {
            const $orderElement = $(`.order-view strong:contains("#${orderId}")`);
            if ($orderElement.length > 0) {
                if ($orderElement.find('.recent-order-label').length > 0) {
                    return;
                }
                // Add "Just Now" label for newly received orders
                $orderElement.append(orderLabeler.createStyledLabel('Just Now', 'very-recent'));
            }
        }
    };

    /**
     * Displays a live notification popup for a new order.
     *
     * @param {number|string} orderId - The ID of the new order to display in the notification.
     * 
     * This function creates a styled notification element, appends it to the notification list,
     * and animates its appearance. The notification can be dismissed manually by clicking the close button,
     * or it will automatically disappear after 5 seconds. The notification content includes the order ID.
     */
    function showNotice(orderId) {
        const noticeList = document.querySelector('.wpc-live-notice-list');
        const soundDuration = wpcLiveOrder.sound_duration * 60 * 1000;
        if (!noticeList) return;
    
        const notice = document.createElement('div');
        notice.className = 'wpc-live-order-notice';
        const orderViewUrl = `${wpcLiveOrder.admin_url}admin.php?page=wc-orders&action=edit&id=${orderId}`;

        notice.innerHTML = `
            <span class="notice-close">×</span>
            <div class="notice-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" fill="#22C55E"/>
                    <path d="M8 12L11 15L16 9" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="notice-content">
                <strong class="notice-title">New Order Placed!</strong>
                <p class="notice-message">Congratulations! You've received Order #<a href="${orderViewUrl}">${orderId}</a></p>
            </div>
        `;
    
        // Append and trigger animation
        noticeList.appendChild(notice);
        requestAnimationFrame(() => notice.classList.add('show'));
        
        
        if (soundDuration > 0) {
            // Play immediately first time
            playSound();
    
            // Then repeat after interval
            soundInterval = setInterval(() => {
                playSound();
            }, soundDuration);
        } else {
            // If no duration, just play once
            playSound();
        }
        
        // Add label to the new order
        orderLabeler.addLabelToOrder(orderId);
    
        // Handle close manually or automatically
        const removeNotice = () => {
            notice.classList.remove('show');
            setTimeout(() => notice.remove(), 300); // match CSS transition
            clearInterval(soundInterval);
        };
    
        notice.querySelector('.notice-close')?.addEventListener('click', removeNotice);
        // setTimeout(removeNotice, 5000);
    }
    
    /**
     * Plays the notification sound for new orders.
     * Uses the sound URL provided in the localized settings.
     * If sound notifications are disabled, this function does nothing.
     */
    function playSound() {
        if (!sound_notify) return;

        var audio       = new Audio(sound_url);
        audio.muted     = false;
        audio.autoplay  = true;   
        var played      = audio.play();

        if (played) {
            played.catch((e) => {
                if (e.name === 'NotAllowedError' ||
                    e.name === 'NotSupportedError') {
                    console.log(e.name);
                }
            });
        }
    }

    /**
     * Check if currently viewing the WooCommerce orders page
     * @returns {boolean} True if on the orders page
     */
    function isOnOrdersPage() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('page') === orders_page_value;
    }

    /**
     * Reload the orders table with latest data from the server
     * @param {number|string} orderId - The new order ID that was created
     */
    function updateOrderTable(orderId) {
        const $tableBody = $(order_table_selector);

        $.get(order_page_url, function(html) {
            const $newTableBody = $(html).find(order_table_selector);

            if ($newTableBody.length) {
                $tableBody.html($newTableBody.html());
                orderLabeler.addRecentLabels();
            }
        });
    }

    /**
     * Handle new order notification
     * Updates table if on orders page, shows notification on all pages
     * @param {number|string} orderId - The new order ID
     */
    function handleNewOrder(orderId) {
        if (isOnOrdersPage()) {
            updateOrderTable(orderId);
        }

        showNotice(orderId);
        setLastOrderId(orderId);
    }

    /**
     * Periodically checks the server for new orders via AJAX.
     * If a new order is detected, displays a notification and plays a sound.
     * Uses the 'wpcafe_check_new_order' AJAX action.
     */
    function checkForNewOrders(e, data) {
        data.wpc_pro_heart = 'live_notify';
        data.new_order_id  = last_order_id;
    }

    /**
     * Handles heartbeat notification for new orders.
     * Triggered by WordPress Heartbeat API when server detects new orders.
     *
     * @param {Event} e - The event object from heartbeat-tick.
     * @param {Object} data - The data object containing new_order_id from the server.
     */
    function notify(e, data) {
        if ( ! data.new_order_id ) return;
        if ( data.new_order_id === getlastOrderId() ) return;

        handleNewOrder(data.new_order_id);
    }

    /**
     * Handles the heartbeat-tick event to check for new orders.
     * 
     * This function updates the data object with the current last order ID and a heartbeat flag,
     * allowing the server to determine if a new order notification should be sent.
     *
     * @param {Event} e - The event object triggered by the heartbeat.
     * @param {Object} data - The data object to be sent with the heartbeat request.
     */
    function setLastOrderId(orderId) {
        localStorage.setItem(local, orderId);
    }

    /**
     * Retrieves the last order ID from localStorage.
     *
     * @returns {string|null} The last order ID stored in localStorage, or null if not set.
     */
    function getlastOrderId() {
        return parseInt(localStorage.getItem(local));
    }

    /**
     * Initialize order labeling on page load
     */
    function initializeOrderLabeling() {
        // Add labels to existing orders
        orderLabeler.addRecentLabels();
        
        // Add labels when table is updated
        $(document).on('DOMNodeInserted', function() {
            setTimeout(orderLabeler.addRecentLabels, 1000);
        });
    }

    /**
     * Starts independent polling (checking for new orders) 
     * incase heartbeat api doesnt work or is too slow
     */
    function startPolling() {
        if (pollInterval) return; // Already polling

        pollInterval = setInterval(function() {
            checkLatestOrder();
        }, pollInterval_ms);
    }

    /**
     * Check for latest order via AJAX
     * This independent polling method works regardless of tab focus
     */
    function checkLatestOrder() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpc_check_latest_order',
                last_order_id: getlastOrderId(),
                nonce: nonce
            },
            success: function(response) {
                if (response.success && response.data.new_order_id && response.data.new_order_id !== getlastOrderId()) {
                    handleNewOrder(response.data.new_order_id);
                }
            }
        });
    }

    /**
     * Initializes the periodic polling for new orders.
     */
    $(document).on('heartbeat-send', checkForNewOrders);
    $(document).on('heartbeat-tick', notify);
    startPolling();
    // Initialize order labeling when document is ready
    initializeOrderLabeling();
});