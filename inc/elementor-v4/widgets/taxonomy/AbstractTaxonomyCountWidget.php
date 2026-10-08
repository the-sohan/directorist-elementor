<?php
/**
 * Base category/location listing count widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Taxonomy;

use DirectoristElementor\ElementorV4\Widgets\Base\AbstractTaxonomyCardFieldWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

abstract class AbstractTaxonomyCountWidget extends AbstractTaxonomyCardFieldWidget {

	abstract protected function get_scope_label(): string;

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_count_content',
			[
				'label' => $this->get_scope_label(),
			]
		);

		$this->add_control(
			'singular_label',
			[
				'label'   => __( 'Singular Text', 'directorist-elementor' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'listing', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'plural_label',
			[
				'label'   => __( 'Plural Text', 'directorist-elementor' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'listings', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'number_only',
			[
				'label'        => __( 'Number Only', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_count_style',
			[
				'label' => $this->get_scope_label(),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'count_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-taxonomy-count' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'count_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-taxonomy-count',
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

		$settings       = $this->get_settings_for_display();
		$count          = $this->get_item_int( 'count' );
		$singular_label = sanitize_text_field( (string) ( $settings['singular_label'] ?? __( 'listing', 'directorist-elementor' ) ) );
		$plural_label   = sanitize_text_field( (string) ( $settings['plural_label'] ?? __( 'listings', 'directorist-elementor' ) ) );
		$suffix         = 1 === $count ? $singular_label : $plural_label;

		echo '<span class="directorist-elementor-taxonomy-count directorist-elementor-taxonomy-count--' . esc_attr( $this->get_taxonomy_scope() ) . '">';
		printf( '<span class="directorist-elementor-taxonomy-count__number">%s</span>', esc_html( number_format_i18n( $count ) ) );

		if ( 'yes' !== ( $settings['number_only'] ?? '' ) && '' !== $suffix ) {
			printf( ' <span class="directorist-elementor-taxonomy-count__label">%s</span>', esc_html( $suffix ) );
		}

		echo '</span>';
	}
}
