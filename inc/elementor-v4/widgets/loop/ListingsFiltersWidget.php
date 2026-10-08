<?php
/**
 * Listings filters widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Loop;

use Directorist\Directorist_Listing_Search_Form;
use Directorist\Helper;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

class ListingsFiltersWidget extends AbstractLoopUtilityWidget {

	/**
	 * Get widget slug.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'directorist_listings_filters';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Listings Filters', 'directorist-elementor' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-filter';
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_filters_content',
			[
				'label' => __( 'Filters', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'filters_text',
			[
				'label'       => __( 'Filters Title', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Filters', 'directorist-elementor' ),
				'placeholder' => __( 'Filters', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'reset_text',
			[
				'label'       => __( 'Reset Text', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Clear All', 'directorist-elementor' ),
				'placeholder' => __( 'Clear All', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'mobile_floating',
			[
				'label'        => __( 'Mobile Floating Panel', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_filters_style_container',
			[
				'label' => __( 'Filter Container', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'filters_container_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-filters',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'filters_container_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-filters',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'filters_container_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-filters',
			]
		);

		$this->add_responsive_control(
			'filters_container_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-filters' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'filters_container_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-filters' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_filters_style_panel',
			[
				'label' => __( 'Filter Panel', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'filters_panel_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-filters .directorist-search-form__box',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'filters_panel_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-filters .directorist-search-form__box',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'filters_panel_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-filters .directorist-search-form__box',
			]
		);

		$this->add_responsive_control(
			'filters_panel_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-search-form__box' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'filters_panel_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-search-form__box' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_filters_style_header_shell',
			[
				'label' => __( 'Filter Header', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'filters_header_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-filters .directorist-advanced-filter__top',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'filters_header_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-filters .directorist-advanced-filter__top',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'filters_header_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-filters .directorist-advanced-filter__top',
			]
		);

		$this->add_responsive_control(
			'filters_header_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-advanced-filter__top' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'filters_header_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-advanced-filter__top' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_filters_style_header',
			[
				'label' => __( 'Filter Title', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'filters_title_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-filters .directorist-advanced-filter__title',
			]
		);

		$this->add_control(
			'filters_title_color',
			[
				'label'     => __( 'Title Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-advanced-filter__title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_filters_style_body',
			[
				'label' => __( 'Filter Body', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'filters_body_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-filters .directorist-advanced-filter__advanced',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'filters_body_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-filters .directorist-advanced-filter__advanced',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'filters_body_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-filters .directorist-advanced-filter__advanced',
			]
		);

		$this->add_responsive_control(
			'filters_body_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-advanced-filter__advanced' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'filters_body_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-advanced-filter__advanced' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_filters_style_field_wrap',
			[
				'label' => __( 'Field Wrapper', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'filters_field_wrap_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-filters .directorist-advanced-filter__advanced__element',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'filters_field_wrap_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-filters .directorist-advanced-filter__advanced__element',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'filters_field_wrap_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-filters .directorist-advanced-filter__advanced__element',
			]
		);

		$this->add_responsive_control(
			'filters_field_wrap_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-advanced-filter__advanced__element' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'filters_field_wrap_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-advanced-filter__advanced__element' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_filters_style_fields',
			[
				'label' => __( 'Fields', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'filters_labels_color',
			[
				'label'     => __( 'Label Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-search-field__label' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-search-basic-dropdown-label' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-price-ranges__label' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-custom-range-slider__label' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'filters_labels_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-filters .directorist-search-field__label, {{WRAPPER}} .directorist-elementor-listings-filters .directorist-search-basic-dropdown-label, {{WRAPPER}} .directorist-elementor-listings-filters .directorist-price-ranges__label, {{WRAPPER}} .directorist-elementor-listings-filters .directorist-custom-range-slider__label',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'filters_fields_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-filters .directorist-search-field__input, {{WRAPPER}} .directorist-elementor-listings-filters .directorist-form-element, {{WRAPPER}} .directorist-elementor-listings-filters .directorist-custom-range-slider__text, {{WRAPPER}} .directorist-elementor-listings-filters .select2-container--default .select2-selection__rendered',
			]
		);

		$this->start_controls_tabs( 'tabs_filters_field_states' );

		$this->start_controls_tab(
			'tab_filters_field_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'filters_field_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-search-field__input' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-form-element' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-custom-range-slider__text' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .select2-container--default .select2-selection__rendered' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'filters_field_placeholder_color',
			[
				'label'     => __( 'Placeholder Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-search-field__input::placeholder' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-form-element::placeholder' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-custom-range-slider__text::placeholder' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'filters_field_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-search-field__input' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-form-element' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-custom-range-slider__text' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .select2-container--default .select2-selection--single' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .select2-container--default .select2-selection--multiple' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-search-basic-dropdown' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'filters_field_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-search-field__input' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-form-element' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-custom-range-slider__text' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .select2-container--default .select2-selection--single' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .select2-container--default .select2-selection--multiple' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-search-basic-dropdown' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_filters_field_focus',
			[
				'label' => __( 'Focus', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'filters_field_focus_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-search-field__input:focus' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-form-element:focus' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-custom-range-slider__text:focus' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .select2-container--default.select2-container--open .select2-selection--single' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .select2-container--default.select2-container--open .select2-selection--multiple' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'filters_field_focus_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-search-field__input:focus' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-form-element:focus' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-custom-range-slider__text:focus' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .select2-container--default.select2-container--open .select2-selection--single' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .select2-container--default.select2-container--open .select2-selection--multiple' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'filters_field_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-search-field__input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-form-element' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-custom-range-slider__text' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .select2-container--default .select2-selection--single' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .select2-container--default .select2-selection--multiple' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-search-basic-dropdown' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_filters_style_action',
			[
				'label' => __( 'Action Area', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'filters_action_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-filters .directorist-advanced-filter__action',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'filters_action_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-filters .directorist-advanced-filter__action',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'filters_action_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-filters .directorist-advanced-filter__action',
			]
		);

		$this->add_responsive_control(
			'filters_action_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-advanced-filter__action' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'filters_action_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-advanced-filter__action' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_filters_style_reset_button',
			[
				'label' => __( 'Clear All Button', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'filters_reset_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-filters .directorist-btn-reset-ajax, {{WRAPPER}} .directorist-elementor-listings-filters .directorist-btn-reset-js',
			]
		);

		$this->start_controls_tabs( 'tabs_filters_reset_states' );

		$this->start_controls_tab(
			'tab_filters_reset_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'filters_reset_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-btn-reset-ajax' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-btn-reset-js'   => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'filters_reset_background',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-btn-reset-ajax' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-btn-reset-js'   => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'filters_reset_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-btn-reset-ajax' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-btn-reset-js'   => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_filters_reset_hover',
			[
				'label' => __( 'Hover', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'filters_reset_hover_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-btn-reset-ajax:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-btn-reset-ajax:focus' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-btn-reset-js:hover'   => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-btn-reset-js:focus'   => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'filters_reset_hover_background',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-btn-reset-ajax:hover' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-btn-reset-ajax:focus' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-btn-reset-js:hover'   => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-btn-reset-js:focus'   => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'filters_reset_hover_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-btn-reset-ajax:hover' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-btn-reset-ajax:focus' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-btn-reset-js:hover'   => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-btn-reset-js:focus'   => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'filters_reset_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-btn-reset-ajax' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-btn-reset-js'   => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'filters_reset_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-btn-reset-ajax' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-filters .directorist-btn-reset-js'   => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render the filters widget.
	 *
	 * @return void
	 */
	protected function render(): void {
		$settings   = $this->get_settings_for_display();
		$rendered   = false;

		$this->with_active_loop_context(
			function() use ( $settings, &$rendered ) {
				$controller = $this->get_cloned_loop_controller();

				if (
					! $controller ||
					! class_exists( Directorist_Listing_Search_Form::class ) ||
					! class_exists( Helper::class )
				) {
					return;
				}

				$controller->options['sidebar_filter_text'] = '' !== trim( (string) ( $settings['filters_text'] ?? '' ) )
					? (string) $settings['filters_text']
					: (string) ( $controller->options['sidebar_filter_text'] ?? __( 'Filters', 'directorist-elementor' ) );

				$search_field_atts = array_filter(
					(array) ( $controller->atts ?? [] ),
					static function( $key ) {
						return 0 === strpos( (string) $key, 'filter_' );
					},
					ARRAY_FILTER_USE_KEY
				);
				$search_form       = new Directorist_Listing_Search_Form( 'search_result', (int) $controller->current_listing_type, $search_field_atts );
				$search_form->has_apply_filters_button = false;
				$search_form->has_reset_filters_button = false;

				if ( '' !== trim( (string) ( $settings['reset_text'] ?? '' ) ) ) {
					$search_form->options['reset_sidebar_filters_text'] = (string) $settings['reset_text'];
				}

				$classes = [
					'directorist-elementor-listings-filters',
					'directorist-elementor-listings-archive-filters',
					'listing-with-sidebar__sidebar',
				];

				if ( 'yes' === (string) ( $settings['mobile_floating'] ?? '' ) ) {
					$classes[] = 'directorist-gbi-filters-mobile-floating';
				}

				$attributes = $this->build_loop_utility_attributes( $classes );

				echo '<div ' . $this->format_html_attributes( $attributes ) . '>';

				if ( 'yes' === (string) ( $settings['mobile_floating'] ?? '' ) ) {
					printf(
						'<button type="button" class="directorist-gbi-filters-mobile-overlay" aria-label="%s"></button>',
						esc_attr__( 'Close filters', 'directorist-elementor' )
					);
				}

				Helper::get_template(
					'archive/advance-search-form',
					[
						'listings'   => $controller,
						'searchform' => $search_form,
					]
				);

				echo '</div>';

				$rendered = true;
			}
		);

		if ( ! $rendered ) {
			$this->render_loop_context_placeholder(
				__( 'Listings Filters', 'directorist-elementor' ),
				__( 'Place this widget inside Listings Loop to render the active directory filter panel.', 'directorist-elementor' )
			);
		}
	}
}
