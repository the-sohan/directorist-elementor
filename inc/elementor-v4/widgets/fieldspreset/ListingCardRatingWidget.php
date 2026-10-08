<?php
/**
 * Listing card rating widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsPreset;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractPresetFieldWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class ListingCardRatingWidget extends AbstractPresetFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_rating';
	}

	public function get_title(): string {
		return __( 'Listing Rating', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-rating';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'rating', 'review', 'stars' ] );
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_rating_content',
			[
				'label' => __( 'Rating', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'show_review_count',
			[
				'label'        => __( 'Show Review Count', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_rating_style',
			[
				'label' => __( 'Rating', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'star_color',
			[
				'label'     => __( 'Active Star Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-rating__star-icon--active' => 'color: {{VALUE}}; fill: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listing-card-rating__star-icon--active:after, {{WRAPPER}} .directorist-elementor-listing-card-rating__star-icon--active i:after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'inactive_star_color',
			[
				'label'     => __( 'Inactive Star Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-rating__star-icon--inactive' => 'color: {{VALUE}}; fill: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listing-card-rating__star-icon--inactive:after, {{WRAPPER}} .directorist-elementor-listing-card-rating__star-icon--inactive i:after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'star_size',
			[
				'label'      => __( 'Star Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 8,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listing-card-rating__stars' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'value_color',
			[
				'label'     => __( 'Value Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-rating__value' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listing-card-rating__count' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'rating_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-listing-card-rating',
			]
		);

		$this->add_responsive_control(
			'rating_gap',
			[
				'label'      => __( 'Gap', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listing-card-rating' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$listing_id = $this->get_current_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_preset_context_placeholder(
				__( 'Place this widget inside a Directorist listing card template to render the listing rating.', 'directorist-elementor' )
			);
			return;
		}

		$rating_summary = DirectoristBridge::get_instance()->get_listing_rating_summary( $listing_id );
		$rating_value   = $rating_summary['rating'];
		$review_count   = $rating_summary['count'];

		if ( $review_count <= 0 && $this->is_editor_context() ) {
			$rating_value = 4.5;
			$review_count = 2;
		}

		$settings = $this->get_settings_for_display();

		echo '<div class="directorist-elementor-listing-card-rating">';
		$this->render_rating_stars( $rating_value );
		printf(
			'<span class="directorist-elementor-listing-card-rating__value">%s</span>',
			esc_html( number_format_i18n( $rating_value, 1 ) )
		);

		if ( 'yes' === ( $settings['show_review_count'] ?? 'yes' ) ) {
			printf(
				'<span class="directorist-elementor-listing-card-rating__count">(%s)</span>',
				esc_html( number_format_i18n( $review_count ) )
			);
		}

		echo '</div>';
	}

	/**
	 * Render five stars with an exact fractional active layer.
	 *
	 * @param float $rating_value Rating value from zero to five.
	 * @return void
	 */
	protected function render_rating_stars( float $rating_value ): void {
		$rating_value = max( 0.0, min( 5.0, $rating_value ) );

		echo '<span class="directorist-elementor-listing-card-rating__stars" aria-hidden="true">';

		for ( $position = 1; $position <= 5; $position++ ) {
			$fill = max( 0.0, min( 1.0, $rating_value - ( $position - 1 ) ) ) * 100;

			printf(
				'<span class="directorist-elementor-listing-card-rating__star" style="--direl-rating-star-fill:%s%%">',
				esc_attr( number_format( $fill, 2, '.', '' ) )
			);
			echo $this->get_star_icon_markup( 'directorist-elementor-listing-card-rating__star-icon directorist-elementor-listing-card-rating__star-icon--inactive' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Directorist icon markup or escaped fallback.
			echo '<span class="directorist-elementor-listing-card-rating__star-fill">';
			echo $this->get_star_icon_markup( 'directorist-elementor-listing-card-rating__star-icon directorist-elementor-listing-card-rating__star-icon--active' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Directorist icon markup or escaped fallback.
			echo '</span></span>';
		}

		echo '</span>';
	}

	/**
	 * Build one star through Directorist's icon renderer.
	 *
	 * @param string $classes Star state classes.
	 * @return string
	 */
	protected function get_star_icon_markup( string $classes ): string {
		if ( function_exists( 'directorist_icon' ) ) {
			$markup = directorist_icon( 'fas fa-star', false, $classes );
			if ( is_string( $markup ) && '' !== trim( $markup ) ) {
				return $markup;
			}
		}

		return '<span class="' . esc_attr( $classes ) . '">&#9733;</span>';
	}
}
