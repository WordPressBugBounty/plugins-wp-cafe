<?php
namespace WpCafe\Integrations\Pointics;

if ( ! defined( 'ABSPATH' ) ) exit;

use Pointics\Storefront\Account_Url;
use Pointics\Storefront\Product_Earn_Badge;
use Pointics\Storefront\Storefront_Assets;
use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Utils\Wpc_Utilities;

/**
 * "Earn 30 points" under each WP Cafe menu card price.
 *
 * Uses Pointics' own `shop` badge: its renderer prints nothing for surfaces it
 * does not know, so a WP Cafe surface name would silently show no badge.
 *
 * @package WpCafe/Integrations/Pointics
 */
class Menu_Badge implements Hookable_Service_Contract {
    /**
     * True only while a menu card badge is being built, so the shorter copy
     * never leaks into Pointics' own shop pages.
     *
     * @var bool
     */
    private $rendering = false;

    /**
     * True once any menu card asked for a badge in this request.
     *
     * @var bool
     */
    private $used = false;

    /**
     * Register hooks.
     *
     * @return void
     */
    public function register() {
        add_action( 'wpcafe_menu_card_after_price', [ $this, 'render' ], 10, 2 );
        add_filter( 'pointics_storefront_badge_text', [ $this, 'shorten_text' ], 10, 3 );
        add_filter( 'pointics_storefront_has_markup', [ $this, 'has_markup' ] );
        // Priority 1: before wp_print_footer_scripts prints late styles.
        add_action( 'wp_footer', [ $this, 'enqueue_styles' ], 1 );
    }

    /**
     * Print the badge for one card.
     *
     * @param \WC_Product $product  Card product.
     * @param array       $settings Menu settings; reads show_points.
     * @return void
     */
    public function render( $product, $settings ) {
        if ( 'no' === ( $settings['show_points'] ?? '' ) ) {
            return;
        }

        $this->used      = true;
        $this->rendering = true;

        try {
            $html = pointics_container()->make( Product_Earn_Badge::class )->html( (int) $product->get_id(), 'shop' );
        } finally {
            $this->rendering = false;
        }

        // No rule earns: Pointics returns an empty placeholder for its hydration
        // script, which menu pages never load, so print nothing instead.
        if ( '' === $html || false !== strpos( $html, 'pointics-earn-badge--empty' ) ) {
            return;
        }

        if ( ! is_user_logged_in() ) {
            $html = $this->link_sign_in( $html );
        }

        // WP Cafe's allowed tags drop Pointics' data-* hydrate attributes; the
        // hydration script does not load on menu pages, so nothing is lost.
        echo wp_kses( '<div class="wpc-pointics-badge">' . $html . '</div>', Wpc_Utilities::wpc_kses_allowed_tags() );
    }

    /**
     * Drop "when you order" on menu cards: the card is already an order button.
     *
     * Matches Pointics' whole translated sentences, so it works in any language
     * that translates both plugins.
     *
     * @param string $text    Badge text; the figure is wrapped in <strong>.
     * @param mixed  $quote   Pointics Earn_Quote.
     * @param string $surface Pointics surface.
     * @return string
     */
    public function shorten_text( $text, $quote, $surface ) {
        if ( ! $this->rendering ) {
            return $text;
        }

        $pairs = [
            // translators: %s: points a customer can earn, e.g. "25 points".
            $this->pointics_string( 'Sign in to earn %s when you order' ) => __( 'Sign in to earn %s', 'wp-cafe' ),
            // translators: %s: points a customer can earn, e.g. "25 points".
            $this->pointics_string( 'Earn %s when you order' )            => __( 'Earn %s', 'wp-cafe' ),
        ];

        foreach ( $pairs as $long => $short ) {
            if ( preg_match( $this->sentence_pattern( $long ), (string) $text, $match ) ) {
                return sprintf( $short, $match[1] );
            }
        }

        // "Earn up to 30 points and 50 points when you order": keep the range, drop the tail.
        $tail = $this->pointics_string( 'and %s when you order' );

        return (string) preg_replace_callback(
            '/ ' . str_replace( '%s', '(<strong>.+?<\/strong>)', preg_quote( $tail, '/' ) ) . '$/su',
            static function ( $m ) {
                /* translators: %s: points, bolded. */
                return ' ' . sprintf( __( 'and %s', 'wp-cafe' ), $m[1] );
            },
            (string) $text
        );
    }

    /**
     * One of Pointics' badge sentences, translated as Pointics prints it.
     *
     * Looked up through a variable domain on purpose: `npm run fix-textdomain`
     * rewrites every literal domain in this plugin to wp-cafe, which would
     * stop these matching Pointics' output.
     *
     * @param string $text Pointics' source string.
     * @return string
     */
    private function pointics_string( $text ) {
        $domain = 'pointics';

        // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.NonSingularStringLiteralDomain -- Pointics owns and translates these strings.
        return __( $text, $domain );
    }

    /**
     * Ask Pointics for its badge stylesheet once a menu card used it.
     *
     * @param bool $has_markup Current answer.
     * @return bool
     */
    public function has_markup( $has_markup ) {
        return $has_markup || $this->used;
    }

    /**
     * Late enqueue for menus rendered after wp_head (shortcodes, widgets, AJAX hosts).
     *
     * @return void
     */
    public function enqueue_styles() {
        // wpc-card-core is enqueued by every menu render, including hosts of AJAX menus.
        if ( ! $this->used && ! wp_style_is( 'wpc-card-core', 'enqueued' ) ) {
            return;
        }

        $this->used = true;

        pointics_container()->make( Storefront_Assets::class )->enqueue();
    }

    /**
     * Turn the badge's leading "Sign in" into a link, and remove Pointics'
     * separate "Sign in to start earning" link so the card asks once.
     *
     * Done here, not in shorten_text(): Pointics runs wp_kses with only span and
     * strong after that filter, which would strip the link.
     *
     * @param string $html Badge markup.
     * @return string
     */
    private function link_sign_in( $html ) {
        $html = (string) preg_replace( '/\s*<a class="pointics-earn-cta"[^>]*>.*?<\/a>\s*$/su', '', $html );
        $url  = (string) pointics_container()->make( Account_Url::class )->resolve();

        if ( '' === $url ) {
            return $html;
        }

        $sign_in = __( 'Sign in', 'wp-cafe' );
        // Own class: Pointics styles pointics-earn-cta as a separate, lighter line.
        $link    = '<a class="wpc-pointics-sign-in" href="' . esc_url( $url ) . '">' . esc_html( $sign_in ) . '</a>';

        return (string) preg_replace_callback(
            '/(<span class="pointics-earn-badge"[^>]*>)' . preg_quote( $sign_in, '/' ) . '/u',
            static function ( $m ) use ( $link ) {
                return $m[1] . $link;
            },
            $html,
            1
        );
    }

    /**
     * Regex for one translated sentence with a single %s.
     *
     * @param string $sentence Translated format string.
     * @return string
     */
    private function sentence_pattern( $sentence ) {
        return '/^' . str_replace( '%s', '(.+)', preg_quote( $sentence, '/' ) ) . '$/su';
    }
}
