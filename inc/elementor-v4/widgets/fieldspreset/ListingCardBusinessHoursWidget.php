<?php
/**
 * Listing card business hours widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsPreset;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractPresetFieldWidget;
use DirectoristElementor\Services\ExtensionStatusService;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;

class ListingCardBusinessHoursWidget extends AbstractPresetFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_business_hours';
	}

	public function get_title(): string {
		return __( 'Listing Business Hours', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-clock-o';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'business', 'hours', 'schedule', 'open', 'closed' ] );
	}

	protected function register_widget_controls(): void {
		$this->register_business_hours_content_controls();
		$this->register_container_style_controls();
		$this->register_header_style_controls();
		$this->register_title_style_controls();
		$this->register_badge_style_controls();
		$this->register_schedule_style_controls();
		$this->register_schedule_row_style_controls();
		$this->register_schedule_row_state_style_controls();
		$this->register_schedule_text_style_controls();
		$this->register_today_label_style_controls();
		$this->register_open_24_hours_style_controls();
	}

	protected function get_important_font_size_typography_fields(): array {
		return [
			'font_size' => [
				'selectors' => [
					'{{SELECTOR}}' => 'font-size: {{SIZE}}{{UNIT}} !important;',
				],
			],
			'font_weight' => [
				'selectors' => [
					'{{SELECTOR}}' => 'font-weight: {{VALUE}} !important;',
				],
			],
			'line_height' => [
				'selectors' => [
					'{{SELECTOR}}' => 'line-height: {{SIZE}}{{UNIT}} !important;',
				],
			],
		];
	}

	protected function register_business_hours_content_controls(): void {
		$directory_type_id = $this->resolve_document_directory_type_id();

		$this->start_controls_section(
			'section_business_hours_content',
			[
				'label' => __( 'Business Hours', 'directorist-elementor' ),
			]
		);

		if ( $this->is_single_listing_template_context() ) {
			$this->add_control(
				'preview_listing_id',
				[
					'label'       => __( 'Preview Listing', 'directorist-elementor' ),
					'type'        => Controls_Manager::SELECT2,
					'label_block' => true,
					'default'     => '',
					'options'     => [ '' => __( 'Document Preview Listing', 'directorist-elementor' ) ] + DirectoristBridge::get_instance()->get_recent_listing_options( 100, $directory_type_id ),
					'description' => __( 'Editor-only preview listing for single listing templates.', 'directorist-elementor' ),
				]
			);
		}

		$this->add_control(
			'show_title',
			[
				'label'        => __( 'Show Title', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'title_text',
			[
				'label'       => __( 'Title', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Business Hours', 'directorist-elementor' ),
				'label_block' => true,
				'condition'   => [
					'show_title' => 'yes',
				],
			]
		);

		$this->add_control(
			'show_icon',
			[
				'label'        => __( 'Show Icon', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'show_title' => 'yes',
				],
			]
		);

		$this->add_control(
			'field_icon',
			[
				'label'       => __( 'Icon', 'directorist-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'default'     => [
					'value'   => 'far fa-clock',
					'library' => 'fa-regular',
				],
				'skin'        => 'inline',
				'label_block' => false,
				'condition'   => [
					'show_title' => 'yes',
					'show_icon'  => 'yes',
				],
			]
		);

		$this->add_control(
			'header_icon_position',
			[
				'label'     => __( 'Header Icon Position', 'directorist-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'left',
				'options'   => [
					'left'  => __( 'Left', 'directorist-elementor' ),
					'right' => __( 'Right', 'directorist-elementor' ),
				],
				'condition' => [
					'show_title' => 'yes',
					'show_icon'  => 'yes',
				],
			]
		);

		$this->add_control(
			'show_badge',
			[
				'label'        => __( 'Show Open/Closed Badge', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'open_badge_label',
			[
				'label'       => __( 'Open Badge Label', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Open', 'directorist-elementor' ),
				'label_block' => true,
				'condition'   => [
					'show_badge' => 'yes',
				],
			]
		);

		$this->add_control(
			'closed_badge_label',
			[
				'label'       => __( 'Closed Badge Label', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Closed', 'directorist-elementor' ),
				'label_block' => true,
				'condition'   => [
					'show_badge' => 'yes',
				],
			]
		);

		$this->add_control(
			'badge_icon_position',
			[
				'label'     => __( 'Badge Icon Position', 'directorist-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'right',
				'options'   => [
					'left'  => __( 'Left', 'directorist-elementor' ),
					'right' => __( 'Right', 'directorist-elementor' ),
				],
				'condition' => [
					'show_badge' => 'yes',
				],
			]
		);

		$this->add_control(
			'show_schedule',
			[
				'label'        => __( 'Show Schedule', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'open_24_hours_label',
			[
				'label'       => __( 'Open 24 Hours Label', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Open 24/7', 'directorist-elementor' ),
				'label_block' => true,
				'condition'   => [
					'show_schedule' => 'yes',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_container_style_controls(): void {
		$selector = '{{WRAPPER}} .directorist-elementor-listing-card-business-hours';

		$this->start_controls_section(
			'section_business_hours_container_style',
			[
				'label' => __( 'Container', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'business_hours_container_background',
				'selector' => $selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'business_hours_container_border',
				'selector' => $selector,
			]
		);

		$this->add_responsive_control(
			'business_hours_container_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'business_hours_container_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'business_hours_container_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'business_hours_container_shadow',
				'selector' => $selector,
			]
		);

		$this->add_responsive_control(
			'business_hours_container_opacity',
			[
				'label'     => __( 'Opacity', 'directorist-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min'  => 0,
						'max'  => 1,
						'step' => 0.01,
					],
				],
				'selectors' => [
					$selector => 'opacity: {{SIZE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_header_style_controls(): void {
		$selector = '{{WRAPPER}} .directorist-elementor-listing-card-business-hours__header';

		$this->start_controls_section(
			'section_business_hours_header_style',
			[
				'label' => __( 'Header', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'business_hours_header_alignment',
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
						'title' => __( 'Space Between', 'directorist-elementor' ),
						'icon'  => 'eicon-justify-space-between-h',
					],
				],
				'selectors' => [
					$selector => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'business_hours_header_gap',
			[
				'label'      => __( 'Gap', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'business_hours_header_background',
				'selector' => $selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'business_hours_header_border',
				'selector' => $selector,
			]
		);

		$this->add_responsive_control(
			'business_hours_header_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'business_hours_header_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'business_hours_header_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'business_hours_header_shadow',
				'selector' => $selector,
			]
		);

		$this->end_controls_section();
	}

	protected function register_title_style_controls(): void {
		$title_selector = '{{WRAPPER}} .directorist-elementor-listing-card-business-hours__title';
		$icon_selector  = '{{WRAPPER}} .directorist-elementor-listing-card-business-hours__title-icon';

		$this->start_controls_section(
			'section_business_hours_title_style',
			[
				'label' => __( 'Title', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'business_hours_title_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$title_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'business_hours_title_typography',
				'selector' => $title_selector,
			]
		);

		$this->add_responsive_control(
			'business_hours_title_gap',
			[
				'label'      => __( 'Icon Gap', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$title_selector => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'business_hours_title_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$title_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'business_hours_title_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$title_selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'business_hours_icon_heading',
			[
				'label'     => __( 'Icon', 'directorist-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'show_title' => 'yes',
					'show_icon' => 'yes',
				],
			]
		);

		$this->add_control(
			'business_hours_icon_color',
			[
				'label'     => __( 'Icon Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$icon_selector => 'color: {{VALUE}};',
					$icon_selector . ' svg' => 'fill: {{VALUE}};',
				],
				'condition' => [
					'show_title' => 'yes',
					'show_icon' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'business_hours_icon_size',
			[
				'label'      => __( 'Icon Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 8,
						'max' => 80,
					],
				],
				'selectors'  => [
					$icon_selector => 'font-size: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'show_title' => 'yes',
					'show_icon' => 'yes',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_badge_style_controls(): void {
		$selector = '{{WRAPPER}} .directorist-elementor-listing-card-business-hours__badge';

		$this->start_controls_section(
			'section_business_hours_badge_style',
			[
				'label'     => __( 'Open/Closed Badge', 'directorist-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_badge' => 'yes',
				],
			]
		);

			$this->add_control(
				'business_hours_badge_color',
				[
					'label'     => __( 'Text Color', 'directorist-elementor' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						$selector => 'color: {{VALUE}} !important;',
						$selector . ' .directorist-bh-module__status__text' => 'color: {{VALUE}} !important;',
						$selector . ' .directorist-bh-module__status__icon' => 'color: {{VALUE}} !important;',
						$selector . ' svg' => 'fill: {{VALUE}} !important;',
						$selector . ' .directorist-icon-mask:after' => 'background-color: {{VALUE}} !important;',
					],
				]
			);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'business_hours_badge_typography',
				'selector' => $selector . ', ' . $selector . ' .directorist-bh-module__status__text',
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'business_hours_badge_background',
				'selector' => $selector,
			]
		);

		$this->add_control(
			'business_hours_open_badge_heading',
			[
				'label'     => __( 'Open State', 'directorist-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'business_hours_open_badge_text_color',
			[
					'label'     => __( 'Text Color', 'directorist-elementor' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						$selector . '.directorist-bh-module__status--open' => 'color: {{VALUE}} !important;',
						$selector . '.directorist-bh-module__status--open .directorist-bh-module__status__text' => 'color: {{VALUE}} !important;',
						$selector . '.directorist-bh-module__status--open .directorist-bh-module__status__icon' => 'color: {{VALUE}} !important;',
						$selector . '.directorist-bh-module__status--open svg' => 'fill: {{VALUE}} !important;',
						$selector . '.directorist-bh-module__status--open .directorist-icon-mask:after' => 'background-color: {{VALUE}} !important;',
					],
				]
			);

		$this->add_control(
			'business_hours_open_badge_background_color',
			[
					'label'     => __( 'Background Color', 'directorist-elementor' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						$selector . '.directorist-bh-module__status--open' => 'background-color: {{VALUE}} !important;',
					],
				]
			);

		$this->add_control(
			'business_hours_closed_badge_heading',
			[
				'label'     => __( 'Closed State', 'directorist-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'business_hours_closed_badge_text_color',
			[
					'label'     => __( 'Text Color', 'directorist-elementor' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						$selector . '.directorist-bh-module__status--closed' => 'color: {{VALUE}} !important;',
						$selector . '.directorist-bh-module__status--closed .directorist-bh-module__status__text' => 'color: {{VALUE}} !important;',
						$selector . '.directorist-bh-module__status--closed .directorist-bh-module__status__icon' => 'color: {{VALUE}} !important;',
						$selector . '.directorist-bh-module__status--closed svg' => 'fill: {{VALUE}} !important;',
						$selector . '.directorist-bh-module__status--closed .directorist-icon-mask:after' => 'background-color: {{VALUE}} !important;',
					],
				]
			);

		$this->add_control(
			'business_hours_closed_badge_background_color',
			[
					'label'     => __( 'Background Color', 'directorist-elementor' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						$selector . '.directorist-bh-module__status--closed' => 'background-color: {{VALUE}} !important;',
					],
				]
			);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'business_hours_badge_border',
				'selector' => $selector,
			]
		);

		$this->add_responsive_control(
			'business_hours_badge_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'business_hours_badge_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'business_hours_badge_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'business_hours_badge_shadow',
				'selector' => $selector,
			]
		);

		$this->end_controls_section();
	}

	protected function register_schedule_style_controls(): void {
		$selector = '{{WRAPPER}} .directorist-elementor-listing-card-business-hours__schedule';

		$this->start_controls_section(
			'section_business_hours_schedule_style',
			[
				'label'     => __( 'Schedule', 'directorist-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_schedule' => 'yes',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'business_hours_schedule_background',
				'selector' => $selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'business_hours_schedule_border',
				'selector' => $selector,
			]
		);

		$this->add_responsive_control(
			'business_hours_schedule_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'business_hours_schedule_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'business_hours_schedule_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'business_hours_schedule_shadow',
				'selector' => $selector,
			]
		);

		$this->end_controls_section();
	}

	protected function register_schedule_row_style_controls(): void {
		$selector = '{{WRAPPER}} .directorist-elementor-listing-card-business-hours .directorist-bh-schedule__item';

		$this->start_controls_section(
			'section_business_hours_row_style',
			[
				'label'     => __( 'Schedule Row', 'directorist-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_schedule' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'business_hours_row_gap',
			[
				'label'      => __( 'Gap', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'business_hours_row_background',
				'selector' => $selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'business_hours_row_border',
				'selector' => $selector,
			]
		);

		$this->add_responsive_control(
			'business_hours_row_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'business_hours_row_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'business_hours_row_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_schedule_row_state_style_controls(): void {
		$base_selector = '{{WRAPPER}} .directorist-elementor-listing-card-business-hours ';

		$this->register_schedule_row_state_style_section(
			'section_business_hours_open_row_style',
			__( 'Open Row', 'directorist-elementor' ),
			'business_hours_open_row',
			$base_selector . '.directorist-gbi-business-hours-row--open'
		);

		$this->register_schedule_row_state_style_section(
			'section_business_hours_closed_row_style',
			__( 'Closed Row', 'directorist-elementor' ),
			'business_hours_closed_row',
			$base_selector . '.directorist-gbi-business-hours-row--closed'
		);

		$this->register_schedule_row_state_style_section(
			'section_business_hours_today_row_style',
			__( 'Today Row', 'directorist-elementor' ),
			'business_hours_today_row',
			$base_selector . '.directorist-gbi-business-hours-row--today'
		);
	}

	protected function register_schedule_row_state_style_section( string $section_id, string $label, string $control_prefix, string $selector ): void {
		$this->start_controls_section(
			$section_id,
			[
				'label'     => $label,
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_schedule' => 'yes',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => $control_prefix . '_background',
				'selector' => $selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => $control_prefix . '_border',
				'selector' => $selector,
			]
		);

		$this->add_responsive_control(
			$control_prefix . '_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			$control_prefix . '_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			$control_prefix . '_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_schedule_text_style_controls(): void {
		$day_selector  = '{{WRAPPER}} .directorist-elementor-listing-card-business-hours .directorist-bh-schedule__day';
		$time_selector = '{{WRAPPER}} .directorist-elementor-listing-card-business-hours .directorist-bh-schedule__time, {{WRAPPER}} .directorist-elementor-listing-card-business-hours .directorist-bh-schedule__time span, {{WRAPPER}} .directorist-elementor-listing-card-business-hours .directorist-time-single, {{WRAPPER}} .directorist-elementor-listing-card-business-hours .directorist-bh-schedule__item__badge';

		$this->start_controls_section(
			'section_business_hours_day_style',
			[
				'label'     => __( 'Day Text', 'directorist-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_schedule' => 'yes',
				],
			]
		);

		$this->add_control(
			'business_hours_day_color',
			[
					'label'     => __( 'Color', 'directorist-elementor' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						$day_selector => 'color: {{VALUE}} !important;',
					],
				]
			);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
				[
					'name'           => 'business_hours_day_typography',
					'selector'       => $day_selector,
					'fields_options' => $this->get_important_font_size_typography_fields(),
				]
			);

		$this->add_responsive_control(
			'business_hours_day_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$day_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'business_hours_day_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$day_selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_business_hours_time_style',
			[
				'label'     => __( 'Time Text', 'directorist-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_schedule' => 'yes',
				],
			]
		);

		$this->add_control(
			'business_hours_time_color',
			[
					'label'     => __( 'Color', 'directorist-elementor' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						$time_selector => 'color: {{VALUE}} !important;',
					],
				]
			);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
				[
					'name'           => 'business_hours_time_typography',
					'selector'       => $time_selector,
					'fields_options' => $this->get_important_font_size_typography_fields(),
				]
			);

		$this->add_responsive_control(
			'business_hours_time_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$time_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'business_hours_time_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$time_selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_today_label_style_controls(): void {
		$selector = '{{WRAPPER}} .directorist-elementor-listing-card-business-hours .directorist-bh-schedule__day__badge--today';

		$this->start_controls_section(
			'section_business_hours_today_label_style',
			[
				'label'     => __( 'Today Label', 'directorist-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_schedule' => 'yes',
				],
			]
		);

		$this->add_control(
			'business_hours_today_label_color',
			[
					'label'     => __( 'Text Color', 'directorist-elementor' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						$selector => 'color: {{VALUE}} !important;',
					],
				]
			);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
				[
					'name'           => 'business_hours_today_label_typography',
					'selector'       => $selector,
					'fields_options' => $this->get_important_font_size_typography_fields(),
				]
			);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'business_hours_today_label_background',
				'selector' => $selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'business_hours_today_label_border',
				'selector' => $selector,
			]
		);

		$this->add_responsive_control(
			'business_hours_today_label_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'business_hours_today_label_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'business_hours_today_label_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_open_24_hours_style_controls(): void {
		$selector = '{{WRAPPER}} .directorist-elementor-listing-card-business-hours .directorist-bh-schedule-247, {{WRAPPER}} .directorist-elementor-listing-card-business-hours .directorist-gbi-business-hours-row--open-24 .directorist-bh-schedule__item__badge--open';

		$this->start_controls_section(
			'section_business_hours_open_24_style',
			[
				'label'     => __( 'Open 24 Hours', 'directorist-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_schedule' => 'yes',
				],
			]
		);

		$this->add_control(
			'business_hours_open_24_color',
			[
					'label'     => __( 'Text Color', 'directorist-elementor' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						$selector => 'color: {{VALUE}} !important;',
					],
				]
			);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
				[
					'name'           => 'business_hours_open_24_typography',
					'selector'       => $selector,
					'fields_options' => $this->get_important_font_size_typography_fields(),
				]
			);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'business_hours_open_24_background',
				'selector' => $selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'business_hours_open_24_border',
				'selector' => $selector,
			]
		);

		$this->add_responsive_control(
			'business_hours_open_24_radius',
			[
					'label'      => __( 'Border Radius', 'directorist-elementor' ),
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', '%', 'em', 'rem' ],
					'selectors'  => [
						$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
					],
				]
			);

		$this->add_responsive_control(
			'business_hours_open_24_padding',
			[
					'label'      => __( 'Padding', 'directorist-elementor' ),
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', '%', 'em', 'rem' ],
					'selectors'  => [
						$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
					],
				]
			);

		$this->add_responsive_control(
			'business_hours_open_24_margin',
			[
					'label'      => __( 'Margin', 'directorist-elementor' ),
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', '%', 'em', 'rem' ],
					'selectors'  => [
						$selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
					],
				]
			);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'business_hours_open_24_shadow',
				'selector' => $selector,
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		if ( $this->maybe_render_search_field() ) {
			return;
		}

		$listing_id = $this->resolve_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_preset_context_placeholder(
				__( 'Place this widget inside a Directorist listing card template to render the listing business hours.', 'directorist-elementor' )
			);
			return;
		}

		if ( ! ExtensionStatusService::get_instance()->is_extension_active( 'business_hours' ) || ! function_exists( 'show_business_hours' ) || ! function_exists( 'directorist_business_open_close_status' ) ) {
			$this->render_preset_context_placeholder(
				__( 'Activate Directorist Business Hours to render listing business hours.', 'directorist-elementor' )
			);
			return;
		}

		if ( ! DirectoristBridge::get_instance()->is_preset_widget_allowed( $listing_id, 'business_hours' ) ) {
			$this->render_preset_context_placeholder(
				__( 'Business Hours is not enabled for this listing directory type.', 'directorist-elementor' )
			);
			return;
		}

		if ( ! $this->has_business_hours_data( $listing_id ) ) {
			$this->render_preset_context_placeholder(
				__( 'The selected listing does not have business hours.', 'directorist-elementor' )
			);
			return;
		}

		$settings      = $this->get_settings_for_display();
		$title_text    = trim( wp_strip_all_tags( (string) ( $settings['title_text'] ?? __( 'Business Hours', 'directorist-elementor' ) ) ) );
		$show_title    = 'yes' === ( $settings['show_title'] ?? 'yes' ) && '' !== $title_text;
		$show_icon     = 'yes' === ( $settings['show_icon'] ?? 'yes' );
		$show_badge    = 'yes' === ( $settings['show_badge'] ?? 'yes' );
		$show_schedule = 'yes' === ( $settings['show_schedule'] ?? 'yes' );
		$show_header   = $show_title || $show_badge;
		$is_open       = (bool) directorist_business_open_close_status( $listing_id );
		$status_class  = $is_open ? 'directorist-bh-module__status--open' : 'directorist-bh-module__status--closed';
		$status_text_class = $is_open
			? 'atbd_upper_badge__text atbd_upper_badge__text--open directorist-bh-module__status__text directorist-bh-module__status__text--open'
			: 'atbd_upper_badge__text atbd_upper_badge__text--close directorist-bh-module__status__text directorist-bh-module__status__text--close directorist-bh-module__status__text--closed module__status__text--close';
		$open_badge_label = $this->resolve_label_setting(
			$settings['open_badge_label'] ?? '',
			function_exists( 'get_directorist_option' )
				? get_directorist_option( 'open_badge_text', __( 'Open', 'directorist-elementor' ) )
				: __( 'Open', 'directorist-elementor' )
		);
		$closed_badge_label = $this->resolve_label_setting(
			$settings['closed_badge_label'] ?? '',
			function_exists( 'get_directorist_option' )
				? get_directorist_option( 'close_badge_text', __( 'Closed', 'directorist-elementor' ) )
				: __( 'Closed', 'directorist-elementor' )
		);
		$badge_label          = $is_open ? $open_badge_label : $closed_badge_label;
		$header_icon_position = 'right' === ( $settings['header_icon_position'] ?? 'left' ) ? 'right' : 'left';
		$badge_icon_position  = 'left' === ( $settings['badge_icon_position'] ?? 'right' ) ? 'left' : 'right';
		$open_24_hours_label  = $this->resolve_label_setting(
			$settings['open_24_hours_label'] ?? '',
			function_exists( 'get_directorist_option' )
				? get_directorist_option( 'text247', __( 'Open 24/7', 'directorist-elementor' ) )
				: __( 'Open 24/7', 'directorist-elementor' )
		);

		echo '<div class="directorist-elementor-listing-card-business-hours">';

		if ( $show_header ) {
			echo '<div class="directorist-elementor-listing-card-business-hours__header directorist-bh-module__header">';

			if ( $show_title ) {
				echo '<h4 class="directorist-elementor-listing-card-business-hours__title directorist-bh-module__title ' . esc_attr( 'right' === $header_icon_position ? 'directorist-elementor-listing-card-business-hours__title--icon-right' : '' ) . '">';

				if ( $show_icon ) {
					echo '<span class="directorist-elementor-listing-card-business-hours__title-icon directorist-bh-module__header__icon" aria-hidden="true">';
					$this->render_icon( $settings['field_icon'] ?? [] );
					echo '</span>';
				}

				echo '<span>' . esc_html( $title_text ) . '</span>';
				echo '</h4>';
			}

			if ( $show_badge ) {
				echo '<div class="directorist-elementor-listing-card-business-hours__badge directorist-elementor-listing-card-business-hours__badge--icon-' . esc_attr( $badge_icon_position ) . ' atbd_upper_badge directorist-bh-module__status ' . esc_attr( $status_class ) . '" data-listing_id="' . esc_attr( $listing_id ) . '">';
				echo '<span class="' . esc_attr( $status_text_class ) . '">' . esc_html( $badge_label ) . '</span>';
				$this->render_badge_icon();
				echo '</div>';
			}

			echo '</div>';
		}

		if ( $show_schedule ) {
			echo '<div class="directorist-elementor-listing-card-business-hours__schedule directorist-bh-schedule" data-listing_id="' . esc_attr( $listing_id ) . '">';
			$this->render_schedule( $listing_id, $open_24_hours_label );
			echo '</div>';
		}

		echo '</div>';
	}

	protected function resolve_listing_id(): int {
		$settings           = $this->get_settings_for_display();
		$preview_listing_id = absint( $settings['preview_listing_id'] ?? 0 );

		if ( $preview_listing_id > 0 && $this->is_editor_context() && empty( $this->get_loop_context() ) && $this->is_single_listing_template_context() ) {
			return $this->is_listing_in_document_directory_type( $preview_listing_id ) ? $preview_listing_id : 0;
		}

		return $this->get_current_listing_id();
	}

	protected function has_business_hours_data( int $listing_id ): bool {
		if ( function_exists( 'directorist_is_business_hour_enabled_for_listing' ) && ! directorist_is_business_hour_enabled_for_listing( $listing_id ) ) {
			return false;
		}

		return ! empty( get_post_meta( $listing_id, '_bdbh', true ) ) || ! empty( get_post_meta( $listing_id, '_enable247hour', true ) );
	}

	protected function render_schedule( int $listing_id, string $open_24_hours_label ): void {
		if ( $this->is_cache_compatibility_enabled() ) {
			return;
		}

		if ( ! empty( get_post_meta( $listing_id, '_enable247hour', true ) ) ) {
			echo '<p class="directorist-bh-schedule-247 directorist-gbi-business-hours-open-24">' . esc_html( $open_24_hours_label ) . '</p>';
			return;
		}

		$previous_timezone = date_default_timezone_get();
		ob_start();
		show_business_hours( $listing_id );
		$schedule_markup = (string) ob_get_clean();
		date_default_timezone_set( $previous_timezone );

		echo $this->normalize_schedule_markup( $schedule_markup, $open_24_hours_label ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	protected function is_cache_compatibility_enabled(): bool {
		return function_exists( 'directorist_hours_cache_plugin_compatibility' ) && directorist_hours_cache_plugin_compatibility();
	}

	protected function resolve_label_setting( $value, string $fallback ): string {
		$label = trim( wp_strip_all_tags( (string) $value ) );

		return '' !== $label ? $label : $fallback;
	}

	protected function is_single_listing_template_context(): bool {
		if ( class_exists( '\\Elementor\\Plugin' ) ) {
			$current_document = \Elementor\Plugin::$instance->documents->get_current();

			if ( $current_document ) {
				if ( method_exists( $current_document, 'get_name' ) ) {
					$document_type = (string) $current_document->get_name();
					if ( 0 === strpos( $document_type, 'directorist-single-listing-directory-' ) ) {
						return true;
					}
				}
			}
		}

		$editor_post_id = $this->resolve_editor_document_post_id_from_request();
		if ( $editor_post_id <= 0 ) {
			return false;
		}

		$template_type = (string) get_post_meta( $editor_post_id, '_elementor_template_type', true );

		return 0 === strpos( $template_type, 'directorist-single-listing-directory-' );
	}

	protected function normalize_schedule_markup( string $markup, string $open_24_hours_label ): string {
		if ( '' === trim( $markup ) ) {
			return '';
		}

		$normalized = preg_replace_callback(
			'/<li\b([^>]*)>(.*?)<\/li>/is',
			function ( array $matches ) use ( $open_24_hours_label ): string {
				$opening_tag = '<li' . $matches[1] . '>';
				$content     = (string) $matches[2];
				$classes     = [];
				$is_closed   = false !== stripos( $opening_tag, 'directorist-bh-schedule__item--closed' )
					|| false !== stripos( $content, 'directorist-bh-schedule__item__badge--closed' );
				$is_today    = false !== stripos( $opening_tag, 'directorist-bh-schedule__item--today' );
				$is_open_24  = false !== stripos( $content, 'directorist-bh-schedule__item__badge--open' );

				$classes[] = $is_closed
					? 'directorist-gbi-business-hours-row--closed'
					: 'directorist-gbi-business-hours-row--open';

				if ( $is_today ) {
					$classes[] = 'directorist-gbi-business-hours-row--today';
				}

				if ( $is_open_24 ) {
					$classes[] = 'directorist-gbi-business-hours-row--open-24';
					$updated_content = preg_replace(
						'/(<span\b[^>]*directorist-bh-schedule__item__badge--open[^>]*>)(.*?)(<\/span>)/is',
						'$1' . esc_html( $open_24_hours_label ) . '$3',
						$content
					);
					$content = is_string( $updated_content ) ? $updated_content : $content;
				}

				return $this->add_classes_to_tag( $opening_tag, $classes ) . $content . '</li>';
			},
			$markup
		);

		return is_string( $normalized ) ? $normalized : $markup;
	}

	/**
	 * Add classes to an HTML opening tag.
	 *
	 * @param string     $tag Opening tag.
	 * @param array<int,string> $classes Classes.
	 * @return string
	 */
	protected function add_classes_to_tag( string $tag, array $classes ): string {
		$classes = array_values( array_unique( array_filter( array_map( 'sanitize_html_class', $classes ) ) ) );
		if ( empty( $classes ) ) {
			return $tag;
		}

		if ( preg_match( '/\sclass=(["\'])(.*?)\1/i', $tag, $matches ) ) {
			$existing_classes = preg_split( '/\s+/', trim( (string) $matches[2] ) );
			$merged_classes   = implode( ' ', array_values( array_unique( array_filter( array_merge( $existing_classes, $classes ) ) ) ) );
			$tag_with_classes = preg_replace( '/\sclass=(["\'])(.*?)\1/i', ' class="' . esc_attr( $merged_classes ) . '"', $tag, 1 );

			return is_string( $tag_with_classes ) ? $tag_with_classes : $tag;
		}

		return rtrim( $tag, '>' ) . ' class="' . esc_attr( implode( ' ', $classes ) ) . '">';
	}

	protected function render_badge_icon(): void {
		echo '<span class="directorist-bh-module__status__icon" aria-hidden="true">';
		if ( function_exists( 'directorist_icon' ) ) {
			directorist_icon( 'la las-clock' );
		}
		echo '</span>';
	}

	protected function render_icon( array $icon ): void {
		if ( ! class_exists( Icons_Manager::class ) || empty( $icon['value'] ) ) {
			echo '<i class="far fa-clock" aria-hidden="true"></i>';
			return;
		}

		Icons_Manager::render_icon( $icon, [ 'aria-hidden' => 'true' ] );
	}
}
