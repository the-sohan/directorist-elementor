<?php
/**
 * Pricing plan duration widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Pricing;

class PricingPlanDurationWidget extends AbstractPricingPlanFieldWidget {

	public function get_name(): string {
		return 'directorist_pricing_plan_duration';
	}

	public function get_title(): string {
		return __( 'Plan Duration', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-clock';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'pricing', 'plan', 'duration', 'interval' ] );
	}

	protected function get_pricing_plan_field_key(): string {
		return 'duration';
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_pricing_plan_duration',
			[
				'label' => __( 'Plan Duration', 'directorist-elementor' ),
			]
		);

		$this->register_preview_plan_control();

		$this->end_controls_section();

		$this->register_text_style_controls(
			'section_pricing_plan_duration_style',
			__( 'Duration', 'directorist-elementor' ),
			'{{WRAPPER}} .directorist-elementor-pricing-plan-duration'
		);
	}
}
