<?php
/**
 * Listing card thumbnail widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsPreset;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractMediaFieldWidget;
use Elementor\Controls_Manager;

class ListingCardThumbnailWidget extends AbstractMediaFieldWidget {

	/**
	 * Get widget slug.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'directorist_listing_card_thumbnail';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Listing Thumbnail', 'directorist-elementor' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-image';
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array<int,string>
	 */
	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'thumbnail', 'image' ] );
	}

	/**
	 * Register content and style controls.
	 *
	 * @return void
	 */
	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_thumbnail_content',
			[
				'label' => __( 'Thumbnail', 'directorist-elementor' ),
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
			'link_to_listing',
			[
				'label'        => __( 'Link To Listing', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_thumbnail_style',
			[
				'label' => __( 'Thumbnail', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'thumbnail_height',
			[
				'label'      => __( 'Height', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vh' ],
				'range'      => [
					'px' => [
						'min' => 80,
						'max' => 800,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listing-card-thumbnail__image' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'thumbnail_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listing-card-thumbnail__image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'thumbnail_object_fit',
			[
				'label'     => __( 'Object Fit', 'directorist-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'cover',
				'options'   => [
					'cover'   => __( 'Cover', 'directorist-elementor' ),
					'contain' => __( 'Contain', 'directorist-elementor' ),
					'fill'    => __( 'Fill', 'directorist-elementor' ),
				],
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-thumbnail__image' => 'object-fit: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render the thumbnail field.
	 *
	 * @return void
	 */
	protected function render(): void {
		$listing_id = $this->get_current_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_preset_context_placeholder(
				__( 'Place this widget inside a Directorist listing card template to render the listing image.', 'directorist-elementor' )
			);
			return;
		}

		$settings   = $this->get_settings_for_display();
		$image_size = $this->sanitize_image_size( (string) ( $settings['image_size'] ?? 'large' ) );
		$image_url  = DirectoristBridge::get_instance()->get_listing_image_url( $listing_id, $image_size );

		if ( '' === $image_url ) {
			$this->render_preset_context_placeholder(
				__( 'The selected listing does not have a preview image.', 'directorist-elementor' )
			);
			return;
		}

		$title         = DirectoristBridge::get_instance()->get_listing_title( $listing_id );
		$permalink     = DirectoristBridge::get_instance()->get_listing_permalink( $listing_id );
		$should_link   = 'yes' === ( $settings['link_to_listing'] ?? 'yes' ) && '' !== $permalink;
		$image_markup  = sprintf(
			'<img class="directorist-elementor-listing-card-thumbnail__image" src="%1$s" alt="%2$s" loading="lazy" />',
			esc_url( $image_url ),
			esc_attr( $title )
		);

		echo '<div class="directorist-elementor-listing-card-thumbnail">';

		if ( $should_link ) {
			printf(
				'<a class="directorist-elementor-listing-card-thumbnail__link" href="%1$s">%2$s</a>',
				esc_url( $permalink ),
				$image_markup // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		} else {
			echo $image_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '</div>';
	}
}
