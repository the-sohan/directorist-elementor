<?php
/**
 * Pricing plan action button widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Pricing;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class PricingPlanActionButtonWidget extends AbstractPricingPlanFieldWidget {

	public function get_name(): string {
		return 'directorist_pricing_plan_action_button';
	}

	public function get_title(): string {
		return __( 'Plan Action Button', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-button';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'pricing', 'plan', 'button', 'continue' ] );
	}

	protected function get_pricing_plan_field_key(): string {
		return 'action-button';
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_pricing_plan_button',
			[
				'label' => __( 'Action Button', 'directorist-elementor' ),
			]
		);

		$this->register_preview_plan_control();

		$this->add_control(
			'label',
			[
				'label'       => __( 'Label', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Continue', 'directorist-elementor' ),
				'label_block' => true,
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_pricing_plan_button_style',
			[
				'label' => __( 'Action Button', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'button_alignment',
			[
				'label'     => __( 'Alignment', 'directorist-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start' => [
						'title' => __( 'Left', 'directorist-elementor' ),
						'icon'  => 'eicon-h-align-left',
					],
					'center'     => [
						'title' => __( 'Center', 'directorist-elementor' ),
						'icon'  => 'eicon-h-align-center',
					],
					'flex-end'   => [
						'title' => __( 'Right', 'directorist-elementor' ),
						'icon'  => 'eicon-h-align-right',
					],
					'stretch'    => [
						'title' => __( 'Stretch', 'directorist-elementor' ),
						'icon'  => 'eicon-h-align-stretch',
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--direl-pricing-plan-button-align: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-pricing-plan-action-button' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_background_color',
			[
				'label'     => __( 'Background Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-pricing-plan-action-button' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_hover_text_color',
			[
				'label'     => __( 'Hover Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-pricing-plan-action-button:hover, {{WRAPPER}} .directorist-elementor-pricing-plan-action-button:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_hover_background_color',
			[
				'label'     => __( 'Hover Background Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-pricing-plan-action-button:hover, {{WRAPPER}} .directorist-elementor-pricing-plan-action-button:focus' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-pricing-plan-action-button',
			]
		);

		$this->add_responsive_control(
			'button_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-pricing-plan-action-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-pricing-plan-action-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-pricing-plan-action-button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}
}
