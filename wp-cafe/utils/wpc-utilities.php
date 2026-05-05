<?php

namespace WpCafe\Utils;

use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * All  helper functions to use 
 */
class Wpc_Utilities {

	private static $settings_key = 'wpcafe_reservation_settings_options';

	/**
	 * Html markup validation
	 */
	 public static function wpc_kses( $raw ) {
		$allowed_tags = [
			'a'                             => [
				'class'  => [],
				'href'   => [],
				'rel'    => [],
				'title'  => [],
				'target' => [],
			],
			'input'                         => [
				'value'       => [],
				'type'        => [],
				'size'        => [],
				'name'        => [],
				'checked'     => [],
				'placeholder' => [],
				'id'          => [],
				'class'       => [],
			],

			'select'                        => [
				'value'       => [],
				'type'        => [],
				'size'        => [],
				'name'        => [],
				'placeholder' => [],
				'id'          => [],
				'class'       => [],
				'option'      => [
					'value'   => [],
					'checked' => [],
				],
			],

			'textarea'                      => [
				'value'       => [],
				'type'        => [],
				'size'        => [],
				'name'        => [],
				'rows'        => [],
				'cols'        => [],
				'placeholder' => [],
				'id'          => [],
				'class'       => [],
			],
			'abbr'                          => [
				'title' => [],
			],
			'b'                             => [],
			'blockquote'                    => [
				'cite' => [],
			],
			'cite'                          => [
				'title' => [],
			],
			'code'                          => [],
			'del'                           => [
				'datetime' => [],
				'title'    => [],
			],
			'dd'                            => [],
			'div'                           => [
				'class' => [],
				'title' => [],
				'style' => [],
			],
			'dl'                            => [],
			'dt'                            => [],
			'em'                            => [],
			'h1'                            => [
				'class' => [],
			],
			'h2'                            => [
				'class' => [],
			],
			'h3'                            => [
				'class' => [],
			],
			'h4'                            => [
				'class' => [],
			],
			'h5'                            => [
				'class' => [],
			],
			'h6'                            => [
				'class' => [],
			],
			'i'                             => [
				'class' => [],
			],
			'img'                           => [
				'alt'         => [],
				'class'       => [],
				'height'      => [],
				'src'         => [],
				'width'       => [],
				'srcset'      => [],
				'sizes'       => [],
				'loading'     => [],
				'decoding'    => [],
				'title'       => [],
				'id'          => [],
				'style'       => [],
				'data-src'    => [],
				'data-srcset' => [],
				'data-sizes'  => [],
			],
			'li'                            => [
				'class' => [],
			],
			'ol'                            => [
				'class' => [],
			],
			'p'                             => [
				'class' => [],
			],
			'q'                             => [
				'cite'  => [],
				'title' => [],
			],
			'span'                          => [
				'class' => [],
				'title' => [],
				'style' => [],
			],
			'small'                          => [
				'class' => [],
				'title' => [],
				'style' => [],
			],
			'iframe'                        => [
				'width'       => [],
				'height'      => [],
				'scrolling'   => [],
				'frameborder' => [],
				'allow'       => [],
				'src'         => [],
			],
			'strike'                        => [],
			'br'                            => [],
			'strong'                        => [],
			'data-wow-duration'             => [],
			'data-wow-delay'                => [],
			'data-wallpaper-options'        => [],
			'data-stellar-background-ratio' => [],
			'ul'                            => [
				'class' => [],
			],
			'label'                         => [
				'class' => [],
				'for' => [],
			],
		];

		if ( function_exists( 'wp_kses' ) ) { // WP is here
			return wp_kses( $raw, $allowed_tags );
		} else {
			return '';
		}

	}

