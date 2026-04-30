<?php
namespace WpCafe\Core\Blocks\BlockTypes;

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- plugin-wpc-prefix, public backward-compat hooks, or third-party (Elementor) hook names.

defined( 'ABSPATH' ) || exit;

use WpCafe\Utils\Wpc_Utilities;

/**
 * Food List Block
 */
class FoodList extends AbstractBlock {
	/**
	 * Block name within this namespace
	 *
	 * @var string
	 */
	protected $block_name = 'food-menu-list';

	/**
	 * Get block attributes
	 *
	 * @return array
	 */
	protected function get_block_type_attributes() {
		return [
			'food_menu_style'       => [
				'type'    => 'string',
				'default' => 'style-1',
			],
			'show_thumbnail'        => [
				'type'    => 'string',
				'default' => 'yes',
			],
			'wpc_menu_cat'          => [
				'type'    => 'array',
				'default' => [],
			],
			'wpc_desc_limit'        => [
				'type'    => 'integer',
				'default' => 20,
			],
			'wpc_show_desc'         => [
				'type'    => 'string',
				'default' => 'yes',
			],
			'wpc_cart_button_show'  => [
				'type'    => 'string',
				'default' => 'yes',
			],
			'title_link_show'       => [
				'type'    => 'string',
				'default' => 'yes',
			],
			'show_item_status'      => [
				'type'    => 'string',
				'default' => 'yes',
			],
			'wpc_price_show'        => [
				'type'    => 'string',
				'default' => 'yes',
			],
			'wpc_menu_count'        => [
				'type'    => 'integer',
				'default' => 20,
			],
			'wpc_menu_order'        => [
				'type'    => 'string',
				'default' => 'DESC',
			],
			'wpc_show_vendor'       => [
				'type'    => 'string',
				'default' => 'yes',
			],
		];
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
		$unique_id = md5( md5( microtime() ) );

		$style               = $attributes['food_menu_style'] ?? 'style-1';
		$show_item_status    = $attributes['show_item_status'] ?? 'yes';
		$show_thumbnail      = $attributes['show_thumbnail'] ?? 'yes';
		$title_link_show     = $attributes['title_link_show'] ?? 'yes';
		$wpc_cart_button     = $attributes['wpc_cart_button_show'] ?? 'yes';
		$wpc_show_desc       = $attributes['wpc_show_desc'] ?? 'yes';
		$wpc_desc_limit      = $attributes['wpc_desc_limit'] ?? 20;
		$wpc_menu_cat        = $attributes['wpc_menu_cat'] ?? [];
		$wpc_menu_count      = $attributes['wpc_menu_count'] ?? 20;
		$wpc_menu_order      = $attributes['wpc_menu_order'] ?? 'DESC';
		$wpc_price_show      = $attributes['wpc_price_show'] ?? 'yes';
		$wpc_show_vendor     = $attributes['wpc_show_vendor'] ?? 'yes';

		apply_filters( 'elementor/control/search_data', $attributes, $unique_id, 'wpc-menus-list' );

		$allowed_file_names = [
			'style-1',
			'style-2',
			'style-3',
		];

		if ( in_array( $style, $allowed_file_names, true ) ) {
			$template_file = esc_html( $style );
		} else {
			$template_file = $allowed_file_names[0];
		}

		ob_start();
		?>
		<div class="main_wrapper_<?php echo esc_html( $unique_id ); ?>">
			<div class="list_template_<?php echo esc_html( $unique_id ); ?> wpc-nav-shortcode wpc-widget-wrapper"  data-id="<?php echo esc_attr( $unique_id ); ?>">
				<?php
				$food_list_args = [
					'post_type'     => 'product',
					'no_of_product' => intval( $wpc_menu_count ),
					'wpc_cat'       => $wpc_menu_cat,
					'order'         => $wpc_menu_order,
				];

				$selected_location = function_exists( 'wpc_selected_location_id' ) ? wpc_selected_location_id() : null;
				if ( ! empty( $selected_location ) ) {
					$food_list_args['wpc_location'] = $selected_location;
				}

				$products       = Wpc_Utilities::product_query( $food_list_args );
				include wpcafe()->plugin_directory . "/widgets/wpc-menus-list/style/{$template_file}.php";
				?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}
}
