<?php

namespace WpCafe\Widgets\Wpc_Menus_list;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use \WpCafe\Utils\Wpc_Utilities as Wpc_Utilities;

defined( "ABSPATH" ) || exit;
// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals -- widget/template scope; locally-extracted variables and Elementor-owned hook names (elementor/control/search_control, elementor/control/search_data).

class Wpc_Menus_List extends Widget_Base {

    /**
     * Retrieve the widget name.
     * @return string Widget name.
     */
    public function get_name() {
        return 'wpc-menu';
    }

    /**
     * Retrieve the widget title.
     * @return string Widget title.
     */
    public function get_title() {
        return esc_html__( 'WPC Food Menu List', 'wp-cafe' );
    }

    /**
     * Retrieve the widget icon.
     * @return string Widget icon.
     */
    public function get_icon() {
        return 'eicon-menu-card';
    }

    /**
     * Retrieve the widget category.
     * @return string Widget category.
     */
    public function get_categories() {
        return ['wpcafe-menu'];
    }

    protected function register_controls() {
        $get_data = apply_filters( 'elementor/control/search_control' , false);

        // Start of event section
        $this->start_controls_section(
            'section_tab',
            [
                'label' => esc_html__( 'WPC Food Menu List', 'wp-cafe' ),
            ]
        );

        $this->add_control(
            'food_menu_style',
            [
                'label'   => esc_html__( 'Menu Style', 'wp-cafe' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'style-1',
                'options' => [
                    'style-1' => esc_html__( 'Menu Style 1', 'wp-cafe' ),
                    'style-2' => esc_html__( 'Menu Style 2', 'wp-cafe' ),
                    'style-3' => esc_html__( 'Menu Style 3', 'wp-cafe' ),
                ],
            ]
        );

        $this->add_responsive_control(
            'wpc_menu_col',
            [
                'label' => esc_html__('Menu Column', 'wp-cafe'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '4',
                'options' => [
                    '12' => esc_html__('1', 'wp-cafe'),
                    '6' => esc_html__('2', 'wp-cafe'),
                    '4' => esc_html__('3', 'wp-cafe'),
                    '3' => esc_html__('4', 'wp-cafe'),
                    '2' => esc_html__('6', 'wp-cafe'),
                ],
                'condition' => ['food_menu_style' => ['style-3']]
            ]
        );

        $this->add_control(
            'wpc_menu_cat',
            [
                'label'       => esc_html__( 'Menu Category', 'wp-cafe' ),
                'type'        => Controls_Manager::SELECT2,
                'options'     => $this->get_menu_category(),
                'multiple'    => true,
                'label_block' => true,

            ]
        );
        $this->add_control(
            'wpc_menu_count',
            [
                'label'   => esc_html__( 'Menu count', 'wp-cafe' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => '6',
            ]
        );
        $this->add_control(
            'wpc_menu_order',
            [
                'label'   => esc_html__( 'Menu Order', 'wp-cafe' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'DESC',
                'options' => [
                    'ASC'  => esc_html__( 'ASC', 'wp-cafe' ),
                    'DESC' => esc_html__( 'DESC', 'wp-cafe' ),
                ],
            ]
        );
        $this->add_control(
            'show_thumbnail',
            [
                'label'        => esc_html__( 'Show Thumbnail', 'wp-cafe' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Show', 'wp-cafe' ),
                'label_off'    => esc_html__( 'Hide', 'wp-cafe' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );
        $this->add_control(
            'show_item_status',
            [
                'label'        => esc_html__( 'Show Item Status', 'wp-cafe' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Show', 'wp-cafe' ),
                'label_off'    => esc_html__( 'Hide', 'wp-cafe' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );
        $this->add_control(
            'wpc_show_desc',
            [
                'label'        => esc_html__( 'Show Description', 'wp-cafe' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Show', 'wp-cafe' ),
                'label_off'    => esc_html__( 'Hide', 'wp-cafe' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );
        $this->add_control(
            'wpc_desc_limit',
            [
                'label'     => esc_html__( 'Description Limit', 'wp-cafe' ),
                'type'      => Controls_Manager::NUMBER,
                'default'   => '15',
                'condition' => ['wpc_show_desc' => 'yes'],
            ]
        );
        $this->add_control(
            'title_link_show',
            [
                'label'        => esc_html__( 'Use Title Link?', 'wp-cafe' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Show', 'wp-cafe' ),
                'label_off'    => esc_html__( 'Hide', 'wp-cafe' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );
        $this->add_control(
            'wpc_cart_button_show',
            [
                'label'        => esc_html__( 'Show add to cart button', 'wp-cafe' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Show', 'wp-cafe' ),
                'label_off'    => esc_html__( 'Hide', 'wp-cafe' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'wpc_price_show',
            [
                'label'   => esc_html__( 'Show Price', 'wp-cafe' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'yes',
                'options' => [
                    'yes'  => esc_html__( 'Show', 'wp-cafe' ),
                    'no'  => esc_html__( 'Hide', 'wp-cafe' ),
                    'min'   => esc_html__( 'Min Price (For Variation)', 'wp-cafe' ),
                    'max'   => esc_html__( 'Max Price (For Variation)', 'wp-cafe' ),
                ],
            ]
        );
        if(class_exists('Wpcafe_Multivendor')) { 
            $this->add_control(
                'wpc_show_vendor',
                [
                    'label'        => esc_html__( 'Show Vendor', 'wp-cafe' ),
                    'type'         => \Elementor\Controls_Manager::SWITCHER,
                    'label_on'     => esc_html__( 'Show', 'wp-cafe' ),
                    'label_off'    => esc_html__( 'Hide', 'wp-cafe' ),
                    'return_value' => 'yes',
                    'default'      => 'no',
                ]
            );
        }

        if( is_array( $get_data ) &&  count( $get_data )>0 && isset( $get_data['search_control'] ) ){
            $this->add_control( $get_data['search_control']['name'], $get_data['search_control']['parameter']);
        }

        $this->end_controls_section();

        // item status style section
        $this->start_controls_section(
            'item_status_style',
            [
                'label'     => esc_html__( 'Item Status Style', 'wp-cafe' ),
                'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => ['show_item_status' => 'yes'],
            ]
        );

        $this->start_controls_tabs('item_status_style_tabs');

        $this->start_controls_tab('item_status_style_normal',
            [
                'label' => esc_html__('Normal', 'wp-cafe'),
            ]
        );

        $this->add_control(
            'wpc_menu_item_status_color',
            [
                'label'     => esc_html__( 'Item Status Color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wpc-menu-tag li' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .wpc-food-menu-item.style3 .wpc-menu-tag li' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'wpc_menu_item_status_bg_color',
            [
                'label'     => esc_html__( 'Item Status BG Color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wpc-menu-tag li' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .wpc-food-menu-item.style3 .wpc-menu-tag li' => 'background-color: {{VALUE}};',

                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab('item_status_style_hover',
            [
                'label' => esc_html__('Hover', 'wp-cafe'),
            ]
        );

        $this->add_control(
            'wpc_menu_item_status_hover_color',
            [
                'label'     => esc_html__( 'Item Status Color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wpc-menu-tag li:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .wpc-food-menu-item.style3:hover .wpc-menu-tag li' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'wpc_menu_item_status_hover_bg_color',
            [
                'label'     => esc_html__( 'Item Status BG Color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wpc-menu-tag li:hover' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .wpc-food-menu-item.style3:hover .wpc-menu-tag li' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'wpc_menu_item_status_separator',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'wpc_menu_status_typo',
                'label'    => esc_html__( 'Typography', 'wp-cafe' ),
                'selector' => '{{WRAPPER}} .wpc-menu-tag li',
            ]
        );
        $this->add_responsive_control(
            'wpc_menu_item_status_paddding',
            [
                'label'      => esc_html__( 'Padding', 'wp-cafe' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .wpc-menu-tag li' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // item cart button style section
        $this->start_controls_section(
            'item_cart_button_style',
            [
                'label'     => esc_html__( 'Cart Button Style', 'wp-cafe' ),
                'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => ['wpc_cart_button_show' => 'yes'],
            ]
        );

        $this->start_controls_tabs('wpc_cart_style_tabs');

        $this->start_controls_tab('wpc_cart_style_normal',
            [
                'label' => esc_html__('Normal', 'wp-cafe'),
            ]
        );

        $this->add_control(
            'wpc_cart_color',
            [
                'label'     => esc_html__( 'Cart Button Color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wpc-food-menu-item .wpc-add-to-cart a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'wpc_cart_button_bg_color',
            [
                'label'     => esc_html__( 'Cart Button BG Color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wpc-food-menu-item .wpc-add-to-cart a' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab('wpc_cart_style_hover',
            [
                'label' => esc_html__('Hover', 'wp-cafe'),
            ]
        );

        $this->add_control(
            'wpc_cart_hover_color',
            [
                'label'     => esc_html__( 'Cart Button Color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wpc-food-menu-item .wpc-add-to-cart a:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .wpc-food-menu-item.style3:hover .wpc-food-menu-item .wpc-add-to-cart a' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'wpc_cart_button_hover_bg_color',
            [
                'label'     => esc_html__( 'Cart Button BG Color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wpc-food-menu-item .wpc-add-to-cart a:hover' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .wpc-food-menu-item.style3:hover .wpc-food-menu-item .wpc-add-to-cart a' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'wpc_cart_button_separator',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'wpc_cart_button_typo',
                'label'    => esc_html__( 'Typography', 'wp-cafe' ),
                'selector' => '{{WRAPPER}} .wpc-food-menu-item .wpc-add-to-cart a i',
            ]
        );
        $this->add_responsive_control(
            'cart_btn_width',
            [
                'label'      => esc_html__( 'Width', 'wp-cafe' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                    '%'  => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => '45',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .wpc-food-menu-item .wpc-add-to-cart a' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'cart_btn_height',
            [
                'label'      => esc_html__( 'Height', 'wp-cafe' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                    '%'  => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => '45',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .wpc-food-menu-item .wpc-add-to-cart a' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'wpc_cart_btn_paddding',
            [
                'label'      => esc_html__( 'Padding', 'wp-cafe' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .wpc-food-menu-item .wpc-add-to-cart a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // title style section
        $this->start_controls_section(
            'title_style',
            [
                'label' => esc_html__( 'Title Style', 'wp-cafe' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_control(
            'wpc_menu_title_color',
            [
                'label'     => esc_html__( 'Title Color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wpc-post-title a' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'wpc_menu_title_hover_color',
            [
                'label'     => esc_html__( 'Title Hover Color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wpc-post-title a:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .wpc-food-menu-item.style3:hover .wpc-post-title a' => 'color: {{VALUE}};',
                ],
            ]
        );

        //control for title typography
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'wpc_menu_title',
                'label'    => esc_html__( 'Title Typography', 'wp-cafe' ),
                'selector' => '{{WRAPPER}} .wpc-post-title',
            ]
        );
        $this->add_responsive_control(
            'wpc_title_margin',
            [
                'label'      => esc_html__( 'Title Margin', 'wp-cafe' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .wpc-post-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // price style section
        $this->start_controls_section(
            'price_style',
            [
                'label' => esc_html__( 'Price Style', 'wp-cafe' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->start_controls_tabs('wpc_menu_price_style_tabs');

        $this->start_controls_tab('wpc_menu_price_style_normal',
            [
                'label' => esc_html__('Normal', 'wp-cafe'),
            ]
        );

        $this->add_control(
            'wpc_menu_price_color',
            [
                'label'     => esc_html__( 'Price Color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wpc-menu-price' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .wpc-menu-currency' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'wpc_menu_price_bg_color',
            [
                'label'     => esc_html__( 'Price Background Color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wpc-menu-price' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .wpc-menu-currency' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab('wpc_menu_price_style_hover',
            [
                'label' => esc_html__('Hover', 'wp-cafe'),
            ]
        );

        $this->add_control(
            'wpc_menu_price_hover_color',
            [
                'label'     => esc_html__( 'Price Color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wpc-food-menu-item:hover .wpc-menu-price' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .wpc-food-menu-item:hover .wpc-menu-currency' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'wpc_menu_price_hover_bg_color',
            [
                'label'     => esc_html__( 'Price Background Color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wpc-food-menu-item:hover .wpc-menu-price' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .wpc-food-menu-item:hover .wpc-menu-currency' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'wpc_menu_price_separator',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        //control for title typography
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'wpc_menu_price',
                'label'    => esc_html__( 'Price Typography', 'wp-cafe' ),
                'selector' => '{{WRAPPER}} .wpc-menu-price, {{WRAPPER}} .wpc-menu-currency',
            ]
        );

        $this->end_controls_section();

        // description style section
        $this->start_controls_section(
            'wpc_desc_style',
            [
                'label' => esc_html__( 'Description Style', 'wp-cafe' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_control(
            'wpc_menu_desc_color',
            [
                'label'     => esc_html__( 'Description Color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wpc-food-inner-content p' => 'color: {{VALUE}};',
                ],
            ]
        );

        //control for title typography
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'wpc_menu_desc',
                'label'    => esc_html__( 'Description Typography', 'wp-cafe' ),
                'selector' => '{{WRAPPER}} .wpc-food-inner-content p',
            ]
        );

        $this->add_responsive_control(
            'wpc_desc_margin',
            [
                'label'      => esc_html__( 'Description Margin', 'wp-cafe' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .wpc-food-inner-content p' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->end_controls_section();

        // advance style section
        $this->start_controls_section(
            'wpc_advance_style',
            [
                'label' => esc_html__( 'Advance Style', 'wp-cafe' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'content_alignment',
            [
                'label' => esc_html__( 'Alignment', 'wp-cafe' ),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__( 'Left', 'wp-cafe' ),
                        'icon' => 'fa fa-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__( 'Center', 'wp-cafe' ),
                        'icon' => 'fa fa-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__( 'Right', 'wp-cafe' ),
                        'icon' => 'fa fa-align-right',
                    ],
                ],
                'default' => 'center',
                'toggle' => true,
                'condition' => ['food_menu_style' => ['style-3']],
                'selectors' => [
                    '{{WRAPPER}} .wpc-food-menu-item .wpc-food-single-item'   => 'text-align: {{VALUE}};'
                ],
            ]
        );

        $this->start_controls_tabs('content_style_tabs', [
            'condition' => ['food_menu_style' => ['style-3']],
        ]);

        $this->start_controls_tab('content_style_normal',
            [
                'label' => esc_html__('Normal', 'wp-cafe'),
            ]
        );

        $this->add_control(
            'content_style_bg_color',
            [
                'label' => esc_html__( 'Background Color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wpc-food-single-item' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab('content_style_hover',
            [
                'label' => esc_html__('Hover', 'wp-cafe'),
            ]
        );

        $this->add_control(
            'content_style_hover_bg_color',
            [
                'label' => esc_html__( 'Background Color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wpc-food-single-item:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control(
            'wpc_box_margin',
            [
                'label'      => esc_html__( 'Margin', 'wp-cafe' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .wpc-food-menu-item' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'wpc_box_padding',
            [
                'label'      => esc_html__( 'Padding', 'wp-cafe' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .wpc-food-menu-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'wpc_box_border',
                'label' => esc_html__( 'Border', 'wp-cafe' ),
                'selector' => '{{WRAPPER}} .wpc-food-menu-item .wpc-food-single-item',
                'condition' => ['food_menu_style' => ['style-3']],
            ]
        );
        $this->end_controls_section();
    }

    protected function render() {
        //check if woocommerce exists
        if (!class_exists('Woocommerce')) { return; }
        $settings            = $this->get_settings();
        $unique_id = $this->get_id();

        // render template
        include wpcafe()->core_dir ."shortcodes/views/food-menu/food-list.php";
    }

    protected function get_menu_category() {
        return Wpc_Utilities::get_menu_category();
    }

}