	/**
	 * Html markup validation
	 */
	public static function wpc_kses_allowed_tags() {
		$allowed_tags = [
			'a'                             => [
				'class'  => [],
				'href'   => [],
				'rel'    => [],
				'title'  => [],
				'target' => [],				
				'id'	 => [],
				'data-product_id' => [],
				'data-tableid'	=> [],
				'data-product_name' => [],
				'data-product_price' => []
			],
			'input'                         => [
				'value'       => [],
				'type'        => [],
				'size'        => [],
				'name'        => [],
				'checked'     => [],
				'placeholder' => [],
				'id'          => [],
				'class'       => [],
				'autocomplete'=> [],
				'step'        => [],
				'min'         => [],
				'max'         => [],
			],

			'select'                        => [
				'value'       => [],
				'type'        => [],
				'size'        => [],
				'name'        => [],
				'placeholder' => [],
				'id'          => [],
				'class'       => [],
				'multiple'    => array(),
				'data-cat'	  => []
				
			],
			'option'      => [
				'value'   => [],
				'checked' => [],
				'selected'=> [],
				'data-formstyle' => []
			],

			'textarea'                      => [
				'value'       => [],
				'type'        => [],
				'size'        => [],
				'name'        => [],
				'rows'        => [],
				'cols'        => [],
				'placeholder' => [],
				'id'          => [],
				'class'       => []
			],
			'abbr'                          => [
				'title' => [],
			],
			'b'                             => [],
			'blockquote'                    => [
				'cite' => [],
			],
			'cite'                          => [
				'title' => [],
			],
			'code'                          => [],
			'del'                           => [
				'datetime' => [],
				'title'    => [],
			],
			'dd'                            => [],
			'div'                           => [
				'class' => [],
				'title' => [],
				'style' => [],
				'id'    => [],
			],
			'dl'                            => [],
			'dt'                            => [],
			'em'                            => [],
			'h1'                            => [
				'class' => [],
			],
			'h2'                            => [
				'class' => [],
			],
			'h3'                            => [
				'class' => [],
			],
			'h4'                            => [
				'class' => [],
			],
			'h5'                            => [
				'class' => [],
			],
			'h6'                            => [
				'class' => [],
			],
			'i'                             => [
				'class' => [],
			],
			'img'                           => [
				'alt'         => [],
				'class'       => [],
				'height'      => [],
				'src'         => [],
				'width'       => [],
				'srcset'      => [],
				'sizes'       => [],
				'loading'     => [],
				'decoding'    => [],
				'title'       => [],
				'id'          => [],
				'style'       => [],
				'data-src'    => [],
				'data-srcset' => [],
				'data-sizes'  => [],
			],
			'li'                            => [
				'class' => [],
			],
			'ol'                            => [
				'class' => [],
			],
			'p'                             => [
				'class' => [],
			],
			'q'                             => [
				'cite'  => [],
				'title' => [],
			],
			'span'                          => [
				'class' => [],
				'id' => [],
				'title' => [],
				'style' => [],
				'span'	=> []
			],
			'small'                          => [
				'class' => [],
				'title' => [],
				'style' => [],
			],
			'iframe'                        => [
				'width'       => [],
				'height'      => [],
				'scrolling'   => [],
				'frameborder' => [],
				'allow'       => [],
				'src'         => [],
			],
			'strike'                        => [],
			'br'                            => [],
			'data-wow-duration'             => [],
			'data-wow-delay'                => [],
			'data-wallpaper-options'        => [],
			'data-stellar-background-ratio' => [],
			'ul'                            => [
				'class' => [],
			],
			'label'                         => [
				'class' => [],
				'for' => [],
			],
			'audio' => array(
				'class' => array(),
				'id' => array(),
				'name' => array(),
				'controls' => array(),
			),
			'source' => array(
				'src' => array(),
				'type' => array(),
			),
			'strong' => array(
				'for' => array(),
			),			
			'attr' => array(
				'style' => array(),
				'class' => array(),
				'input_class' => array(),
			),
			'svg' => array(
				'width'   => array(),
				'height'  => array(),
				'viewbox' => array(),
				'viewBox' => array(),
				'fill'    => array(),
				'stroke' => array(),
				'xmlns'   => array(),
			),
			'path' => array(
				'd'            => array(),
				'fill'         => array(),
				'stroke'       => array(),
				'stroke-width' => array(),
				'stroke-linecap' => array(),
				'stroke-linejoin' => array(),
			),
			'polyline' => array(
				'points' => array(),
				'fill'  => array(),
				'stroke' => array(),
				'stroke-width' => array(),
			),
			'line' => array(
				'x1'    => array(),
				'y1'    => array(),
				'x2'    => array(),
				'y2'    => array(),
				'stroke' => array(),
				'stroke-width' => array(),
			),
			'circle' => array(
				'cx'     => array(),
				'cy'     => array(),
				'r'      => array(),
				'fill'   => array(),
				'stroke' => array(),
				'stroke-width' => array(),
			),
		];

		return $allowed_tags;

	}

