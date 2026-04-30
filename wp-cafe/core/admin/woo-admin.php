<?php
namespace WpCafe\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract; 
/**
 * WooCommerce Admin Integration class
 *
 * @package WpCafe/WooCommerce
 */
class WooCommerce_Admin implements Hookable_Service_Contract {
    /**
     * Register hooks
     *
     * @return  void
     */
    public function register() {
        // Load custom sidebar for WooCommerce
        if ( class_exists( 'WooCommerce' ) ) {
            add_filter( 'admin_body_class', [ $this, 'wpc_add_wpcafe_body_class' ] );
            add_action( 'admin_enqueue_scripts', [ $this, 'wpc_enqueue_link_param_script' ] );
            add_action( 'admin_head', [ $this, 'wpc_woocommerce_custom_sidebar' ] );
            add_filter( 'redirect_post_location', [ $this, 'wpcafe_keep_param_after_save' ] );
            add_action( 'wp_redirect', [ $this, 'wpc_preserve_wpcafe_redirect' ], 1 );
        }
    }

    /**
     * Check if current page is WooCommerce admin page with wpcafe parameter
     *
     * @return bool
     */
    private function is_wpcafe_woocommerce_page() {
        // Check if wpcafe parameter is present
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin list-table filter, capability-gated
        if ( ! isset( $_GET['wpcafe'] ) || $_GET['wpcafe'] !== 'true' ) {
            return false;
        }

        $current_screen = get_current_screen(); 
        if ( ! $current_screen || $current_screen->id === 'toplevel_page_wpcafe' ) {
            return false;
        }

        // Define WooCommerce screen IDs
        $woocommerce_screens = apply_filters( 'wpcafe_woocommerce_screens', [
            'product',
            'edit-product',
            'edit-product_cat',
            'edit-product_tag',
            'shop_order',
            'edit-shop_order',
            'shop_coupon',
            'edit-shop_coupon',
            'edit-product_brand',
            'product_page_product_importer',
            'product_page_product_exporter',
            'woocommerce_page_wc-admin'
        ] );

        // Check for WooCommerce page prefix
        $is_woocommerce_page = strpos( $current_screen->id, 'woocommerce_page_' ) === 0;
        
        // Check for specific WooCommerce screens
        $is_woocommerce_screen = in_array( $current_screen->id, $woocommerce_screens, true );

        return $is_woocommerce_page || $is_woocommerce_screen;
    }

    /**
     * WP Cafe Custom Sidebar
     *
     * @return void
     */
    public function wpc_woocommerce_custom_sidebar() {
        if ( ! $this->is_wpcafe_woocommerce_page() ) {
            return;
        }
        // Get menu items for WooCommerce sidebar
        $menu_items = $this->get_woo_menu_items(); 
 
        ?>
        <!-- Mobile Overlay -->
        <div class="woo-wpc-sidebar-overlay" id="woo-wpc-sidebar-overlay"></div>

        <div class="woo-wpc-sidebar-wrapper" id="woo-wpc-sidebar-wrapper">
            <?php $this->render_woo_sidebar_html($menu_items); ?>
        </div>
        <?php
        $this->render_woocommerce_styles();
    }

    /**
     * Render WooCommerce custom styles
     *
     * @return void
     */
    private function render_woocommerce_styles() {
        ?>
        <style>
        .toplevel_page_wpcafe #adminmenumain,
        body.wpcafe-woo-admin-page #adminmenumain {
            display: none;
        }
        
        .woocommerce-layout__header {
            width: calc(100% - 220px);
        }

     
        
