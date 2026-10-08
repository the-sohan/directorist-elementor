<?php
/**
 * Listing card location widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsPreset;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractPresetFieldWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;

class ListingCardLocationWidget extends AbstractPresetFieldWidget {

	/**
	 * Get widget slug.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'directorist_listing_card_location';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Listing Location', 'directorist-elementor' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-map-pin';
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array<int,string>
	 */
	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'location' ] );
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_location_content',
			[
				'label' => __( 'Location', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'show_icon',
			[
				'label'        => __( 'Show Icon', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'field_icon',
			[
				'label'       => __( 'Icon', 'directorist-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'default'     => [
					'value'   => 'fas fa-map-marker-alt',
					'library' => 'fa-solid',
				],
				'skin'        => 'inline',
				'label_block' => false,
				'condition'   => [
					'show_icon' => 'yes',
				],
			]
		);

		$this->add_control(
			'show_label',
			[
				'label'        => __( 'Show Label', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'label_text',
			[
				'label'     => __( 'Label Text', 'directorist-elementor' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Location:', 'directorist-elementor' ),
				'condition' => [
					'show_label' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_location_style',
			[
				'label' => __( 'Location', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'icon_color',
			[
				'label'     => __( 'Icon Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-location__icon' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listing-card-location__icon svg' => 'fill: {{VALUE}};',
				],
				'condition' => [
					'show_icon' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'icon_size',
			[
				'label'      => __( 'Icon Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 8,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listing-card-location__icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'show_icon' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'field_gap',
			[
				'label'      => __( 'Gap', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listing-card-location' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'label_color',
			[
				'label'     => __( 'Label Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-location__label' => 'color: {{VALUE}};',
				],
				'condition' => [
					'show_label' => 'yes',
				],
			]
		);

		$this->add_control(
			'value_color',
			[
				'label'     => __( 'Value Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-location__value' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'location_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-listing-card-location',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render the location field.
	 *
	 * @return void
	 */
	protected function render(): void {
		if ( $this->maybe_render_search_field() ) {
			return;
		}

		$listing_id = $this->get_current_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_preset_context_placeholder(
				__( 'Place this widget inside a Directorist listing card template to render the first listing location.', 'directorist-elementor' )
			);
			return;
		}

		$location = DirectoristBridge::get_instance()->get_listing_location_label( $listing_id );

		if ( '' === $location ) {
			return;
		}

		$settings   = $this->get_settings_for_display();
		$show_icon  = 'yes' === ( $settings['show_icon'] ?? 'yes' );
		$show_label = 'yes' === ( $settings['show_label'] ?? '' );
		$label_text = sanitize_text_field( (string) ( $settings['label_text'] ?? __( 'Location:', 'directorist-elementor' ) ) );

		echo '<div class="directorist-elementor-listing-card-location">';

		if ( $show_icon ) {
			$icon_markup = $this->get_location_icon_markup( (array) ( $settings['field_icon'] ?? [] ) );

			if ( '' !== $icon_markup ) {
				echo '<span class="directorist-elementor-listing-card-location__icon" aria-hidden="true">' . $icon_markup . '</span>';
			}
		}

		if ( $show_label && '' !== $label_text ) {
			printf(
				'<span class="directorist-elementor-listing-card-location__label">%s</span>',
				esc_html( $label_text )
			);
		}

		printf(
			'<span class="directorist-elementor-listing-card-location__value">%s</span>',
			esc_html( $location )
		);

		echo '</div>';
	}

	/**
	 * Build the location icon markup.
	 *
	 * @param array<string,mixed> $icon_settings Elementor icon control settings.
	 * @return string
	 */
	protected function get_location_icon_markup( array $icon_settings ): string {
		if ( empty( $icon_settings['value'] ) ) {
			$icon_settings = [
				'value'   => 'fas fa-map-marker-alt',
				'library' => 'fa-solid',
			];
		}

		ob_start();
		Icons_Manager::render_icon(
			$icon_settings,
			[
				'aria-hidden' => 'true',
			]
		);

		return (string) ob_get_clean();
	}
}
