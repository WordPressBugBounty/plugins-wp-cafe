<?php


namespace WpCafe\Widgets\Wpc_Location_Menu;

defined("ABSPATH") || exit;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use \WpCafe\Utils\Wpc_Utilities as Wpc_Utilities;

class Wpc_Location_Menu extends Widget_Base
{

	/**
     * Retrieve the widget name.
     * @return string Widget name.
     */
	public function get_name()
    {
        return 'wpc-location-menu';
    }

	/**
     * Retrieve the widget title.
     * @return string Widget title.
     */
	public function get_title()
    {
        return esc_html__('WPC Food Location Filter', 'wp-cafe');
    }

	/**
     * Retrieve the widget icon.
     * @return string Widget icon.
     */
	public function get_icon()
    {
        return 'eicon-filter';
    }

	/**
     * Retrieve the widget category.
     * @return string Widget category.
     */
	public function get_categories()
    {
        return ['wpcafe-menu'];
    }

	protected function register_controls()
    {
        // Start of event section
        $this->start_controls_section(
            'section_tab',
            [
                'label' => esc_html__('WPC Food Location Filter', 'wp-cafe'),
            ]
        );

        $this->add_control(
            'food_menu_style',
            [
                'label' => esc_html__('Menu Style', 'wp-cafe'),
                'type' => Controls_Manager::SELECT,
                'default' => 'style-1',
                'options' => [
                    'style-1'  => esc_html__('Menu Style 1', 'wp-cafe'),
                ],
            ]
        );

        $this->add_control(
            'wpc_menu_cat',
            [
                'label' => esc_html__('Menu Category', 'wp-cafe'),
                'type' => Controls_Manager::SELECT2,
                'options' => $this->get_menu_category(),
                'multiple' => true,
            ]
        );
        $this->add_control(
            'wpc_menu_count',
            [
                'label'         => esc_html__('Menu count', 'wp-cafe'),
                'type'          => Controls_Manager::NUMBER,
                'default'       => '6',
            ]
        );
        $this->add_control(
            'wpc_menu_order',
            [
                'label' => esc_html__('Menu Order', 'wp-cafe'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'DESC',
                'options' => [
                    'ASC'  => esc_html__('ASC', 'wp-cafe'),
                    'DESC' => esc_html__('DESC', 'wp-cafe'),
                ],
            ]
        );


        $this->add_control(
            'show_thumbnail',
            [
                'label' => esc_html__('Show Thumbnail', 'wp-cafe'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'wp-cafe'),
                'label_off' => esc_html__('Hide', 'wp-cafe'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_item_status',
            [
                'label' => esc_html__('Show Item Status', 'wp-cafe'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'wp-cafe'),
                'label_off' => esc_html__('Hide', 'wp-cafe'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        $this->add_control(
            'wpc_show_desc',
            [
                'label' => esc_html__('Show Description', 'wp-cafe'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'wp-cafe'),
                'label_off' => esc_html__('Hide', 'wp-cafe'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        $this->add_control(
            'wpc_desc_limit',
            [
                'label'         => esc_html__('Description Limit', 'wp-cafe'),
                'type'          => Controls_Manager::NUMBER,
                'default'       => '15',
                'condition' => ['wpc_show_desc' => 'yes']
            ]
        );
        $this->add_control(
            'title_link_show',
            [
                'label' => esc_html__('Use Title Link?', 'wp-cafe'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'wp-cafe'),
                'label_off' => esc_html__('Hide', 'wp-cafe'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'wpc_delivery_time_show',
            [
                'label' => esc_html__('Show Preparing and Delivery Time', 'wp-cafe'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'wp-cafe'),
                'label_off' => esc_html__('Hide', 'wp-cafe'),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );
        $this->add_control(
            'wpc_cart_button_show',
            [
                'label' => esc_html__('Show Cart Button', 'wp-cafe'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'wp-cafe'),
                'label_off' => esc_html__('Hide', 'wp-cafe'),
                'return_value' => 'yes',
                'default' => 'yes',
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

        $this->end_controls_section();

        // item thumbnail style section
        $this->start_controls_section(
            'item_pro_thumbanil_style',
            [
                'label' => esc_html__('Thumbnail Style', 'wp-cafe'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,

            ]
        );
        $this->add_responsive_control(
            'thumbnail_width',
            [
                'label' => esc_html__('Width', 'wp-cafe'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 1000,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 1000,
                    ],
                ],

                'selectors' => [
                    '{{WRAPPER}} .wpc-food-menu-item .wpc-food-menu-thumb' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'thumbnail_height',
            [
                'label' => esc_html__('Height', 'wp-cafe'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 1000,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 1000,
                    ],
                ],

                'selectors' => [
                    '{{WRAPPER}} .wpc-food-menu-item .wpc-food-menu-thumb' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'wpc_pro_thum_border_radius',
            [
                'label' => esc_html__('Border Radius', 'wp-cafe'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .wpc-food-menu-item .wpc-food-menu-thumb' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // item cart button style section
        $this->start_controls_section(
            'item_pro_cart_button_style',
            [
                'label' => esc_html__('Cart Button Style', 'wp-cafe'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => ['wpc_cart_button_show' => 'yes']
            ]
        );
        $this->add_control(
            'wpc_pro_cart_color',
            [
                'label'         => esc_html__('Cart Button Color', 'wp-cafe'),
                'type'         => Controls_Manager::COLOR,
                'selectors'     => [
                    '{{WRAPPER}} .wpc-food-menu-item .wpc-add-to-cart a' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'wpc_pro_cart_button_bg_color',
            [
                'label'         => esc_html__('Cart Button BG Color', 'wp-cafe'),
                'type'         => Controls_Manager::COLOR,
                'selectors'     => [
                    '{{WRAPPER}} .wpc-food-menu-item .wpc-add-to-cart a' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'         => 'wpc_pro_cart_button_typo',
                'label'         => esc_html__('Typography', 'wp-cafe'),
                'selector'     => '{{WRAPPER}} .wpc-food-menu-item .wpc-add-to-cart a',
            ]
        );
        $this->add_responsive_control(
            'wpc_pro_cart_btn_width',
            [
                'label' => esc_html__('Width', 'wp-cafe'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 500,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => '',
                ],
                'selectors' => [
                    '{{WRAPPER}} .wpc-food-menu-item .wpc-add-to-cart a' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'wpc_pro_cart_btn_height',
            [
                'label' => esc_html__('Height', 'wp-cafe'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => '',
                ],
                'selectors' => [
                    '{{WRAPPER}} .wpc-food-menu-item .wpc-add-to-cart a' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'wpc_pro_cart_btn_paddding',
            [
                'label' => esc_html__('Padding', 'wp-cafe'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .wpc-food-menu-item .wpc-add-to-cart a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'wpc_pro_cart_btn_border_raidus',
            [
                'label' => esc_html__('Border Radius', 'wp-cafe'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .wpc-food-menu-item .wpc-add-to-cart a' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'wpc_pro_cart_btn_position_rtl',
            [
                'label' => esc_html__('Button Right To Left', 'wp-cafe'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => -200,
                        'max' => 500,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 0,
                ],
                'selectors' => [
                    '{{WRAPPER}} .wpc-food-menu-item .wpc-add-to-cart' => 'right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'wpc_pro_cart_btn_position_ttb',
            [
                'label' => esc_html__('Button Bottom To Top', 'wp-cafe'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => -200,
                        'max' => 500,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 0,
                ],
                'selectors' => [
                    '{{WRAPPER}} .wpc-food-menu-item .wpc-add-to-cart' => 'bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );


        $this->end_controls_section();

        // item status style section
        $this->start_controls_section(
            'item_status_style',
            [
                'label' => esc_html__('Item Status Style', 'wp-cafe'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => ['show_item_status' => 'yes']
            ]
        );
        $this->add_control(
            'wpc_menu_item_status_color',
            [
                'label'         => esc_html__('Item Status Color', 'wp-cafe'),
                'type'         => Controls_Manager::COLOR,
                'selectors'     => [
                    '{{WRAPPER}} .wpc-menu-tag li' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'wpc_menu_item_status_bg_color',
            [
                'label'         => esc_html__('Item Status BG Color', 'wp-cafe'),
                'type'         => Controls_Manager::COLOR,
                'selectors'     => [
                    '{{WRAPPER}} .wpc-menu-tag li' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'         => 'wpc_menu_status_typo',
                'label'         => esc_html__('Typography', 'wp-cafe'),
                'selector'     => '{{WRAPPER}} .wpc-menu-tag li',
            ]
        );
        $this->add_responsive_control(
            'wpc_menu_item_status_paddding',
            [
                'label' => esc_html__('Padding', 'wp-cafe'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .wpc-menu-tag li' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'wpc_item_status_border_radius',
            [
                'label' => esc_html__('Border Radius', 'wp-cafe'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .wpc-menu-tag li' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();



        // title style section
        $this->start_controls_section(
            'title_style',
            [
                'label' => esc_html__('Title Style', 'wp-cafe'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_control(
            'wpc_menu_title_color',
            [
                'label'         => esc_html__('Title Color', 'wp-cafe'),
                'type'         => Controls_Manager::COLOR,
                'selectors'     => [
                    '{{WRAPPER}} .wpc-post-title a' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'wpc_menu_title_hover_color',
            [
                'label'         => esc_html__('Title Hover Color', 'wp-cafe'),
                'type'         => Controls_Manager::COLOR,
                'selectors'     => [
                    '{{WRAPPER}} .wpc-post-title a:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'wpc_menu_title_border_color',
            [
                'label'         => esc_html__('Title Border Color', 'wp-cafe'),
                'type'         => Controls_Manager::COLOR,
                'condition' => ['food_menu_style' => 'style-1'],
                'selectors'     => [
                    '{{WRAPPER}} .wpc-post-title.wpc-title-with-border .wpc-title-border' => 'background-image:radial-gradient(circle, {{VALUE}}, {{VALUE}} 10%, transparent 50%, transparent);',
                ],
            ]
        );
        //control for title typography
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'         => 'wpc_menu_title',
                'label'         => esc_html__('Title Typography', 'wp-cafe'),
                'selector'     => '{{WRAPPER}} .wpc-post-title',
            ]
        );
        $this->add_responsive_control(
            'wpc_title_margin',
            [
                'label' => esc_html__('Title Margin', 'wp-cafe'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .wpc-post-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // price style section
        $this->start_controls_section(
            'price_style',
            [
                'label' => esc_html__('Price Style', 'wp-cafe'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_control(
            'wpc_menu_price_color',
            [
                'label'         => esc_html__('Price Color', 'wp-cafe'),
                'type'         => Controls_Manager::COLOR,
                'selectors'     => [
                    '{{WRAPPER}} .wpc-menu-currency' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'wpc_menu_price_bg_color',
            [
                'label'         => esc_html__('Price background Color', 'wp-cafe'),
                'type'         => Controls_Manager::COLOR,
                'condition' => ['food_menu_style' => 'style-3'],
                'selectors'     => [
                    '{{WRAPPER}} .wpc-price' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        //control for title typography
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'         => 'wpc_menu_price',
                'label'         => esc_html__('Price Typography', 'wp-cafe'),
                'selector'     => '{{WRAPPER}} .wpc-menu-currency',
            ]
        );

        $this->end_controls_section();

        // description style section
        $this->start_controls_section(
            'wpc_desc_style',
            [
                'label' => esc_html__('Description Style', 'wp-cafe'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => ['wpc_show_desc' => 'yes'],
            ]
        );
        $this->add_control(
            'wpc_menu_desc_color',
            [
                'label'         => esc_html__('Description Color', 'wp-cafe'),
                'type'         => Controls_Manager::COLOR,
                'selectors'     => [
                    '{{WRAPPER}} .wpc-food-inner-content p' => 'color: {{VALUE}};',
                ],
            ]
        );

        //control for title typography
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'         => 'wpc_menu_desc',
                'label'         => esc_html__('Description Typography', 'wp-cafe'),
                'selector'     => '{{WRAPPER}} .wpc-food-inner-content p',
            ]
        );

        $this->add_responsive_control(
            'wpc_desc_padding',
            [
                'label' => esc_html__('Description Padding', 'wp-cafe'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .wpc-food-inner-content p' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'wpc_desc_margin',
            [
                'label' => esc_html__('Description Margin', 'wp-cafe'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .wpc-food-inner-content p' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->end_controls_section();

        // search field
        $this->start_controls_section(
            'wpc_location_field_style',
            [
                'label' => esc_html__('Location Field Style', 'wp-cafe'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_responsive_control(
            'location_alignment',
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

            ]
        );

        $this->end_controls_section();

        // advance style section
        $this->start_controls_section(
            'wpc_advance_style',
            [
                'label' => esc_html__('Advance Style', 'wp-cafe'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_responsive_control(
            'wpc_box_margin',
            [
                'label' => esc_html__('Margin', 'wp-cafe'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .wpc-food-menu-item' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'wpc_box_padding',
            [
                'label' => esc_html__('Padding', 'wp-cafe'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .wpc-food-menu-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .wpc-menu-list-style2 .wpc-food-inner-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .wpc-menu-list-style4 .wpc-food-menu-item .wpc-food-inner-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'box_shadow',
                'label' => esc_html__('Box Shadow', 'wp-cafe'),
                'selector' => '{{WRAPPER}} .wpc-food-menu-item',
            ]
        );
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'wpc_menu_item_border',
                'label' => esc_html__('Border', 'wp-cafe'),
                'selector' => '{{WRAPPER}} .wpc-food-menu-item',
            ]
        );

        $this->end_controls_section();
    }


	protected function render(){

        $settings   			= $this->get_settings();
        $style      			= $settings["food_menu_style"];
        $wpc_menu_col      		= '';
        $wpc_menu_cat      		= $settings["wpc_menu_cat"];
        $wpc_menu_count    		= $settings["wpc_menu_count"];
        $wpc_menu_order      	= $settings["wpc_menu_order"];

        $show_item_status      	= $settings["show_item_status"];
        $show_thumbnail      	= $settings["show_thumbnail"];
        $wpc_cart_button        = $settings["wpc_cart_button_show"];
        $wpc_price_show      	= $settings["wpc_price_show"];
        $title_link_show      	= $settings["title_link_show"];
        $wpc_desc_limit      	= $settings["wpc_desc_limit"];
        $wpc_show_desc      	= $settings["wpc_show_desc"];
        $wpc_delivery_time_show = $settings["wpc_delivery_time_show"];

        $location_alignment		= $settings['location_alignment'];

        $unique_id = $this->get_id();
        $no_desc_class = ($wpc_show_desc != 'yes') ? 'wpc-no-desc' : '';

        ?>
        <?php
        $product_data = [
            'style'                 => $style,
            'wpc_food_categories'   => $wpc_menu_cat,
            'no_of_product'         => $wpc_menu_count,

            'show_thumbnail'        => $show_thumbnail,
            'wpc_cart_button'       => $wpc_cart_button,
            'wpc_price_show'        => $wpc_price_show,
            'title_link_show'       => $title_link_show,
            'wpc_menu_col'          => $wpc_menu_col,
            'wpc_show_desc'         => $wpc_show_desc,
            'wpc_desc_limit'        => $wpc_desc_limit,
            'wpc_delivery_time_show'=> $wpc_delivery_time_show,
            'show_item_status'      => $show_item_status,
            'wpc_menu_order'        => $wpc_menu_order,
            'unique_id'             => $unique_id,
            'location_alignment'   => $location_alignment
        ];
        $food_list_args = array(
            'post_type'     => 'product',
            'no_of_product' => $wpc_menu_count,
            'wpc_cat'       => $wpc_menu_cat,
            'order'         => $wpc_menu_order,
        );

        $unique_id = md5(md5(microtime()));

        $products = Wpc_Utilities::product_query( $food_list_args );
        ?>
        <div class="food_location_wrapper main_wrapper_<?php echo esc_html($unique_id)?>" data-id="<?php echo esc_attr($unique_id);?>" >

            <div class="location_menu" data-product_data ="<?php echo esc_attr( json_encode( $product_data  ));?>"
            data-id="<?php echo esc_attr( $unique_id );?>">
                <?php include wpcafe()->plugin_directory . "/core/shortcodes/views/food-menu/location-select.php"; ?>
            </div>
        </div>
        <?php
    }

	protected function get_menu_category()
    {
        return Wpc_Utilities::get_menu_category();
    }
}
