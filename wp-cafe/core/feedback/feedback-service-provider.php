<?php
namespace WpCafe\Feedback;

use WpCafe\Providers\Base_Service_Provider;

/**
 * Feedback Service Provider
 *
 * @since 1.0.0
 */
class Feedback_Service_Provider extends Base_Service_Provider {

	/**
	 * Get services
	 *
	 * @return array service lists
	 */
	public function get_services() {
		return apply_filters( 'wpcafe_feedback_services', [
			Feedback_Hooks::class,
		] );
	}
}
