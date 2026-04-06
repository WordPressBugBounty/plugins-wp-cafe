<?php
namespace WpCafe\Core\Blocks\BlockTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Pickup Delivery Block
 */
class PickupDelivery extends AbstractBlock {
	/**
	 * Block name within this namespace
	 *
	 * @var string
	 */
	protected $block_name = 'pickup-delivery';

	/**
	 * Get block attributes
	 *
	 * @return array
	 */
	protected function get_block_type_attributes() {
		return [];
	}

	/**
	 * Render the block
	 *
	 * @param array      $attributes Block attributes.
	 * @param string     $content    Block content.
	 * @param \WP_Block  $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		// Check if the pickup/delivery module is active.
		$allowed_options = [
			'Both',
			'Delivery',
			'Pickup',
		];

		$settings = get_option( 'wpcafe_reservation_settings_options', [] );

		if ( ! in_array( $settings['wpc_pro_allow_order_for'] ?? '', $allowed_options, true ) ) {
			return '';
		}

		if ( is_checkout() ) {
			wp_enqueue_script( 'frontend-js-block-pickup' );
		}

		return do_shortcode( '[wpc_pickup_delivery_checkout]' );
	}
}
