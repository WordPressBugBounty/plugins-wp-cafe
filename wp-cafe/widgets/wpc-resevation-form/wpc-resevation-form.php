<?php

namespace WpCafe\Widgets\Wpc_Resevation_Form;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use \WpCafe\Utils\Wpc_Utilities as Wpc_Utilities;

defined( "ABSPATH" ) || exit;

class Wpc_Resevation_Form extends Widget_Base {

    /**
     * Retrieve the widget name.
     * @return string Widget name.
     */
    public function get_name() {
        return 'wpc-reservation-form';
    }

    /**
     * Retrieve the widget title.
     * @return string Widget title.
     */
    public function get_title() {
        return esc_html__( 'WPC Reservation Form', 'wp-cafe' );
    }

    /**
     * Retrieve the widget icon.
     * @return string Widget icon.
     */
    public function get_icon() {
        return 'eicon-user-circle-o';
    }

    /**
     * Retrieve the widget category.
     * @return string Widget category.
     */
    public function get_categories() {
        return ['wpcafe-menu'];
    }

    /**
     * Script handles this widget depends on.
     *
     * @return array
     */
    public function get_script_depends() {
        return [ 'wpcafe-frontend-scripts' ];
    }

    /**
     * Style handles this widget depends on.
     *
     * @return array
     */
    public function get_style_depends() {
        return [ 'wpcafe-frontend-style' ];
    }

    protected function register_controls() {
        // Start of event section
        $this->start_controls_section(
            'section_tab',
            [
                'label' => esc_html__( 'WPC Reservation Form', 'wp-cafe' ),
            ]
        );

        $this->add_control(
            'date_selector',
            [
                'label'       => esc_html__( 'Date Selection Method', 'wp-cafe' ),
                'description' => esc_html__( 'Choose how customers select a date.', 'wp-cafe' ),
                'type'        => Controls_Manager::SELECT,
                'options'     => [
                    'date_picker' => esc_html__( 'Datepicker Input', 'wp-cafe' ),
                    'calendar'    => esc_html__( 'Calendar View', 'wp-cafe' ),
                ],
                'default'     => 'date_picker',
            ]
        );

        $this->add_control(
            'form_display_type',
            [
                'label'       => esc_html__( 'Form Display Type', 'wp-cafe' ),
                'description' => esc_html__( 'How the booking form is displayed to customers.', 'wp-cafe' ),
                'type'        => Controls_Manager::SELECT,
                'options'     => [
                    'wizard' => esc_html__( 'Step by Step (Wizard)', 'wp-cafe' ),
                    'single' => esc_html__( 'Single Page (All Steps at Once)', 'wp-cafe' ),
                ],
                'default'     => 'wizard',
            ]
        );

        $this->add_control(
            'reservation_style',
            [
                'label'   => esc_html__( 'Style', 'wp-cafe' ),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'style-1' => esc_html__( 'Style 1', 'wp-cafe' ),
                    'style-2' => esc_html__( 'Style 2', 'wp-cafe' ),
                ],
                'default' => 'style-1',
            ]
        );

        $this->add_control(
            'image_link',
            [
                'label'       => esc_html__( 'Summary Image', 'wp-cafe' ),
                'description' => esc_html__( 'Displayed at the reservation summary.', 'wp-cafe' ),
                'type'        => Controls_Manager::MEDIA,
            ]
        );

