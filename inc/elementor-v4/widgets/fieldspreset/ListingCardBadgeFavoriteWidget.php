<?php
/**
 * Listing card favorite badge widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsPreset;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractBadgeFieldWidget;
use Elementor\Controls_Manager;
use Elementor\Icons_Manager;

class ListingCardBadgeFavoriteWidget extends AbstractBadgeFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_badge_favorite';
	}

	public function get_title(): string {
		return __( 'Badge Favorite', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-heart-o';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'badge', 'favorite', 'bookmark' ] );
	}

	protected function register_widget_controls(): void {
		$this->register_favorite_badge_content_controls();

		$this->register_badge_style_controls(
			'section_badge_favorite_style',
			__( 'Favorite Badge', 'directorist-elementor' ),
			'.directorist-elementor-listing-card-badge-favorite__button'
		);

		$this->register_favorite_badge_icon_style_controls();
	}

	/**
	 * Register favorite badge content controls.
	 *
	 * @return void
	 */
	protected function register_favorite_badge_content_controls(): void {
		$this->start_controls_section(
			'section_badge_favorite_content',
			[
				'label' => __( 'Favorite Badge', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'favorite_icon',
			[
				'label'       => __( 'Icon', 'directorist-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'default'     => [
					'value'   => 'far fa-heart',
					'library' => 'fa-regular',
				],
				'skin'        => 'inline',
				'label_block' => false,
			]
		);

		$this->add_control(
			'favorite_active_icon',
			[
				'label'       => __( 'Active Icon', 'directorist-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'default'     => [
					'value'   => 'fas fa-heart',
					'library' => 'fa-solid',
				],
				'skin'        => 'inline',
				'label_block' => false,
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Register favorite badge icon style controls.
	 *
	 * @return void
	 */
	protected function register_favorite_badge_icon_style_controls(): void {
		$this->start_controls_section(
			'section_badge_favorite_icon_style',
			[
				'label' => __( 'Favorite Icon', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'favorite_icon_color',
			[
				'label'     => __( 'Icon Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-badge-favorite__icon' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listing-card-badge-favorite__icon svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'favorite_active_icon_color',
			[
				'label'     => __( 'Active Icon Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-added-to-favorite .directorist-elementor-listing-card-badge-favorite__icon--active' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-added-to-favorite .directorist-elementor-listing-card-badge-favorite__icon--active svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'favorite_icon_size',
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
					'{{WRAPPER}} .directorist-elementor-listing-card-badge-favorite__icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$listing_id = $this->get_current_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_preset_context_placeholder(
				__( 'Place this widget inside a Directorist listing card template to render the favorite badge.', 'directorist-elementor' )
			);
			return;
		}

		$settings    = $this->get_settings_for_display();
		$is_favorite = DirectoristBridge::get_instance()->is_listing_favorited_by_current_user( $listing_id );
		$classes     = [
			'directorist-elementor-listing-card-badge-favorite__button',
			'directorist-mark-as-favorite__btn',
			'directorist-fav_' . $listing_id,
		];

		if ( $is_favorite ) {
			$classes[] = 'directorist-added-to-favorite';
		}

		printf(
			'<div class="%1$s"><button type="button" class="%2$s" data-listing_id="%3$d" data-listing-id="%3$d" aria-label="%4$s">%5$s%6$s<span class="directorist-favorite-tooltip directorist-elementor-listing-card-badge-favorite__tooltip"></span></button></div>',
			esc_attr( 'directorist-elementor-listing-card-badge directorist-elementor-listing-card-badge-favorite' ),
			esc_attr( implode( ' ', array_filter( $classes ) ) ),
			absint( $listing_id ),
			esc_attr__( 'Add to favorites', 'directorist-elementor' ),
			$this->get_favorite_icon_wrapper_markup(
				(array) ( $settings['favorite_icon'] ?? [] ),
				'directorist-elementor-listing-card-badge-favorite__icon--inactive',
				[
					'value'   => 'far fa-heart',
					'library' => 'fa-regular',
				]
			), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted Elementor icon markup is escaped in wrapper method.
			$this->get_favorite_icon_wrapper_markup(
				(array) ( $settings['favorite_active_icon'] ?? [] ),
				'directorist-elementor-listing-card-badge-favorite__icon--active',
				[
					'value'   => 'fas fa-heart',
					'library' => 'fa-solid',
				]
			) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted Elementor icon markup is escaped in wrapper method.
		);
	}

	/**
	 * Render favorite icon inside the widget-owned icon wrapper.
	 *
	 * @param array<string,mixed> $icon_settings Elementor icon control settings.
	 * @param string              $state_class State-specific icon class.
	 * @param array<string,mixed> $fallback_icon Fallback icon control settings.
	 * @return string
	 */
	protected function get_favorite_icon_wrapper_markup( array $icon_settings, string $state_class, array $fallback_icon ): string {
		$icon_markup = $this->get_favorite_icon_markup( $icon_settings, $fallback_icon );

		return sprintf(
			'<span class="%1$s" aria-hidden="true">%2$s</span>',
			esc_attr( 'directorist-elementor-listing-card-badge-favorite__icon ' . $state_class ),
			$icon_markup // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted Elementor icon markup from get_favorite_icon_markup().
		);
	}

	/**
	 * Render selected Elementor icon markup.
	 *
	 * @param array<string,mixed> $icon_settings Elementor icon control settings.
	 * @param array<string,mixed> $fallback_icon Fallback icon control settings.
	 * @return string
	 */
	protected function get_favorite_icon_markup( array $icon_settings, array $fallback_icon ): string {
		if ( empty( $icon_settings['value'] ) ) {
			$icon_settings = $fallback_icon;
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
