<?php
namespace WpCafe\Core\Blocks\BlockTypes;

defined( 'ABSPATH' ) || exit;

use WpCafe\Utils\Wpc_Utilities;

/**
 * Food Tab Block
 */
class FoodTab extends AbstractBlock {
	/**
	 * Block name within this namespace
	 *
	 * @var string
	 */
	protected $block_name = 'food-menu-tab';

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
			'wpc_food_categories'   => [
				'type'    => 'array',
				'default' => [],
			],
			'show_thumbnail'        => [
				'type'    => 'string',
				'default' => 'yes',
			],
			'wpc_desc_limit'        => [
				'type'    => 'integer',
				'default' => 20,
			],
			'wpc_show_desc'         => [
				'type'    => 'string',
				'default' => 'yes',
			],
			'wpc_cart_button'       => [
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
		// Check if woocommerce exists.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return '';
		}

		$unique_id = md5( md5( microtime() ) );

		$style                = $attributes['food_menu_style'] ?? 'style-1';
		$wpc_menu_order       = $attributes['wpc_menu_order'] ?? 'DESC';
		$wpc_desc_limit       = $attributes['wpc_desc_limit'] ?? 20;
		$wpc_cart_button      = $attributes['wpc_cart_button'] ?? 'yes';
		$show_item_status     = $attributes['show_item_status'] ?? 'yes';
		$wpc_price_show       = $attributes['wpc_price_show'] ?? 'yes';
		$wpc_show_vendor      = $attributes['wpc_show_vendor'] ?? 'yes';
		$wpc_cat_arr          = $attributes['wpc_food_categories'] ?? [];

		$food_menu_tabs = [];

		// If no categories are selected, get all categories
		if ( empty( $wpc_cat_arr ) ) {
			$all_categories = get_terms( [
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
			] );

			if ( ! empty( $all_categories ) && ! is_wp_error( $all_categories ) ) {
				$wpc_cat_arr = wp_list_pluck( $all_categories, 'term_id' );
			}
		}

		if ( count( $wpc_cat_arr ) > 0 ) {
			foreach ( $wpc_cat_arr as $key => $value ) {
				$wpc_cat = get_term_by( 'id', $value, 'product_cat' );
				if ( $wpc_cat ) {
					$wpc_get_menu_order = get_term_meta( $wpc_cat->term_id, 'wpc_menu_order_priority', true );
					$cat_name           = $wpc_cat->name ?? '';
					$tab_data           = [
						'post_cats' => [ $value ],
						'tab_title' => $cat_name,
					];
					if ( empty( $wpc_get_menu_order ) ) {
						$food_menu_tabs[ $key ] = $tab_data;
					} else {
						$food_menu_tabs[ $wpc_get_menu_order ] = $tab_data;
					}
				}
			}
		}

		if ( ! is_array( $food_menu_tabs ) || count( $food_menu_tabs ) <= 0 ) {
			return '';
		}

		$wpc_menu_count = intval( $attributes['wpc_menu_count'] ?? 5 );
		$wpc_show_desc  = $attributes['wpc_show_desc'] ?? 'yes';
		$show_thumbnail = $attributes['show_thumbnail'] ?? 'yes';
		$title_link_show= $attributes['title_link_show'] ?? 'yes';

		$allowed_file_names = [
			'style-1',
			'style-2',
		];

		if ( in_array( $style, $allowed_file_names, true ) ) {
			$template_file = esc_html( $style );
		} else {
			$template_file = $allowed_file_names[0];
		}

		ob_start();
		?>
		<div class="wpc-food-tab-wrapper wpc-nav-shortcode main_wrapper_<?php echo esc_html( $unique_id ); ?>" data-id="<?php echo esc_attr( $unique_id ); ?>">
			<ul class="wpc-nav">
				<?php
				if ( is_array( $food_menu_tabs ) && count( $food_menu_tabs ) > 0 ) {
					foreach ( $food_menu_tabs as $tab_key => $value ) {
						$active_class = ( ( $tab_key === array_keys( $food_menu_tabs )[0] ) ? 'wpc-active' : ' ' );
						$cat_id       = isset( $value['post_cats'][0] ) ? intval( $value['post_cats'][0] ) : 0;
						?>
						<li>
							<a href='#' class='wpc-tab-a <?php echo esc_attr( $active_class ); ?>' data-id='tab_<?php echo intval( $tab_key ); ?>'
							   data-cat_id='<?php echo esc_attr( $cat_id ); ?>'>
								<span><?php echo esc_html( $value['tab_title'] ); ?></span>
							</a>
						</li>
						<?php
					}
				}
				?>
			</ul>
			<div class="wpc-tab-content wpc-widget-wrapper">
				<?php
				foreach ( $food_menu_tabs as $content_key => $value ) {
					if ( isset( $value['post_cats'][0] ) ) {
						$active_class = ( ( $content_key === array_keys( $food_menu_tabs )[0] ) ? 'tab-active' : ' ' );
						$cat_id       = isset( $value['post_cats'][0] ) ? intval( $value['post_cats'][0] ) : 0;
						?>
						<div class='wpc-tab <?php echo esc_attr( $active_class ); ?>' data-id='tab_<?php echo intval( $content_key ); ?>'
							 data-cat_id='<?php echo esc_attr( $cat_id ); ?>'>
							<div class="tab_template_<?php echo esc_attr( $cat_id . '_' . $unique_id ); ?>"></div>
							<div class="template_data_<?php echo esc_attr( $cat_id . '_' . $unique_id ); ?>">
								<?php
								$food_tab_args = [
									'post_type'     => 'product',
									'no_of_product' => $wpc_menu_count,
									'wpc_cat'       => $value['post_cats'],
									'order'         => $wpc_menu_order,
								];

								$selected_location = wpc_selected_location_id();
								if ( ! empty( $selected_location ) ) {
									$food_tab_args['wpc_location'] = $selected_location;
								}

								$products      = Wpc_Utilities::product_query( $food_tab_args );
								include wpcafe()->plugin_directory . "/widgets/wpc-food-menu-tab/style/{$template_file}.php";
								?>
							</div>
						</div>
						<?php
					}
				}
				?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}
}