	/**
	 * Auto generate class name from path.
	 */
	public static function make_classname( $dirname ) {
		$dirname    = pathinfo( $dirname, PATHINFO_FILENAME );
		$class_name = explode( '-', $dirname );
		$class_name = array_map( 'ucfirst', $class_name );
		$class_name = implode( '_', $class_name );

		return $class_name;
	}

	/**
	 * Seat count min , max limit
	 */
	public  static function get_seat_count_limit() {
		$seat_count_limit = array();
		try {
			$settings_obj     = new \WpCafe\Core\Base\Wpc_Settings_Field;
			$settings         = $settings_obj->get_settings_option();
			
			$get_seat_capacity= apply_filters('wpcafe/reservation/seat_capacity', $settings );
			$seat_capacity    = function_exists('wpcafe_pro') && isset( $get_seat_capacity ) ? $get_seat_capacity : 20;
			$wpc_min_guest_no = isset( $settings['wpc_min_guest_no'] ) && $settings['wpc_min_guest_no']!=="" ? $settings['wpc_min_guest_no'] : 1;
			$wpc_max_guest_no = !empty( $settings['wpc_max_guest_no'] )  ? $settings['wpc_max_guest_no'] : $seat_capacity;
			for ( $i = $wpc_min_guest_no; $i <= $wpc_max_guest_no; $i++ ) {
				$seat_count_limit[$i] = $i;
			}

			return $seat_count_limit;
		} catch ( \Exception $es ) {
			return [];
		}

	}

	/**
	 * Email sending function
		*
	 * @param array $args = [$to, $subject, $mail_body, $from, $from_name].
	 */
	public static function wpc_send_email( $args ) {
		$to        = $args['to'] ?? '';
		$subject   = $args['subject'] ?? '';
		$mail_body = $args['mail_body'] ?? '';
		$from      = $args['from'] ?? '';
		$from_name = $args['from_name'] ?? '';

		$body    = wpautop( html_entity_decode( $mail_body ) );
		$from_name = html_entity_decode( $from_name );

		$headers = array( 'Content-Type: text/html; charset=UTF-8', 'From: ' . $from_name . ' <' . $from . '>' );

		$result  = wp_mail( $to, $subject, $body, $headers );
		
		return $result;
	}

	/**
	 * Markup Notice.
	 */
	public static function pro_banner_markup( $notice = [] ) {
		?>
		<div id="<?php echo esc_attr( $notice['id'] ); ?>" class="wpc notice wpc-notice wpc-notice-buy-pro-banner is-dismissible" <?php echo esc_attr( $notice['data'] ); ?>>
			 <?php if ( !empty( $notice['btn'] ) ) { ?>
					<a target="_blank" href="<?php echo esc_url( $notice['btn']['url'] ); ?>" class="notice-banner-link"></a>
			<?php } ?>
		</div>
		<?php
	}


	/**
	 * Markup Notice.
	 */
	public static function markup( $notice = [] ) {
		?>
		<div id="<?php echo esc_attr( $notice['id'] ); ?>" class="<?php echo esc_attr( $notice['classes'] ); ?>" <?php echo esc_attr( $notice['data'] ); ?>>
			<p>
				<?php echo esc_attr( $notice['message'] ); ?>
			</p>

			<?php if ( !empty( $notice['btn'] ) ): ?>
				<p>
					<a href="<?php echo esc_url( $notice['btn']['url'] ); ?>" class="button-primary"><?php echo esc_html( $notice['btn']['label'] ); ?></a>
				</p>
			<?php endif;?>
		</div>
		<?php
	}

	/**
	 * Render html.
	 */
	public static function wpc_render( $content ) {
		if ( $content == "" ) {
			return "";
		}

		return $content;
	}

