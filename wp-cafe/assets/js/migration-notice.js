jQuery(document).ready(function($) {
    var $notice = $('.wpcafe-migration-notice');

    if ($notice.length === 0) {
        return;
    }

    // Get data from notice element
    var restUrl = $notice.data('rest-url');
    var nonce = $notice.data('nonce');
    var successMessage = $notice.data('success-message');
    var errorMessage = $notice.data('error-message');

    $('#wpcafe-run-migration').on('click', function(e) {
        e.preventDefault();

        var $button = $(this);
        var $spinner = $button.next('.spinner');
        var $message = $('.wpcafe-migration-message');

        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        $message.text('');

        $.ajax({
            url: restUrl,
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
            },
            success: function(response) {
                $spinner.removeClass('is-active');
                $message.css('color', 'green').text(response.message || successMessage);

                setTimeout(function() {
                    $notice.fadeOut();
                }, 2000);
            },
            error: function(xhr) {
                $spinner.removeClass('is-active');
                $button.prop('disabled', false);

                var error = errorMessage;
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    error = xhr.responseJSON.message;
                }

                $message.css('color', 'red').text(error);
            }
        });
    });

    $(document).on('click', '.wpcafe-migration-notice .notice-dismiss', function() {
        $('.wpcafe-migration-notice').fadeOut();
    });
});
