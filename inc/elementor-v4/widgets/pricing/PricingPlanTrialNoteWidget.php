<?php
/**
 * Pricing plan trial note widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Pricing;

class PricingPlanTrialNoteWidget extends AbstractPricingPlanFieldWidget {

	public function get_name(): string {
		return 'directorist_pricing_plan_trial_note';
	}

	public function get_title(): string {
		return __( 'Plan Trial Note', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-info-circle-o';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'pricing', 'plan', 'trial' ] );
	}

	protected function get_pricing_plan_field_key(): string {
		return 'trial-note';
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_pricing_plan_trial_note',
			[
				'label' => __( 'Plan Trial Note', 'directorist-elementor' ),
			]
		);

		$this->register_preview_plan_control();

		$this->end_controls_section();

		$this->register_text_style_controls(
			'section_pricing_plan_trial_note_style',
			__( 'Trial Note', 'directorist-elementor' ),
			'{{WRAPPER}} .directorist-elementor-pricing-plan-trial-note'
		);
	}
}