	/**
	 * Menu category
	 */
	public static function get_menu_category( $id = null ) {
		$menu_category = [];
		try {

			if ( is_null( $id ) ) {
				$terms = get_terms( [
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
				] );

				foreach ( $terms as $cat ) {
					if(is_object( $cat ) ){
						$menu_category[$cat->term_id] = $cat->name;
					}
				}

				return $menu_category;
			} else {
				// return single menu.
				return get_post( $id );
			}

		} catch ( \Exception $es ) {
			return [];
		}

	}

	/**
	 * content crop function
	 */
	public static function wpcafe_trim_words( $content, $count = 150, $readmore = null ) {
		return wp_trim_words( $content, $count, $readmore );
	}



	public static function get_location_data( $default_options="", $no_options="" , $value_type = "key", $number = false ) {
		$default_options = esc_html__('Select Delivery Location', 'wp-cafe');
		$no_options = esc_html__('No Delivery Location is Set', 'wp-cafe');
		// get location
		$wpc_location     = get_terms(['taxonomy' => 'wpcafe_location', 'hide_empty' => 0, 'orderby' => 'DESC', 'parent' => 0, 'number'=> $number]);

		$wpc_location_arr = ['' => $default_options];

		if ( !empty($wpc_location) ) {
			foreach ($wpc_location as $value) {
				if ( $value_type =="key" ) {
					$wpc_location_arr["$value->slug"] = $value->name;
				}
				else if ( $value_type =="id" ) {
					$wpc_location_arr["$value->term_id"] = $value->name;
				}
				else{
					$wpc_location_arr["$value->name"] = $value->name;
				}
			}
		}

		return $wpc_location_arr;
	}

	/**
	* Show tag function
 */
	public static function wpc_tag( $id , $stock_status ){
		$current_tags = get_the_terms($id, 'product_tag');
		//create a list to hold our tags.
		?>
		<ul class="wpc-menu-tag">
			<?php
			if ( $stock_status == true || ( $current_tags && ! is_wp_error( $current_tags ) ) ) {
				//for each tag we create a list item.
				if ( is_array( $current_tags ) && count( $current_tags )>0 ) {
					foreach ( $current_tags as $tag ) {
						$tag_title = $tag->name;
						?>
						<li>	<?php echo esc_html( $tag_title ); ?></li>
						<?php
					}
				}
			}
			else{
				?><li><?php echo esc_html__('Out of stock','wp-cafe'); ?></li><?php 
			}
			?>
		</ul>
		<?php
	}

	/**
	 * Product query
	 * @param array $args = [ $post_type, $no_of_product, $wpc_cat, $order, $page, $total_count, $search_value, $taxonomy ]
	 */
	public static function product_query( $params ){
		$defaults = array(
			'post_type'     => 'product',
			'no_of_product' => 10,
			'wpc_cat'       => array(),
			'order'         => 'DESC',
			'page'          => null,
			'total_count'   => false,
			'search_value'  => false,
			'taxonomy'      => 'product_cat',
			'wpc_location'  => null
		);

		$parsed = wp_parse_args( $params, $defaults );

		$post_type     = $parsed['post_type'];
		$no_of_product = $parsed['no_of_product'];
		$wpc_cat       = $parsed['wpc_cat'];
		$order         = $parsed['order'];
		$page          = $parsed['page'];
		$total_count   = $parsed['total_count'];
		$search_value  = $parsed['search_value'];
		$taxonomy      = $parsed['taxonomy'];
		$wpc_location  = $parsed['wpc_location'];

		$args = [];
		$args['post_type']      = $post_type;
		if ( $total_count ) {
			$args    = [ 'posts_per_page' =>  -1 ];
		}
		elseif( $search_value ){
			$args['posts_per_page']  = $no_of_product;
			$args['post_title_like'] = $search_value;
		}
		elseif( $page ){
			$args    = [
				'posts_per_page' =>  $no_of_product,
				'paged'          =>  $page,
			];
		}
		else{
			$args    = [ 'posts_per_page' =>  $no_of_product ];
		}

		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- required for report/filter functionality
		$args['tax_query'] = array(
			'relation' => 'AND',
		);

		if( is_array( $wpc_cat ) && count( $wpc_cat )>0 ){
			$args['tax_query'][] = array(
				'taxonomy'          => $taxonomy,
				'terms'             =>  $wpc_cat,
				'field'             => 'id',
				'include_children'  => true,
				'operator'          => 'IN'
			);
		}

		// Add location filtering if wpc_location is specified
		if ( ! empty( $wpc_location ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'wpcafe_location',
				'field'    => 'term_id',
				'terms'    => $wpc_location,
				'operator' => 'IN'
			);
		}

		$args['orderby']        = 'date';
		$args['order']          = $order;
		$args['post_status']    = 'publish';

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wpc_ is the plugin's registered prefix.
		$args = apply_filters( 'wpc_product_query_args', $args );

		// Translate WC_Product_Query keys (used by filters like timed-product) to WP_Query keys.
		$key_map = [
			'include' => 'post__in',
			'exclude' => 'post__not_in', // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- exclusion applied only when explicitly requested by a filter hook; no alternative exists here.
		];
		foreach ( $key_map as $from => $to ) {
			if ( ! empty( $args[ $from ] ) ) {
				$args[ $to ] = (array) $args[ $from ];
			}
			unset( $args[ $from ] );
		}

		$args['post_type']     = $args['post_type'] ?? 'product';
		$args['fields']        = 'ids';
		$args['no_found_rows'] = true;

		$ids = ( new \WP_Query( $args ) )->posts;

		return $ids ? array_filter( array_map( 'wc_get_product', $ids ) ) : [];
	}

