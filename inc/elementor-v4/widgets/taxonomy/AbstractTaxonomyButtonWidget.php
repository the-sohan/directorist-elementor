<?php
/**
 * Base category/location button widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Taxonomy;

use DirectoristElementor\ElementorV4\Widgets\Base\AbstractTaxonomyCardFieldWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;

abstract class AbstractTaxonomyButtonWidget extends AbstractTaxonomyCardFieldWidget {

	abstract protected function get_scope_label(): string;

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_button_content',
			[
				'label' => $this->get_scope_label(),
			]
		);

		$this->add_control(
			'button_text',
			[
				'label'       => __( 'Text', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'View', 'directorist-elementor' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'open_new_window',
			[
				'label'        => __( 'Open in New Window', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_button_style',
			[
				'label' => $this->get_scope_label(),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'tabs_button_style' );
		$this->start_controls_tab( 'tab_button_normal', [ 'label' => __( 'Normal', 'directorist-elementor' ) ] );

		$this->add_control(
			'button_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-taxonomy-button__link' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_background',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-taxonomy-button__link' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->start_controls_tab( 'tab_button_hover', [ 'label' => __( 'Hover', 'directorist-elementor' ) ] );

		$this->add_control(
			'button_hover_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-taxonomy-button__link:hover, {{WRAPPER}} .directorist-elementor-taxonomy-button__link:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_hover_background',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-taxonomy-button__link:hover, {{WRAPPER}} .directorist-elementor-taxonomy-button__link:focus' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-taxonomy-button__link',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'button_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-taxonomy-button__link',
			]
		);

		$this->add_control(
			'button_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-taxonomy-button__link' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-taxonomy-button__link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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

		$settings  = $this->get_settings_for_display();
		$text      = sanitize_text_field( (string) ( $settings['button_text'] ?? __( 'View', 'directorist-elementor' ) ) );
		$permalink = $this->get_item_string( 'permalink' );

		if ( '' === $text || '' === $permalink ) {
			return;
		}

		$attrs = [
			'class' => 'directorist-elementor-taxonomy-button__link',
			'href'  => esc_url( $permalink ),
		];

		if ( 'yes' === ( $settings['open_new_window'] ?? '' ) ) {
			$attrs['target'] = '_blank';
			$attrs['rel']    = 'noopener';
		}

		echo '<div class="directorist-elementor-taxonomy-button directorist-elementor-taxonomy-button--' . esc_attr( $this->get_taxonomy_scope() ) . '">';
		echo '<a ' . $this->format_html_attributes( $attrs ) . '>' . esc_html( $text ) . '</a>';
		echo '</div>';
	}
}
