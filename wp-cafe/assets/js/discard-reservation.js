(function() {
    'use strict';

    // Get translated messages using WordPress i18n
    var __ = window.wp && window.wp.i18n ? window.wp.i18n.__ : function( text ) { return text; };

    var messages = {
        confirm: __( 'Are you sure you want to discard this reservation?', 'wp-cafe' ),
        success: __( 'Reservation discarded successfully.', 'wp-cafe' ),
        error: __( 'Error discarding reservation.', 'wp-cafe' ),
        cancel: __( 'Cancel', 'wp-cafe' ),
        discard: __( 'Discard', 'wp-cafe' )
    };

    /**
     * Show confirmation modal dialog
     * @returns {Promise} Promise that resolves to true if confirmed, false otherwise
     */
    function showConfirmationDialog() {
        return new Promise(function( resolve ) {
            // Create modal overlay
            var overlay = document.createElement('div');
            overlay.className = 'wpc-discard-modal-overlay';

            // Create modal content
            var modal = document.createElement('div');
            modal.className = 'wpc-discard-modal';

            // Modal title
            var title = document.createElement('h3');
            title.className = 'wpc-discard-modal-title';
            title.textContent = messages.confirm;

            // Button container
            var buttonContainer = document.createElement('div');
            buttonContainer.className = 'wpc-discard-modal-buttons';

            // Cancel button
            var cancelBtn = document.createElement('button');
            cancelBtn.className = 'wpc-discard-modal-cancel';
            cancelBtn.textContent = messages.cancel;
            cancelBtn.addEventListener('click', function() {
                overlay.remove();
                resolve(false);
            });

            // Discard button
            var discardBtn = document.createElement('button');
            discardBtn.className = 'wpc-discard-modal-discard';
            discardBtn.textContent = messages.discard;
            
            // Apply dynamic primary color if available
            if (typeof wpcDiscardReservationColors !== 'undefined' && wpcDiscardReservationColors.primary) {
                discardBtn.style.backgroundColor = wpcDiscardReservationColors.primary;
            }
            
            discardBtn.addEventListener('click', function() {
                overlay.remove();
                resolve(true);
            });

            // Close on overlay click
            overlay.addEventListener('click', function( e ) {
                if (e.target === overlay) {
                    overlay.remove();
                    resolve(false);
                }
            });

            // Append elements
            buttonContainer.appendChild(cancelBtn);
            buttonContainer.appendChild(discardBtn);
            modal.appendChild(title);
            modal.appendChild(buttonContainer);
            overlay.appendChild(modal);
            document.body.appendChild(overlay);
        });
    }

    /**
     * Initialize discard reservation button functionality
     */
    function initDiscardReservationButton() {
        var discardButton = document.getElementById('wpc-discard-reservation');

        if (!discardButton) {
            return;
        }

        discardButton.addEventListener('click', function(e) {
            e.preventDefault();

            showConfirmationDialog().then(function( confirmed ) {
                if (!confirmed) {
                    return;
                }

                var nonce = discardButton.getAttribute('data-nonce');

                var xhr = new XMLHttpRequest();
                xhr.open('POST', typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                xhr.onload = function() {
                    if (xhr.status !== 200) {
                        alert(messages.error);
                        return;
                    }

                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (!response.success) {
                            alert(response.data && response.data.message ? response.data.message : messages.error);
                            return;
                        }

                        location.reload();
                    } catch (e) {
                        alert(messages.error);
                    }
                };

                xhr.onerror = function() {
                    alert(messages.error);
                };

                var data = 'action=wpc_discard_reservation&nonce=' + encodeURIComponent(nonce);
                xhr.send(data);
            });
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDiscardReservationButton);
    } else {
        initDiscardReservationButton();
    }
})();