	/**
	 * Render cart icon whether through custom images or icon classes
	 *
	 * @param  bool $is_custom_icon Whether the icon is a custom image
	 * @param  string|array $icon_value The icon value (URL for custom, class name for default, or array with type/value)
	 * @return string HTML markup for the icon
	 */
	public static function render_cart_icon( $is_custom_icon, $icon_value ) {
		// Handle array format with type and value (from settings)
		if ( is_array( $icon_value ) ) {
			$icon_type = ! empty( $icon_value['type'] ) ? $icon_value['type'] : '';
			$icon_url = ! empty( $icon_value['value'] ) ? $icon_value['value'] : '';

			if ( $icon_type === 'custom' && ! empty( $icon_url ) ) {
				return '<img src="' . esc_url( $icon_url ) . '" alt="cart icon" width="24" height="24" class="wpc-custom-cart-icon">';
			} elseif ( ! empty( $icon_url ) ) {
				// SVG icon from assets directory
				$svg_url = wpcafe()->assets_url . '/images/mini-cart/' . esc_attr( $icon_url ) . '.svg';
				return '<img src="' . esc_url( $svg_url ) . '" alt="' . esc_attr( $icon_url ) . '" width="24" height="24" class="wpc-svg-cart-icon">';
			}
		}

		// Handle string format (custom URL or font icon class)
		if ( $is_custom_icon ) {
			return '<img src="' . esc_url( $icon_value ) . '" alt="cart icon" width="24" height="24" class="wpc-custom-cart-icon">';
		}

		return '<i class="wpcafe-cart_icon"></i>';
	}

