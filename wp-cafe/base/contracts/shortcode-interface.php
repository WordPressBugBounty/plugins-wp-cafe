<?php
namespace WpCafe\Contracts;

/**
 * Shortcode interface
 * 
 * @package wpcafe/contracts
 */
interface Shortcode_Interface {
    /**
     * Shortcode tag name
     *
     * @return  string
     */
    public function tag();

    /**
     * Render content
     *
     * @param   array  $atts     Shortcode attributes
     * @param   string  $content  Shortcode content
     *
     * @return  string
     */
    public function render($atts = [], $content = null);
}
