<?php
namespace WpCafe\Feedback;

use WpCafe\Contracts\Hookable_Service_Contract;

/**
 * Feedback Hooks Service
 *
 * Handles UninstallerForm feedback integration
 *
 * @since 1.0.0
 */
class Feedback_Hooks implements Hookable_Service_Contract {

	/**
	 * Register Services
	 *
	 * @return void
	 */
	public function register() {
		$this->init_uninstaller_form();
	}

	/**
	 * Initialize UninstallerForm feedback integration
	 *
	 * @return void
	 */
	private function init_uninstaller_form() {
		if ( ! class_exists( '\UninstallerForm\UninstallerForm' ) ) {
			return;
		}

		if ( ! is_callable( [ '\UninstallerForm\UninstallerForm', 'init' ] ) ) {
			return;
		}

		// Check the number of parameters for the init method using reflection
		try {
			$reflection = new \ReflectionMethod( '\UninstallerForm\UninstallerForm', 'init' );
			$total_params = $reflection->getNumberOfParameters();

			if ( 6 === $total_params ) {
				\UninstallerForm\UninstallerForm::init(
					'WP Cafe',                                      // Plugin name
					'wp-cafe',                                      // Plugin Slug
					WPCAFE_FILE,                                    // __FILE__
					'wp-cafe',                                      // Text Domain Name
					'wpcafe-feedback-modal',                        // plugins-admin-script-handler
					'https://themewinter.com/?fluentcrm=1&route=contact&hash=eb90d4fe-dcda-4457-b8b4-86f121d2cb16'  // Feedback URL
				);
			} else {
				\UninstallerForm\UninstallerForm::init(
					'WP Cafe',                                      // Plugin name
					'wp-cafe',                                      // Plugin Slug
					WPCAFE_FILE,                                    // __FILE__
					'wp-cafe',                                      // Text Domain Name
					'wpcafe-feedback-modal'                         // plugins-admin-script-handler
				);
			}
		} catch ( \ReflectionException $e ) {
			\UninstallerForm\UninstallerForm::init(
				'WP Cafe',
				'wp-cafe',
				WPCAFE_FILE,
				'wp-cafe',
				'wpcafe-feedback-modal'
			);
		}
	}
}
