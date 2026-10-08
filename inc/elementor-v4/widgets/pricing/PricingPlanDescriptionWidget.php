<?php
/**
 * Pricing plan description widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Pricing;

class PricingPlanDescriptionWidget extends AbstractPricingPlanFieldWidget {

	public function get_name(): string {
		return 'directorist_pricing_plan_description';
	}

	public function get_title(): string {
		return __( 'Plan Description', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-text';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'pricing', 'plan', 'description' ] );
	}

	protected function get_pricing_plan_field_key(): string {
		return 'description';
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_pricing_plan_description',
			[
				'label' => __( 'Plan Description', 'directorist-elementor' ),
			]
		);

		$this->register_preview_plan_control();

		$this->end_controls_section();

		$this->register_text_style_controls(
			'section_pricing_plan_description_style',
			__( 'Description', 'directorist-elementor' ),
			'{{WRAPPER}} .directorist-elementor-pricing-plan-description'
		);
	}
}
