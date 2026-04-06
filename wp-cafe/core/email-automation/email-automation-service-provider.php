<?php
namespace WpCafe\Email_Automation;

use WpCafe\Providers\Base_Service_Provider;
use WpCafe\Email_Automation\Service\Email_Notification;
use WpCafe\Email_Automation\Handlers\Order_Email_Handler;
use WpCafe\Contracts\Switchable_Provider_Contract;

/**
 * Email Automation Service Provider Class
 *
 * Responsible for registering all email automation and notification services.
 *
 * @package WpCafe\Email_Automation
 */
class Email_Automation_Service_Provider extends Base_Service_Provider implements Switchable_Provider_Contract {

    /**
     * Store services.
     *
     * @var array
     */
    protected $services = array(
        Email_Notification::class,
        Order_Email_Handler::class,
        Email_Automation_Dummy_Data_Manager::class,
    );

    /**
     * Get registered services.
     *
     * @return array Array of service classes.
     */
    public function get_services() {
        return apply_filters( 'wpcafe_email_automation_services', $this->services );
    }

    /**
     * Check if the service provider is enabled.
     *
     * @return bool True if enabled, false otherwise.
     */
    public function is_enable() {
        return true;
    }
}