        body.wpcafe-woo-admin-page #wpcontent {
            margin-left: 80px;
            transition: margin-left 0.3s ease;
        }
        
        .woo-wpc-topbar-wrapper {
            position: fixed;
            z-index: 1111;
            background: #fff;
            margin-left: 220px;
            display: flex;
            justify-content: space-between;
            padding: 10px 20px;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        /* #woocommerce-embedded-root {
            visibility: hidden;
            opacity: 0;
        } */
        
        .woo-wpc-topbar-center ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .woo-wpc-topbar-center ul li {
            margin: 0;
        }
        
        .woo-wpc-topbar-center ul a {
            font-size: 16px;
            display: block;
            margin: 0 20px;
            color: #000;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }
        
        .woo-wpc-topbar-center ul a:hover,
        .woo-wpc-topbar-center ul a.active {
            background-color: #fff2ee;
            color: #f45925;
        }
        
        .woo-wpc-topbar-left h2 {
            font-size: 20px;
            margin: 0;
            color: #23282d;
        }
        
        .woo-wpc-topbar-right-logo a {
            font-size: 16px;
            color: #0073aa;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }
        
        .woo-wpc-topbar-right-logo a:hover {
            background-color: #f0f0f0;
        }
        .sidebar-group-title {
            font-size: 17px; 
            font-weight: 700;
            margin: 0 0 20px;
        }
        .sidebar-group{
            padding: 30px 0;
        }
        .wpc-back-to-home{
            display: block;  
            color: #f55925;
            font-size: 14px; 
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .sidebar-top{
            padding: 10px 20px;
        }
        
        /* Responsive design */
        @media (max-width: 1200px) {
            .woo-wpc-topbar-center ul a {
                margin: 0 10px;
                font-size: 14px;
            }
        }
        
        @media (max-width: 768px) {
            .woo-wpc-topbar-wrapper {
                flex-direction: column;
                padding: 10px;
            }
            
            .woo-wpc-topbar-center ul {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .woo-wpc-topbar-center ul a {
                margin: 5px;
                font-size: 14px;
            }
        }

        /* WooCommerce Sidebar Styles */
        .woo-wpc-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 220px;
            height: calc(100vh - 30px);
            background: #ffffff;
            border-right: 1px solid #e5e7eb;
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.05);
            z-index: 1050;
            margin-top: 32px;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .woo-wpc-sidebar.collapsed {
            width: 80px;
        }

        .sidebar-header {
            padding: 40px 20px 10px;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff; 
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            min-width: 0;
            text-decoration: none;
        }

        .sidebar-logo {
            width: 40px;
            height: 40px;
            background: #f97316;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .sidebar-logo img {
            width: 24px;
            height: 24px;
            filter: brightness(0) invert(1);
        }

        .sidebar-title {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            white-space: nowrap;
            transition: all 0.3s ease;
            letter-spacing: -0.025em;
        }

        .woo-wpc-sidebar.collapsed .sidebar-title {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }

        .woo-wpc-sidebar .sidebar-title {
            opacity: 1;
            width: auto;
        }

        .sidebar-toggle {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
            margin-right: -29px;
        }

        .sidebar-toggle:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }

        .toggle-icon {
            font-size: 16px;
            font-weight: 600;
            color: #6b7280;
            transition: transform 0.3s ease;
        }

        .woo-wpc-sidebar .toggle-icon {
            transform: rotate(0deg);
        }

        .woo-wpc-sidebar.collapsed .toggle-icon {
            transform: rotate(180deg);
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 0;
            /* Hide scrollbar for webkit browsers */
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* Internet Explorer 10+ */
        }
        
        .sidebar-nav::-webkit-scrollbar {
            display: none; /* WebKit browsers */
        }

        .sidebar-menu {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .sidebar-menu-item {
            margin: 0;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #374151;
            text-decoration: none;
            transition: all 0.2s ease;
            border-radius: 0;
            position: relative;
            font-size: 14px;
            font-weight: 500;
            
        }

        .sidebar-link:hover {
            background: #f9fafb;
            color: #111827;
        }

        .sidebar-link.active {
            background: #fff2ee;
            color: #f45925;
        }

        .sidebar-link.active:hover {
            background: #fff2ee;
            color: #f45925;
        }


        .sidebar-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .sidebar-icon svg {
            width: 100%;
            height: 100%;
            stroke-width: 1.5;
        }

        .sidebar-text {
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
            transition: all 0.3s ease; 
        }

        .woo-wpc-sidebar.collapsed .sidebar-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }

        .woo-wpc-sidebar .sidebar-text {
            opacity: 1;
            width: auto;
        }

        .sidebar-footer {
            padding: 10px;
            border-top: 1px solid #f3f4f6;
            background: #ffffff;
        }

        .wp-return-link { 
            gap: 12px;   
            text-decoration: none; 
        }
 

        .wp-icon {
            width: 24px;
            height: 24px;
            background: #0369a1;
            color: white;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .wp-text {
            font-size: 14px;
            white-space: nowrap;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .woo-wpc-sidebar.collapsed .wp-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }
        .woo-wpc-sidebar .wp-text {
            opacity: 1;
            width: auto;
        }

        .woo-wpc-sidebar.collapsed .wp-return-link {
            justify-content: center; 
        }

        .woo-wpc-sidebar .wp-return-link {
            justify-content: flex-start; 
        }

        /* Sidebar Groups */
        body.sidebar-collapsed .sidebar-link {
            justify-content: center;
        }
        

        /* Mobile Overlay */
        .woo-wpc-sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .woo-wpc-sidebar-overlay.active {
            display: block;
            opacity: 1;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            /* Hide desktop toggle */
            .sidebar-toggle {
                display: none !important;
            }

            /* Sidebar mobile behavior */
            .woo-wpc-sidebar {
                width: 280px;
                transform: translateX(-100%);
                box-shadow: none;
                z-index: 1050;
            }

            .woo-wpc-sidebar.mobile-open {
                transform: translateX(0);
                box-shadow: 4px 0 12px rgba(0, 0, 0, 0.15);
            }

            /* Content area adjustments */
            body.wpcafe-woo-admin-page #wpcontent {
                margin-left: 0 !important;
            }

            body.wpcafe-woo-admin-page.sidebar-collapsed #wpcontent {
                margin-left: 0 !important;
            }

            /* Topbar adjustments */
            .woo-wpc-topbar-wrapper {
                width: 100% !important;
                margin-left: 0 !important;
                padding-left: 70px;
            }

            .woocommerce-layout__header {
                width: 100% !important;
            }
        }

        @media (max-width: 480px) {
            .woo-wpc-sidebar {
                width: 100%;
                max-width: 280px;
            }

            .woo-wpc-topbar-wrapper {
                padding-left: 60px;
                padding-right: 10px;
            }
        }

        /* Update wpcontent margin when sidebar is present */
        body.wpcafe-woo-admin-page #wpcontent {
            margin-left: 220px;
            transition: margin-left 0.3s ease;
        }

        body.wpcafe-woo-admin-page.sidebar-collapsed #wpcontent {
            margin-left: 80px;
        }

        .woo-wpc-topbar-wrapper {
            width: calc(100% - 260px);
            margin-left: 220px;
            transition: all 0.3s ease;
        }

        body.wpcafe-woo-admin-page.sidebar-collapsed .woo-wpc-topbar-wrapper {
            width: calc(100% - 120px);
            margin-left: 80px;
        }

       
        
        </style>
        <?php
    }

    /**
     * Add body class when wpcafe=true parameter is present
     *
     * @param string $classes Existing body classes
     * @return string Modified body classes
     */
    public function wpc_add_wpcafe_body_class( $classes ) {
        if ( $this->is_wpcafe_woocommerce_page() ) {
            $classes .= ' wpcafe-woo-admin-page';
        }

        return $classes;
    }

    /**
     * Enqueue script to add wpcafe=true parameter to all links
     *
     * @return void
     */
    public function wpc_enqueue_link_param_script() {
        if ( ! $this->is_wpcafe_woocommerce_page() ) {
            return;
        }

        wp_add_inline_script( 'jquery', $this->wpc_get_link_param_script() );
    }

    /**
     * Handle form submissions to preserve wpcafe parameter in redirects
     *
     * @param  string $location The redirect location URL
     * @return string Modified location with wpcafe parameter if needed
     */
    public function wpcafe_keep_param_after_save( $location ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin list-table filter, capability-gated
        if ( ! empty( $_REQUEST['wpcafe'] ) && $_REQUEST['wpcafe'] === 'true' ) {
            $location = add_query_arg( 'wpcafe', 'true', $location );
        }
        return $location;
    }

    /**
     * Preserve wpcafe parameter in all redirects
     *
     * @param string $location The location to redirect to
     * @return string Modified location with wpcafe parameter if needed
     */
    public function wpc_preserve_wpcafe_redirect( $location ) {
        // Check if the current request has the wpcafe parameter
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin list-table filter, capability-gated
        if ( ! empty( $_REQUEST['wpcafe'] ) && $_REQUEST['wpcafe'] === 'true' ) {
            if ( strpos( $location, admin_url() ) === 0 || strpos( $location, '/wp-admin/' ) !== false ) {
                // Check if wpcafe parameter is already present
                if ( strpos( $location, 'wpcafe=' ) === false ) {
                    $location = add_query_arg( 'wpcafe', 'true', $location );
                }
            }
        }

        return $location;
    }
 

    /**
     * Get JavaScript code to add wpcafe=true to all links and handle sidebar
     *
     * @return string JavaScript code
     */
    private function wpc_get_link_param_script() {
        $admin_url = esc_js( admin_url() );
        
        return "
        (function($) {
            'use strict';
            
            // Sidebar functionality
            function initSidebar() {
                var sidebar = $('#woo-wpc-sidebar');
                var toggleBtn = $('#sidebar-toggle');
                var wpcontent = $('#wpcontent');
                var topbar = $('.woo-wpc-topbar-wrapper');
                
                if (sidebar.length === 0) return;
                
                // Toggle sidebar
                toggleBtn.on('click', function() {
                    sidebar.toggleClass('collapsed');
                    $('body').toggleClass('sidebar-collapsed');
                    
                    // Update wpcontent margin and topbar
                    if (sidebar.hasClass('collapsed')) {
                        wpcontent.css('margin-left', '80px');
                        topbar.css({
                            'width': 'calc(100% - 120px)',
                            'margin-left': '80px'
                        });
                    } else {
                        wpcontent.css('margin-left', '220px');
                        topbar.css({
                            'width': 'calc(100% - 260px)',
                            'margin-left': '220px'
                        });
                    }
                });
                
                // Handle sidebar link clicks
                sidebar.on('click', '.sidebar-link', function(e) {
                    var link = $(this);
                    var href = link.attr('href');
                    var isExternal = link.hasClass('external-link');
                    var isExcluded = link.attr('data-wpcafe-exclude') === 'true';
                    
                    if (isExternal || href.indexOf('http') === 0) {
                        return; // Let external links work normally
                    }
                    
                    // If the link already has wpcafe parameter, let it work normally
                    if (href.indexOf('wpcafe=') !== -1) {
                        return; // Let the link work normally
                    }
                    
                    e.preventDefault();
                    
                    if (href.indexOf('admin.php?page=wpcafe') !== -1) {
                        // Internal WpCafe links
                        window.location.href = href;
                    } else if (isExcluded) {
                        // Excluded links - navigate without wpcafe parameter
                        window.location.href = href;
                    } else {
                        // Other admin links - add wpcafe parameter
                        var separator = href.indexOf('?') !== -1 ? '&' : '?';
                        var newHref = href + separator + 'wpcafe=true';
                        window.location.href = newHref;
                    }
                });
            }
            
            // Function to add wpcafe=true parameter to URL
            function addWpcafeParam(url) {
                if (!url) return url;
                
                if (url.indexOf('wpcafe=') !== -1) {
                    return url;
                }
                
                var separator = url.indexOf('?') !== -1 ? '&' : '?';
                return url + separator + 'wpcafe=true';
            }
            
            // Function to process links
            function processLinks(links) {
                links.each(function() {
                    var link = $(this);
                    var href = link.attr('href');
                    
                    if (!href || href.indexOf('http') === 0 && href.indexOf(window.location.origin) === -1) {
                        return;
                    }
                    
                    if (href.indexOf('#') === 0) {
                        return;
                    }
                    
                    // Skip excluded links
                    if (link.hasClass('wp-wordpress-icon') || 
                        link.attr('data-wpcafe-exclude') === 'true' ||
                        link.attr('href') === '/wp-admin/' || 
                        link.attr('href') === '{$admin_url}' ||
                        link.attr('href') === '{$admin_url}index.php' ||
                        link.text().toLowerCase().includes('wordpress') ||
                        (link.find('svg').length > 0 && link.attr('href').includes('/wp-admin/'))) {
                        return;
                    }
                    
                    var adminUrl = '{$admin_url}';
                    if (href.indexOf(adminUrl) !== -1 && !href.includes('?')) {
                        return;
                    }
                    
                    var newHref = addWpcafeParam(href);
                    link.attr('href', newHref);
                });
            }
            
            // Mobile sidebar functionality using WordPress admin bar toggle
            function initMobileSidebar() {
                var wpAdminToggle = $('#wp-admin-bar-menu-toggle');
                var sidebar = $('#woo-wpc-sidebar');
                var overlay = $('#woo-wpc-sidebar-overlay');
                var body = $('body');
                
                if (wpAdminToggle.length === 0 || sidebar.length === 0) return;
                
                // Hijack WordPress admin bar toggle for mobile sidebar
                wpAdminToggle.on('click', function(e) {
                    // Only handle on mobile
                    if ($(window).width() <= 768) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        var isOpen = sidebar.hasClass('mobile-open');
                        
                        if (isOpen) {
                            closeMobileSidebar();
                        } else {
                            openMobileSidebar();
                        }
                    }
                });
                
                // Close sidebar when clicking overlay
                overlay.on('click', function() {
                    closeMobileSidebar();
                });
                
                // Close sidebar when clicking a menu item on mobile
                sidebar.on('click', '.sidebar-link', function() {
                    if ($(window).width() <= 768) {
                        setTimeout(closeMobileSidebar, 200);
                    }
                });
                
                // Close sidebar on escape key
                $(document).on('keydown', function(e) {
                    if (e.key === 'Escape' && sidebar.hasClass('mobile-open')) {
                        closeMobileSidebar();
                    }
                });
                
                function openMobileSidebar() {
                    sidebar.addClass('mobile-open');
                    overlay.addClass('active');
                    body.css('overflow', 'hidden');
                }
                
                function closeMobileSidebar() {
                    sidebar.removeClass('mobile-open');
                    overlay.removeClass('active');
                    body.css('overflow', '');
                }
            }
            
            // Initialize on document ready
            $(document).ready(function() {
                // Initialize sidebar
                initSidebar();
                initMobileSidebar();
                
                processLinks($('a[href]'));

                // Helper function to add wpcafe parameter to form
                function addWpcafeToForm(form) {
                    if (form.length > 0 && form.find('input[name=\"wpcafe\"]').length === 0) {
                        form.append('<input type=\"hidden\" name=\"wpcafe\" value=\"true\">');
                    }
                }

                // Preserve wpcafe parameter in form submissions
                // Handle WooCommerce order update button
                $(document).on('click', 'button[name=\"save\"].save_order', function(e) {
                    addWpcafeToForm($(this).closest('form'));
                });

                // Handle standard WordPress publish button (posts, pages, etc.)
                $(document).on('click', '#publish', function(e) {
                    addWpcafeToForm($(this).closest('form'));
                });
                
                // Handle dynamically added content
                if (window.MutationObserver) {
                    var observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'childList') {
                                mutation.addedNodes.forEach(function(node) {
                                    if (node.nodeType === 1) {
                                        var target = $(node);
                                        processLinks(target.find('a[href]'));
                                        if (target.is('a[href]')) {
                                            processLinks(target);
                                        }
                                    }
                                });
                            }
                        });
                    });
                    
                    observer.observe(document.body, {
                        childList: true,
                        subtree: true
                    });
                }
            });
        })(jQuery);
        ";
    }

    

    /**
     * Get topbar menu items with active state detection
     *
     * @return array Menu items
     */
    private function get_woo_menu_items() {
        $current_screen = get_current_screen();
        $current_url = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

        return apply_filters( 'wpcafe_woo_menu_items', [
            [
                'title'     => esc_html__( 'Products', 'wp-cafe' ),
                'url'       => admin_url( 'edit.php?post_type=product&wpcafe=true' ),
                'icon'       => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27,6.96 12,12.01 20.73,6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
                'is_active' => $this->is_menu_item_active( $current_screen, $current_url, [ 'product', 'edit-product' ], 'post_type=product' ),
            ],
            [
                'title'     => esc_html__( 'Live Orders', 'wp-cafe' ),
                'url'       => admin_url( 'admin.php?page=wc-orders&wpcafe=true' ),
                'icon'       => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M14 8H8"/><path d="M16 12H8"/><path d="M13 16H8"/></svg>',
                'is_active' => $this->is_menu_item_active( $current_screen, $current_url, [ 'shop_order', 'edit-shop_order' ], 'page=wc-orders' ),
            ],
            [
                'title'     => esc_html__( 'Categories', 'wp-cafe' ),
                'url'       => admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product&wpcafe=true' ),
                'icon'       => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
                'is_active' => $this->is_menu_item_active( $current_screen, $current_url, [ 'edit-product_cat' ], 'taxonomy=product_cat' ),
            ],
            [
                'title'     => esc_html__( 'Brands', 'wp-cafe' ),
                'url'       => admin_url( 'edit-tags.php?taxonomy=product_brand&post_type=product&wpcafe=true' ),
                'icon'       => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 4l3 3 3-3 3 3 3-3 3 3 3-3"/><path d="M2 4v16h20V4"/></svg>',
                'is_active' => $this->is_menu_item_active( $current_screen, $current_url, [ 'edit-product_brand' ], 'taxonomy=product_brand' ),
            ],
            [
                'title'     => esc_html__( 'Tags', 'wp-cafe' ),
                'url'       => admin_url( 'edit-tags.php?taxonomy=product_tag&post_type=product&wpcafe=true' ),
                'icon'       => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
                'is_active' => $this->is_menu_item_active( $current_screen, $current_url, [ 'edit-product_tag' ], 'taxonomy=product_tag' ),
            ],
            [
                'title'     => esc_html__( 'Coupons', 'wp-cafe' ),
                'url'       => admin_url( 'edit.php?post_type=shop_coupon&wpcafe=true' ),
                'icon'       => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12c0 1.66-1.34 3-3 3H6c-1.66 0-3-1.34-3-3s1.34-3 3-3h12c1.66 0 3 1.34 3 3z"/><path d="M9 12h6"/><path d="M12 9v6"/></svg>',
                'is_active' => $this->is_menu_item_active( $current_screen, $current_url, [ 'shop_coupon', 'edit-shop_coupon' ], 'post_type=shop_coupon' ),
            ],
            [
                'title'     => esc_html__( 'Reports', 'wp-cafe' ),
                'url'       => admin_url( 'admin.php?page=wc-reports&wpcafe=true' ),
                'icon'       => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/></svg>',
                'is_active' => $this->is_menu_item_active( $current_screen, $current_url, [ 'woocommerce_page_wc-reports' ], 'page=wc-reports' ),
            ],
            [
                'title'     => esc_html__( 'Woo Settings', 'wp-cafe' ),
                'url'       => admin_url( 'admin.php?page=wc-settings&wpcafe=true' ),
                'icon'       => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
                'is_active' => $this->is_menu_item_active( $current_screen, $current_url, [ 'woocommerce_page_wc-settings' ], 'page=wc-settings' ),
            ],
         
        ] );
    }


    /**
     * Check if menu item should be active
     *
     * @param WP_Screen $current_screen Current screen object
     * @param string    $current_url    Current URL
     * @param array     $screen_ids     Screen IDs to check
     * @param string    $url_pattern    URL pattern to check
     * @return bool
     */
    private function is_menu_item_active( $current_screen, $current_url, $screen_ids, $url_pattern ) {
        if ( ! $current_screen ) {
            return false;
        }

        // Check screen ID first (most reliable)
        $is_screen_match = in_array( $current_screen->id, $screen_ids, true );
        
        if ( $is_screen_match ) {
            return true;
        }

        // Check URL pattern only if screen doesn't match
        if ( empty( $url_pattern ) ) {
            return false;
        }

        // More specific URL pattern matching
        $is_url_match = false;
        
        // For product pages, check specific patterns
        if ( $url_pattern === 'post_type=product' ) {
            $is_url_match = ( strpos( $current_url, 'edit.php?post_type=product' ) !== false && 
                             strpos( $current_url, 'taxonomy=' ) === false );
        } elseif ( $url_pattern === 'page=wc-orders' ) {
            $is_url_match = strpos( $current_url, 'page=wc-orders' ) !== false;
        } elseif ( $url_pattern === 'taxonomy=product_cat' ) {
            $is_url_match = strpos( $current_url, 'taxonomy=product_cat' ) !== false;
        } elseif ( $url_pattern === 'taxonomy=product_brand' ) {
            $is_url_match = strpos( $current_url, 'taxonomy=product_brand' ) !== false;
        } elseif ( $url_pattern === 'taxonomy=product_tag' ) {
            $is_url_match = strpos( $current_url, 'taxonomy=product_tag' ) !== false;
        } else {
            $is_url_match = strpos( $current_url, $url_pattern ) !== false;
        }

        return $is_url_match;
    }

    
    /**
     * Render WooCommerce sidebar HTML
     *
     * @param array $menu_items Menu items array
     * @return void
     */
    private function render_woo_sidebar_html($menu_items) {
        ?>
        <aside class="woo-wpc-sidebar" id="woo-wpc-sidebar">
            <!-- Header -->
            <div class="sidebar-header">
                <div class="sidebar-brands">
                    <a class="sidebar-brand" href="<?php echo esc_url(admin_url("admin.php?page=wpcafe")); ?>">
                        <div class="sidebar-logo">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="40"
                            height="40"
                            viewBox="0 0 40 40"
                            fill="none"
                        >
                            <rect width="40" height="40" rx="20" fill="#F45925" />
                            <path
                                d="M20.4119 16.105C21.4894 16.105 22.3628 15.2315 22.3628 14.1541C22.3628 13.0766 21.4894 12.2031 20.4119 12.2031C19.3344 12.2031 18.4609 13.0766 18.4609 14.1541C18.4609 15.2315 19.3344 16.105 20.4119 16.105Z"
                                fill="white"
                            />
                            <path
                                d="M34.9517 14.3105C34.9165 13.2368 34.5429 12.4641 33.9001 12.1337C33.6786 12.0198 33.4297 11.9619 33.16 11.9619C31.9119 11.9619 30.3237 13.221 29.4647 14.8913C28.9884 15.8172 28.8916 16.5778 29.1691 17.2165C29.3045 17.5279 29.5269 17.8048 29.8498 18.0621C29.8377 18.0826 29.8253 18.1038 29.8134 18.1249L29.2155 19.0775C28.4471 20.2993 26.3972 23.5792 25.6244 24.8193C25.5709 24.9021 25.5018 25.0144 25.4284 25.1335C25.3183 25.3124 25.1938 25.5153 25.1126 25.6325L25.1092 25.6378C24.8865 25.9816 24.4808 26.1956 24.0505 26.1956C23.8692 26.1956 23.69 26.1561 23.5328 26.0818L23.5269 26.079C23.3393 25.9975 23.1956 25.8438 23.0802 25.7022C23.0727 25.6901 23.0646 25.677 23.0569 25.6633C23.0338 25.6245 23.0077 25.5809 22.9825 25.5439C22.7473 25.1108 22.4306 24.5309 22.0395 23.8185C22.2704 23.434 22.5193 23.0199 22.7784 22.589C23.9124 20.7043 25.1976 18.5676 26.0453 17.0675L26.0497 17.0588C27.0207 15.1246 26.913 12.8554 25.7619 10.9897C24.6068 9.11749 22.6219 8 20.4513 8H20.419C18.0978 8.00124 16.0436 9.22171 14.9249 11.2638C13.8086 13.3009 13.8855 15.6828 15.1296 17.6356C16.1817 19.4198 17.4825 21.5947 18.7981 23.7715L17.862 25.4829C17.8508 25.5034 17.8418 25.5202 17.8328 25.5342H17.8281L17.7712 25.6319C17.5773 25.9617 17.1947 26.1664 16.7725 26.1664C16.5809 26.1664 16.3927 26.1228 16.2265 26.0401C16.0315 25.9452 15.8411 25.762 15.6924 25.5274C15.5583 25.3071 14.9544 24.3421 14.3148 23.3207C13.6764 22.3012 13.0632 21.2071 12.8971 20.9358L11.1574 18.0036C11.4311 17.6962 11.5329 17.4452 11.6424 17.0361C11.8269 16.3467 11.7317 15.6264 11.3748 15.0083L9.54926 11.8509L8.64861 12.3717L10.2297 15.1075C10.281 15.1962 10.2505 15.3097 10.1618 15.3608C10.1329 15.3773 10.1021 15.3857 10.0691 15.3857C10.0032 15.3857 9.94126 15.3505 9.90797 15.2929L8.32693 12.5571L7.39579 13.095L8.97714 15.8315C9.02816 15.9198 8.99767 16.0337 8.90901 16.0847C8.88039 16.1012 8.84927 16.1096 8.81692 16.1096C8.75034 16.1096 8.68905 16.0741 8.65577 16.0169L7.07473 13.2804L6.14359 13.8186L7.72463 16.5545C7.74952 16.5971 7.75636 16.6475 7.74329 16.6954C7.73054 16.7433 7.69974 16.7831 7.65712 16.8077C7.6285 16.8242 7.59738 16.8326 7.56441 16.8326C7.49845 16.8326 7.43654 16.7974 7.40388 16.7399L5.82253 14.004L4.92188 14.5245L6.74682 17.6832C7.22281 18.507 8.1104 19.0187 9.063 19.0187C9.20954 19.0187 9.35513 19.0066 9.49917 18.9827C9.49949 18.9839 9.50011 18.9845 9.50042 18.9858L9.50322 18.992L10.0109 19.9536C10.0184 19.9679 10.0259 19.9822 10.0343 19.9959H10.0333C10.5765 21.0304 11.475 22.7162 12.2677 24.2036C12.7785 25.1625 13.2611 26.0684 13.6111 26.7286C13.78 27.0409 13.9819 27.3234 14.2106 27.5686C14.3456 27.5303 14.4846 27.5007 14.6278 27.4818C14.7556 27.4643 14.8888 27.4553 15.0222 27.4553C15.8373 27.4553 16.6247 27.7969 17.1826 28.3927C17.2718 28.4876 17.3546 28.5877 17.4296 28.6923C18.3909 28.5616 19.2751 28.0763 19.8627 27.338L19.8658 27.334C19.9788 27.1853 20.1063 26.9998 20.2093 26.8362C20.2497 26.7703 20.3306 26.6371 20.4349 26.4654C20.5089 26.5864 20.6564 26.8275 20.6564 26.8275C20.802 27.0546 20.96 27.2926 21.1451 27.4852C21.6883 28.1021 22.4418 28.5081 23.2734 28.6444C23.348 28.5445 23.4296 28.449 23.5179 28.3578C24.0813 27.7757 24.8367 27.4553 25.6449 27.4553C25.83 27.4553 26.0117 27.4724 26.1847 27.5057C26.3157 27.53 26.4436 27.5623 26.5671 27.6031C26.736 27.4295 26.8869 27.2366 27.0169 27.0272C27.0325 27.0014 27.0518 26.9715 27.072 26.9398C27.1199 26.8645 27.1743 26.7799 27.2082 26.7087C27.5352 26.0927 27.9813 25.2552 28.4539 24.3685C29.2821 22.8155 30.2207 21.0543 30.7829 19.9822L31.3092 18.9879C31.3338 18.9459 31.3575 18.9039 31.3796 18.8613C31.6418 18.9413 31.8848 18.9802 32.1181 18.9802C32.9793 18.9802 33.6724 18.4457 34.238 17.3453C34.7239 16.3999 34.984 15.2939 34.9517 14.3105ZM20.4081 20.8574C20.0394 20.1897 19.6922 19.5635 19.4169 19.0667L17.7572 16.1133C17.6374 15.9055 17.4881 15.6308 17.3935 15.3471C17.06 14.4038 17.2199 13.3333 17.8203 12.4837C18.4186 11.6381 19.3696 11.1331 20.3639 11.1331C20.3813 11.1331 20.3988 11.1335 20.4162 11.1335H20.4557C21.5337 11.1335 22.5267 11.695 23.1129 12.6355C23.7008 13.5794 23.7677 14.7218 23.293 15.6933C22.8332 16.5243 22.2452 17.5743 21.6223 18.6852C21.2182 19.4079 20.802 20.1515 20.4081 20.8574Z"
                                fill="white"
                            />
                            <path
                                d="M16.7146 28.8249C16.29 28.371 15.6864 28.0879 15.0166 28.0879C14.9111 28.0879 14.8079 28.0947 14.7055 28.1087C13.5669 28.2609 12.6861 29.239 12.6861 30.4181C12.6861 31.1843 13.0582 31.8653 13.6313 32.2906L8.96094 32.4872L14.7264 32.7299C14.8215 32.7417 14.9183 32.7486 15.0169 32.7486C16.3018 32.7486 17.3474 31.7029 17.3474 30.4181C17.3471 29.8021 17.1066 29.2415 16.7146 28.8249ZM15.0166 31.4096C14.47 31.4096 14.0254 30.965 14.0254 30.4184C14.0254 29.8718 14.47 29.4272 15.0166 29.4272C15.5632 29.4272 16.0078 29.8718 16.0078 30.4184C16.0078 30.965 15.5632 31.4096 15.0166 31.4096Z"
                                fill="white"
                            />
                            <path
                                d="M26.0682 28.1274C25.9313 28.1006 25.7898 28.0879 25.6457 28.0879C24.9899 28.0879 24.3969 28.3607 23.9735 28.7981C23.5672 29.2172 23.3168 29.7881 23.3168 30.4184C23.3168 31.1868 23.6904 31.8694 24.2657 32.294L19.6719 32.4872L25.3153 32.7249C25.4233 32.7405 25.5337 32.7489 25.646 32.7489C26.9318 32.7489 27.9765 31.7033 27.9765 30.4184C27.9762 29.2776 27.1533 28.3259 26.0682 28.1274ZM25.6457 31.4096C25.0991 31.4096 24.6545 30.965 24.6545 30.4184C24.6545 29.8718 25.0991 29.4272 25.6457 29.4272C26.1923 29.4272 26.6382 29.8718 26.6382 30.4184C26.6382 30.965 26.1923 31.4096 25.6457 31.4096Z"
                                fill="white"
                            />
                        </svg>
                        </div>
                        <div class="sidebar-title">
                            <span><?php echo esc_html__('WPCafe', 'wp-cafe'); ?></span>
                        </div>
                    </a>
                </div>
                <!-- <button class="sidebar-toggle" id="sidebar-toggle" title="<?php echo esc_attr__('Toggle Sidebar', 'wp-cafe'); ?>">
                    <span class="toggle-icon">←</span>
                </button> -->
            </div>

            <!-- Navigation -->
            <nav class="sidebar-nav"> 
                <div class="sidebar-group">
                    <div class="sidebar-top sidebar-text">
                        <a class="wpc-back-to-home" href="<?php echo esc_url(admin_url("admin.php?page=wpcafe")); ?>">  
                            <svg xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                            <?php echo esc_html__('Back to Home ', 'wp-cafe');?></a> 
                        <h3 class="sidebar-group-title">
                            <?php echo esc_html__('Food Menus', 'wp-cafe'); ?>
                        </h3>
                    </div>
                    <ul class="sidebar-menu"> 
                        <?php foreach ($menu_items as $item): ?>
                            <li class="sidebar-menu-item">
                                <a href="<?php echo esc_url($item['url']); ?>" 
                                data-wpcafe-exclude="false"
                                    class="sidebar-link <?php echo esc_attr( $item['is_active'] ? 'active' : '' ); ?>">
                                    <span class="sidebar-icon">
                                        <?php echo wp_kses( $item['icon'], \WpCafe\Utils\Wpc_Utilities::wpc_kses_allowed_tags() ); ?>
                                    </span>
                                    <span class="sidebar-text"><?php echo esc_html($item['title']); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div> 
            </nav>

            <!-- Footer -->
            <div class="sidebar-footer">
                <a href="<?php echo esc_url(admin_url()); ?>" title="<?php echo esc_html__('Return to WordPress', 'wp-cafe'); ?>" class="wp-return-link" data-wpcafe-exclude="true">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 512 512"
                        width="30"
                        height="30"
                        {...props}
                    >
                        <path
                            fill="currentColor"
                            d="M259 271.3L226.2 367h-.1l-25.4 73.1c1.8.5 3.5.9 5.3 1.4h.3a192.5 192.5 0 0 0 49.5 6.5a157 157 0 0 0 24.9-1.8a184.3 184.3 0 0 0 32.5-7.1c2.6-.8 5.2-1.7 7.8-2.6c-2.8-6-8.8-19.3-9.1-19.9ZM80.8 180.5C70.8 203.1 64 230.9 64 256c0 6.3.3 12.6.9 18.8c6.9 71.2 52.9 131 116.1 157.9c2.6 1.1 5.3 2.2 8 3.2L96 180.6c-8-.3-9.5.2-15.2-.1"
                        ></path>
                        <path
                            fill="currentColor"
                            d="M430.2 175.4a188 188 0 0 0-15.1-26.6c-1.6-2.4-3.4-4.8-5.1-7.2A193 193 0 0 0 325.1 77a189.2 189.2 0 0 0-69.2-13a191.51 191.51 0 0 0-149.4 71.7A196 196 0 0 0 89 161.3c14.2.1 31.8.1 33.8.1c18.1 0 46-2.2 46-2.2c9.4-.6 10.4 13.1 1.1 14.2c0 0-9.4 1.1-19.8 1.6L213 362l37.8-113.3l-26.8-73.6c-9.4-.5-18.1-1.6-18.1-1.6c-9.4-.5-8.2-14.8 1-14.2c0 0 28.5 2.2 45.5 2.2c18.1 0 46-2.2 46-2.2c9.3-.6 10.5 13.1 1.1 14.2c0 0-9.3 1.1-19.7 1.6l62.3 185.6l17.3-57.6c8.7-22.4 13.1-40.9 13.1-55.7c0-21.3-7.7-36.1-14.3-47.6c-8.7-14.3-16.9-26.3-16.9-40.4c0-15.9 12-30.7 29-30.7h2.2c26.2-.7 34.8 25.3 35.9 43v.6c.4 7.2.1 12.5.1 18.8c0 17.4-3.3 37.1-13.1 61.8l-39 112.8l-22.3 65.7c1.8-.8 3.5-1.6 5.3-2.5c56.7-27.4 98-82 106.7-146.7a172 172 0 0 0 1.9-26a191.1 191.1 0 0 0-17.8-80.8"
                        ></path>
                        <path
                            fill="currentColor"
                            d="M256 48a208.06 208.06 0 0 1 81 399.66A208.06 208.06 0 0 1 175 64.34A206.7 206.7 0 0 1 256 48m0-16C132.29 32 32 132.29 32 256s100.29 224 224 224s224-100.29 224-224S379.71 32 256 32"
                        ></path>
                    </svg>
                  </a>
            </div>
        </aside>
        <?php
    }
   
    
}