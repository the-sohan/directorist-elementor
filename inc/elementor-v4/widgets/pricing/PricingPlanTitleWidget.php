<?php
/**
 * Pricing plan title widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Pricing;

use Elementor\Controls_Manager;

class PricingPlanTitleWidget extends AbstractPricingPlanFieldWidget {

	public function get_name(): string {
		return 'directorist_pricing_plan_title';
	}

	public function get_title(): string {
		return __( 'Plan Title', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-heading';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'pricing', 'plan', 'title' ] );
	}

	protected function get_pricing_plan_field_key(): string {
		return 'title';
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_pricing_plan_title',
			[
				'label' => __( 'Plan Title', 'directorist-elementor' ),
			]
		);

		$this->register_preview_plan_control();

		$this->add_control(
			'heading_level',
			[
				'label'   => __( 'HTML Tag', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '4',
				'options' => [
					'1' => 'H1',
					'2' => 'H2',
					'3' => 'H3',
					'4' => 'H4',
					'5' => 'H5',
					'6' => 'H6',
				],
			]
		);

		$this->end_controls_section();

		$this->register_text_style_controls(
			'section_pricing_plan_title_style',
			__( 'Title', 'directorist-elementor' ),
			'{{WRAPPER}} .directorist-elementor-pricing-plan-title'
		);
	}
}
