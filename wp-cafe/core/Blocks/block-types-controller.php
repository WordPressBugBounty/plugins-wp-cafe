<?php
namespace WpCafe\Core\Blocks;

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- plugin-wpc-prefix, public backward-compat hooks, or third-party (Elementor) hook names.

defined( 'ABSPATH' ) || exit;

/**
 * Block Types Controller
 * Handles block registration lifecycle
 */
class BlockTypesController {
	/**
	 * Store Block Types
	 *
	 * @var array
	 */
	private $blocks = [];

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
		add_action( 'init', [ $this, 'register_block_assets' ], 21 );
		add_action( 'init', [ $this, 'register_blocks' ], 22 );
	}

	/**
	 * Register block category and global assets
	 *
	 * @return void
	 */
	public function register_block_assets() {
		global $wp_version;

		$filter_hook = 'block_categories';
		if ( version_compare( $wp_version, '5.8' ) >= 0 ) {
			$filter_hook = 'block_categories_all';
		}
		add_filter( $filter_hook, [ $this, 'register_block_category' ], 10, 2 );

		$editor_style_url = wpcafe()->assets_url . '/build/css/gutenberg-blocks.css';
		$editor_script_url = wpcafe()->assets_url . '/build/js/gutenberg-blocks.js';

		wp_register_style( 'wpc-block-editor-style-css', $editor_style_url, [ 'wp-edit-blocks' ], wpcafe()->version );
		wp_register_script( 'wpc-block-js', $editor_script_url, [ 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-compose', 'wp-server-side-render' ], wpcafe()->version, true );

		// Register frontend styles
		wp_register_style( 'wpc-block-style-css', wpcafe()->assets_url . '/build/css/gutenberg-blocks.css', [], wpcafe()->version );
	}

	/**
	 * Register block category
	 *
	 * @param array   $categories Block categories.
	 * @param WP_Post $post       Post object.
	 * @return array
	 */
	public function register_block_category( $categories, $post ) {
		return array_merge(
			$categories,
			[
				[
					'slug'  => 'wp-cafe-blocks',
					'title' => esc_html__( 'WPCafe', 'wp-cafe' ),
				],
			]
		);
	}

	/**
	 * Register blocks
	 *
	 * @return void
	 */
	public function register_blocks() {
		// Check if WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$block_types = $this->get_block_types();

		foreach ( $block_types as $block_type ) {
			new $block_type();
		}
	}

	/**
	 * Get all registered blocks
	 *
	 * @return array
	 */
	private function get_block_types() {
		return apply_filters( 'wpc_gutenberg_blocks', $this->blocks );
	}
}