	/**
	 * Add to cart button based on product type
	 *
	 * @param [type] $args [ $product, $cart_button, $wpc_btn_text='', $customize_btn= '', $widget_id=''].
	 */
	public static function product_add_to_cart( $args ) {

		$product            = $args['product'] ?? null;
		$cart_button        = $args['cart_button'] ?? '';
		$wpc_btn_text       = $args['wpc_btn_text'] ?? '';
		$customize_btn      = $args['customize_btn'] ?? '';
		$widget_id          = $args['widget_id'] ?? '';
		$cart_icon           = $args['cart_icon'] ?? '';
		$customization_icon = $args['customization_icon'] ?? '';
		$settings           = get_option('wpcafe_reservation_settings_options');
		$icon_type = '';
		$icon_value = '';

		if ( is_array($cart_icon) && !empty($cart_icon['type']) ) {
			$icon_type = $cart_icon['type'];
			$icon_value = !empty($cart_icon['value']) ? $cart_icon['value'] : '';
		} else {
			$icon_value = !empty($cart_icon) ? $cart_icon : 'wpcafe-cart_icon';
		}

		$is_custom_icon = ($icon_type === 'custom' && !empty($icon_value));

		$customization_icon = !empty($settings['wpc_customization_icon']) ? $settings['wpc_customization_icon'] : 'wpcafe-customize';
		// qr code parameter.
		$html = self::qr_code_input();
		$price_html = "";

		switch ( $product->get_type() ) {

			case ( $product->get_type() == 'variable' || $product->get_type() == 'grouped' )
			&& $product->is_in_stock() == true :
				if( $cart_button=='on' || $cart_button =='yes' ) {
					// Free's Product_Popup_Service hooks into this filter to
					// render the Customize button + open the variation popup.
					// Pro replaces it with its own (richer) version. Either
					// way, fire the filter unconditionally.
					$filtered = apply_filters( 'wpcafe/shortcode/variation', $product, $customize_btn, $widget_id, $customization_icon );

					if ( is_string( $filtered ) && '' !== $filtered ) {
						return $filtered;
					}

					// No listener returned markup (popup module disabled?).
					// Fall back to a plain permalink link with the cart icon.
					$icon_html = self::render_cart_icon( $is_custom_icon, $cart_icon );
					$price_html = '
						<div class="wpc-add-to-cart">
							<a href="' . esc_url( $product->get_permalink() ) . '" class="wpc-btn">
								' . $icon_html . '
							</a>
						</div>
					';
				}
				break;

			case ($product->get_type() == 'simple' ) &&
				($cart_button == 'on' || $cart_button == 'yes' ) &&
				$product->is_in_stock() == true :

				$class = !empty($wpc_btn_text) ? 'cart-text-added' : 'cart-text-no-added';

				// Give add-on integrations (e.g. Optiontics) a chance to
				// replace the default add-to-cart with their own Customize UI.
				// Listeners return empty when they do not handle this product.
				$filtered = apply_filters( 'wpcafe/shortcode/simple', $product, $customize_btn, $widget_id, $customization_icon, $is_custom_icon, $cart_icon );
				if ( is_string( $filtered ) && '' !== $filtered ) {
					return $filtered;
				}

				$price_html ='<div class="wpc-add-to-cart">
					<a href="?add-to-cart='.esc_html($product->get_id()).'"
					data-product_name="'.esc_html($product->get_name()).'"
					data-product_price="'.esc_html( wc_get_price_to_display( $product ) . get_woocommerce_currency_symbol() ).'"
					data-product_id="'.esc_html($product->get_id()).'"
					'.esc_html($html).'
					rel="nofollow" class="button  add_to_cart_button ajax_add_to_cart '.esc_attr($class).'">
						<span class="adding"> '.esc_html__('Adding...', 'wp-cafe').'</span>
						<span class="added"> '.esc_html__('Added', 'wp-cafe').'</span>';
						if (isset($wpc_btn_text) && $wpc_btn_text  != '') {

							$price_html .='<span class="add-cart-text"> '.esc_html($wpc_btn_text).' </span>';
						}
						$price_html .= self::render_cart_icon( $is_custom_icon, $cart_icon );
						$price_html .='</a>
				</div>';

				break;

			case $product->get_type() == 'external'  &&
				($cart_button == 'on' || $cart_button == 'yes' ) &&
				$product->is_in_stock() == true :
				$price_html = '
					<div class="wpc-external-product-link">
						<a href="'.esc_url( $product->get_product_url() ).'" class="wpc-btn">
								'.esc_html( $product->get_button_text() ).'
						</a>
					</div>
					';
				break;
			
			default:
				break;
		}

		return wp_kses( $price_html, Wpc_Utilities::wpc_kses_allowed_tags() );

	}

