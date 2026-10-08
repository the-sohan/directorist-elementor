<?php
/**
 * Search more filters button widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Search;

use Directorist\Directorist_Listing_Search_Form;
use DirectoristElementor\ElementorV4\CategoryRegistrar;
use DirectoristElementor\ElementorV4\Context\RenderContext;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractDirectoristWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

class SearchMoreFiltersButtonWidget extends AbstractDirectoristWidget {

	public function get_name(): string {
		return 'directorist_search_more_filters_button';
	}

	public function get_title(): string {
		return __( 'More Filters Button', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-filter';
	}

	protected function get_directorist_category_slug(): string {
		return CategoryRegistrar::CATEGORY_SEARCH_FIELDS;
	}

	/**
	 * Mark the button wrapper as a search composition item before Elementor prints it.
	 *
	 * @return void
	 */
	public function before_render() {
		if ( ! empty( RenderContext::get_instance()->current_search_form_context() ) ) {
			$this->add_render_attribute(
				'_wrapper',
				'class',
				[
					'directorist-elementor-search-composition-item',
					'directorist-elementor-search-more-filters-widget',
				]
			);
		}

		parent::before_render();
	}

	protected function register_widget_controls(): void {
		$form_scope_selector  = 'form:has({{WRAPPER}})';
		$modal_overlay        = $form_scope_selector . ' .directorist-search-modal__overlay';
		$modal_content        = $form_scope_selector . ' .directorist-search-modal__contents';
		$modal_header         = $form_scope_selector . ' .directorist-search-modal__contents__header';
		$modal_title          = $form_scope_selector . ' .directorist-search-modal__contents__title';
		$modal_close          = $form_scope_selector . ' .directorist-search-modal__contents__btn--close';
		$modal_close_icon     = $modal_close . ' .directorist-icon-mask::after';
		$modal_body           = $form_scope_selector . ' .directorist-search-modal__contents__body';
		$field_wrapper        = $form_scope_selector . ' .directorist-advanced-filter__advanced__element, ' . $form_scope_selector . ' .directorist-advanced-filter__basic__element';
		$field_label          = $form_scope_selector . ' .directorist-search-field__label, ' . $form_scope_selector . ' .directorist-price-ranges__label, ' . $form_scope_selector . ' .directorist-custom-range-slider__label';
		$field_input          = $form_scope_selector . ' input.directorist-form-element:not([type="hidden"]), ' . $form_scope_selector . ' textarea.directorist-form-element, ' . $form_scope_selector . ' select, ' . $form_scope_selector . ' .directorist-search-basic-dropdown-label, ' . $form_scope_selector . ' .select2.select2-container.select2-container--default .select2-selection, ' . $form_scope_selector . ' .directorist-form-group__with-prefix, ' . $form_scope_selector . ' .directorist-price-ranges__item.directorist-form-group, ' . $form_scope_selector . ' .directorist-custom-range-slider__value, ' . $form_scope_selector . ' .wp-picker-container .wp-color-result';
		$field_input_text     = $form_scope_selector . ' .select2-selection__rendered, ' . $form_scope_selector . ' .directorist-form-group__prefix, ' . $form_scope_selector . ' .directorist-price-ranges__currency, ' . $form_scope_selector . ' .directorist-pf-range, ' . $form_scope_selector . ' .directorist-custom-range-slider__range__wrap, ' . $form_scope_selector . ' .directorist-checkbox__label, ' . $form_scope_selector . ' .directorist-radio__label, ' . $form_scope_selector . ' .wp-picker-container .wp-color-result-text';
		$action_area          = $form_scope_selector . ' .directorist-advanced-filter__action';
		$apply_button         = $form_scope_selector . ' .directorist-advanced-filter__action .directorist-btn-submit';
		$reset_button         = $form_scope_selector . ' .directorist-advanced-filter__action .directorist-btn-reset-js';

		$this->start_controls_section(
			'section_button_content',
			[
				'label' => __( 'Button', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'button_text',
			[
				'label'       => __( 'Text', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => __( 'More Filters', 'directorist-elementor' ),
				'description' => __( 'Leave empty to use the Directorist more filters text.', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'show_icon',
			[
				'label'        => __( 'Show Icon', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'directorist-elementor' ),
				'label_off'    => __( 'Hide', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'icon_only',
			[
				'label'        => __( 'Icon Only', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'directorist_home_search_inherited_settings',
			[
				'type'    => Controls_Manager::HIDDEN,
				'default' => '',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_button_style',
			[
				'label' => __( 'Trigger', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'container_heading',
			[
				'label' => __( 'Container', 'directorist-elementor' ),
				'type'  => Controls_Manager::HEADING,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'container_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-search-more-filters',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'container_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-search-more-filters',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'container_box_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-search-more-filters',
			]
		);

		$this->add_responsive_control(
			'container_radius',
			[
				'label'      => __( 'Container Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-search-more-filters' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'container_alignment',
			[
				'label'     => __( 'Alignment', 'directorist-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start' => [
						'title' => __( 'Left', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center'     => [
						'title' => __( 'Center', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'flex-end'   => [
						'title' => __( 'Right', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-search-more-filters' => 'display: flex; justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'container_padding',
			[
				'label'      => __( 'Container Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-search-more-filters' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'container_margin',
			[
				'label'      => __( 'Container Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-search-more-filters' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'button_heading',
			[
				'label'     => __( 'Button', 'directorist-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-search-more-filters__button',
			]
		);

		$this->add_control(
			'button_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-search-more-filters__button' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_background',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-search-more-filters__button' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'button_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-search-more-filters__button',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'button_box_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-search-more-filters__button',
			]
		);

		$this->add_responsive_control(
			'button_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-search-more-filters__button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_padding',
			[
				'label'      => __( 'Button Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-search-more-filters__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_margin',
			[
				'label'      => __( 'Button Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-search-more-filters__button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'icon_heading',
			[
				'label'     => __( 'Icon', 'directorist-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'icon_size',
			[
				'label'      => __( 'Icon Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 8,
						'max' => 64,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-search-more-filters__button .directorist-icon-mask::after' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-search-more-filters__button svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'icon_margin',
			[
				'label'      => __( 'Icon Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-search-more-filters__button .directorist-icon-mask' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-search-more-filters__button svg' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'icon_color',
			[
				'label'     => __( 'Icon Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-search-more-filters__button .directorist-icon-mask::after' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-search-more-filters__button svg' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'icon_gap',
			[
				'label'      => __( 'Icon Gap', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 40,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-search-more-filters__button' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_modal_style',
			[
				'label' => __( 'Modal', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'modal_overlay_background',
				'label'    => __( 'Overlay Background', 'directorist-elementor' ),
				'selector' => $modal_overlay,
			]
		);

		$this->add_responsive_control(
			'modal_overlay_opacity',
			[
				'label'      => __( 'Overlay Opacity', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '' ],
				'range'      => [
					'' => [
						'min'  => 0,
						'max'  => 1,
						'step' => 0.05,
					],
				],
				'selectors'  => [
					$modal_overlay => '--directorist-gbi-search-more-filters-modal-overlay-opacity: {{SIZE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'modal_background',
				'label'    => __( 'Panel Background', 'directorist-elementor' ),
				'selector' => $modal_content,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'modal_border',
				'selector' => $modal_content,
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'modal_box_shadow',
				'selector' => $modal_content,
			]
		);

		$this->add_responsive_control(
			'modal_padding',
			[
				'label'      => __( 'Panel Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$modal_content => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'modal_margin',
			[
				'label'      => __( 'Panel Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					$modal_content => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'modal_radius',
			[
				'label'      => __( 'Panel Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$modal_content => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'modal_opacity',
			[
				'label'      => __( 'Panel Opacity', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '' ],
				'range'      => [
					'' => [
						'min'  => 0,
						'max'  => 1,
						'step' => 0.05,
					],
				],
				'selectors'  => [
					$modal_content => '--directorist-gbi-search-more-filters-modal-opacity: {{SIZE}};',
				],
			]
		);

		$this->add_control(
			'modal_header_heading',
			[
				'label' => __( 'Header', 'directorist-elementor' ),
				'type'  => Controls_Manager::HEADING,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'modal_header_background',
				'selector' => $modal_header,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'modal_header_border',
				'selector' => $modal_header,
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'modal_header_box_shadow',
				'selector' => $modal_header,
			]
		);

		$this->add_responsive_control(
			'modal_header_padding',
			[
				'label'      => __( 'Header Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$modal_header => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'modal_header_margin',
			[
				'label'      => __( 'Header Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					$modal_header => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'modal_header_radius',
			[
				'label'      => __( 'Header Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$modal_header => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'modal_title_heading',
			[
				'label'     => __( 'Title', 'directorist-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'modal_title_typography',
				'selector' => $modal_title,
			]
		);

		$this->add_control(
			'modal_title_color',
			[
				'label'     => __( 'Title Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$modal_title => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'modal_title_background',
				'selector' => $modal_title,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'modal_title_border',
				'selector' => $modal_title,
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'modal_title_box_shadow',
				'selector' => $modal_title,
			]
		);

		$this->add_responsive_control(
			'modal_title_padding',
			[
				'label'      => __( 'Title Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$modal_title => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'modal_title_margin',
			[
				'label'      => __( 'Title Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					$modal_title => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'modal_title_radius',
			[
				'label'      => __( 'Title Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$modal_title => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'modal_close_heading',
			[
				'label'     => __( 'Close Button', 'directorist-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'modal_close_color',
			[
				'label'     => __( 'Close Icon Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$modal_close      => 'color: {{VALUE}};',
					$modal_close_icon => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'modal_close_background',
				'selector' => $modal_close,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'modal_close_border',
				'selector' => $modal_close,
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'modal_close_box_shadow',
				'selector' => $modal_close,
			]
		);

		$this->add_responsive_control(
			'modal_close_size',
			[
				'label'      => __( 'Close Icon Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 8,
						'max' => 64,
					],
				],
				'selectors'  => [
					$modal_close_icon => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					$modal_close . ' svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'modal_close_padding',
			[
				'label'      => __( 'Close Button Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$modal_close => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'modal_close_margin',
			[
				'label'      => __( 'Close Button Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					$modal_close => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'modal_close_radius',
			[
				'label'      => __( 'Close Button Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$modal_close => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'modal_body_heading',
			[
				'label'     => __( 'Body', 'directorist-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'modal_body_background',
				'selector' => $modal_body,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'modal_body_border',
				'selector' => $modal_body,
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'modal_body_box_shadow',
				'selector' => $modal_body,
			]
		);

		$this->add_responsive_control(
			'modal_body_padding',
			[
				'label'      => __( 'Body Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$modal_body => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'modal_body_margin',
			[
				'label'      => __( 'Body Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					$modal_body => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'modal_body_radius',
			[
				'label'      => __( 'Body Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$modal_body => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_fields_style',
			[
				'label' => __( 'Fields', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'field_wrapper_heading',
			[
				'label' => __( 'Field Wrapper', 'directorist-elementor' ),
				'type'  => Controls_Manager::HEADING,
			]
		);

		$this->add_responsive_control(
			'field_gap',
			[
				'label'      => __( 'Field Gap', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 80,
					],
				],
				'selectors'  => [
					$field_wrapper => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'field_wrapper_background',
				'selector' => $field_wrapper,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'field_wrapper_border',
				'selector' => $field_wrapper,
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'field_wrapper_box_shadow',
				'selector' => $field_wrapper,
			]
		);

		$this->add_responsive_control(
			'field_wrapper_padding',
			[
				'label'      => __( 'Wrapper Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$field_wrapper => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'field_wrapper_radius',
			[
				'label'      => __( 'Wrapper Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$field_wrapper => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'field_label_heading',
			[
				'label'     => __( 'Label', 'directorist-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'field_label_typography',
				'selector' => $field_label,
			]
		);

		$this->add_control(
			'field_label_color',
			[
				'label'     => __( 'Label Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$field_label => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'field_label_background',
				'selector' => $field_label,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'field_label_border',
				'selector' => $field_label,
			]
		);

		$this->add_responsive_control(
			'field_label_padding',
			[
				'label'      => __( 'Label Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$field_label => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'field_label_margin',
			[
				'label'      => __( 'Label Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					$field_label => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'field_label_radius',
			[
				'label'      => __( 'Label Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$field_label => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'field_input_heading',
			[
				'label'     => __( 'Input', 'directorist-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'field_input_typography',
				'selector' => $field_input . ', ' . $field_input_text,
			]
		);

		$this->add_control(
			'field_input_color',
			[
				'label'     => __( 'Input Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$field_input      => 'color: {{VALUE}};',
					$field_input_text => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'field_input_background',
			[
				'label'     => __( 'Input Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$field_input => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'field_input_border',
				'selector' => $field_input,
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'field_input_box_shadow',
				'selector' => $field_input,
			]
		);

		$this->add_responsive_control(
			'field_input_padding',
			[
				'label'      => __( 'Input Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$field_input => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'field_input_margin',
			[
				'label'      => __( 'Input Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					$field_input => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'field_input_radius',
			[
				'label'      => __( 'Input Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$field_input => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_actions_style',
			[
				'label' => __( 'Actions', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'actions_alignment',
			[
				'label'     => __( 'Alignment', 'directorist-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start'    => [
						'title' => __( 'Left', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center'        => [
						'title' => __( 'Center', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'flex-end'      => [
						'title' => __( 'Right', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
					'space-between' => [
						'title' => __( 'Justify', 'directorist-elementor' ),
						'icon'  => 'eicon-justify-space-between-h',
					],
				],
				'selectors' => [
					$action_area => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'actions_background',
				'selector' => $action_area,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'actions_border',
				'selector' => $action_area,
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'actions_box_shadow',
				'selector' => $action_area,
			]
		);

		$this->add_responsive_control(
			'actions_padding',
			[
				'label'      => __( 'Action Area Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$action_area => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'actions_margin',
			[
				'label'      => __( 'Action Area Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					$action_area => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'actions_radius',
			[
				'label'      => __( 'Action Area Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$action_area => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'apply_button_heading',
			[
				'label' => __( 'Apply Button', 'directorist-elementor' ),
				'type'  => Controls_Manager::HEADING,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'apply_button_typography',
				'selector' => $apply_button,
			]
		);

		$this->add_control(
			'apply_button_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$apply_button => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'apply_button_background',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$apply_button => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'apply_button_border',
				'selector' => $apply_button,
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'apply_button_box_shadow',
				'selector' => $apply_button,
			]
		);

		$this->add_responsive_control(
			'apply_button_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$apply_button => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'apply_button_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					$apply_button => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'apply_button_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$apply_button => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'reset_button_heading',
			[
				'label'     => __( 'Reset Button', 'directorist-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'reset_button_typography',
				'selector' => $reset_button,
			]
		);

		$this->add_control(
			'reset_button_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$reset_button => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'reset_button_background',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$reset_button => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'reset_button_border',
				'selector' => $reset_button,
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'reset_button_box_shadow',
				'selector' => $reset_button,
			]
		);

		$this->add_responsive_control(
			'reset_button_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$reset_button => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'reset_button_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					$reset_button => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'reset_button_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$reset_button => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$context     = RenderContext::get_instance()->current_search_form_context();
		$search_form = $this->resolve_search_form( $context );

		if ( ! $this->has_advanced_filters( $search_form ) ) {
			if ( $this->is_editor_context() ) {
				echo wp_kses_post(
					$this->render_placeholder(
						$this->get_title(),
						__( 'No advanced filter fields are configured for this directory type.', 'directorist-elementor' )
					)
				);
			}
			return;
		}

		$settings  = $this->get_settings_for_display();
		$text      = trim( (string) ( $settings['button_text'] ?? '' ) );
		$icon_only = 'yes' === (string) ( $settings['icon_only'] ?? '' );
		$show_icon = 'yes' === (string) ( $settings['show_icon'] ?? 'yes' );

		if ( '' === $text && ! empty( $search_form->more_filters_text ) ) {
			$text = (string) $search_form->more_filters_text;
		}

		if ( '' === $text ) {
			$text = __( 'More Filters', 'directorist-elementor' );
		}

		if ( method_exists( $search_form, 'has_more_filters_icon' ) ) {
			$show_icon = $show_icon && $search_form->has_more_filters_icon();
		}

		if ( $icon_only && ! $show_icon ) {
			$icon_only = false;
		}

		$classes = [
			'directorist-search-form-action',
			'directorist-elementor-search-more-filters',
		];

		if ( $icon_only ) {
			$classes[] = 'directorist-elementor-search-more-filters--icon-only';
		}

		echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';
		echo '<div class="directorist-search-form-action__filter">';
		echo '<a href="#" class="directorist-btn directorist-btn-lg directorist-filter-btn directorist-modal-btn directorist-modal-btn--advanced directorist-elementor-search-more-filters__button" aria-label="' . esc_attr( $text ) . '">';

		if ( $show_icon && function_exists( 'directorist_icon' ) ) {
			directorist_icon( 'fas fa-filter' );
		}

		if ( ! $icon_only ) {
			echo '<span class="directorist-elementor-search-more-filters__label">' . esc_html( $text ) . '</span>';
		}

		echo '</a>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Resolve the active search form model.
	 *
	 * @param array<string,mixed> $context Search composition context.
	 * @return Directorist_Listing_Search_Form|null
	 */
	protected function resolve_search_form( array $context ): ?Directorist_Listing_Search_Form {
		$search_form = $context['searchform'] ?? null;
		if ( $search_form instanceof Directorist_Listing_Search_Form ) {
			return $search_form;
		}

		$settings          = $this->get_settings_for_display();
		$directory_type_id = absint( $settings['directory_type_id'] ?? 0 );
		if ( $directory_type_id <= 0 ) {
			$directory_type_id = absint( $context['directory_type_id'] ?? 0 );
		}

		if ( $directory_type_id <= 0 || ! class_exists( Directorist_Listing_Search_Form::class ) ) {
			return null;
		}

		return new Directorist_Listing_Search_Form( 'search_result', $directory_type_id, [] );
	}

	/**
	 * Check whether the active search form has advanced filters.
	 *
	 * @param Directorist_Listing_Search_Form|null $search_form Search form model.
	 * @return bool
	 */
	protected function has_advanced_filters( ?Directorist_Listing_Search_Form $search_form ): bool {
		if ( ! $search_form || empty( $search_form->has_more_filters_button ) || ! method_exists( $search_form, 'get_advance_fields' ) ) {
			return false;
		}

		$fields = $search_form->get_advance_fields();

		return ! empty( $fields ) && is_array( $fields );
	}
}
