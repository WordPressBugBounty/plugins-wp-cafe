(function($){
    const $modal       = $('#wpc-location-selector-modal');
    const $openBtn     = $('.wpc-location__address-button');
    const $closeBtn    = $modal.find('.wpc-modal-close');
    const $saveBtn     = $('#wpc-saveLocation');
    const $select      = $('#wpc-locationSelect');
    const $hiddenInput = $('#selected_location');
    const $label       = $('#wpc-locationLabel');
    const $loader      = $('.wpc-btn-loader');

    const selectedLocation = wpcLocation.selectedLocation;
    const cartUrl          = wpcLocation.wc_cart_url;


    // Modal Events
    $closeBtn.on('click', closeModal);
    $saveBtn.on('click', saveLocation);
    $(document).on('click', '.wpc-location__address-button', open_location_popup);

    // Open location modal if not selected.
    autoOpenModal();

    /**
     * Automatically opens the location selector modal if a location has not been selected
     * and the modal is enabled for the current page based on plugin settings.
     *
     * The modal will not open if:
     *   - A location is already selected.
     *   - The location selector is set to "dont_show".
     *   - The location selector is set to "specific_pages" and the current page is not in the allowed list.
     *
     * @return {void}
     */
    function autoOpenModal() {
        if (selectedLocation) {
            return;
        }

        if ('dont_show' === wpcLocation.location_selector) {
            return;
        }
 
        
        openModal();
    }

    /**
     * Close modal
     *
     * @return  void
     */
    function closeModal() {
        $modal.css('display', 'none');
        localStorage.setItem('wpc_close_location_modal', true);
    }

    /**
     * Check if the location modal has been closed by the user.
     *
     * @return {string|null} Returns the value of 'wpc_close_location_modal' from localStorage, or null if not set.
     */
    function isCloseModal() {
        return localStorage.getItem('wpc_close_location_modal');
    }

    /**
     * Open location popup
     *
     * @param   Object  e  [e description]
     *
     * @return  void     [return description]
     */
    function open_location_popup(e) {
        e.preventDefault();
        openModal();
    }

    /**
     * Save location
     *
     * @param   Object  e  Event Object
     *
     * @return  void
     */
    function saveLocation(e) {
        e.preventDefault();

        const locationId = $('#wpc-locationSelect').val();

        if (!locationId) return;

        if ( selectedLocation == locationId ) {
            closeModal();
            return;
        }

        if (!wpcLocation.wc_cart_empty) {
            const confirmed = window.confirm('If you select different location your current cart items will be empty');

            if (!confirmed) return; // If Cancel is pressed, stop execution
        }

        

        $loader.css('display', 'flex');
        $.post({
            url: wpcLocation.ajaxUrl,
            data: {
                action: 'save_location',
                location_id: locationId,
                nonce: wpcLocation.nonce
            },
            success(response) {

                window.location.reload();

                if (response.success) {
                    $loader.css('display', 'none');
                    $modal.css('display', 'none');

                } else {
                    alert('Error saving location');
                }
            }
        });
    }

    /**
     * Open modal
     *
     * @return  void
     */
    function openModal() {
        $modal.css('display', 'flex');
    }

})(jQuery);
