<?php
namespace WpCafe\Core\Blocks\BlockTypes;

defined( 'ABSPATH' ) || exit;

/**
 * AbstractBlock class
 * Provides common functionality for all block types
 */
abstract class AbstractBlock {
	/**
	 * Block Namespace
	 *
	 * @var string
	 */
	protected $namespace = 'wpc';

	/**
	 * Block name within this namespace
	 *
	 * @var string
	 */
	protected $block_name = '';

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->initialize();
	}

	/**
	 * Initialize this block type
	 * - Hook into WP lifecycle
	 * - Register the block with WordPress
	 */
	protected function initialize() {
		$this->register_block_type_assets();
		$this->register_block_type();
	}

	/**
	 * Get block name with namespace
	 *
	 * @return string
	 */
	protected function get_full_block_name() {
		return $this->namespace . '/' . $this->block_name;
	}

	/**
	 * Registers the block type with WordPress
	 *
	 * @return void
	 */
	protected function register_block_type() {
		// Skip if block is already registered
		if ( \WP_Block_Type_Registry::get_instance()->is_registered( $this->get_block_type() ) ) {
			return;
		}

		$block_settings = [
			'render_callback'   => $this->get_block_type_render_callback(),
			'editor_script'     => $this->get_block_type_editor_script( 'handle' ),
			'editor_style'      => $this->get_block_type_editor_style(),
			'style'             => $this->get_block_type_style(),
			'attributes'        => $this->get_block_type_attributes(),
		];

		$metadata_path = $this->get_metadata_path();

		if ( $metadata_path ) {
			register_block_type( $metadata_path, $block_settings );
			return;
		}

		register_block_type( $this->get_block_type(), $block_settings );
	}

	/**
	 * Register script and style assets for the block type before it is registered.
	 *
	 * This registers the scripts; it does not enqueue them.
	 */
	protected function register_block_type_assets() {
		// Register editor scripts.
		if ( null !== $this->get_block_type_editor_script() ) {
			$handle       = $this->get_block_type_editor_script( 'handle' );
			$dependencies = $this->get_block_type_editor_script( 'dependencies' );
			$path         = $this->get_block_type_editor_script( 'path' );

			$this->register_script( $handle, $path, $dependencies );
		}

		// Register frontend scripts.
		if ( null !== $this->get_block_type_script() ) {
			$handle       = $this->get_block_type_script( 'handle' );
			$dependencies = $this->get_block_type_script( 'dependencies' );
			$path         = $this->get_block_type_script( 'path' );

			$this->register_script( $handle, $path, $dependencies );
		}

		// Register editor styles.
		if ( null !== $this->get_block_type_editor_style() ) {
			$handle = $this->get_block_type_editor_style();
			$path   = $this->get_block_editor_style_path();

			if ( $path ) {
				$this->register_style( $handle, $path );
			}
		}

		// Register frontend styles.
		if ( null !== $this->get_block_type_style() ) {
			$handle = is_array( $this->get_block_type_style() ) ? $this->get_block_type_style()[0] : $this->get_block_type_style();
			$path   = $this->get_block_style_path();

			if ( $path ) {
				$this->register_style( $handle, $path );
			}
		}
	}

	/**
	 * Register script
	 *
	 * @param string $handle Handle name.
	 * @param string $path   File path.
	 * @param array  $dependencies Script dependencies.
	 * @param string $version Version string.
	 * @return void
	 */
	protected function register_script( $handle, $path, $dependencies = [], $version = null ) {
		if ( ! $version ) {
			$version = wpcafe()->version;
		}
		wp_register_script( $handle, $path, $dependencies, $version, true );
	}

	/**
	 * Register style
	 *
	 * @param string $handle Handle name.
	 * @param string $path   File path.
	 * @param array  $dependencies Style dependencies.
	 * @param string $media   Media type.
	 * @return void
	 */
	protected function register_style( $handle, $path, $dependencies = [], $media = 'all' ) {
		wp_register_style( $handle, $path, $dependencies, wpcafe()->version, $media );
	}

	/**
	 * Get block type
	 *
	 * @return string
	 */
	protected function get_block_type() {
		return $this->namespace . '/' . $this->block_name;
	}

	/**
	 * Get the render callback for this blocktype
	 *
	 * @return callable|null
	 */
	protected function get_block_type_render_callback() {
		return [ $this, 'render_callback' ];
	}

	/**
	 * Get the editor script data for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 * @return array|string|null
	 */
	protected function get_block_type_editor_script( $key = null ) {
		$script = [
			'handle'       => 'wpc-' . $this->block_name . '-block',
			'path'         => wpcafe()->assets_url . '/build/js/gutenberg-blocks.js',
			'dependencies' => [ 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-compose', 'wp-server-side-render' ],
		];
		return $key ? ( $script[ $key ] ?? null ) : $script;
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 * @return array|string|null
	 */
	protected function get_block_type_script( $key = null ) {
		return $key ;
	}

	/**
	 * Get the editor style handle for this block type.
	 *
	 * @return string|null
	 */
	protected function get_block_type_editor_style() {
		return 'wpc-block-editor-style-css';
	}

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return array|string|null
	 */
	protected function get_block_type_style() {
		return 'wpc-block-style-css';
	}

	/**
	 * Get block attributes
	 *
	 * @return array
	 */
	protected function get_block_type_attributes() {
		return [];
	}

	/**
	 * Get block metadata path
	 *
	 * @return string|false
	 */
	protected function get_metadata_path() {
		return false;
	}

	/**
	 * Get editor style path
	 *
	 * @return string|null
	 */
	protected function get_block_editor_style_path() {
		return wpcafe()->assets_url . '/build/css/gutenberg-blocks.css';
	}

	/**
	 * Get frontend style path
	 *
	 * @return string|null
	 */
	protected function get_block_style_path() {
		return wpcafe()->assets_url . '/build/css/gutenberg-blocks.css';
	}

	/**
	 * Render callback. This will ensure assets are enqueued just in time
	 *
	 * @param array         $attributes Block attributes.
	 * @param string        $content    Block content.
	 * @param \WP_Block|null $block     Block instance.
	 * @return string Rendered block type output
	 */
	public function render_callback( $attributes = [], $content = '', $block = null ) {
		$render_callback_attributes = $this->parse_render_callback_attributes( $attributes );
		return $this->render( $render_callback_attributes, $content, $block );
	}

	/**
	 * Parses block attributes from the render_callback.
	 *
	 * @param array|\WP_Block $attributes Block attributes, or an instance of a WP_Block.
	 * @return array
	 */
	protected function parse_render_callback_attributes( $attributes ) {
		return is_a( $attributes, 'WP_Block' ) ? $attributes->attributes : $attributes;
	}

	/**
	 * Render the block. Extended by children.
	 *
	 * @param array      $attributes Block attributes.
	 * @param string     $content    Block content.
	 * @param \WP_Block  $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		return $content;
	}

	/**
	 * Register/enqueue scripts used for this block on the frontend, during render.
	 *
	 * @param array $attributes Any attributes that currently are available from the block.
	 * @return void
	 */
	protected function enqueue_scripts( $attributes = [] ) {
		if ( null !== $this->get_block_type_script() ) {
			wp_enqueue_script( $this->get_block_type_script( 'handle' ) );
		}
	}

	/**
	 * Register/enqueue styles used for this block on the frontend, during render.
	 *
	 * @param array $attributes Any attributes that currently are available from the block.
	 * @return void
	 */
	protected function enqueue_styles( $attributes = [] ) {
		if ( null !== $this->get_block_type_style() ) {
			$style_handle = is_array( $this->get_block_type_style() ) ? $this->get_block_type_style()[0] : $this->get_block_type_style();
			wp_enqueue_style( $style_handle );
		}
	}
}
