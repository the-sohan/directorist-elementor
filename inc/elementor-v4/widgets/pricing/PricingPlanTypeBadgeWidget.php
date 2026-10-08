<?php
/**
 * Pricing plan type badge widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Pricing;

class PricingPlanTypeBadgeWidget extends AbstractPricingPlanFieldWidget {

	public function get_name(): string {
		return 'directorist_pricing_plan_type_badge';
	}

	public function get_title(): string {
		return __( 'Plan Type Badge', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-tags';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'pricing', 'plan', 'type', 'badge' ] );
	}

	protected function get_pricing_plan_field_key(): string {
		return 'type-badge';
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_pricing_plan_type_badge',
			[
				'label' => __( 'Plan Type Badge', 'directorist-elementor' ),
			]
		);

		$this->register_preview_plan_control();

		$this->end_controls_section();

		$this->register_box_style_controls(
			'section_pricing_plan_type_badge_style',
			__( 'Type Badge', 'directorist-elementor' ),
			'{{WRAPPER}} .directorist-elementor-pricing-plan-type-badge'
		);
	}
}
