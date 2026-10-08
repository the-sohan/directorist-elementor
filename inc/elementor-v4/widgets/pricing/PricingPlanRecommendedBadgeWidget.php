<?php
/**
 * Pricing plan recommended badge widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Pricing;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class PricingPlanRecommendedBadgeWidget extends AbstractPricingPlanFieldWidget {

	public function get_name(): string {
		return 'directorist_pricing_plan_recommended_badge';
	}

	public function get_title(): string {
		return __( 'Plan Recommended Badge', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-favorite';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'pricing', 'plan', 'recommended', 'badge' ] );
	}

	protected function get_pricing_plan_field_key(): string {
		return 'recommended-badge';
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_pricing_plan_recommended_badge',
			[
				'label' => __( 'Recommended Badge', 'directorist-elementor' ),
			]
		);

		$this->register_preview_plan_control();

		$this->add_control(
			'label',
			[
				'label'       => __( 'Label', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Recommended', 'directorist-elementor' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'show_label',
			[
				'label'        => __( 'Show Label', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'directorist-elementor' ),
				'label_off'    => __( 'Hide', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
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
				'default'      => '',
			]
		);

		$this->add_control(
			'icon',
			[
				'label'     => __( 'Icon', 'directorist-elementor' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => [
					'value'   => 'fas fa-star',
					'library' => 'fa-solid',
				],
				'condition' => [
					'show_icon' => 'yes',
				],
			]
		);

		$this->add_control(
			'icon_position',
			[
				'label'     => __( 'Icon Position', 'directorist-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'before' => [
						'title' => __( 'Before', 'directorist-elementor' ),
						'icon'  => 'eicon-h-align-left',
					],
					'after'  => [
						'title' => __( 'After', 'directorist-elementor' ),
						'icon'  => 'eicon-h-align-right',
					],
				],
				'default'   => 'before',
				'toggle'    => false,
				'condition' => [
					'show_icon' => 'yes',
					'icon_only!' => 'yes',
				],
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
				'condition'    => [
					'show_icon' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		$this->register_box_style_controls(
			'section_pricing_plan_recommended_badge_style',
			__( 'Recommended Badge', 'directorist-elementor' ),
			'{{WRAPPER}} .directorist-elementor-pricing-plan-recommended-badge'
		);

		$this->start_controls_section(
			'section_pricing_plan_recommended_badge_content_style',
			[
				'label' => __( 'Recommended Badge Content', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'badge_content_gap',
			[
				'label'      => __( 'Icon Gap', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 60,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-pricing-plan-recommended-badge' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'badge_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-pricing-plan-recommended-badge__label' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'badge_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-pricing-plan-recommended-badge__label',
			]
		);

		$this->add_control(
			'badge_icon_color',
			[
				'label'     => __( 'Icon Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-pricing-plan-recommended-badge__icon, {{WRAPPER}} .directorist-elementor-pricing-plan-recommended-badge__icon svg' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
				'condition' => [
					'show_icon' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'badge_icon_size',
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
					'{{WRAPPER}} .directorist-elementor-pricing-plan-recommended-badge__icon' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-pricing-plan-recommended-badge__icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'show_icon' => 'yes',
				],
			]
		);

		$this->end_controls_section();
	}
}
