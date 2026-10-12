<?php
namespace WpCafe\CustomerAccounts\Email;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;

/**
 * Sends the "Account created" automation with a link to set a password.
 *
 * @package WpCafe/CustomerAccounts/Email
 */
class Account_Email implements Hookable_Service_Contract {
	/**
	 * @return void
	 */
	public function register() {
		add_filter( 'wpc_available_email_triggers', [ $this, 'add_trigger' ] );
		add_action( 'wpcafe_customer_account_created', [ $this, 'send' ] );
	}

	/**
	 * @param array $triggers Trigger class names.
	 * @return array
	 */
	public function add_trigger( $triggers ) {
		$triggers[] = Account_Created_Trigger::class;

		return $triggers;
	}

	/**
	 * @param int $user_id New account.
	 * @return void
	 */
	public function send( $user_id ) {
		$user = get_userdata( (int) $user_id );

		if ( ! $user ) {
			return;
		}

		$key = get_password_reset_key( $user );

		$data = array(
			'admin_email'              => get_option( 'admin_email' ),
			'customer_email'           => $user->user_email,
			'customer_number'          => '',
			'admin_number'             => wpc_get_option( 'whatsapp_admin_number', '' ),
			'account_name'             => '' !== $user->first_name ? $user->first_name : $user->display_name,
			'account_email'            => $user->user_email,
			'account_username'         => $user->user_login,
			// A failed key still leaves "lost password" on the sign-in page.
			'account_set_password_url' => is_wp_error( $key )
				? wp_lostpassword_url()
				: network_site_url( 'wp-login.php?action=rp&key=' . $key . '&login=' . rawurlencode( $user->user_login ), 'login' ),
			'account_login_url'        => wp_login_url(),
			'site_name'                => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
		);

		do_action( 'wpcafe_gln_hook', Account_Created_Trigger::VALUE, apply_filters( 'wpcafe_account_created_notification_data', $data, $user ) );
	}
}
