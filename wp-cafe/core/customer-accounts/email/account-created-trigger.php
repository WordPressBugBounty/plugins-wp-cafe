<?php
namespace WpCafe\CustomerAccounts\Email;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Email_Automation\Triggers\Abstract_Trigger;

/**
 * "Account created": a booking made the customer a WordPress account.
 *
 * @package WpCafe/CustomerAccounts/Email
 */
class Account_Created_Trigger extends Abstract_Trigger {
	const VALUE = 'account_created';

	/**
	 * @return string
	 */
	public function get_trigger_label() {
		return __( 'Account Created', 'wp-cafe' );
	}

	/**
	 * @return string
	 */
	public function get_trigger_value() {
		return self::VALUE;
	}

	/**
	 * Placeholders the admin can use. Account_Email fills every one.
	 *
	 * @return array
	 */
	public function get_trigger_data() {
		return array(
			array(
				'label' => __( 'Customer Name', 'wp-cafe' ),
				'value' => 'account_name',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Customer Email', 'wp-cafe' ),
				'value' => 'account_email',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Username', 'wp-cafe' ),
				'value' => 'account_username',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Set Password Link', 'wp-cafe' ),
				'value' => 'account_set_password_url',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Sign In Link', 'wp-cafe' ),
				'value' => 'account_login_url',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Site Name', 'wp-cafe' ),
				'value' => 'site_name',
				'type'  => 'string',
			),
		);
	}

	/**
	 * @return array
	 */
	public function get_delay_dependencies() {
		return array();
	}

	/**
	 * Customer only: the email carries their set-password link.
	 *
	 * @return array
	 */
	public function get_email_receivers() {
		return array(
			array(
				'label' => __( 'Customer Email', 'wp-cafe' ),
				'value' => 'customer_email',
			),
		);
	}

	/**
	 * No WhatsApp receivers: the account has no number, and the admin's must not get the link.
	 *
	 * @return array
	 */
	public function get_whatsapp_receivers() {
		return array();
	}
}
