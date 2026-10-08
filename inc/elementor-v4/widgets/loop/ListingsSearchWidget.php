<?php
/**
 * Listings search widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Loop;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

class ListingsSearchWidget extends AbstractSearchCompositionWidget {

	/**
	 * Get element type.
	 *
	 * @return string
	 */
	public static function get_type() {
		return 'directorist_listings_search';
	}

	/**
	 * Get element slug.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return static::get_type();
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Listings Search', 'directorist-elementor' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-search';
	}

	/**
	 * Get the Elementor top-level tab used by search design controls.
	 *
	 * @return string
	 */
	protected function get_search_style_controls_tab(): string {
		return Controls_Manager::TAB_STYLE;
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_search_style_container',
			[
				'label' => __( 'Search Container', 'directorist-elementor' ),
				'tab'   => $this->get_search_style_controls_tab(),
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'search_container_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-search',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'search_container_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-search',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'search_container_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-search',
			]
		);

		$this->add_responsive_control(
			'search_container_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-search' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'search_container_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-search' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_search_style_shell',
			[
				'label' => __( 'Search Box', 'directorist-elementor' ),
				'tab'   => $this->get_search_style_controls_tab(),
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'search_shell_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form__box',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'search_shell_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form__box',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'search_shell_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form__box',
			]
		);

		$this->add_responsive_control(
			'search_shell_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form__box' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'search_shell_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form__box' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_search_style_fields_row',
			[
				'label' => __( 'Search Fields Row', 'directorist-elementor' ),
				'tab'   => $this->get_search_style_controls_tab(),
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'search_fields_row_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form__top',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'search_fields_row_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form__top',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'search_fields_row_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form__top',
			]
		);

		$this->add_responsive_control(
			'search_fields_row_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form__top' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'search_fields_row_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form__top' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_search_style_field_wrap',
			[
				'label' => __( 'Field Wrapper', 'directorist-elementor' ),
				'tab'   => $this->get_search_style_controls_tab(),
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'search_field_wrap_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form__top > .directorist-form-group, {{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form__top > .directorist-search-field',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'search_field_wrap_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form__top > .directorist-form-group, {{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form__top > .directorist-search-field',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'search_field_wrap_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form__top > .directorist-form-group, {{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form__top > .directorist-search-field',
			]
		);

		$this->add_responsive_control(
			'search_field_wrap_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form__top > .directorist-form-group'  => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form__top > .directorist-search-field' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'search_field_wrap_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form__top > .directorist-form-group'  => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form__top > .directorist-search-field' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_search_style_fields',
			[
				'label' => __( 'Field Input', 'directorist-elementor' ),
				'tab'   => $this->get_search_style_controls_tab(),
			]
		);

		$this->add_control(
			'search_fields_label_color',
			[
				'label'     => __( 'Label Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-field__label' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-basic-dropdown-label' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-price-ranges__label' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-custom-range-slider__label' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'search_fields_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-field__input, {{WRAPPER}} .directorist-elementor-listings-search .directorist-form-element, {{WRAPPER}} .directorist-elementor-listings-search .directorist-custom-range-slider__text, {{WRAPPER}} .directorist-elementor-listings-search .select2-container--default .select2-selection__rendered',
			]
		);

		$this->start_controls_tabs( 'tabs_search_field_states' );

		$this->start_controls_tab(
			'tab_search_field_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'search_field_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-field__input' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-form-element' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-custom-range-slider__text' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .select2-container--default .select2-selection__rendered' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'search_field_placeholder_color',
			[
				'label'     => __( 'Placeholder Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-field__input::placeholder' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-form-element::placeholder' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-custom-range-slider__text::placeholder' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'search_field_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-field__input' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-form-element' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-custom-range-slider__text' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .select2-container--default .select2-selection--single' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .select2-container--default .select2-selection--multiple' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-basic-dropdown' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'search_field_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-field__input' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-form-element' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-custom-range-slider__text' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .select2-container--default .select2-selection--single' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .select2-container--default .select2-selection--multiple' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-basic-dropdown' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_search_field_focus',
			[
				'label' => __( 'Focus', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'search_field_focus_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-field__input:focus' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-form-element:focus' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-custom-range-slider__text:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'search_field_focus_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-field__input:focus' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-form-element:focus' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-custom-range-slider__text:focus' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .select2-container--default.select2-container--open .select2-selection--single' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .select2-container--default.select2-container--open .select2-selection--multiple' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'search_field_focus_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-field__input:focus' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-form-element:focus' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-custom-range-slider__text:focus' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .select2-container--default.select2-container--open .select2-selection--single' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .select2-container--default.select2-container--open .select2-selection--multiple' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'search_field_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-field__input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-form-element' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-custom-range-slider__text' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .select2-container--default .select2-selection--single' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .select2-container--default .select2-selection--multiple' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-basic-dropdown' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'search_field_padding',
			[
				'label'      => __( 'Field Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-field__input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-form-element' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-custom-range-slider__text' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .select2-container--default .select2-selection--single' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .select2-container--default .select2-selection--multiple' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_search_style_submit_area',
			[
				'label' => __( 'Submit Area', 'directorist-elementor' ),
				'tab'   => $this->get_search_style_controls_tab(),
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'search_submit_area_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form-action',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'search_submit_area_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form-action',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'search_submit_area_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form-action',
			]
		);

		$this->add_responsive_control(
			'search_submit_area_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form-action' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'search_submit_area_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-search-form-action' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_search_style_button',
			[
				'label' => __( 'Submit Button', 'directorist-elementor' ),
				'tab'   => $this->get_search_style_controls_tab(),
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'search_button_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-search .directorist-btn-search',
			]
		);

		$this->start_controls_tabs( 'tabs_search_button_states' );

		$this->start_controls_tab(
			'tab_search_button_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'search_button_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-btn-search' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'search_button_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-btn-search' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'search_button_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-btn-search' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_search_button_hover',
			[
				'label' => __( 'Hover', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'search_button_hover_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-btn-search:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-btn-search:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'search_button_hover_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-btn-search:hover' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-btn-search:focus' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'search_button_hover_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-btn-search:hover' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-btn-search:focus' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'search_button_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-btn-search' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'search_button_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-search .directorist-btn-search' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render the search widget.
	 *
	 * @return void
	 */
	protected function render(): void {
		$rendered = false;

		$this->with_active_loop_context(
			function() use ( &$rendered ) {
				$rendered = $this->render_search_composition(
					[
						'directorist-elementor-listings-search',
						'directorist-elementor-listings-archive-search',
					]
				);
			}
		);

		if ( ! $rendered ) {
			$this->render_loop_context_placeholder(
				__( 'Listings Search', 'directorist-elementor' ),
				__( 'Place this widget inside Listings Loop to render the active directory search form.', 'directorist-elementor' )
			);
		}
	}

}
