<?php
/**
 * Product Popup Service
 *
 * Owns the variation/customize popup end-to-end so Free is functional on its
 * own (variable products, third-party addon plugins, etc.). Pro previously
 * provided everything: AJAX handler, modal markup, action chain, opener JS,
 * and the customize button for variable/grouped products. Pro now defers to
 * this class and only contributes its richer styles + slider/loadmore
 * widget bindings.
 *
 * Responsibilities:
 *  - Register the AJAX handler that returns the product's single-product
 *    HTML for the modal.
 *  - Wire the WooCommerce `variation/*` action chain that the AJAX template
 *    calls to render rating/price/excerpt/add-to-cart.
 *  - Print the modal shell once in `wp_head`.
 *  - Render the customize button for variable / grouped products via the
 *    `wpcafe/shortcode/variation` filter.
 *  - Localise the `wpc_obj` JS object (ajax url + popup nonce) on
 *    `wpc-public` so the opener JS in `wpc-public.js` has what it needs.
 *
 * @package WpCafe\FoodOrder\Shortcodes
 * @since   3.0.x
 */

namespace WpCafe\FoodOrder\Shortcodes;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Utils\Wpc_Utilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Product_Popup_Service
 */
class Product_Popup_Service implements Hookable_Service_Contract {

	/**
	 * AJAX action — kept stable (typo and all) for back-compat with Pro's
	 * widgets.js and any third-party openers (Optiontics, etc.).
	 */
	private const AJAX_ACTION = 'variaion_product_popup_content';

	/**
	 * AJAX action that adds the popup product to the cart.
	 *
	 * Variable/grouped products cannot go through WooCommerce's own
	 * `wc-ajax=add_to_cart`: that endpoint derives the variation attributes
	 * from the variation post, so an "any" attribute (stored empty) comes back
	 * blank and WC rejects the add with "choose product options". This handler
	 * instead passes the attributes the customer actually picked, so any-value
	 * attributes get filled.
	 */
	private const AJAX_ADD_ACTION = 'wpcafe_popup_add_to_cart';