	public static function qr_code_input() {
		
		$html = '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin list-table filter, capability-gated
		if ( !empty( $_GET['tableId'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin list-table filter, capability-gated
			$html = ' data-tableid="'. esc_attr( sanitize_text_field( wp_unslash( $_GET['tableId'] ?? '' ) ) ).'"';
		}

		return $html;
	}

	/**
	 * Get variation price
	 */
	public static function get_variation_price($product){
		if($product->get_type() == 'grouped'){
			$children = $product->get_children();
			$price = 0;
			$var_price = get_woocommerce_currency_symbol( '' ) . ' ' . $price;
		}else{
			$variation_price = $product->get_variation_prices(true); // true for getting tax price
			$var_price = '';
			if (is_array($variation_price) && isset($variation_price['price'])) {
				$first = array_shift($variation_price['price']);
				$array_pop = array_pop($variation_price['price']);
				$last = ( !empty( $array_pop ) ) ?  "-" . get_woocommerce_currency_symbol() . $array_pop : '';
				$var_price = get_woocommerce_currency_symbol() . $first . $last ;
			}
		}

		return $var_price;
	}

	/**
	 * Menu price by tax for short code and widget
	 */
	public static function menu_price_by_tax( $product ){
		$price = '';
		if (wc_get_price_excluding_tax($product)) {
			$price      = wc_get_price_excluding_tax($product);
		} else {
			$price      = wc_get_price_including_tax($product);
		}

		return $price;
	}

	/**
	 * Get Tab Array From Category List
	 *
	 * @param [type] $wpc_cat_arr
	 *
	 * @since 1.3.3
	 *
	 * @return array tab array
	 */
	public static function get_tab_array_from_category( $wpc_cat_arr ){

		$food_menu_tabs = [];

		foreach ($wpc_cat_arr as $key => $value) {
			if ($wpc_cat = get_term_by('id', $value, 'product_cat')) {
				$wpc_get_menu_order = get_term_meta($wpc_cat->term_id, 'wpc_menu_order_priority', true);
				$wpc_cat    = get_term_by('id', $value, 'product_cat');
				$cat_name   = ($wpc_cat && $wpc_cat->name ) ? $wpc_cat->name : "";
				$tab_data   = array('post_cats'=>[$value, $wpc_cat->slug],'tab_title' => $cat_name);
				if ($wpc_get_menu_order == '') {
					$food_menu_tabs[$key] = $tab_data;
				} else {
					$food_menu_tabs[$wpc_get_menu_order] = $tab_data;
				}
			}
		}

		return $food_menu_tabs;
	}

	/**
	 * Replace qoute of sting
	 */
	public static function replace_qoute( $data ){
		if( count( $data )>0 ){
			array_walk( $data , function( &$value , $index )
			{ $value = str_replace(['&#039;','&lsquo;','&quot;'],'', $value); });
		}

		return $data;
	}

	/**
	 * shortcode builder option range
	 */
	public static function get_option_range( $arr=[],  $class="" ) {
		$html = '';
		$html .='<select  class="wpc-setting-input '.esc_attr($class).'">';
		foreach($arr as $key=> $value){
			$html .='<option value="'.esc_html( $key ).'"> '.esc_html($value).' </option>';
		}
		$html .='</select>';

	  return $html;
  	}

	/**
	 * select food locations
	 *
	 * @return void
	 */
	public static function select_food_locations_filter($atts) {

		// shortcode option
        $atts = extract(shortcode_atts(
            [
                'location_alignment'   => 'center'
            ], $atts ));
        
		$food_location  = Wpc_Utilities::get_location_data ( esc_html__("Select food location", "wp-cafe") , esc_html__("No location is set", "wp-cafe"),"id" );
		?>
		<!-- select location -->
		<form class="location_menu">
			<select id="filter_location" name="filter_location" class="filter-location <?php echo esc_attr($location_alignment); ?>">
				<?php foreach ( $food_location as $key => $value ) { ?>
					<option value="<?php echo esc_attr($key); ?>"><?php echo esc_html( $value ) ?></option>
				<?php } ?>
			</select>
		</form>

		<div id="location_change" class="location_modal hide_field location_change">
				<div class="modal-content">
						<div>
								<?php echo esc_html__("By changing your current location, You will
												lose your selected item from the cart.",'wp-cafe');?>
						</div>
						<button class="change_yes wpc-btn wpc-btn-primary"><?php echo esc_html__( "Yes", "wp-cafe" );?></button>
						<button class="change_no wpc-btn wpc-btn-primary"><?php echo esc_html__( "No", "wp-cafe" );?></button>
				</div>
		</div>
		<?php
	}

	public static function get_query_cache( $query ){

		$cache_key = 'wpcafe_query_' . md5($query);
		return $cache_key;
	}

	public static function get_formatted_time($timeString){
		$time_format = get_option('time_format');
		$dateTime = \DateTime::createFromFormat($time_format, $timeString);
		if ($dateTime) {
		    $timestamp = $dateTime->getTimestamp();
		    return date_i18n($time_format, $timestamp );
		} else {
		    return $timeString;
		}
	}
}
