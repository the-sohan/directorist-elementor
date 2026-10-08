<?php
/**
 * Base category/location icon field widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Taxonomy;

use DirectoristElementor\ElementorV4\Widgets\Base\AbstractTaxonomyCardFieldWidget;
use Elementor\Controls_Manager;

abstract class AbstractTaxonomyIconWidget extends AbstractTaxonomyCardFieldWidget {

	abstract protected function get_scope_label(): string;

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_icon_style',
			[
				'label' => $this->get_scope_label(),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'icon_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-taxonomy-icon, {{WRAPPER}} .directorist-elementor-taxonomy-icon i, {{WRAPPER}} .directorist-elementor-taxonomy-icon svg' => 'color: {{VALUE}}; fill: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-taxonomy-icon .directorist-icon-mask::after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'icon_size',
			[
				'label'      => __( 'Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 8, 'max' => 120 ],
					'em' => [ 'min' => 0.5, 'max' => 8, 'step' => 0.1 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-taxonomy-icon, {{WRAPPER}} .directorist-elementor-taxonomy-icon i, {{WRAPPER}} .directorist-elementor-taxonomy-icon svg' => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-taxonomy-icon .directorist-icon-mask::after' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->register_position_controls();
	}

	protected function render(): void {
		if ( ! $this->has_matching_taxonomy_context() ) {
			$this->render_taxonomy_context_placeholder(
				__( 'Place this widget inside its matching category or location card template.', 'directorist-elementor' )
			);
			return;
		}

		$icon_class = $this->get_item_string( 'icon_class' );

		if ( '' === $icon_class || 'none' === $icon_class ) {
			return;
		}

		echo '<span class="directorist-elementor-taxonomy-icon directorist-elementor-taxonomy-icon--' . esc_attr( $this->get_taxonomy_scope() ) . '">';
		if ( function_exists( 'directorist_icon' ) ) {
			directorist_icon( $icon_class );
		} else {
			printf( '<i class="%s" aria-hidden="true"></i>', esc_attr( $icon_class ) );
		}
		echo '</span>';
	}
}
