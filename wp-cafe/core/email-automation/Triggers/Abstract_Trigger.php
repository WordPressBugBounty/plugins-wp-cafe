<?php

namespace WpCafe\Email_Automation\Triggers;

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- plugin-wpc-prefix, public backward-compat hooks, or third-party (Elementor) hook names.

/**
 * Abstract Trigger Base Class
 *
 * Provides a common interface and shared functionality for all email triggers.
 * Subclasses should implement the configuration methods.
 */
abstract class Abstract_Trigger {

	/**
	 * Get trigger label
	 *
	 * @return string
	 */
	abstract public function get_trigger_label();

	/**
	 * Get trigger value (unique identifier)
	 *
	 * @return string
	 */
	abstract public function get_trigger_value();

	/**
	 * Get trigger data fields
	 *
	 * @return array
	 */
	abstract public function get_trigger_data();

	/**
	 * Get delay dependency fields
	 *
	 * @return array
	 */
	abstract public function get_delay_dependencies();

	/**
	 * Get email receiver options
	 *
	 * @return array
	 */
	abstract public function get_email_receivers();

	/**
	 * Build complete trigger configuration
	 *
	 * Combines all trigger components into the final configuration array
	 * expected by the email automation SDK
	 *
	 * @return array
	 */
	public function build_configuration() {
		$trigger_data = $this->get_trigger_data();
		
		// Allow custom fields to be added to trigger data for SDK UI
		$trigger_data = apply_filters( 'wpc_trigger_custom_fields', $trigger_data, $this->get_trigger_value() );
		
		return array(
			'trigger_label'      => $this->get_trigger_label(),
			'trigger_value'      => $this->get_trigger_value(),
			'trigger_data'       => $trigger_data,
			'delay_dependencies' => $this->get_delay_dependencies(),
			'email_receivers'    => $this->get_email_receivers(),
		);
	}
}