        $this->add_control(
            'wpc_label_color',
            [
                'label'     => esc_html__( 'Label Color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}  .wpc-reservation-field label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'wpc_label_typo',
                'label'    => esc_html__( 'Typography', 'wp-cafe' ),
                'selector' => '{{WRAPPER}} .wpc-reservation-field label',
            ]
        );

        $this->end_controls_section();
        // Start of event section
        $this->start_controls_section(
            'section_input_field',
            [
                'label' => esc_html__( 'Input field', 'wp-cafe' ),
                'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_control(
            'wpc_input_color',
            [
                'label'     => esc_html__( 'Input Color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}   .wpc-reservation-field .wpc-form-control' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'wpc_input_bg_color',
            [
                'label'     => esc_html__( 'Input Background Color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}   .wpc-reservation-field .wpc-form-control' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'wpc_input_placeholder_color',
            [
                'label'     => esc_html__( 'Input Placeholder Color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}   .wpc-reservation-field .wpc-form-control::placeholder' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'wpc_input_typo',
                'label'    => esc_html__( 'Typography', 'wp-cafe' ),
                'selector' => '{{WRAPPER}}  .wpc-reservation-field .wpc-form-control',
            ]
        );
        $this->add_responsive_control(
            'input_height',
            [
                'label'      => esc_html__( 'Input Height', 'wp-cafe' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 1000,
                    ],
                    '%'  => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
            
                'selectors'  => [
                    '{{WRAPPER}} .wpc-reservation-field .wpc-form-control' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'input_textarea_height',
            [
                'label'      => esc_html__( 'Textarea Height', 'wp-cafe' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 1000,
                    ],
                    '%'  => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
             
                'selectors'  => [
                    '{{WRAPPER}} .wpc-reservation-form .wpc-reservation-field .wpc-form-control#wpc-message,{{WRAPPER}} .wpc-reservation-form .wpc-reservation-field .wpc_cancell_message' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'input_padding',
            [
                'label'      => esc_html__( 'Input Padding', 'wp-cafe' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .wpc-reservation-field .wpc-form-control' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Start of button section
        $this->start_controls_section(
            'section_button',
            [
                'label' => esc_html__( 'Button', 'wp-cafe' ),
                'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_control(
            'wpc_btn_link_color',
            [
                'label'     => esc_html__( 'Button Link color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}   #wpc_book_table'     => 'color: {{VALUE}};',
                    '{{WRAPPER}}   #wpc_cancel_request' => 'color: {{VALUE}};',
                ],
            ]
        );

        //start of nav color tabs (normal and hover)
        $this->start_controls_tabs(
            'wpc_btn_tabs'
            
        );

        //start of nav normal color tab
        $this->start_controls_tab(
            'wpc_btn_normal_tab',
            [
                'label' => esc_html__( 'Normal', 'wp-cafe' ),
            ]
        );

        $this->add_control(
            'wpc_btn_color',
            [
                'label'     => esc_html__( 'Button color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}   .wpc-btn' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'wpc_btn_bg_color',
            [
                'label'     => esc_html__( 'Button Background color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}   .wpc-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'btn_box_shadow',
                'label'    => esc_html__( 'Box Shadow', 'wp-cafe' ),
                'selector' => '{{WRAPPER}}  .wpc-btn',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'btn_border',
                'label'    => esc_html__( 'Border', 'wp-cafe' ),
                'selector' => '{{WRAPPER}} .wpc-btn',
            ]
        );

        $this->end_controls_tab();
		//end of nav normal color tab

        //start of nav active color tab
        $this->start_controls_tab(
            'wpc_btn_hover_tab',
            [
                'label' => esc_html__( 'Hover', 'wp-cafe' ),
            ]
        );
        $this->add_control(
            'wpc_btn_Hover_color',
            [
                'label'     => esc_html__( 'Button Hover color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}   .wpc-btn:hover' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'wpc_btn_bg_hover_color',
            [
                'label'     => esc_html__( 'Button Background Hover color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}   .wpc-btn:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'btn_box__hover_shadow',
                'label'    => esc_html__( 'Box Shadow', 'wp-cafe' ),
                'selector' => '{{WRAPPER}}  .wpc-btn:hover',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'btn_border_hover',
                'label'    => esc_html__( 'Border', 'wp-cafe' ),
                'selector' => '{{WRAPPER}} .wpc-btn:hover',
            ]
        );
        $this->end_controls_tab();
        //end of nav hover color tab

        $this->end_controls_tabs();
        //end of nav color tabs (normal and hover)

        $this->add_responsive_control(
            'wpc_btn_padding',
            [
                'label'      => esc_html__( 'Button Padding', 'wp-cafe' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .wpc-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Start of event section
        $this->start_controls_section(
            'section_advance',
            [
                'label' => esc_html__( 'Advance', 'wp-cafe' ),
                'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_control(
            'wpc_form_bg_color',
            [
                'label'     => esc_html__( 'Form Backround color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}   .wpc-reservation-form' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'calender_bg_color',
            [
                'label'     => esc_html__( 'Calender BG color', 'wp-cafe' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wpc-reservation-field.date.wpc-reservation-calender-field, {{WRAPPER}} .wpc-reservation-form .wpc_reservation_user_info' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .wpc-reservation-field.date .flatpickr-day, {{WRAPPER}} .wpc-food-menu-item.style2:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'box_shadow',
                'label'    => esc_html__( 'Box Shadow', 'wp-cafe' ),
                'selector' => '{{WRAPPER}}  .wpc_reservation_form',
            ]
        );

        $this->add_responsive_control(
            'box_padding',
            [
                'label'      => esc_html__( 'Box Padding', 'wp-cafe' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .wpc_reservation_form' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $date_selector     = ! empty( $settings['date_selector'] ) ? $settings['date_selector'] : 'date_picker';
        $reservation_style = ! empty( $settings['reservation_style'] ) ? $settings['reservation_style'] : 'style-1';
        $form_display_type = ! empty( $settings['form_display_type'] ) ? $settings['form_display_type'] : 'wizard';
        $image_link        = ! empty( $settings['image_link']['url'] ) ? $settings['image_link']['url'] : '';

        $allowed_date_selectors = [ 'date_picker', 'calendar' ];
        $allowed_styles         = [ 'style-1', 'style-2' ];
        $allowed_display_types  = [ 'wizard', 'single' ];

        if ( ! in_array( $date_selector, $allowed_date_selectors, true ) ) {
            $date_selector = 'date_picker';
        }
        if ( ! in_array( $reservation_style, $allowed_styles, true ) ) {
            $reservation_style = 'style-1';
        }
        if ( ! in_array( $form_display_type, $allowed_display_types, true ) ) {
            $form_display_type = 'wizard';
        }

        echo do_shortcode( sprintf(
            "[wpc_reservation_form date_selector='%s' reservation_style='%s' form_display_type='%s' image_link='%s']",
            esc_attr( $date_selector ),
            esc_attr( $reservation_style ),
            esc_attr( $form_display_type ),
            esc_url( $image_link )
        ) );
    }

    protected function get_menu_category() {
        return Wpc_Utilities::get_menu_category();
    }

}
