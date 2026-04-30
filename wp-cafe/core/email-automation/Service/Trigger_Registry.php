<?php

namespace WpCafe\Email_Automation\Service;

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- plugin-wpc-prefix, public backward-compat hooks, or third-party (Elementor) hook names.

use WpCafe\Email_Automation\Triggers\Abstract_Trigger;
use WpCafe\Email_Automation\Triggers\Order_Created_Trigger;
use WpCafe\Email_Automation\Triggers\Order_Status_Changed_Trigger;
use WpCafe\Email_Automation\Triggers\Order_Cancelled_Trigger;

/**
 * Trigger Registry Service
 *
 * Manages and provides access to all available email triggers.
 * Handles registration, retrieval, and configuration of email notification triggers.
 */
class Trigger_Registry {

	/**
	 * Registered triggers
	 *
	 * @var Abstract_Trigger[]
	 */
	private $triggers = array();

	/**
	 * Constructor
	 *
	 * Initializes the registry with default triggers
	 */
	public function __construct() {
		$this->register_default_triggers();
	}

	/**
	 * Registers all built-in email triggers based on module availability.
	 * Uses a filter to allow modules to conditionally register their triggers.
	 *
	 * @return void
	 */
	private function register_default_triggers() {
		$available_triggers = [];
		$available_triggers = apply_filters( 'wpc_available_email_triggers', $available_triggers );

		foreach ( $available_triggers as $trigger_class ) {
			if ( $this->is_trigger_class_available( $trigger_class ) ) {
				$this->register( new $trigger_class() );
			}
		}
	}
    
	/**
	 * Check if a trigger class is available
	 * 
	 * @param string $class_name The trigger class name
	 * @return bool True if the class is available, false otherwise
	 */
	private function is_trigger_class_available( $class_name ) {
		if ( class_exists( $class_name, false ) ) {
			return true;
		}

		try {
			return class_exists( $class_name, true );
		} catch ( \Throwable $e ) {
			// Tigger class could not be loaded, file may be missing.
			return false;
		}
	}

	/**
	 * Register a trigger
	 *
	 * @param Abstract_Trigger $trigger The trigger to register
	 * @return void
	 */
	public function register( Abstract_Trigger $trigger ) {
		$trigger_value                     = $trigger->get_trigger_value();
		$this->triggers[ $trigger_value ] = $trigger;
	}

	/**
	 * Get all trigger configurations
	 *
	 * Returns an array of all trigger configurations ready for the SDK
	 *
	 * @return array
	 */
	public function get_all_configurations() {
		$configurations = array();
		foreach ( $this->triggers as $trigger ) {
			$configurations[] = $trigger->build_configuration();
		}
		return $configurations;
	}
}
