<?php
namespace WpCafe\CustomerAccounts;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\CustomerAccounts\Email\Account_Email;
use WpCafe\CustomerAccounts\Email\Account_Flow_Seeder;
use WpCafe\Providers\Base_Service_Provider;

/**
 * WordPress accounts for booking customers (Settings → Reservation → Customer Accounts).
 *
 * @package WpCafe/CustomerAccounts
 */
class Customer_Accounts_Service_Provider extends Base_Service_Provider {
    /**
     * @var array
     */
    protected $services = [
        Booking_Account::class,
        Account_Email::class,
        Account_Flow_Seeder::class,
    ];

    /**
     * @return array
     */
    public function get_services() {
        return apply_filters( 'wpcafe_customer_accounts_services', $this->services );
    }
}
