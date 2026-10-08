<?php
/**
 * Pricing plan active badge widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Pricing;

use Elementor\Controls_Manager;

class PricingPlanActiveBadgeWidget extends AbstractPricingPlanFieldWidget {

	public function get_name(): string {
		return 'directorist_pricing_plan_active_badge';
	}

	public function get_title(): string {
		return __( 'Plan Active Badge', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-check-circle';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'pricing', 'plan', 'active', 'badge' ] );
	}

	protected function get_pricing_plan_field_key(): string {
		return 'active-badge';
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_pricing_plan_active_badge',
			[
				'label' => __( 'Active Badge', 'directorist-elementor' ),
			]
		);

		$this->register_preview_plan_control();

		$this->add_control(
			'label',
			[
				'label'       => __( 'Label', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Active', 'directorist-elementor' ),
				'label_block' => true,
			]
		);

		$this->end_controls_section();

		$this->register_box_style_controls(
			'section_pricing_plan_active_badge_style',
			__( 'Active Badge', 'directorist-elementor' ),
			'{{WRAPPER}} .directorist-elementor-pricing-plan-active-badge'
		);
	}
}
