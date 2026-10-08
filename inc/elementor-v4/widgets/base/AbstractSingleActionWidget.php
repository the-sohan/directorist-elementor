<?php
/**
 * Base widget for single listing action buttons.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Base;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;

abstract class AbstractSingleActionWidget extends AbstractSingleListingWidget {

	/**
	 * Register shared action controls.
	 *
	 * @param string $section_id Section id.
	 * @param string $label Section label.
	 * @param array  $args Defaults.
	 * @return void
	 */
	protected function register_single_action_content_controls( string $section_id, string $label, array $args = [] ): void {
		$defaults = [
			'default_label'     => '',
			'default_icon'      => [],
			'default_show_icon' => 'yes',
			'default_show_label' => 'yes',
		];

		$args = array_merge( $defaults, $args );

		$this->start_controls_section(
			$section_id,
			[
				'label' => $label,
			]
		);

		$this->add_control(
			'label',
			[
				'label'       => __( 'Label', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => $args['default_label'],
				'label_block' => true,
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
				'default'      => $args['default_show_icon'],
			]
		);

		$this->add_control(
			'action_icon',
			[
				'label'       => __( 'Icon', 'directorist-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'default'     => $args['default_icon'],
				'skin'        => 'inline',
				'label_block' => false,
				'condition'   => [
					'show_icon' => 'yes',
				],
			]
		);

		$this->add_control(
			'show_label',
			[
				'label'        => __( 'Show Label', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => $args['default_show_label'],
			]
		);

		$this->add_control(
			'icon_position',
			[
				'label'   => __( 'Icon Position', 'directorist-elementor' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'before',
				'options' => [
					'before' => [
						'title' => __( 'Before', 'directorist-elementor' ),
						'icon'  => 'eicon-h-align-left',
					],
					'after' => [
						'title' => __( 'After', 'directorist-elementor' ),
						'icon'  => 'eicon-h-align-right',
					],
				],
				'toggle' => false,
				'condition' => [
					'show_icon'  => 'yes',
					'show_label' => 'yes',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Register shared action button style controls.
	 *
	 * @param string $section_id Section id.
	 * @param string $label Section label.
	 * @param string $button_selector Button selector.
	 * @return void
	 */
	protected function register_single_action_style_controls( string $section_id, string $label, string $button_selector = '.directorist-single-listing-action' ): void {
		$this->start_controls_section(
			$section_id,
			[
				'label' => $label,
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'action_align',
			[
				'label'     => __( 'Alignment', 'directorist-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start' => [
						'title' => __( 'Left', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => __( 'Center', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'flex-end' => [
						'title' => __( 'Right', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-single-action' => 'display:flex;justify-content:{{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'action_gap',
			[
				'label'      => __( 'Gap', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} ' . $button_selector => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs( 'tabs_single_action_style' );

		$this->start_controls_tab(
			'tab_single_action_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'action_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} ' . $button_selector => 'color: {{VALUE}};',
					'{{WRAPPER}} ' . $button_selector . ' .directorist-single-listing-action__text' => 'color: {{VALUE}};',
					'{{WRAPPER}} ' . $button_selector . ' a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'action_icon_color',
			[
				'label'     => __( 'Icon Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} ' . $button_selector . ' .directorist-elementor-single-action__icon' => 'color: {{VALUE}};',
					'{{WRAPPER}} ' . $button_selector . ' .directorist-elementor-single-action__icon svg' => 'fill: {{VALUE}};',
					'{{WRAPPER}} ' . $button_selector . ' .directorist-elementor-single-action__icon .directorist-icon-mask::after' => 'background-color: {{VALUE}};',
				],
				'condition' => [
					'show_icon' => 'yes',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'action_background',
				'selector' => '{{WRAPPER}} ' . $button_selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'action_border',
				'selector' => '{{WRAPPER}} ' . $button_selector,
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_single_action_hover',
			[
				'label' => __( 'Hover', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'action_hover_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} ' . $button_selector . ':hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} ' . $button_selector . ':hover .directorist-single-listing-action__text' => 'color: {{VALUE}};',
					'{{WRAPPER}} ' . $button_selector . ':hover a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'action_hover_icon_color',
			[
				'label'     => __( 'Icon Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} ' . $button_selector . ':hover .directorist-elementor-single-action__icon' => 'color: {{VALUE}};',
					'{{WRAPPER}} ' . $button_selector . ':hover .directorist-elementor-single-action__icon svg' => 'fill: {{VALUE}};',
					'{{WRAPPER}} ' . $button_selector . ':hover .directorist-elementor-single-action__icon .directorist-icon-mask::after' => 'background-color: {{VALUE}};',
				],
				'condition' => [
					'show_icon' => 'yes',
				],
			]
		);

		$this->add_control(
			'action_hover_background',
			[
				'label'     => __( 'Background Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} ' . $button_selector . ':hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'action_hover_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} ' . $button_selector . ':hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'action_box_shadow',
				'selector' => '{{WRAPPER}} ' . $button_selector,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'action_typography',
				'selector' => '{{WRAPPER}} ' . $button_selector,
			]
		);

		$this->add_responsive_control(
			'action_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} ' . $button_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'action_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} ' . $button_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render a single-context placeholder.
	 *
	 * @param string $message Placeholder message.
	 * @return void
	 */
	protected function render_single_action_placeholder( string $message ): void {
		echo wp_kses_post(
			$this->render_placeholder(
				__( 'Single Listing Action', 'directorist-elementor' ),
				$message
			)
		);
	}

	/**
	 * Build icon markup from Elementor icon settings.
	 *
	 * @param array|string $icon_settings Icon settings.
	 * @return string
	 */
	protected function get_single_action_icon_markup( $icon_settings ): string {
		if ( empty( $icon_settings ) || ! is_array( $icon_settings ) ) {
			return '';
		}

		ob_start();
		Icons_Manager::render_icon(
			$icon_settings,
			[
				'aria-hidden' => 'true',
				'class'       => 'directorist-elementor-single-action__icon',
			]
		);

		return (string) ob_get_clean();
	}

	/**
	 * Get a normalized label/icon configuration.
	 *
	 * @param array $settings Widget settings.
	 * @param array $defaults Default values.
	 * @return array<string,mixed>
	 */
	protected function get_single_action_parts( array $settings, array $defaults = [] ): array {
		$defaults = array_merge(
			[
				'label' => '',
				'icon'  => [],
			],
			$defaults
		);

		$show_icon  = 'yes' === ( $settings['show_icon'] ?? 'yes' );
		$show_label = 'yes' === ( $settings['show_label'] ?? 'yes' );

		if ( ! $show_icon && ! $show_label ) {
			$show_label = true;
		}

		$label = trim( (string) ( $settings['label'] ?? $defaults['label'] ) );
		$icon_settings = $settings['action_icon'] ?? $defaults['icon'];
		$icon_markup   = $show_icon ? $this->get_single_action_icon_markup( $icon_settings ) : '';
		$label_markup  = $show_label && '' !== $label
			? sprintf( '<span class="directorist-single-listing-action__text">%s</span>', esc_html( $label ) )
			: '';

		$content = 'after' === ( $settings['icon_position'] ?? 'before' )
			? trim( $label_markup . ' ' . $icon_markup )
			: trim( $icon_markup . ' ' . $label_markup );

		return [
			'label'       => $label,
			'icon_markup' => $icon_markup,
			'label_markup'=> $label_markup,
			'content'     => $content,
			'show_icon'   => $show_icon,
			'show_label'  => $show_label,
		];
	}
}
