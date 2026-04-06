<?php
namespace WpCafe\Core\Blocks\BlockTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Location Block
 */
class Location extends AbstractBlock {
	/**
	 * Block name within this namespace
	 *
	 * @var string
	 */
	protected $block_name = 'location';

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
		// Check if the location module is active.
		if ( ! wpc_is_module_enable( 'location' ) ) {
			return '';
		}

		if ( is_checkout() ) {
			wp_enqueue_script( 'frontend-js-block-location' );
		}

		return do_shortcode( '[wpc_location_checkout]' );
	}
}
