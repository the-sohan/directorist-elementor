<?php
/**
 * Base pricing plan field widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Pricing;

use DirectoristElementor\ElementorV4\CategoryRegistrar;
use DirectoristElementor\ElementorV4\Context\RenderContext;
use DirectoristElementor\ElementorV4\Render\PricingPlanRenderService;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractDirectoristWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

abstract class AbstractPricingPlanFieldWidget extends AbstractDirectoristWidget {

	/**
	 * Pricing plan widgets belong to their own field category.
	 *
	 * @return string
	 */
	protected function get_directorist_category_slug(): string {
		return CategoryRegistrar::CATEGORY_PRICING_PLAN;
	}

	/**
	 * Get field key used by the render service.
	 *
	 * @return string
	 */
	abstract protected function get_pricing_plan_field_key(): string;

	/**
	 * Register a common text style section.
	 *
	 * @param string $section_id Section id.
	 * @param string $label Label.
	 * @param string $selector CSS selector.
	 * @return void
	 */
	protected function register_text_style_controls( string $section_id, string $label, string $selector ): void {
		$this->start_controls_section(
			$section_id,
			[
				'label' => $label,
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			$section_id . '_alignment',
			[
				'label'     => __( 'Alignment', 'directorist-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [
						'title' => __( 'Left', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => __( 'Center', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'  => [
						'title' => __( 'Right', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					$selector => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			$section_id . '_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => $section_id . '_typography',
				'selector' => $selector,
			]
		);

		$this->add_responsive_control(
			$section_id . '_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					$selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Register common box style controls.
	 *
	 * @param string $section_id Section id.
	 * @param string $label Label.
	 * @param string $selector CSS selector.
	 * @return void
	 */
	protected function register_box_style_controls( string $section_id, string $label, string $selector ): void {
		$this->start_controls_section(
			$section_id,
			[
				'label' => $label,
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => $section_id . '_background',
				'selector' => $selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => $section_id . '_border',
				'selector' => $selector,
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => $section_id . '_shadow',
				'selector' => $selector,
			]
		);

		$this->add_responsive_control(
			$section_id . '_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			$section_id . '_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					$selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			$section_id . '_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Resolve pricing plan id from context or editor fallback.
	 *
	 * @return int
	 */
	protected function resolve_pricing_plan_id(): int {
		$context_plan_id = absint( RenderContext::get_instance()->current_pricing_plan_context()['plan_id'] ?? 0 );

		if ( $context_plan_id > 0 ) {
			return $context_plan_id;
		}

		if ( $this->is_editor_context() ) {
			$settings = $this->get_settings_for_display();
			$preview_plan_id = absint( $settings['preview_plan_id'] ?? 0 );

			if ( $preview_plan_id > 0 ) {
				return $preview_plan_id;
			}

			$default_ids = PricingPlanRenderService::get_instance()->get_default_plan_ids( 1 );

			return absint( $default_ids[0] ?? 0 );
		}

		return 0;
	}

	/**
	 * Register standalone editor preview control.
	 *
	 * @return void
	 */
	protected function register_preview_plan_control(): void {
		$this->add_control(
			'preview_plan_id',
			[
				'label'       => __( 'Preview Plan', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'default'     => '',
				'options'     => [ '' => __( 'Parent Pricing Plan', 'directorist-elementor' ) ] + PricingPlanRenderService::get_instance()->get_plan_options(),
				'description' => __( 'Editor-only fallback when this widget is not inside Pricing Plans.', 'directorist-elementor' ),
			]
		);
	}

	/**
	 * Render field widget.
	 *
	 * @return void
	 */
	protected function render(): void {
		$plan_id = $this->resolve_pricing_plan_id();

		if ( $plan_id <= 0 ) {
			if ( $this->is_editor_context() ) {
				echo wp_kses_post(
					$this->render_placeholder(
						$this->get_title(),
						__( 'Place this widget inside Pricing Plans or choose a preview plan.', 'directorist-elementor' )
					)
				);
			}

			return;
		}

		echo PricingPlanRenderService::get_instance()->render_field(
			$this->get_pricing_plan_field_key(),
			$plan_id,
			$this->get_settings_for_display()
		); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Render service escapes field data and may output trusted Elementor SVG icons.
	}
}
