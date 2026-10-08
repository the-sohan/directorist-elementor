<?php
/**
 * Pricing plan price widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Pricing;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class PricingPlanPriceWidget extends AbstractPricingPlanFieldWidget {

	public function get_name(): string {
		return 'directorist_pricing_plan_price';
	}

	public function get_title(): string {
		return __( 'Plan Price', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'directorist-eicon directorist-eicon--pricing';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'pricing', 'plan', 'price', 'amount' ] );
	}

	protected function get_pricing_plan_field_key(): string {
		return 'price';
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_pricing_plan_price',
			[
				'label' => __( 'Plan Price', 'directorist-elementor' ),
			]
		);

		$this->register_preview_plan_control();

		$this->add_control(
			'show_duration',
			[
				'label'        => __( 'Show Duration', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'directorist-elementor' ),
				'label_off'    => __( 'Hide', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_tax_tooltip',
			[
				'label'        => __( 'Show Tax Tooltip', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'directorist-elementor' ),
				'label_off'    => __( 'Hide', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_pricing_plan_price_style',
			[
				'label' => __( 'Price', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'price_alignment',
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
					'{{WRAPPER}} .directorist-elementor-pricing-plan-price' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'price_amount_color',
			[
				'label'     => __( 'Amount Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-pricing-plan-price__amount, {{WRAPPER}} .directorist-elementor-pricing-plan-price__free' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'price_amount_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-pricing-plan-price__amount, {{WRAPPER}} .directorist-elementor-pricing-plan-price__free',
			]
		);

		$this->add_control(
			'price_currency_color',
			[
				'label'     => __( 'Currency Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-pricing-plan-price__currency' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'price_currency_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-pricing-plan-price__currency',
			]
		);

		$this->add_control(
			'price_duration_color',
			[
				'label'     => __( 'Duration Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-pricing-plan-price__duration' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'price_duration_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-pricing-plan-price__duration',
			]
		);

		$this->add_responsive_control(
			'price_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-pricing-plan-price' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}
}
