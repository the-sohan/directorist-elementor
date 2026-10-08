<?php
/**
 * Base category/location title field widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Taxonomy;

use DirectoristElementor\ElementorV4\Widgets\Base\AbstractTaxonomyCardFieldWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

abstract class AbstractTaxonomyTitleWidget extends AbstractTaxonomyCardFieldWidget {

	abstract protected function get_scope_label(): string;

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_title_content',
			[
				'label' => $this->get_scope_label(),
			]
		);

		$this->add_control(
			'html_tag',
			[
				'label'   => __( 'HTML Tag', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => [
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'span' => 'span',
					'p'    => 'p',
				],
			]
		);

		$this->add_control(
			'link_to_term',
			[
				'label'        => __( 'Link To Archive', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_title_style',
			[
				'label' => $this->get_scope_label(),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'tabs_title_colors' );
		$this->start_controls_tab( 'tab_title_normal', [ 'label' => __( 'Normal', 'directorist-elementor' ) ] );

		$this->add_control(
			'title_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-taxonomy-title__text, {{WRAPPER}} .directorist-elementor-taxonomy-title__link' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->start_controls_tab( 'tab_title_hover', [ 'label' => __( 'Hover', 'directorist-elementor' ) ] );

		$this->add_control(
			'title_hover_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-taxonomy-title__link:hover, {{WRAPPER}} .directorist-elementor-taxonomy-title__link:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-taxonomy-title__text, {{WRAPPER}} .directorist-elementor-taxonomy-title__link',
			]
		);

		$this->add_responsive_control(
			'title_align',
			[
				'label'   => __( 'Alignment', 'directorist-elementor' ),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
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
					'{{WRAPPER}} .directorist-elementor-taxonomy-title' => 'text-align: {{VALUE}};',
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

		$name = $this->get_item_string( 'name' );
		if ( '' === trim( $name ) ) {
			return;
		}

		$settings     = $this->get_settings_for_display();
		$html_tag     = strtolower( (string) ( $settings['html_tag'] ?? 'h3' ) );
		$html_tag     = in_array( $html_tag, [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p' ], true ) ? $html_tag : 'h3';
		$permalink    = $this->get_item_string( 'permalink' );
		$should_link  = 'yes' === ( $settings['link_to_term'] ?? 'yes' ) && '' !== $permalink;
		$scope        = esc_attr( $this->get_taxonomy_scope() );

		echo '<div class="directorist-elementor-taxonomy-title directorist-elementor-taxonomy-title--' . $scope . '">';
		printf( '<%1$s class="directorist-elementor-taxonomy-title__text">', esc_attr( $html_tag ) );

		if ( $should_link ) {
			printf(
				'<a class="directorist-elementor-taxonomy-title__link" href="%1$s">%2$s</a>',
				esc_url( $permalink ),
				esc_html( $name )
			);
		} else {
			echo esc_html( $name );
		}

		printf( '</%s>', esc_attr( $html_tag ) );
		echo '</div>';
	}
}
