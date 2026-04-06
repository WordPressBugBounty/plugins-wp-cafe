<?php
namespace WpCafe\GutenBlock;

defined( 'ABSPATH' ) || exit;

use WpCafe\Providers\Base_Service_Provider;
use WpCafe\Core\Blocks\BlockTypesController;
use WpCafe\Core\Blocks\BlockService;

/**
 * Guten Block Service Provider
 *
 * @package WpCafe/GutenBlock
 */
class Guten_Block_Service_Provider extends Base_Service_Provider {
	/**
	 * Get services
	 *
	 * @return array service lists
	 */
	public function get_services() {
		return [
			BlockTypesController::class,
			BlockService::class,
		];
	}
}
