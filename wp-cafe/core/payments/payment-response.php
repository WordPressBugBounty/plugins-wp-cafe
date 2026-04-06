<?php
namespace WpCafe\Payments;

class Payment_Response {
    /**
     * The status of the payment response.
     *
     * @var string success | failed | pending
     */
    public string $status;

    /**
     * The redirect URL of the payment response.
     *
     * @var string redirect URL (if needed)
     */
    public string $redirect;

    /**
     * The message of the payment response.
     *
     * @var string human readable
     */
    public string $message;

    /**
     * Constructor for Payment_Response class
     *
     * @param string $status The status of the payment response. success | failed | pending
     * @param string $redirect The redirect URL of the payment response. redirect URL (if needed)
     * @param string $message The message of the payment response. human readable   
     */
    public function __construct( string $status, string $redirect = '', string $message = '' ) {
        $this->status   = $status;
        $this->redirect = $redirect;
        $this->message  = $message;
    }
}

