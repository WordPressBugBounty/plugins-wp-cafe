<?php
namespace WpCafe\Core\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Block Provider
 * Service provider for dependency injection
 */
class BlockProvider {
	/**
	 * Store services
	 *
	 * @var array
	 */
	protected $services = [
		BlockTypesController::class,
		BlockService::class,
	];

	/**
	 * Register all services
	 *
	 * @return void
	 */
	public function register() {
		foreach ( $this->services as $service ) {
			$this->register_service( $service );
		}
	}

	/**
	 * Register a single service
	 *
	 * @param string $service Service class name.
	 * @return void
	 */
	private function register_service( $service ) {
		$instance = new $service();

		if ( method_exists( $instance, 'register_hooks' ) ) {
			$instance->register_hooks();
		}
	}
}
