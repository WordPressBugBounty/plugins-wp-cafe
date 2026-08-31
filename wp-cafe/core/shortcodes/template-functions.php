<?php

namespace WpCafe\Core\Shortcodes;
use \WpCafe\Utils\Wpc_Utilities;

defined( 'ABSPATH' ) || exit;

class Template_Functions {

	/**
	 * Food Menu List Template One
	 */
	public static function wpc_food_menu_list_template( $args ){
			$show_thumbnail     = $args['show_thumbnail'] ?? '';
			$product            = $args['product'] ?? null;
			$permalink          = $args['permalink'] ?? '';
			$class              = $args['class'] ?? '';
			$col                = $args['col'] ?? '';
			$show_item_status   = $args['show_item_status'] ?? '';
			$show_item_label    = $args['show_item_label'] ?? 'no';
			$wpc_price_show     = $args['wpc_price_show'] ?? '';
			$wpc_show_vendor    = $args['wpc_show_vendor'] ?? '';
			$wpc_show_desc      = $args['wpc_show_desc'] ?? '';
			$wpc_desc_limit     = $args['wpc_desc_limit'] ?? '';
			$wpc_cart_button    = $args['wpc_cart_button'] ?? '';
			$unique_id          = $args['unique_id'] ?? '';
			$customization_icon = $args['customization_icon'] ?? '';
			// Get cart icon configuration from settings
			$cart_icon_config = wpc_get_option('cart_icon');
			?>
				<div class="wpc-food-menu-item wpc-row">
					<?php
					if ($show_thumbnail == 'yes' || $show_thumbnail == 'on') { ?>
							<div class="wpc-col-md-4">
								<!-- thumbnail -->
								<?php if ($product->get_image()) { ?>
										<div class="wpc-food-menu-thumb">
												<a href="<?php echo esc_url( $permalink ); ?>" class="<?php echo esc_attr($class); ?>">
														<?php echo wp_kses( $product->get_image('woocommerce_thumbnail'), Wpc_Utilities::wpc_kses_allowed_tags()) ; ?>
												</a>
										</div>
								<?php  } ?>
							</div>
					<?php }  ?>
					<div class="<?php echo esc_attr($col); ?>">
							<div class="wpc-food-inner-content">
									<!-- product tag and tax -->
									<div class="wpc-menu-tag-wrap">
											<?php
											$show_item_status == 'yes' ? Wpc_Utilities::wpc_tag( $product->get_id() , $product->is_in_stock() ) : "";
											if ( $show_item_label === 'yes' ) { Wpc_Utilities::wpc_product_labels( $product->get_id() ); }
											if ($product->get_price_suffix() != '') { ?>
													<ul class="wpc-menu-tag">
															<li>
																	<?php if (wc_get_price_including_tax($product)) {
																			// get percentage tax
																			echo wp_kses($product->get_price_suffix(), Wpc_Utilities::wpc_kses_allowed_tags() );
																	} ?>
															</li>
													</ul>
													<?php
											} ?>
									</div>

									<h3 class="wpc-post-title wpc-title-with-border">
											<a href="<?php echo esc_url($permalink); ?>" class="<?php echo esc_attr($class); ?>"> <?php echo esc_html( $product->get_name() );  ?> </a>
											<span class="wpc-title-border"></span>
											<?php
											if( $product->get_type() !== 'variable' && $wpc_price_show !== 'no') {
													?>
													<span class="wpc-menu-price"><?php
													echo wp_kses($product->get_price_html(), Wpc_Utilities::wpc_kses_allowed_tags() );
													 ?></span></span>
													<?php
											} else {
													if( $wpc_price_show !== 'no'){
															// variation price.
															$variation_price = $product->get_variation_prices( true ); // true for getting tax price
															$var_price = '';
															if( is_array( $variation_price ) && isset( $variation_price['price'] ) ){
																	if( $wpc_price_show == 'yes' || $wpc_price_show == 'min'){
																			$var_price .= "<span class='min_price'>". get_woocommerce_currency_symbol() . array_shift($variation_price['price']) . "</span>";
																	}
																	if( $wpc_price_show == 'yes' ){
																			$var_price .= " - ";
																	}
																	if( $wpc_price_show == 'yes' || $wpc_price_show == 'max'){
																			$var_price .= "<span class='max_price'>". get_woocommerce_currency_symbol() . array_pop($variation_price['price']) . "</span>";
																	}
															}

													?>
													<span class="wpc-menu-currency"><span class="wpc-menu-price">
														<?php echo wp_kses($var_price, Wpc_Utilities::wpc_kses_allowed_tags() );
														?></span></span>
													<?php
													}
											}
											?>
									</h3>
									<?php
											if(wpcafe_is_multivendor() && !empty( $wpc_show_vendor ) && $wpc_show_vendor == 'yes') {
													do_action( 'wpcafe_multivendor_seller', $product->get_id());
											}
									?>
									<p>
											<?php
											if ($wpc_show_desc == 'yes') {
													echo  esc_html( Wpc_Utilities::wpcafe_trim_words( get_the_excerpt( $product->get_id() ) , $wpc_desc_limit) );
											}
											?>
									</p>
									<?php
											// cart button
											$add_cart_args = array(
													'product'       => $product,
													'cart_button'   => $wpc_cart_button,
													'wpc_btn_text'  => "",
													'customize_btn' => "",
													'widget_id'     => $unique_id,
													'cart_icon'         => $cart_icon_config,
													'customization_icon'=> !empty($customization_icon)
											);

											echo wp_kses(Wpc_Utilities::product_add_to_cart( $add_cart_args ), Wpc_Utilities::wpc_kses_allowed_tags() );
									?>
							</div>
					</div>
					<div class="wpc_loader_wrapper">
							<div class="loder-dot dot-a"></div>
							<div class="loder-dot dot-b"></div>
							<div class="loder-dot dot-c"></div>
							<div class="loder-dot dot-d"></div>
							<div class="loder-dot dot-e"></div>
							<div class="loder-dot dot-f"></div>
							<div class="loder-dot dot-g"></div>
							<div class="loder-dot dot-h"></div>
					</div>
			</div>

			<?php
	}

