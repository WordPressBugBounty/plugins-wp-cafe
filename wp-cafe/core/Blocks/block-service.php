<?php
namespace WpCafe\Core\Blocks;

defined( 'ABSPATH' ) || exit;

use WpCafe\Core\Blocks\BlockTypes\FoodList;
use WpCafe\Core\Blocks\BlockTypes\FoodTab;
use WpCafe\Core\Blocks\BlockTypes\PickupDelivery;
use WpCafe\Core\Blocks\BlockTypes\Location;

/**
 * Block Service Class
 * Manages block discovery via filter hook
 */
class BlockService {
	/**
	 * Register service (called by service provider)
	 *
	 * @return void
	 */
	public function register() {
		$this->register_hooks();
	}

	/**
	 * Register hooks
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'wpc_gutenberg_blocks', [ $this, 'add_blocks' ], 5 );
	}

	/**
	 * Add blocks to the block registry
	 *
	 * @param array $blocks Current blocks array.
	 * @return array
	 */
	public function add_blocks( $blocks ) {
		$new_blocks = [
			FoodList::class,
			FoodTab::class,
			PickupDelivery::class,
			Location::class,
		];

		return array_unique( array_merge( $blocks, $new_blocks ) );
	}
}
