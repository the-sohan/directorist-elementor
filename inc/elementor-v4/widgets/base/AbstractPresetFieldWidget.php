<?php
/**
 * Base preset field widget for listing-card composition.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Base;

use DirectoristElementor\ElementorV4\CategoryRegistrar;
use DirectoristElementor\ElementorV4\Context\RenderContext;
use DirectoristElementor\ElementorV4\Render\SearchFormRenderService;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

abstract class AbstractPresetFieldWidget extends AbstractLoopAwareWidget {

	/**
	 * Register card controls plus the contextual search-field design surface.
	 *
	 * @return void
	 */
	protected function register_controls(): void {
		parent::register_controls();

		if ( ! SearchFormRenderService::get_instance()->is_search_field_widget( $this->get_name() ) ) {
			return;
		}

		$this->scope_listing_card_controls_outside_search_context();
		$this->register_search_field_context_controls();
		$this->register_search_field_style_controls();
	}

	/**
	 * Hide listing-card-only sections while the widget is a search field.
	 *
	 * @return void
	 */
	protected function scope_listing_card_controls_outside_search_context(): void {
		foreach ( $this->get_controls() as $control_id => $control ) {
			if ( Controls_Manager::SECTION !== ( $control['type'] ?? '' ) ) {
				continue;
			}

			$condition = is_array( $control['condition'] ?? null ) ? (array) $control['condition'] : [];
			$condition['directorist_search_context!'] = 'yes';

			$this->update_control(
				$control_id,
				[
					'condition' => $condition,
				]
			);
		}
	}

	/**
	 * Register hidden state carried by projected search-field widgets.
	 *
	 * @return void
	 */
	protected function register_search_field_context_controls(): void {
		$this->start_controls_section(
			'section_directorist_search_context_state',
			[
				'label'     => __( 'Search Field State', 'directorist-elementor' ),
				'condition' => [
					'directorist_search_context' => '__state_only__',
				],
			]
		);

		foreach (
			[
				'directorist_search_context'           => '',
				'directorist_search_field_key'         => '',
				'directorist_search_field_label'       => '',
				'directorist_search_widget_name'       => '',
				'directorist_search_design_type'       => '',
				'directorist_home_search_inherited_settings' => '',
			] as $control_id => $default
		) {
			$this->add_control(
				$control_id,
				[
					'type'    => Controls_Manager::HIDDEN,
					'default' => $default,
				]
			);
		}

		$this->end_controls_section();
	}

	/**
	 * Register design controls that target Directorist search-form markup.
	 *
	 * @return void
	 */
	protected function register_search_field_style_controls(): void {
		$field_selector           = '{{WRAPPER}} .directorist-elementor-search-field';
		$label_selector           = $field_selector . ' .directorist-search-field__label, ' . $field_selector . ' .directorist-form-label, ' . $field_selector . ' label:not(.directorist-checkbox__label):not(.directorist-radio__label)';
		$input_selector           = $field_selector . ' input.directorist-form-element:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]), ' . $field_selector . ' textarea.directorist-form-element, ' . $field_selector . ' select, ' . $field_selector . ' .directorist-search-basic-dropdown-label, ' . $field_selector . ' .select2-selection, ' . $field_selector . ' .directorist-form-group__with-prefix, ' . $field_selector . ' .wp-color-result';
		$input_text_selector      = $input_selector . ', ' . $field_selector . ' .select2-selection__rendered, ' . $field_selector . ' .directorist-form-group__prefix';
		$choice_label_selector    = $field_selector . ' .directorist-checkbox__label, ' . $field_selector . ' .directorist-radio__label';
		$choice_input_selector    = $field_selector . ' input[type="checkbox"], ' . $field_selector . ' input[type="radio"]';
		$icon_selector            = $field_selector . ' i, ' . $field_selector . ' svg, ' . $field_selector . ' .directorist-icon-mask::after, ' . $field_selector . ' .select2-selection__arrow';
		$icon_foreground_selector = $field_selector . ' i, ' . $field_selector . ' svg, ' . $field_selector . ' .select2-selection__arrow';
		$icon_mask_selector       = $field_selector . ' .directorist-icon-mask::after';
		$input_design_types       = [
			'text-input',
			'number-input',
			'number-range',
			'date-input',
			'time-input',
			'textarea',
			'select-field',
			'taxonomy-select',
			'pricing-dropdown',
			'color-field',
			'file-field',
			'button-field',
		];

		$this->start_controls_section(
			'section_directorist_search_field_wrapper_style',
			[
				'label'     => __( 'Search Field Wrapper', 'directorist-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'directorist_search_context' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'search_field_width',
			[
				'label'      => __( 'Width', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%', 'px', 'em', 'rem' ],
				'range'      => [
					'%' => [
						'min' => 5,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}}' => 'width: {{SIZE}}{{UNIT}}; flex-basis: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'search_field_wrapper_background',
				'selector' => $field_selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'search_field_wrapper_border',
				'selector' => $field_selector,
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'search_field_wrapper_shadow',
				'selector' => $field_selector,
			]
		);

		$this->add_responsive_control(
			'search_field_wrapper_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$field_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'search_field_wrapper_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$field_selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'search_field_wrapper_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$field_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_directorist_search_field_label_style',
			[
				'label'     => __( 'Search Field Label', 'directorist-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'directorist_search_context' => 'yes',
				],
			]
		);

		$this->add_control(
			'search_field_label_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$label_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'search_field_label_typography',
				'selector' => $label_selector,
			]
		);

		$this->add_responsive_control(
			'search_field_label_gap',
			[
				'label'      => __( 'Spacing', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$label_selector => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_directorist_search_field_input_style',
			[
				'label'     => __( 'Search Field Input', 'directorist-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'directorist_search_context'     => 'yes',
					'directorist_search_design_type' => $input_design_types,
				],
			]
		);

		$this->add_control(
			'search_field_input_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$input_text_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'search_field_placeholder_color',
			[
				'label'     => __( 'Placeholder Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$field_selector . ' input::placeholder, ' . $field_selector . ' textarea::placeholder' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'search_field_input_background',
				'selector' => $input_selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'search_field_input_border',
				'selector' => $input_selector,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'search_field_input_typography',
				'selector' => $input_text_selector,
			]
		);

		$this->add_responsive_control(
			'search_field_input_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$input_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'search_field_input_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$input_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'search_field_input_height',
			[
				'label'      => __( 'Minimum Height', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$input_selector => 'min-height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_directorist_search_field_choice_style',
			[
				'label'     => __( 'Search Field Choices', 'directorist-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'directorist_search_context'     => 'yes',
					'directorist_search_design_type' => [ 'choice-dropdown', 'choice-inline' ],
				],
			]
		);

		$this->add_control(
			'search_field_choice_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$choice_label_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'search_field_choice_typography',
				'selector' => $choice_label_selector,
			]
		);

		$this->add_control(
			'search_field_choice_accent_color',
			[
				'label'     => __( 'Control Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$choice_input_selector => 'accent-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'search_field_choice_size',
			[
				'label'      => __( 'Control Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$choice_input_selector => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_directorist_search_field_icon_style',
			[
				'label'     => __( 'Search Field Icon', 'directorist-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'directorist_search_context' => 'yes',
				],
			]
		);

		$this->add_control(
			'search_field_icon_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$icon_foreground_selector => 'color: {{VALUE}}; fill: {{VALUE}};',
					$icon_mask_selector       => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'search_field_icon_size',
			[
				'label'      => __( 'Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$icon_selector => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Preset fields are available both as listing-card fields and contextual search fields.
	 *
	 * @return array<int,string>
	 */
	public function get_categories(): array {
		$categories = [ CategoryRegistrar::CATEGORY_PRESET ];

		if ( SearchFormRenderService::get_instance()->is_search_field_widget( $this->get_name() ) ) {
			$categories[] = CategoryRegistrar::CATEGORY_SEARCH_FIELDS;
		}

		return $categories;
	}

	/**
	 * Preset field widgets belong to the preset field category.
	 *
	 * @return string
	 */
	protected function get_directorist_category_slug(): string {
		return CategoryRegistrar::CATEGORY_PRESET;
	}

	/**
	 * Add shared preset keywords.
	 *
	 * @return array<int,string>
	 */
	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'card', 'preset' ] );
	}

	/**
	 * Add search composition wrapper classes before Elementor prints the widget wrapper.
	 *
	 * @return void
	 */
	public function before_render() {
		$search_form_render_service = SearchFormRenderService::get_instance();

		if (
			$search_form_render_service->is_search_field_widget( $this->get_name() ) &&
			! empty( RenderContext::get_instance()->current_search_form_context() )
		) {
			$this->add_render_attribute(
				'_wrapper',
				'class',
				[
					'directorist-elementor-search-composition-item',
					'directorist-elementor-search-field-widget',
					'directorist-elementor-search-field-widget--' . sanitize_html_class( $this->get_name() ),
				]
			);
		}

		parent::before_render();
	}

	/**
	 * Render a standard card-template placeholder for preset fields.
	 *
	 * @param string $description Placeholder description.
	 * @return void
	 */
	protected function render_preset_context_placeholder( string $description ): void {
		if ( ! $this->is_editor_context() ) {
			return;
		}

		echo wp_kses_post(
			$this->render_placeholder(
				$this->get_title(),
				$description
			)
		);
	}

	/**
	 * Render this field as a composable search field when search context exists.
	 *
	 * @return bool
	 */
	protected function maybe_render_search_field(): bool {
		return SearchFormRenderService::get_instance()->maybe_render_search_field_widget( $this );
	}
}
