<?php
/**
 * Base category/location image field widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Taxonomy;

use DirectoristElementor\ElementorV4\Widgets\Base\AbstractTaxonomyCardFieldWidget;
use Elementor\Controls_Manager;

abstract class AbstractTaxonomyImageWidget extends AbstractTaxonomyCardFieldWidget {

	abstract protected function get_scope_label(): string;

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_image_content',
			[
				'label' => $this->get_scope_label(),
			]
		);

		$this->add_control(
			'render_mode',
			[
				'label'   => __( 'Render Mode', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'image',
				'options' => [
					'image'      => __( 'Image', 'directorist-elementor' ),
					'background' => __( 'Card Background', 'directorist-elementor' ),
				],
			]
		);

		$this->add_control(
			'image_size',
			[
				'label'   => __( 'Image Size', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'large',
				'options' => [
					'thumbnail' => __( 'Thumbnail', 'directorist-elementor' ),
					'medium'    => __( 'Medium', 'directorist-elementor' ),
					'large'     => __( 'Large', 'directorist-elementor' ),
					'full'      => __( 'Full', 'directorist-elementor' ),
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
				'condition'    => [
					'render_mode' => 'image',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_image_style',
			[
				'label' => $this->get_scope_label(),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'image_height',
			[
				'label'      => __( 'Height', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vh' ],
				'range'      => [
					'px' => [ 'min' => 50, 'max' => 800 ],
					'vh' => [ 'min' => 5, 'max' => 100 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-taxonomy-image__img' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'object_fit',
			[
				'label'   => __( 'Object Fit', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'cover',
				'options' => [
					'cover'   => __( 'Cover', 'directorist-elementor' ),
					'contain' => __( 'Contain', 'directorist-elementor' ),
					'fill'    => __( 'Fill', 'directorist-elementor' ),
				],
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-taxonomy-image__img' => 'object-fit: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-taxonomy-image__img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'background_overlay_color',
			[
				'label'     => __( 'Background Overlay', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#000000',
				'selectors' => [
					'{{WRAPPER}}' => '--direl-taxonomy-background-overlay: {{VALUE}};',
				],
				'condition' => [
					'render_mode' => 'background',
				],
			]
		);

		$this->add_control(
			'background_overlay_opacity',
			[
				'label'     => __( 'Overlay Opacity', 'directorist-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [ 'size' => 0.45 ],
				'size_units' => [ '' ],
				'range'     => [
					'' => [ 'min' => 0, 'max' => 1, 'step' => 0.05 ],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--direl-taxonomy-background-opacity: {{SIZE}};',
				],
				'condition' => [
					'render_mode' => 'background',
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

		$settings = $this->get_settings_for_display();

		if ( 'background' === ( $settings['render_mode'] ?? 'image' ) ) {
			printf(
				'<span class="directorist-elementor-taxonomy-image directorist-elementor-taxonomy-image--background-marker directorist-elementor-taxonomy-image--%s" aria-hidden="true"></span>',
				esc_attr( $this->get_taxonomy_scope() )
			);
			return;
		}

		$image_url = $this->resolve_image_url( (string) ( $settings['image_size'] ?? 'large' ) );
		if ( '' === $image_url ) {
			return;
		}

		$name        = $this->get_item_string( 'name' );
		$permalink   = $this->get_item_string( 'permalink' );
		$should_link = 'yes' === ( $settings['link_to_term'] ?? 'yes' ) && '' !== $permalink;
		$image       = sprintf(
			'<img class="directorist-elementor-taxonomy-image__img" src="%1$s" alt="%2$s" loading="lazy" />',
			esc_url( $image_url ),
			esc_attr( $name )
		);

		echo '<div class="directorist-elementor-taxonomy-image directorist-elementor-taxonomy-image--' . esc_attr( $this->get_taxonomy_scope() ) . '">';
		if ( $should_link ) {
			printf(
				'<a class="directorist-elementor-taxonomy-image__link" href="%1$s">%2$s</a>',
				esc_url( $permalink ),
				$image // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		} else {
			echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</div>';
	}

	protected function resolve_image_url( string $size ): string {
		$image_id = $this->get_item_int( 'image_id' );

		if ( $image_id > 0 ) {
			$size = sanitize_key( $size );
			$size = in_array( $size, [ 'thumbnail', 'medium', 'large', 'full' ], true ) ? $size : 'large';
			$url  = wp_get_attachment_image_url( $image_id, $size );

			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}

		return $this->get_item_string( 'img' );
	}
}
