<?php
/**
 * Base widget for Directorist single listing sections.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Base;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

abstract class AbstractSingleSectionWidget extends AbstractSingleListingWidget {

	/**
	 * Render a section-context placeholder.
	 *
	 * @param string $message Placeholder message.
	 * @return void
	 */
	protected function render_single_section_placeholder( string $message ): void {
		echo wp_kses_post(
			$this->render_placeholder(
				__( 'Single Listing Section', 'directorist-elementor' ),
				$message
			)
		);
	}

	/**
	 * Register shared section style controls.
	 *
	 * @param string $section_id Section id.
	 * @param string $label Section label.
	 * @param string $wrapper_selector Section wrapper selector.
	 * @param string $title_selector Title selector.
	 * @param string $body_selector Body selector.
	 * @return void
	 */
	protected function register_single_section_style_controls(
		string $section_id,
		string $label,
		string $wrapper_selector,
		string $title_selector,
		string $body_selector = ''
	): void {
		$this->start_controls_section(
			$section_id,
			[
				'label' => $label,
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'section_background',
				'selector' => '{{WRAPPER}} ' . $wrapper_selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'section_border',
				'selector' => '{{WRAPPER}} ' . $wrapper_selector,
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'section_box_shadow',
				'selector' => '{{WRAPPER}} ' . $wrapper_selector,
			]
		);

		$this->add_responsive_control(
			'section_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} ' . $wrapper_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'section_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} ' . $wrapper_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'section_title_color',
			[
				'label'     => __( 'Title Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} ' . $title_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'section_title_typography',
				'selector' => '{{WRAPPER}} ' . $title_selector,
			]
		);

		if ( '' !== $body_selector ) {
			$this->add_control(
				'section_body_color',
				[
					'label'     => __( 'Body Text Color', 'directorist-elementor' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} ' . $body_selector => 'color: {{VALUE}};',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name'     => 'section_body_typography',
					'selector' => '{{WRAPPER}} ' . $body_selector,
				]
			);
		}

		$this->end_controls_section();
	}
}