	/**
	 * Food Menu List Template Two
	 */
	public static function wpc_food_menu_list_template_two( $args ){
			$show_thumbnail     = $args['show_thumbnail'] ?? '';
			$product            = $args['product'] ?? null;
			$permalink          = $args['permalink'] ?? '';
			$class              = $args['class'] ?? '';
			$show_item_status   = $args['show_item_status'] ?? '';
			$show_item_label    = $args['show_item_label'] ?? 'no';
			$wpc_price_show     = $args['wpc_price_show'] ?? '';
			$wpc_show_vendor    = $args['wpc_show_vendor'] ?? '';
			$wpc_show_desc      = $args['wpc_show_desc'] ?? '';
			$wpc_desc_limit     = $args['wpc_desc_limit'] ?? '';
			$wpc_cart_button    = $args['wpc_cart_button'] ?? '';
			$unique_id          = $args['unique_id'] ?? '';
			$customization_icon = $args['customization_icon'] ?? '';
			// Get cart icon configuration from settings
			$cart_icon_config = wpc_get_option('cart_icon');
			?>

			<div class="wpc-food-menu-item style2">
							<div class="wpc-row">
									<div class="wpc-col-md-8 wpc-align-self-center">
											<div class="wpc-food-inner-content">
													<!-- display tag -->
											<div class="wpc-menu-tag-wrap">
											<?php
											$show_item_status == 'yes' ? Wpc_Utilities::wpc_tag( $product->get_id() , $product->is_in_stock() ) : "";
											if ( $show_item_label === 'yes' ) { Wpc_Utilities::wpc_product_labels( $product->get_id() ); }
											$price = Wpc_Utilities::menu_price_by_tax( $product );
											?>
													<?php
														if ($show_item_status == 'yes' && $product->get_price_suffix() != '') {
													?>
															<ul class="wpc-menu-tag">
																	<li>
																			<?php
																	if (wc_get_price_including_tax($product)) {
																			// get percentage tax
																			echo wp_kses($product->get_price_suffix(), Wpc_Utilities::wpc_kses_allowed_tags() );
																	}
																	?>
																	</li>
															</ul>
															<?php
															}
													?>
													</div>
													<h3 class="wpc-post-title">
															<a href="<?php echo esc_url( $permalink ); ?>"
																	class="<?php echo esc_attr( $class); ?>"><?php echo esc_html($product->get_name());  ?>
															</a>

													</h3>
													<?php
															if(wpcafe_is_multivendor() && !empty( $wpc_show_vendor ) && $wpc_show_vendor == 'yes') {
																	do_action( 'wpcafe_multivendor_seller', $product->get_id());
															}
													?>
													<?php  if( $wpc_show_desc == 'yes' ){ ?>
													<p>
															<?php echo  esc_html( Wpc_Utilities::wpcafe_trim_words( get_the_excerpt($product->get_id() ), $wpc_desc_limit) ); ?>
													</p>
													<?php } ?>

											</div>
									</div>
									<!-- thumbnail -->
									<?php
									if ( $show_thumbnail == 'yes' ) {

											if ($product->get_image()) {
													?>
															<div class="wpc-col-md-4">
																	<div class="wpc-food-menu-thumb">
																			<?php
																	if( $product->get_type() !== 'variable' && $wpc_price_show !== 'no') {
																			?>
																			<span class="wpc-menu-currency">
																					<?php
																					echo wp_kses($product->get_price_html(), Wpc_Utilities::wpc_kses_allowed_tags() );
																					?></span>
																			</span>
																			<?php
																	} else {
																			if( $wpc_price_show !== 'no'){
																					// variation price.
																					$variation_price = $product->get_variation_prices( true ); // true for getting tax price

																					$var_price = '';
																					if( is_array( $variation_price ) && isset( $variation_price['price'] ) ){
																							if( $wpc_price_show == 'yes' || $wpc_price_show == 'min'){
																									$var_price .= "<span class='min_price'>". get_woocommerce_currency_symbol() . array_shift($variation_price['price']) . "</span>";
																							}
																							if( $wpc_price_show == 'yes' ){
																									$var_price .= " - ";
																							}
																							if( $wpc_price_show == 'yes' || $wpc_price_show == 'max'){
																									$var_price .= "<span class='max_price'>". get_woocommerce_currency_symbol() . array_pop($variation_price['price']) . "</span>";
																							}
																					}

																			?>
																					<span class="wpc-menu-currency"><span class="wpc-menu-price"><?php
																					        echo wp_kses($var_price, Wpc_Utilities::wpc_kses_allowed_tags() );
																					    ?></span></span>
																			<?php
																			}
																	}
																	?>
																			<a href="<?php echo esc_url(get_permalink($product->get_id())); ?>"
																					class="<?php echo esc_attr( $class ); ?>">
																					<?php echo wp_kses( $product->get_image('woocommerce_thumbnail'), Wpc_Utilities::wpc_kses_allowed_tags()) ; ?>
																			</a>
																			<?php
																			// cart button
																			$add_cart_args = array(
																					'product'       => $product,
																					'cart_button'   => $wpc_cart_button,
																					'wpc_btn_text'  => "",
																					'customize_btn' => "",
																					'widget_id'     => $unique_id,
																					'cart_icon'         => $cart_icon_config,
																					'customization_icon'=> $customization_icon
																			);
																			echo wp_kses(Wpc_Utilities::product_add_to_cart( $add_cart_args ), Wpc_Utilities::wpc_kses_allowed_tags() );
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
	}

	/**
	 * Food Menu List Template Three
	 */
	public static function wpc_food_menu_list_template_three( $args ){
			$show_thumbnail     = $args['show_thumbnail'] ?? '';
			$product            = $args['product'] ?? null;
			$permalink          = $args['permalink'] ?? '';
			$class              = $args['class'] ?? '';
			$show_item_status   = $args['show_item_status'] ?? '';
			$show_item_label    = $args['show_item_label'] ?? 'no';
			$wpc_price_show     = $args['wpc_price_show'] ?? '';
			$wpc_show_vendor    = $args['wpc_show_vendor'] ?? '';
			$wpc_show_desc      = $args['wpc_show_desc'] ?? '';
			$wpc_desc_limit     = $args['wpc_desc_limit'] ?? '';
			$wpc_cart_button    = $args['wpc_cart_button'] ?? '';
			$unique_id          = $args['unique_id'] ?? '';
			$customization_icon = $args['customization_icon'] ?? '';
			$column_desktop     = $args['column_desktop'] ?? '4';
			$column_tablet      = $args['column_tablet'] ?? '6';
			$column_mobile      = $args['column_mobile'] ?? '12';
			// Get cart icon configuration from settings
			$cart_icon_config = wpc_get_option('cart_icon');
			?>
					<div class="wpc-col-lg-<?php echo esc_attr($column_desktop); ?> wpc-col-md-<?php echo esc_attr($column_tablet); ?> wpc-col-sm-<?php echo esc_attr($column_mobile); ?>">
							<div class="wpc-food-single-item">
									<div class="wpc-food-inner-content">
											<!-- display tag -->
											<?php
											$show_item_status == 'yes' ? Wpc_Utilities::wpc_tag( $product->get_id() , $product->is_in_stock() ) : "";
											if ( $show_item_label === 'yes' ) { Wpc_Utilities::wpc_product_labels( $product->get_id() ); }
											$price = Wpc_Utilities::menu_price_by_tax( $product );
											?>
											<?php
											if ($show_item_status == 'yes' && $product->get_price_suffix() != '') {
													?>
													<div class="wpc-menu-tag-wrap">
															<ul class="wpc-menu-tag">
															<li>
																	<?php
																	if (wc_get_price_including_tax($product)) {
																			// get percentage tax
																			echo wp_kses($product->get_price_suffix(), Wpc_Utilities::wpc_kses_allowed_tags() );
																	}
																	?>
															</li>
													</ul>
													</div>
													<?php
											}
											?>
											<h3 class="wpc-post-title">
													<a href="<?php echo esc_url( $permalink ); ?>"
														class="<?php echo esc_attr( $class); ?>"><?php echo esc_html($product->get_name());  ?>
													</a>

											</h3>
											<?php
													if(wpcafe_is_multivendor() && !empty( $wpc_show_vendor ) && $wpc_show_vendor == 'yes') {
															do_action( 'wpcafe_multivendor_seller', $product->get_id());
													}
											?>
											<?php  if( $wpc_show_desc == 'yes' ){ ?>
													<p>
															<?php echo  esc_html( Wpc_Utilities::wpcafe_trim_words( get_the_excerpt($product->get_id() ), $wpc_desc_limit) ); ?>
													</p>
											<?php } ?>
									</div>
									<!-- thumbnail -->
									<?php
									if ( $show_thumbnail == 'yes' ) {

											if ($product->get_image()) {
													?>
													<div class="wpc-food-menu-thumb">
															<?php
															if( $product->get_type() !== 'variable' && $wpc_price_show !== 'no') {
																	?>
																	<span class="wpc-menu-currency">
																					<?php
																					echo wp_kses($product->get_price_html(), Wpc_Utilities::wpc_kses_allowed_tags() );
																					?></span>
																	</span>
																	<?php
															} else {
																	if( $wpc_price_show !== 'no'){
																			// variation price
																			$variation_price = $product->get_variation_prices( true ); // true for getting tax price
																			$var_price = '';
																			if( is_array( $variation_price ) && isset( $variation_price['price'] ) ){
																					if( $wpc_price_show == 'yes' || $wpc_price_show == 'min'){
																							$var_price .= "<span class='min_price'>". get_woocommerce_currency_symbol() . array_shift($variation_price['price']) . "</span>";
																					}
																					if( $wpc_price_show == 'yes' ){
																							$var_price .= " - ";
																					}
																					if( $wpc_price_show == 'yes' || $wpc_price_show == 'max'){
																							$var_price .= "<span class='max_price'>". get_woocommerce_currency_symbol() . array_pop($variation_price['price']) . "</span>";
																					}
																			}
																	?>
																			<span class="wpc-menu-currency"><span class="wpc-menu-price">
																				<?php
																					        echo wp_kses($var_price, Wpc_Utilities::wpc_kses_allowed_tags() );
																					    ?>
																			</span></span>
																	<?php
																	}
															}
															?>

															<?php
															// cart button
															$add_cart_args = array(
																	'product'       => $product,
																	'cart_button'   => $wpc_cart_button,
																	'wpc_btn_text'  => "",
																	'customize_btn' => "",
																	'widget_id'     => $unique_id,
																	'cart_icon'         => $cart_icon_config,
																	'customization_icon'=> $customization_icon
															);

															echo wp_kses( Wpc_Utilities::product_add_to_cart( $add_cart_args ), Wpc_Utilities::wpc_kses_allowed_tags() );
															?>
															<a href="<?php echo esc_url(get_permalink($product->get_id())); ?>"
																class="<?php echo esc_attr( $class ); ?>">
																	<?php echo wp_kses( $product->get_image('woocommerce_thumbnail'), Wpc_Utilities::wpc_kses_allowed_tags()) ; ?>
															</a>
													</div>
													<?php
											}
									}
									?>
							</div>
					</div>
			<?php
	}

	/**
	 * Normalize the argument bag every newer card partial reads.
	 * Callers only pass what they know about; everything else falls back to the
	 * same defaults the legacy card templates use, so a style file can be as
	 * short as product + permalink.
	 *
	 * @param array $args Raw card arguments.
	 *
	 * @return array Normalized arguments.
	 */
	public static function card_args( $args ) {
		$cafe_settings = wpc_get_option( 'cart_icon' );

		return [
			'product'            => $args['product'] ?? null,
			'permalink'          => $args['permalink'] ?? '',
			'class'              => $args['class'] ?? '',
			'unique_id'          => $args['unique_id'] ?? '',
			'show_thumbnail'     => $args['show_thumbnail'] ?? 'yes',
			'wpc_price_show'     => $args['wpc_price_show'] ?? 'yes',
			'wpc_cart_button'    => $args['wpc_cart_button'] ?? 'yes',
			'show_item_status'   => $args['show_item_status'] ?? 'yes',
			'show_item_label'    => $args['show_item_label'] ?? 'no',
			'wpc_show_desc'      => $args['wpc_show_desc'] ?? 'yes',
			'wpc_desc_limit'     => $args['wpc_desc_limit'] ?? 20,
			'wpc_show_vendor'    => $args['wpc_show_vendor'] ?? 'no',
			// The newer cards show a labelled button, so an empty value falls back
			// to text instead of the icon-only button the legacy styles render.
			'wpc_btn_text'       => ! empty( $args['wpc_btn_text'] ) ? $args['wpc_btn_text'] : __( 'Add to cart', 'wp-cafe' ),
			'customize_btn'      => $args['customize_btn'] ?? '',
			'cart_icon'          => $args['cart_icon'] ?? $cafe_settings,
			'customization_icon' => $args['customization_icon'] ?? '',
			'variant'            => $args['variant'] ?? '',
		];
	}

	/**
	 * Price node for the newer cards.
	 *
	 * Simple products reuse get_price_html() so a sale renders as
	 * <del>old</del> <ins>new</ins> — that is what the strike-through price in
	 * the designs is. Variable products keep the legacy min/max handling because
	 * the wpc_price_show att ('yes'|'min'|'max') has no WooCommerce equivalent.
	 *
	 * The Pro discount module rewrites get_price_html(), so its strike-through
	 * already reaches simple cards for free. Variable cards build their own range
	 * and never call get_price_html(), so the module can't touch them — the range
	 * is discounted here instead (see variable_discount_range()).
	 *
	 * @param WC_Product $product        Product object.
	 * @param string     $wpc_price_show yes|no|min|max.
	 *
	 * @return string Price markup, or empty when prices are hidden.
	 */
	public static function card_price_html( $product, $wpc_price_show ) {
		if ( ! is_object( $product ) || 'no' === $wpc_price_show ) {
			return '';
		}

		if ( 'variable' !== $product->get_type() ) {
			return (string) $product->get_price_html();
		}

		$variation_price = $product->get_variation_prices( true ); // true = tax-adjusted.
		if ( ! is_array( $variation_price ) || empty( $variation_price['price'] ) ) {
			return '';
		}

		$prices = $variation_price['price'];
		$symbol = get_woocommerce_currency_symbol();

		// Keys are variation ids ordered cheapest → priciest; grab them before
		// array_shift/array_pop below consume the array.
		$min_id = (int) array_key_first( $prices );
		$max_id = (int) array_key_last( $prices );

		// One wrapper so a stacked price column keeps "min - max" on one line.
		$markup = '';

		if ( 'yes' === $wpc_price_show || 'min' === $wpc_price_show ) {
			$markup .= '<span class="min_price">' . esc_html( $symbol . array_shift( $prices ) ) . '</span>';
		}
		if ( 'yes' === $wpc_price_show ) {
			$markup .= ' - ';
		}
		if ( 'yes' === $wpc_price_show || 'max' === $wpc_price_show ) {
			$markup .= '<span class="max_price">' . esc_html( $symbol . array_pop( $prices ) ) . '</span>';
		}

		$plain = '<span class="wpc-price-range">' . $markup . '</span>';

		// When the Pro discount module marks this product down, swap in the
		// discounted range; free-only sites keep the plain range.
		$discounted = self::variable_discount_range( $product, $min_id, $max_id, $wpc_price_show );

		return '' !== $discounted ? $discounted : $plain;
	}

	/**
	 * Strike-through range for a variable product when the Pro discount module
	 * applies. Each end of the range is discounted with the module's own math so
	 * the card matches the strike-through it already prints on simple products.
	 * Returns '' when Pro is absent or no rule matches, so the caller keeps the
	 * plain range.
	 *
	 * @param WC_Product $product        Variable product.
	 * @param int        $min_id         Cheapest variation id.
	 * @param int        $max_id         Priciest variation id.
	 * @param string     $wpc_price_show yes|min|max.
	 *
	 * @return string Discounted-range markup, or '' to fall back to the plain range.
	 */
	private static function variable_discount_range( $product, $min_id, $max_id, $wpc_price_show ) {
		if ( ! class_exists( '\WpCafePro\FoodOrder\Discount\Discount' ) ) {
			return '';
		}

		$prices = $product->get_variation_prices( true );
		if ( empty( $prices['price'] ) ) {
			return '';
		}

		$min = (float) reset( $prices['price'] );
		$max = (float) end( $prices['price'] );

		// calculate_product_discount() reads the variation's own price, so each
		// end is cut correctly for both percentage and fixed rules.
		$cut_min = (float) \WpCafePro\FoodOrder\Discount\Discount::calculate_product_discount( $product->get_id(), $min_id );
		$cut_max = (float) \WpCafePro\FoodOrder\Discount\Discount::calculate_product_discount( $product->get_id(), $max_id );

		if ( $cut_min <= 0 && $cut_max <= 0 ) {
			return '';
		}

		$original   = self::price_range_markup( $min, $max, $wpc_price_show );
		$discounted = self::price_range_markup( max( 0, $min - $cut_min ), max( 0, $max - $cut_max ), $wpc_price_show );

		// Reuse the module's own price classes so the look matches simple cards.
		return '<div class="wpc-single-price-display">'
			. '<span class="wpc-single-original-price" style="text-decoration: line-through;">' . $original . '</span>'
			. '<span class="wpc-single-discounted-price">' . $discounted . '</span>'
			. '</div>';
	}

	/**
	 * Build a "min - max" range with wc_price(), honouring wpc_price_show.
	 *
	 * @param float  $min            Low price.
	 * @param float  $max            High price.
	 * @param string $wpc_price_show yes|min|max.
	 *
	 * @return string
	 */
	private static function price_range_markup( $min, $max, $wpc_price_show ) {
		$markup = '';

		// wc_price() returns markup, so filter it here rather than at the echo,
		// where kses would have to trust the whole assembled string.
		if ( 'yes' === $wpc_price_show || 'min' === $wpc_price_show ) {
			$markup .= '<span class="min_price">' . wp_kses_post( wc_price( $min ) ) . '</span>';
		}
		if ( 'yes' === $wpc_price_show ) {
			$markup .= ' - ';
		}
		if ( 'yes' === $wpc_price_show || 'max' === $wpc_price_show ) {
			$markup .= '<span class="max_price">' . wp_kses_post( wc_price( $max ) ) . '</span>';
		}

		return '<span class="wpc-price-range">' . $markup . '</span>';
	}

	/**
	 * Add-to-cart markup for the newer cards, with a text label.
	 *
	 * The customize/variation popup path returns an icon-only button and takes
	 * no button-text argument, so the label is added here rather than changing
	 * the shared filter signature every style depends on.
	 *
	 * @param array $wpc_card Normalized card arguments.
	 *
	 * @return string
	 */
	public static function card_cart_button( $wpc_card ) {
		$markup = Wpc_Utilities::product_add_to_cart(
			[
				'product'            => $wpc_card['product'],
				'cart_button'        => $wpc_card['wpc_cart_button'],
				'wpc_btn_text'       => $wpc_card['wpc_btn_text'],
				'customize_btn'      => $wpc_card['customize_btn'],
				'widget_id'          => $wpc_card['unique_id'],
				'cart_icon'          => $wpc_card['cart_icon'],
				'customization_icon' => $wpc_card['customization_icon'],
			]
		);

		$label = $wpc_card['wpc_btn_text'];

		if ( '' === $markup || '' === $label || false !== strpos( $markup, 'add-cart-text' ) ) {
			return $markup;
		}

		$position = strpos( $markup, '</a>' );
		if ( false === $position ) {
			return $markup;
		}

		return substr_replace(
			$markup,
			'<span class="add-cart-text">' . esc_html( $label ) . '</span>',
			$position,
			0
		);
	}

	/**
	 * Render the row card (food menu list style-4, tab style-7).
	 *
	 * @param array $args See card_args().
	 *
	 * @return void
	 */
	public static function wpc_food_menu_card_row( $args ) {
		self::render_card_partial( 'card-row.php', $args );
	}

	/**
	 * Render the grid card (food menu tab style-6 and style-8).
	 *
	 * @param array $args See card_args().
	 *
	 * @return void
	 */
	public static function wpc_food_menu_card_grid( $args ) {
		self::render_card_partial( 'card-grid.php', $args );
	}

	/**
	 * Include a card partial with normalized arguments.
	 * File name comes from this class only — never from request data.
	 *
	 * @param string $file Partial file name inside widgets/card-style/.
	 * @param array  $args Raw card arguments.
	 *
	 * @return void
	 */
	private static function render_card_partial( $file, $args ) {
		$wpc_card = self::card_args( $args );

		if ( ! is_object( $wpc_card['product'] ) ) {
			return;
		}

		$path = trailingslashit( wpcafe()->plugin_directory ) . 'widgets/card-style/' . $file;

		if ( file_exists( $path ) ) {
			include $path;
		}
	}

	/**
	 * Food tab list
	 *
	 * The second parameter is optional so every existing caller keeps the
	 * original underline nav markup unchanged. Newer tab styles pass a nav
	 * variant; the tab anchor keeps its .wpc-tab-a / data-id / data-cat_id
	 * contract in every variant, so the delegated click handler in
	 * wpc-public.js works without changes.
	 *
	 * @param array $food_menu_tabs Tabs from Wpc_Utilities::get_tab_array_from_category().
	 * @param array $args {
	 *     @type string $nav default|pills-end|rail|pills-thumb.
	 * }
	 *
	 * @return void
	 */
	public static function render_food_menu_tab_nav( $food_menu_tabs, $args = [] ){
			$args = wp_parse_args(
					$args,
					[
							'nav' => 'default',
					]
			);

			$allowed_navs = [ 'default', 'pills-end', 'rail', 'pills-thumb' ];
			$nav          = in_array( $args['nav'], $allowed_navs, true ) ? $args['nav'] : 'default';

			if ( 'default' !== $nav ) {
					self::render_food_menu_tab_nav_v2( $food_menu_tabs, $nav );
					return;
			}
			?>
			<ul class="wpc-nav">
			<?php
			if( is_array( $food_menu_tabs ) && count( $food_menu_tabs )>0 ){
					foreach ($food_menu_tabs as $tab_key => $value) {
							$active_class = (($tab_key == array_keys($food_menu_tabs)[0]) ? 'wpc-active' : ' ');
							$cat_id       = isset($value['post_cats'][0] ) ? intval( $value['post_cats'][0] ) : 0 ;
							?>
							<li>
									<a href='#' class='wpc-tab-a <?php echo esc_attr($active_class); ?>' data-id='tab_<?php echo intval($tab_key); ?>'
									data-cat_id='<?php echo esc_attr( $cat_id ); ?>'>
											<span><?php echo esc_html($value['tab_title']); ?></span>
									</a>
							</li>
							<?php
					}
			}
			?>
			</ul>
			<?php
	}

	/**
	 * Pill / rail nav used by tab styles 6, 7 and 8.
	 *
	 * @param array  $food_menu_tabs Tabs, optionally carrying 'count' and 'thumb_id'.
	 * @param string $nav            Validated nav variant.
	 *
	 * @return void
	 */
	private static function render_food_menu_tab_nav_v2( $food_menu_tabs, $nav ) {
			if ( ! is_array( $food_menu_tabs ) || empty( $food_menu_tabs ) ) {
					return;
			}

			$nav_slug   = sanitize_html_class( $nav );
			$first_key  = array_keys( $food_menu_tabs )[0];
			?>
			<div class="wpc-tab-nav-v2 wpc-tab-nav-v2--<?php echo esc_attr( $nav_slug ); ?>">
					<ul class="wpc-nav wpc-nav--<?php echo esc_attr( $nav_slug ); ?>">
							<?php foreach ( $food_menu_tabs as $tab_key => $value ) : ?>
									<?php
									$active_class = ( $tab_key === $first_key ) ? 'wpc-active' : '';
									$cat_id       = isset( $value['post_cats'][0] ) ? intval( $value['post_cats'][0] ) : 0;
									$thumb_id     = isset( $value['thumb_id'] ) ? (int) $value['thumb_id'] : 0;
									$has_count    = isset( $value['count'] );
									?>
									<li>
											<a href="#" class="wpc-tab-a <?php echo esc_attr( $active_class ); ?>"
												data-id="tab_<?php echo intval( $tab_key ); ?>"
												data-cat_id="<?php echo esc_attr( $cat_id ); ?>">

													<?php if ( 'pills-thumb' === $nav ) : ?>
															<span class="wpc-tab-thumb<?php echo $thumb_id ? '' : ' wpc-tab-thumb--placeholder'; ?>">
																	<?php
																	if ( $thumb_id ) {
																			echo wp_kses(
																					wp_get_attachment_image( $thumb_id, 'thumbnail', false, [ 'alt' => '', 'loading' => 'lazy' ] ),
																					Wpc_Utilities::wpc_kses_allowed_tags()
																			);
																	}
																	?>
															</span>
													<?php endif; ?>

													<span class="wpc-tab-title"><?php echo esc_html( $value['tab_title'] ); ?></span>

													<?php if ( $has_count ) : ?>
															<span class="wpc-tab-count"><?php echo esc_html( zeroise( (int) $value['count'], 2 ) ); ?></span>
													<?php endif; ?>
											</a>
									</li>
							<?php endforeach; ?>
					</ul>
			</div>
			<?php
	}

	/**
	 * Render Food Menu Tab Holder Markup
	 *
	 * @param [type] $active_class
	 * @param [type] $content_key
	 * @param [type] $cat_id
	 * @param [type] $unique_id
	 * @param [type] $products
	 * @param [type] $style
	 * @param [type] $wpc_cart_button
	 * @param [type] $wpc_show_desc
	 * @param [type] $show_thumbnail
	 * @param [type] $title_link_show
	 * @param [type] $show_item_status
	 * @param [type] $wpc_desc_limit
	 * 
	 * @since 1.3.3
	 * 
	 * @return html markup
	 */
	public static function render_food_menu_tab_product_block( $args ){
			$active_class     = $args['active_class']     ?? '';
			$content_key      = $args['content_key']      ?? 0;
			$cat_id           = $args['cat_id']           ?? '';
			$unique_id        = $args['unique_id']        ?? '';
			$style            = $args['style']            ?? 'style-1';
			$products         = $args['products']         ?? array();
			$wpc_cart_button  = $args['wpc_cart_button']  ?? 'yes';
			$wpc_price_show   = $args['wpc_price_show']   ?? 'yes';
			$wpc_show_desc    = $args['wpc_show_desc']    ?? 'yes';
			$show_thumbnail   = $args['show_thumbnail']   ?? 'yes';
			$title_link_show  = $args['title_link_show']  ?? 'yes';
			$show_item_status = $args['show_item_status'] ?? 'yes';
			$show_item_label  = $args['show_item_label']  ?? 'no';
			$wpc_desc_limit   = $args['wpc_desc_limit']   ?? 15;
			$wpc_show_vendor  = $args['wpc_show_vendor']  ?? 'no';
			$wpc_menu_col     = $args['wpc_menu_col']     ?? 6;
			$current_page     = isset( $args['current_page'] ) ? max( 1, (int) $args['current_page'] ) : 1;
			$total_pages      = isset( $args['total_pages'] ) ? (int) $args['total_pages'] : 0;
			$show_pagination  = isset( $args['show_pagination'] ) ? $args['show_pagination'] : 'yes';
			$product_data     = isset( $args['product_data'] ) && is_array( $args['product_data'] ) ? $args['product_data'] : array();
			// Extras the style-6/7/8 cards read; harmless for the older styles.
			$grid_columns     = $args['grid_columns']     ?? 3;
			$wpc_menu_count   = $args['wpc_menu_count']   ?? count( $products );
			$wpc_result_total = $args['total_products']   ?? 0;
			$wpc_current_page = $current_page;
			?>
			<div class='wpc-tab <?php echo esc_attr($active_class); ?>' data-id='tab_<?php echo intval($content_key); ?>' data-cat_id='<?php echo  esc_attr($cat_id);?>'>
					<div class="tab_template_<?php echo esc_attr( $cat_id.'_'.$unique_id );?>"></div>
					<div class="template_data_<?php echo esc_attr( $cat_id.'_'.$unique_id );?>">
							<div class="wpc-paginated-products"
								data-shortcode="food_menu_tab"
								data-cat_id="<?php echo esc_attr( $cat_id ); ?>"
								data-current="<?php echo esc_attr( $current_page ); ?>"
								data-product_data="<?php echo esc_attr( wp_json_encode( $product_data ) ); ?>">
								<div class="wpc-paginated-products-body">
									<?php
									$is_pro_active = function_exists('wpcafe_pro') || defined('WPCAFE_PRO_FILE');
									$style_path = wpcafe()->plugin_directory . "/widgets/wpc-food-menu-tab/style/{$style}.php";

									if ( !file_exists( $style_path ) && $is_pro_active && function_exists('wpcafe_pro') ) {
										$pro_style_path = wpcafe_pro()->plugin_directory . "/widgets/food-menu-tab/style/{$style}.php";
										if ( file_exists( $pro_style_path ) ) {
											$style_path = $pro_style_path;
										}
									}

							ob_start();
									include $style_path;
							$style_markup = ob_get_clean();

							// The newer cards print WooCommerce sale markup (<ins>/<bdi>);
							// the legacy list stays as-is so older styles render unchanged.
							$kses_tags = in_array( $style, [ 'style-6', 'style-7', 'style-8' ], true )
									? Wpc_Utilities::wpc_kses_card_tags()
									: Wpc_Utilities::wpc_kses_allowed_tags();

							echo wp_kses( $style_markup, $kses_tags );
									?>
								</div>
								<?php
								if ( 'yes' === $show_pagination ) {
									echo \WpCafe\Utils\Wpc_Utilities::render_menu_pagination( $current_page, $total_pages ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								?>
							</div>
					</div>
			</div><!-- Tab pane 1 end -->
			<?php
	}

	/**
	 * Inject product label markup into legacy pro tab styles that render only tag terms.
	 *
	 * @param array $args Injection arguments.
	 *
	 * @return string
	 */
	private static function maybe_inject_tab_product_labels( $args ) {
			$style_markup    = $args['style_markup'] ?? '';
			$style_path      = $args['style_path'] ?? '';
			$products        = $args['products'] ?? [];
			$show_item_label = $args['show_item_label'] ?? 'no';

			if ( 'yes' !== $show_item_label || empty( $style_markup ) || empty( $products ) || ! is_array( $products ) ) {
					return $style_markup;
			}

			if ( ! self::is_pro_food_menu_tab_style( $style_path ) ) {
					return $style_markup;
			}

			$label_markup_by_product = [];
			foreach ( $products as $product ) {
					if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
							$label_markup_by_product[] = '';
							continue;
					}

					ob_start();
					Wpc_Utilities::wpc_product_labels( $product->get_id() );
					$label_markup_by_product[] = ob_get_clean();
			}

			if ( ! array_filter( $label_markup_by_product ) ) {
					return $style_markup;
			}

			$index = 0;
			$updated_markup = preg_replace_callback(
					'/(<div[^>]*class=(["\'])[^"\']*\bwpc-menu-tag-wrap\b[^"\']*\2[^>]*>)/i',
					function ( $matches ) use ( &$index, $label_markup_by_product ) {
							$label_markup = $label_markup_by_product[ $index ] ?? '';
							$index++;

							return $matches[1] . $label_markup;
					},
					$style_markup
			);

			return is_string( $updated_markup ) ? $updated_markup : $style_markup;
	}

	/**
	 * Check whether a resolved style path points to a pro food-menu-tab style.
	 *
	 * @param string $style_path Style file path.
	 *
	 * @return bool
	 */
	private static function is_pro_food_menu_tab_style( $style_path ) {
			if ( empty( $style_path ) || ! function_exists( 'wpcafe_pro' ) ) {
					return false;
			}

			$pro_style_base = trailingslashit( wpcafe_pro()->plugin_directory ) . 'widgets/food-menu-tab/style/';

			return 0 === strpos( wp_normalize_path( $style_path ), wp_normalize_path( $pro_style_base ) );
	}

	public static function modal_markup( $wpc_locations, $store_id = null ){
			?>
			<div id="wpc_location_modal" class="wpc_modal">
					<!-- Modal content -->
					<div class="modal-content">
							<?php 
							if(!is_null($store_id)){
									?>
									<input type='hidden' class="wpc-location-store" name='wpc-store-id' value="<?php echo esc_attr( $store_id );?>"/>
									<?php
							}
							?>
							<select name="wpc-location" class="wpc-location">
									<?php
									// get wpcafe locations
									foreach ( $wpc_locations as $key => $value) {
											?> 
											<option value="<?php echo esc_html( $key ) ?>" <?php echo count($wpc_locations) <= 2? "selected='selected'" : "" ?> ><?php echo esc_html( $value ) ?></option>  
											<?php 
									}
									?>
							</select>
							<button class="wpc-select-location wpc-btn wpc-btn-primary"><?php echo esc_html__( "Ok", 'wp-cafe' );?></button>
							<button class="wpc-close wpc-btn"> X </button>
					</div>
			</div>
			<?php
	}

}
