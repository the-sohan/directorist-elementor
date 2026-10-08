<?php
/**
 * Pricing plan features widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Pricing;

use DirectoristElementor\ElementorV4\Render\PricingPlanRenderService;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class PricingPlanFeaturesWidget extends AbstractPricingPlanFieldWidget {

	public function get_name(): string {
		return 'directorist_pricing_plan_features';
	}

	public function get_title(): string {
		return __( 'Plan Features', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-bullet-list';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'pricing', 'plan', 'features', 'list' ] );
	}

	protected function get_pricing_plan_field_key(): string {
		return 'features';
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_pricing_plan_features',
			[
				'label' => __( 'Plan Features', 'directorist-elementor' ),
			]
		);

		$this->register_preview_plan_control();

		foreach (
			[
				'show_auto_renew'           => __( 'Show Auto Renew', 'directorist-elementor' ),
				'show_listing_limit'        => __( 'Show Listing Limit', 'directorist-elementor' ),
				'show_plan_features'        => __( 'Show Plan Features', 'directorist-elementor' ),
				'show_unavailable_features' => __( 'Show Unavailable Features', 'directorist-elementor' ),
				'show_feature_suffix'       => __( 'Show Feature Limit Suffix', 'directorist-elementor' ),
			] as $control_id => $label
		) {
			$this->add_control(
				$control_id,
				[
					'label'        => $label,
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => __( 'Show', 'directorist-elementor' ),
					'label_off'    => __( 'Hide', 'directorist-elementor' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				]
			);
		}

		$this->add_control(
			'available_icon',
			[
				'label'   => __( 'Available Icon', 'directorist-elementor' ),
				'type'    => Controls_Manager::ICONS,
				'default' => [
					'value'   => 'fas fa-check',
					'library' => 'fa-solid',
				],
			]
		);

		$this->add_control(
			'unavailable_icon',
			[
				'label'   => __( 'Unavailable Icon', 'directorist-elementor' ),
				'type'    => Controls_Manager::ICONS,
				'default' => [
					'value'   => 'fas fa-times',
					'library' => 'fa-solid',
				],
			]
		);

		$this->add_control(
			'feature_order',
			[
				'label'       => __( 'Feature Order', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => PricingPlanRenderService::get_instance()->get_feature_order_options(),
				'description' => __( 'Selected features are moved to the top in this order. Unselected features keep their plan order.', 'directorist-elementor' ),
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_pricing_plan_features_style',
			[
				'label' => __( 'Features', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'features_gap',
			[
				'label'      => __( 'Item Gap', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 80,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-pricing-plan-features ul' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'features_item_padding',
			[
				'label'      => __( 'Item Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-pricing-plan-features li' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'features_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-pricing-plan-features__text' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'features_text_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-pricing-plan-features__text',
			]
		);

		$this->add_control(
			'features_available_icon_color',
			[
				'label'     => __( 'Available Icon Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-pricing-plan-features__icon--available, {{WRAPPER}} .directorist-elementor-pricing-plan-features__icon--available svg' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'features_unavailable_icon_color',
			[
				'label'     => __( 'Unavailable Icon Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-pricing-plan-features__icon--unavailable, {{WRAPPER}} .directorist-elementor-pricing-plan-features__icon--unavailable svg' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'features_icon_size',
			[
				'label'      => __( 'Icon Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 6,
						'max' => 80,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-pricing-plan-features__icon' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-pricing-plan-features__icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'features_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-pricing-plan-features' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}
}