	/**
	 * Nonce action name. Stable string used by Pro's existing JS.
	 */
	private const NONCE_ACTION = 'wpcafe_product_popup_nonce';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		// AJAX handler — runs for both logged-in and anonymous users.
		add_action( 'wp_ajax_' . self::AJAX_ACTION,        [ $this, 'ajax_popup_content' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, [ $this, 'ajax_popup_content' ] );

		// Add-to-cart handler for the popup (variable/grouped products).
		add_action( 'wp_ajax_' . self::AJAX_ADD_ACTION,        [ $this, 'ajax_add_to_cart' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ADD_ACTION, [ $this, 'ajax_add_to_cart' ] );

		// Customize button HTML for variable / grouped products.
		add_filter( 'wpcafe/shortcode/variation', [ $this, 'variation_button_html' ], 10, 4 );

		// Modal shell — printed once per page in <head>.
		add_action( 'wp_head', [ $this, 'print_modal_shell' ] );

		// Variation action chain consumed inside the AJAX template.
		add_action( 'variation/product_title',      'woocommerce_template_single_title',       5  );
		add_action( 'variation/product_thumbnails', 'woocommerce_show_product_images',         20 );
		add_action( 'variation/popup_content',      'woocommerce_template_single_rating',      10 );
		add_action( 'variation/popup_content',      'woocommerce_template_single_price',       15 );
		add_action( 'variation/popup_content',      'woocommerce_show_product_sale_flash',     10 );
		add_action( 'variation/popup_content',      'woocommerce_template_single_excerpt',     20 );
		add_action( 'variation/popup_content',      'woocommerce_template_single_add_to_cart', 30 );

		// Localise wpc_obj on the existing wpc-public handle (registered by
		// Frontend_Assets). Runs late so it cannot fight with other
		// localizations attached to the same handle.
		add_action( 'wp_enqueue_scripts', [ $this, 'localize' ], 20 );
	}

	// =========================================================================
	// AJAX
	// =========================================================================

	/**
	 * Handle the popup AJAX request.
	 *
	 * Responds with the same JSON envelope shape Pro used so legacy openers
	 * (and Optiontics' opener) continue to work unchanged.
	 *
	 * @return void
	 */
	public function ajax_popup_content(): void {
		check_ajax_referer( self::NONCE_ACTION, 'security' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
		$wpc_action = isset( $_POST['wpc_action'] ) ? sanitize_text_field( wp_unslash( $_POST['wpc_action'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;

		if ( 'variation_popup' !== $wpc_action || $product_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'invalid_request' ] );
			return;
		}

		$product_post = get_post( $product_id );
		if ( ! ( $product_post instanceof \WP_Post )
			|| 'product' !== $product_post->post_type
			|| 'publish' !== $product_post->post_status ) {
			wp_send_json_error( [ 'message' => 'not_found' ] );
			return;
		}

		ob_start();
		$this->render_popup_template( $product_id );

		wp_send_json_success( [
			'success' => 1,
			'message' => 'success',
			// Matches Pro's response shape (double-encoded data) for drop-in
			// client compatibility.
			'data'    => wp_json_encode( ob_get_clean() ),
		] );
	}

	/**
	 * Add the popup product to the cart.
	 *
	 * Used for variable/grouped products. Passes the attributes the customer
	 * picked so WooCommerce can fill "any" variation attributes that it cannot
	 * derive from the variation post. add_to_cart() fires
	 * `woocommerce_add_cart_item_data`, so addon plugins (Optiontics) still read
	 * their own $_POST fields and attach their selections.
	 *
	 * @return void Sends a JSON response (refreshed fragments on success).
	 */
	public function ajax_add_to_cart(): void {
		check_ajax_referer( self::NONCE_ACTION, 'security' );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified above.
		$product_id   = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( wp_unslash( $_POST['variation_id'] ) ) : 0;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wc_stock_amount() coerces this to a safe numeric quantity.
		$quantity     = empty( $_POST['quantity'] ) ? 1 : wc_stock_amount( wp_unslash( $_POST['quantity'] ) );

		if ( $product_id <= 0 && $variation_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid product.', 'wp-cafe' ) ] );
			return;
		}

		// A variation id may arrive in either field; normalise to parent + variation.
		$resolved = wc_get_product( $variation_id > 0 ? $variation_id : $product_id );
		if ( $resolved instanceof \WC_Product && $resolved->is_type( 'variation' ) ) {
			$variation_id = $resolved->get_id();
			$product_id   = $resolved->get_parent_id();
		}

		// Pull the chosen variation attributes straight from POST. These fill
		// "any" attributes WC stores empty on the variation, so the add passes.
		$variation = [];
		foreach ( array_keys( $_POST ) as $key ) {
			$key = (string) $key;
			if ( 0 === strpos( $key, 'attribute_' ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- $key is enumerated from array_keys( $_POST ), so it is always defined.
				$variation[ sanitize_title( wp_unslash( $key ) ) ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
			}
		}
		// phpcs:enable

		$added = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation );

		if ( false === $added ) {
			$messages = [];
			if ( function_exists( 'wc_get_notices' ) ) {
				foreach ( wc_get_notices( 'error' ) as $notice ) {
					$messages[] = is_array( $notice ) ? ( $notice['notice'] ?? '' ) : (string) $notice;
				}
				wc_clear_notices();
			}
			wp_send_json_error( [
				'message'     => trim( implode( ' ', array_filter( $messages ) ) ) ?: __( 'Could not add to cart.', 'wp-cafe' ),
				'product_url' => get_permalink( $product_id ),
			] );
			return;
		}

		// Echoes { fragments, cart_hash } and dies — the same shape the popup
		// opener JS already consumes from WC's own add-to-cart response.
		\WC_AJAX::get_refreshed_fragments();
	}

	/**
	 * Render the product template inside the popup.
	 *
	 * @param  int $product_id Product ID.
	 * @return void
	 */
	private function render_popup_template( int $product_id ): void {
		wp(
			[
				'p'         => $product_id,
				'post_type' => 'product',
			]
		);

		while ( have_posts() ) :
			the_post();
			?>
			<div id="product-<?php echo esc_attr( (string) $product_id ); ?>" <?php post_class( 'product wpc-row' ); ?>>
				<div class="wpc-col-lg-6 variation_product_image">
					<?php
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- shared cross-plugin hook, consumed by wpcafe-pro's product-popup-ajax.php and hooks.php; renaming breaks that integration.
					do_action( 'variation/product_thumbnails' );
					?>
				</div>
				<div class="wpc-col-lg-6">
					<div class="wpc-single-content summary entry-summary">
						<h2 class="product_title entry-title"><?php the_title(); ?></h2>
						<?php
						// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- shared cross-plugin hook, consumed by wpcafe-pro's product-popup-ajax.php and hooks.php; renaming breaks that integration.
						do_action( 'variation/popup_content' );
						?>
					</div>
				</div>
			</div>
			<?php
		endwhile;
	}

	// =========================================================================
	// BUTTON FILTER — variable / grouped products
	// =========================================================================

	/**
	 * Render the Customize button HTML for variable / grouped products.
	 *
	 * Mirrors the previous Pro filter behaviour. Simple-product addon
	 * integrations (Optiontics, etc.) attach to the same `wpcafe/shortcode/simple`
	 * surface separately.
	 *
	 * @param  mixed       $product            WC_Product or fallback.
	 * @param  string      $customize_btn      Button text/icon.
	 * @param  string      $unique_id          Widget unique ID.
	 * @param  string      $customization_icon Icon class.
	 * @return string
	 */
	public function variation_button_html( $product, string $customize_btn = '', string $unique_id = '', $customization_icon = 'wpcafe-customize' ): string {
		if ( ! ( $product instanceof \WC_Product ) ) {
			return '';
		}

		if ( ! $product->is_in_stock() ) {
			return '';
		}

		$type = $product->get_type();
		if ( 'variable' !== $type && 'grouped' !== $type ) {
			return '';
		}

		// Variation price is computed for display side-effects elsewhere.
		if ( method_exists( '\WpCafe\Utils\Wpc_Utilities', 'get_variation_price' ) ) {
			Wpc_Utilities::get_variation_price( $product );
		}

		return sprintf(
			'<div class="wpc-menu-footer">
				<div class="wpc-customize-btn">
					<div class="wpc-add-to-cart">
						<a href="#" id="product_popup%1$d%2$s" class="customize_button" data-product_id="%1$d">
							%3$s
							%4$s
						</a>
					</div>
				</div>
			</div>',
			(int) $product->get_id(),
			esc_attr( $unique_id ),
			// Button text may already contain HTML — let upstream filter
			// decide. Mirrors prior Pro behaviour.
			$customize_btn, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			// Popup icon: new menu_popup_icon setting (array) → SVG/img, else
			// legacy font class. Escaped inside render_button_icon().
			Wpc_Utilities::render_button_icon( $customization_icon )
		);
	}

	// =========================================================================
	// MODAL SHELL
	// =========================================================================

	/**
	 * Print the empty modal markup once per page.
	 *
	 * @return void
	 */
	public function print_modal_shell(): void {
		static $printed = false;
		if ( $printed ) {
			return;
		}
		$printed = true;
		// The shell prints sitewide, but its overlay styling (incl. display:none)
		// lives in wpc-public.css, which only loads where WP Cafe renders. Inline
		// display:none keeps it hidden on plain pages too; the popup opener
		// (jQuery fadeIn) overrides this when a card opens it.
		?>
		<div class="wpc-product-popup-content" id="popup_wrapper" style="display:none;">
			<div class="wpc-popup-wrap" id="product_popup">
				<div class="wpc-popup-wrap-inner">
					<button class="wpc-close wpc-btn" type="button" aria-label="<?php esc_attr_e( 'Close', 'wp-cafe' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
						<line x1="18" y1="6" x2="6" y2="18"></line>
						<line x1="6" y1="6" x2="18" y2="18"></line>
					</svg>
				</button>
					<div class="wpc_variation_popup_content"></div>
				</div>
			</div>
		</div>
		<?php
	}

	// =========================================================================
	// LOCALIZATION
	// =========================================================================

	/**
	 * Attach `wpc_obj` to the `wpc-popup` script.
	 *
	 * The popup opener JS lives in wpc-popup.js (FE2 split from wpc-public.js),
	 * so the ajax url + nonce it reads must ride on that handle. Pro registers
	 * the same global on its own bundle; when both run, Pro's later
	 * localization simply overwrites with identical values. Free guarantees the
	 * global exists even when Pro is absent.
	 *
	 * @return void
	 */
	public function localize(): void {
		if ( ! wp_script_is( 'wpc-popup', 'registered' ) ) {
			return;
		}

		wp_localize_script(
			'wpc-popup',
			'wpc_obj',
			[
				'ajax_url'            => admin_url( 'admin-ajax.php' ),
				'product_popup_nonce' => wp_create_nonce( self::NONCE_ACTION ),
			]
		);
	}
}
