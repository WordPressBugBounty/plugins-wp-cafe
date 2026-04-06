<?php
namespace WpCafe\Assets;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Manage feedback modal scripts and styles
 */
class Feedback_Assets extends Base_Assets {
	/**
	 * Register single service
	 *
	 * @return  void
	 */
	public function register() {
		add_action( 'admin_enqueue_scripts', [ $this, 'register_styles_scripts' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @return  void
	 */
	public function enqueue( $handle ) {
		if ( 'plugins.php' !== $handle ) {
			return;
		}

		wp_enqueue_script( 'wpcafe-feedback-modal' );
		wp_enqueue_style( 'wpcafe-feedback-modal' );

		$feedback_obj = [
			'site_url'    => site_url(),
			'admin_email' => get_option( 'admin_email' ),
		];

		wp_localize_script( 'wpcafe-feedback-modal', 'wpcafe_feedback', $feedback_obj );
	}

	/**
	 * Get all scripts
	 *
	 * @return  array List register scripts
	 */
	public function get_scripts() {
		$scripts = [
			'wpcafe-feedback-modal' => [
				'src'       => wpcafe()->assets_url . '/build/js/feedback-modal.js',
				'deps'      => [ 'wp-plugins', 'wp-i18n', 'wp-element', 'wp-dom', 'wp-data' ],
				'in_footer' => true,
			],
		];

		return apply_filters( 'wpcafe_feedback_scripts', $scripts );
	}

	/**
	 * List of register styles
	 *
	 * @return  array
	 */
	public function get_styles() {
		$styles = [
			'wpcafe-feedback-modal' => [
				'src' => wpcafe()->assets_url . '/build/css/feedback-modal.css',
			],
		];

		return apply_filters( 'wpcafe_feedback_styles', $styles );
	}
}
